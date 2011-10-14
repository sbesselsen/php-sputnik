<?php
/**
 * Connect to Redis.
 * @param string $host
 * @param int $port
 * @param int $timeout
 */
function sp_sredis_connect($host = '127.0.0.1', $port = 6379, $timeout = null) {
    return _sp_sredis_connect(false, $host, $port, $timeout);
}

/**
 * Connect to Redis using a persistent connection.
 * @param string $host
 * @param int $port
 * @param int $timeout
 */
function sp_sredis_pconnect($host = '127.0.0.1', $port = 6379, $timeout = null) {
    return _sp_sredis_connect(true, $host, $port, $timeout);
}

/**
 * Close the Redis connection.
 * @param object $redis
 */
function sp_sredis_close($r) {
    sp_sredis_cmd($r, 'quit');
    fclose($r->sock);
}

/**
 * Perform a Redis command and read its output.
 * @param object $redis
 * @param ..    The command, split in words.
 * @return object|null  Response, or null if we are in pipeline mode.
 */
function sp_sredis_cmd() {
    $args = func_get_args();
    if (sizeof($args) < 2) {
        throw new RuntimeException("Invalid arguments to sp_sredis_cmd(): need \$r, \$cmd, \$args...");
    }
    $r = array_shift($args);
    return sp_sredis_cmd_array($r, $args);
}

/**
 * Perform a Redis command and read its output.
 * @param object $redis
 * @param array $cmd    The command, split in words.
 * @return object|null  Response, or null if we are in pipeline mode.
 */
function sp_sredis_cmd_array($r, array $cmd) {
    sp_sredis_write_cmd($r, $cmd);
    if (!$r->pipeline) {
        return sp_sredis_read_resp($r);
    }
}

/**
 * Write a command to the Redis connection.
 * @param object $redis
 * @param array $cmd    The command, split in words.
 */
function sp_sredis_write_cmd($r, array $cmd) {
    $data = _sp_sredis_serialize_cmd($cmd);
    if ($r->pipeline) {
        $r->pipelined++;
    }
    fwrite($r->sock, $data);
}

/**
 * Start pipelining Redis commands, meaning they all get sent at once instead of waiting for a response.
 * @param object $redis
 */
function sp_sredis_pipeline_start($r) {
    $r->pipeline = true;
    $r->pipelined = 0;
}

/**
 * End pipelining and read all responses from the pipelined commands.
 * @param object $redis
 */
function sp_sredis_pipeline_end($r) {
    $r->pipeline = false;
    while ($r->pipelined-- > 0) {
        $output[] = sp_sredis_read_resp($r);
    }
    return $output;
}

/**
 * End pipelining and discard all responses from the pipelined commands.
 * @param object $redis
 */
function sp_sredis_pipeline_end_clear($r) {
    $r->pipeline = false;
    while ($r->pipelined-- > 0) {
        sp_sredis_read_resp($r);
    }
    return $output;
}

function _sp_sredis_serialize_cmd(array $cmd) {
    $lines = array ("*" . sizeof($cmd) . "\r\n");
    foreach ($cmd as $item) {
        $lines[] = "\$" . strlen($item) . "\r\n";
        $lines[] = $item . "\r\n";
    }
    return implode("", $lines);
}

function sp_sredis_read_resp($r) {
    $line = trim(fgets($r->sock, 1024));
    $type = substr($line, 0, 1);
    $rem = substr($line, 1);
    switch ($type) {
        case '+':
            return sp_sredis_read_status_resp($r, $rem);
        case '-':
            return sp_sredis_read_error_resp($r, $rem);
        case ':':
            return sp_sredis_read_integer_resp($r, $rem);
        case '$':
            return sp_sredis_read_bulk_resp($r, $rem);
        case '*':
            return sp_sredis_read_multibulk_resp($r, $rem);
    }
}

function sp_sredis_read_status_resp($r, $rem) {
    return (object)array ('status' => $rem, 'error' => null, 'data' => null);
}

function sp_sredis_read_error_resp($r, $rem) {
    return (object)array ('status' => null, 'error' => $rem, 'data' => null);
}

function sp_sredis_read_integer_resp($r, $rem) {
    return (object)array ('status' => null, 'error' => null, 'data' => (int)$rem);
}

