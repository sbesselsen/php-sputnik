<?php
/**
 * Run a request from the CLI.
 */
function sp_cli_run() {
    $request = new stdClass;
    $request->url = isset ($_SERVER['argv'][1]) ? "/" . $_SERVER['argv'][1] : "/";
    $request->host = null;
    $request->port = 80;
    $request->https = false;
    $request->query = '';
    $request->get = array ();
    $request->post = array ();
    $request->method = 'GET';
    $request->cli = true;
    
    return sp_run_request($request);
}