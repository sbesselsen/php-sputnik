<?php
/**
 * Create a new multicurl handler.
 * 
 * Options:
 * - timeout: in seconds. Defaults to 30.
 * 
 * @param array $options
 * @return mixed
 */
function sp_multicurl_init(array $options = array ()) {
    return new _SP_MultiCurl($options);
}

/**
 * Add a new cURL request to be handled through the multicurl handler.
 * @param mixed $mc     Multicurl handler.
 * @param resource $c   cURL handle.
 * @param callback $callback    Callback to call when we are done. Receives params (resource $c, string $output, ... $params ...)
 * @param array $params
 */
function sp_multicurl_add($mc, $c, $callback, array $params = array ()) {
    $mc->add($c, $callback, $params);
}

/**
 * Run the multicurl request and block until we are done.
 * @param mixed $mc
 */
function sp_multicurl_exec($mc) {
    $mc->exec();
}

/**
 * Operation based on RollingCurl:
 * - authored by Josh Fraser (www.joshfraser.com)
 * - maintained by Alexander Makarov, http://rmcreative.ru/
 * - released under Apache License 2.0
 * - http://code.google.com/p/rolling-curl/
 */
class _SP_MultiCurl {
    private $_mc;
    private $_callbacks = array ();
    private $_timeout;
    
    public function __construct(array $options) {
        $options += array ('timeout' => 30);
        $this->_timeout = $options['timeout'];
        $this->_mc = curl_multi_init();
    }
    
    public function add($c, $callback, array $params = array ()) {
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        $id = (string)$c;
        $this->_callbacks[$id] = array ('f' => $callback, 'params' => $params);
        curl_multi_add_handle($this->_mc, $c);
    }
    
    public function exec() {
        $error = null;
        
        do {
            // give all requests a whirl
            while (($execrun = curl_multi_exec($this->_mc, $running)) == CURLM_CALL_MULTI_PERFORM);
            
            if ($execrun != CURLM_OK) {
                $error = "Error in multicurl request. Error code: {$execrun}";
                break;
            }
            
            // a request was just completed -- find out which one
            while ($done = curl_multi_info_read($this->_mc)) {
                // get the info and content returned on the request
                $id = (string)$done['handle'];
                $output = curl_multi_getcontent($done['handle']);
                
                // call the callback function
                $params = $this->_callbacks[$id]['params'];
                array_unshift($params, $done['handle'], $output);
                try {
                    call_user_func_array($this->_callbacks[$id]['f'], $params);
                } catch (Exception $e) {
                    // neatly close cURL when an exception occurs
                    curl_multi_close($this->_mc);
                    throw $e;
                }
                unset ($this->_callbacks[$id]);
                
                /*
                // TODO: maybe implement a RequestIterator or something like that some time
                // start a new request (it's important to do this before removing the old one)
                if ($i < sizeof($this->requests) && isset($this->requests[$i]) && $i < count($this->requests)) {
                    $ch = curl_init();
                    $options = $this->get_options($this->requests[$i]);
                    curl_setopt_array($ch, $options);
                    curl_multi_add_handle($master, $ch);
        
                    // Add to our request Maps
                    $key = (string) $ch;
                    $this->requestMap[$key] = $i;
                    $i++;
                }
                */
        
                // remove the curl handle that just completed
                curl_multi_remove_handle($this->_mc, $done['handle']);
            }
        
            // Block for data in / output; error handling is done by curl_multi_exec
            if ($running) {
                curl_multi_select($this->_mc, $this->_timeout);
            }
        
        } while ($running);
        
        curl_multi_close($this->_mc);
        if ($error) {
            throw new RuntimeException($error);
        }
    }
}
