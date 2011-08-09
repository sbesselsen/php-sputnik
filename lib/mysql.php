<?php
function sp_mysql_connect(array $options = array ()) {
    static $conns = array ();
    
    $options += array (
        'id' => '*',
        'host' => 'localhost',
        'user' => '',
        'pass' => '',
        'port' => null,
        'db' => null,
    );
    
    $id = $options['id'];
    if (!isset ($conns[$id])) {
        $conns[$id] = null;
        $host = $options['host'];
        if ($options['port']) {
            $host .= ":{$options['port']}";
        }
        if ($conn = @mysql_connect($host, $options['user'], $options['pass'])) {
            if ($options['db']) {
                mysql_select_db($options['db'], $conn);
            }
            $conns[$id] = $conn;
        }
    }
    return $conns[$id];
}

function sp_mysql_query($q, array $params = array (), $conn = null) {
    if (!$conn) {
        if (!$conn = sp_mysql_connect()) {
            return null;
        }
    }
    $qr = _sp_mysql_query_rewrite($q, $params, $conn);
    return @mysql_query($qr, $conn);
}

function sp_mysql_update($table, array $update, $where = null, array $params = array (), $conn = null) {
    if (!$update) {
        return;
    }
    $q = "UPDATE {{$table}} SET ";
    foreach ($update as $k => $v) {
        $field = substr($k, 1);
        $param_key = '__update_' . $field;
        $placeholder = substr($k, 0, 1) . $param_key;
        $params[$param_key] = $v;
        $sets[] = "{{$field}} = {$placeholder}";
    }
    $q .= implode(", ", $sets);
    if ($where) {
        $q .= " WHERE {$where}";
    }
    return sp_mysql_query($q, $params, $conn);
}

function sp_mysql_delete($table, $where, array $params = array (), $conn = null) {
    if (!$where) {
        throw new InvalidArgumentException("You must pass a WHERE clause for sp_mysql_delete()");
    }
    $where .= " LIMIT 1";
    return sp_mysql_delete_multiple($table, $where, $params, $conn);
}

function sp_mysql_delete_multiple($table, $where = null, array $params = array (), $conn = null) {
    $q = "DELETE FROM {{$table}}";
    if ($where) {
        $q .= " WHERE {$where}";
    }
    return sp_mysql_query($q, $params, $conn);
}

function sp_mysql_insert($table, array $insert, $upsert = true, $conn = null) {
    if (!$insert) {
        return;
    }
    $q = "INSERT INTO {{$table}} ";
    $fields = array ();
    $values = array ();
    $params = array ();
    $sets = array ();
    foreach ($insert as $k => $v) {
        $field = substr($k, 1);
        $placeholder = substr($k, 0, 1) . $field;
        $params[$field] = $v;
        $fields[] = "{{$field}}";
        $values[] = $placeholder;
        $sets[] = "{{$field}} = {$placeholder}";
    }
    $q .= "(" . implode(", ", $fields) . ") VALUES (" . implode(", ", $values) . ")";
    if ($upsert) {
        $q .= " ON DUPLICATE KEY UPDATE " . implode(", ", $sets);
    }
    return sp_mysql_query($q, $params, $conn);
}

function _sp_mysql_query_rewrite($q, array $params, $conn) {
    // table and field escaping
    $q = preg_replace('(\{([^}\.]*)\.([^}\.]*?)\})', "`\\1`.`\\2`", $q);
    $q = preg_replace('(\{([^}\.]*)\})', "`\\1`", $q);
    
    // params
    $q = preg_replace_callback('(([!@%#])([a-z0-9_\-A-Z]+))', function ($match) use ($params, $conn) {
        $v = isset ($params[$match[2]]) ? $params[$match[2]] : '';
        if ($v === null) {
            return 'NULL';
        }
        switch ($match[1]) {
            case '!':
                return '(' . $v . ')';
            case '@':
                return "'" . mysql_real_escape_string($v, $conn) . "'";
            case '%':
                $v = number_format((double)$v, 10, '.', '');
                $v = preg_replace('((\.[0-9])0+$)', '\\1', $v);
                return $v;
            case '#':
                return (int)$v;
        }
    }, $q);
    
    return $q;
}