function sp_sredis_read_bulk_resp($r, $rem) {
    $length = (int)$rem;
    if ($length == -1) {
        return null;
    }
    return (object)array ('status' => null, 'error' => null, 'data' => substr(_sp_sredis_fread_blocks($r->sock, $length + 2), 0, $length));
}

function sp_sredis_read_multibulk_resp($r, $rem) {
    $num = (int)$rem;
    if ($num == -1) {
        return null;
    }
    $output = array ();
    for ($i = $num; $i > 0; $i--) {
        $line = trim(fgets($r->sock, 32));
        $line_type = substr($line, 0, 1);
        $length = (int)substr($line, 1);
        switch ($line_type) {
            case ':':
                $output[] = (int)$length;
                break;
            case '$':
                if ($length == -1) {
                    $output[] = null;
                } else {
                    $output[] = substr(_sp_sredis_fread_blocks($r->sock, $length + 2), 0, $length);
                }
                break;
            default:
                throw new RuntimeException("Error reading multi-bulk reply: invalid line: {$line}");
        }
    }
    return (object)array ('status' => null, 'error' => null, 'data' => $output);
}

/**
 * Wait for a response from Redis.
 * @param object $r
 * @param int $timeout  In seconds (and fractions of seconds). If set to -1, block until something comes in.
 * @return object|null
 */
function sp_sredis_await_resp($r, $timeout = 1) {
    if ($timeout != -1) {
        $timeout_s = floor($timeout);
        $timeout_us = round(1000000 * ($timeout - $timeout_s));
        $num = stream_select($read = array ($r->sock), $w = null, $e = null, $timeout_s, $timeout_us);
        if ($num === false) {
            throw new RuntimeException("Error while waiting for stream");
        }
    }
    if ($timeout == -1 || $num == 1) {
        return sp_sredis_read_resp($r);
    }
    return null;
}

/**
 * Try to receive a pubsub message. If no message is received within the timeout period, this will return null.
 * @param object $r
 * @param int $timeout
 * @return object|null
 */
function sp_sredis_pubsub_try_receive($r, $timeout = 1) {
    $msg = sp_sredis_await_resp($r, $timeout);
    if ($msg && !$msg->error && is_array($msg->data) && $msg->data[0] == 'message') {
        return (object)array ('channel' => $msg->data[1], 'message' => $msg->data[2]);
    }
}

/**
 * Receive a pubsub message. Block until something comes in.
 * @param object $r
 * @return object
 */
function sp_sredis_pubsub_receive($r) {
    do {
        if ($msg = sp_sredis_pubsub_try_receive($r, -1)) {
            return $msg;
        }
        // something came through, but it's not a message. Try again
    } while (true);
}

/**
 * Continuously receive messages from Redis and call the callback function when one comes in.
 * 
 * The loop ends when the callback function returns false.
 * 
 * @param object $r
 * @param callback $f
 */
function sp_sredis_pubsub_receive_all($r, $f) {
    while ($msg = sp_sredis_pubsub_receive($r)) {
        if ($f($msg) === false) {
            break;
        }
    }
}

function _sp_sredis_connect($persistent, $host, $port, $timeout) {
    $f = $persistent ? 'pfsockopen' : 'fsockopen';
    if ($timeout === null) {
        $timeout = 2;
    }
    if (!$conn = $f($host, $port, $errno, $errstr, $timeout)) {
        throw new RuntimeException("Connection error: {$errno}: {$errstr}");
    }
    
    $r = (object)array ('sock' => $conn, 'pipeline' => false);
    
    if ($persistent && ftell($conn) > 0) {
        // move out of pub/sub mode
        $resp = sp_sredis_cmd($r, 'ping');
        if ($resp->error) {
            $resp2 = sp_sredis_cmd($r, 'unsubscribe');
            if ($resp2->error) {
                // use a non-persistent connection
                return _sp_sredis_connect(false, $host, $port, $timeout);
            }
        }
    }
    return $r;
}

function _sp_sredis_fread_blocks($fp, $size, $buffer_size = 8192) {
    $buffer = array ();
    $remaining = $size;
    while ($remaining > 0) {
        $block = min($remaining, $buffer_size);
        if (!$data = fread($fp, $remaining)) {
            break;
        }
        $buffer[] = $data;
        $remaining -= strlen($data);
    }
    return implode("", $buffer);
}
