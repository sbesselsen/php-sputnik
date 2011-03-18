<?php
/**
 * Initialize the framework: load the config file and the modules.
 * @param string|null $env            Defaults to the value of $_SERVER['SP_ENV'] (can be set from .htaccess using SetEnv) or else 'prod'
 * @param string|null $base_dir        Defaults to '..'
 */
function sp_init($env = null, $base_dir = null) {
    // set the environment
    if (defined('SP_ENV')) {
        throw new RuntimeException("Sputnik ENV already set");
    }
    if ($env === null) {
        $env = isset ($_SERVER['SP_ENV']) ? $_SERVER['SP_ENV'] : 'prod';
    }
    define('SP_ENV', $env);
    
    // set the appcache flag
    define('SP_APPCACHE', !isset ($_SERVER['SP_APPCACHE']) || $_SERVER['SP_APPCACHE'] != 'off');
    
    // set the base directory
    if (defined('SP_BASE')) {
        throw new RuntimeException("Sputnik BASE already set");
    }
    if ($base_dir === null) {
        $base_dir = '..';
    } else {
        $base_dir = rtrim($base_dir, '/');
    }
    define('SP_BASE', realpath($base_dir));
    
    // load and initialize the modules
    foreach (sp_modules() as $module => $info) {
        if (isset ($info['loader'])) {
            require $info['loader'];
            $module_init = "{$module}_init";
            if (is_callable($module_init)) {
                $module_init();
            }
        }
    }
}

/**
 * Get a list of loaded modules and their properties.
 * @param bool $reset
 * @return array
 */
function sp_modules($reset = false) {
    static $modules = null;
    if ($modules === null || $reset) {
        if (!$reset && $modules = sp_appcache_fetch('sp_modules')) {
            return $modules;
        }
        $modules = array ();
        foreach (glob(SP_BASE . "/modules/*") as $dir) {
            $module = basename($dir);
            $info = array ('name' => $module, 'dir' => $dir, 'loader' => null);
            if (file_exists($loaderPath = "{$dir}/module.php")) {
                $info['loader'] = $loaderPath;
            }
            if (file_exists($viewHelperPath = "{$dir}/view/helpers.php")) {
                $info['view_helpers'] = $viewHelperPath;
            }
            $modules[$module] = $info;
        }
        sp_appcache_store('sp_modules', $modules);
    }
    return $modules;
}

/**
 * Get the config for the current environment.
 * @param bool $reset
 * @return array
 */
function sp_config($reset = false) {
    static $config = null;
    if ($config === null || $reset) {
        $config_all = _sp_config_all($reset);
        $config = isset ($config_all[SP_ENV]) ? $config_all[SP_ENV] : array ();
    }
    return $config;
}

/**
 * Broadcast an event across controllers.
 * 
 * Additional params after $hook will be sent to the subscribers.
 * 
 * @param string $hook
 */
function sp_broadcast($event) {
    $args = func_get_args();
    array_shift($args);
    foreach (_sp_listeners($event) as $f) {
        call_user_func_array($f, $args);
    }
}

/**
 * Require a file from a module.
 * @param string $module
 * @param string $file
 * @param bool $check_if_exists     Check if the file exists before we call require_once? Defaults to false.
 * @return bool Success.
 */
function sp_module_require($module, $file, $check_if_exists = false) {
    return sp_require("modules/{$module}/{$file}", $check_if_exists);
}

/**
 * Include a file and return the output.
 * @param string $file
 * @param bool $check_if_exists     Check if the file exists before we call require_once? Defaults to false.
 * @return bool Success.
 */
function sp_require($path, $check_if_exists = false) {
    if (substr($path, 0, 1) != '/') {
        $path = SP_BASE . '/' . $path;
    }
    if ($check_if_exists && !file_exists($path)) {
        return false;
    }
    require_once $path;
    return true;
}

/**
 * Run the current request through the framework after initializing it.
 * @param string|null $env            See sp_init().
 * @param string|null $base_dir        See sp_init().
 */
function sp_run($env = null, $base_dir = null) {
    sp_init($env, $base_dir);
    sp_run_request();
}

/**
 * Run a request through the controllers.
 * 
 * If you do not pass a request, the current browser request will be loaded.
 * 
 * @param object|null $request
 */
function sp_run_request($request = null) {
    if ($request = sp_route($request)) {
        return sp_dispatch($request);
    }
}

/**
 * Dispatch a request.
 * @param object $request
 */
function sp_dispatch($request) {
    $params = $request->params;
    if (sp_module_require($params['module'], "controller/{$params['controller']}.php", true)) {
        $f = "{$params['module']}_page_{$params['controller']}_{$params['action']}";
        if (is_callable($f)) {
            $f($request);
            return;
        }
    }
    sp_error_404($request);
}

/**
 * Route a request to get the params for this call.
 * 
 * If you do not pass a request, the current browser request will be loaded.
 * If routing fails, the function will return null.
 * 
 * @param object|null $request
 * @return object|null
 */
function sp_route($request = null) {
    // autoload the request if it is not specified
    if ($request === null) {
        $request = _sp_current_request();
    }
    
    // load all routes
    $routes = _sp_routes();
    
    // carry out the actual routing
    foreach ($routes->ordered as $route => $f) {
        $route_function = "{$f}_route";
        if ($params = $route_function($request)) {
            $request->params = $params;
            return $request;
        }
    }
}

/**
 * Assemble a URL from the specified params.
 * @param array $params
 * @param string|null       Name of the route.
 * @param bool $absolute    Generate an absolute URL? Defaults to false.
 * @return string
 */
function sp_assemble(array $params, $route = null, $absolute = false) {
    $routes = _sp_routes();
    $url = null;
    if ($route === null) {
        foreach ($routes->ordered as $route => $f) {
            $assemble_function = "{$f}_assemble";
            if ($url = $assemble_function($params)) {
                break;
            }
        }
    } else {
        if (isset ($routes->all[$route])) {
            $f = $routes->all[$route];
            $assemble_function = "{$f}_assemble";
            $url = $assemble_function($params);
        }
    }
    if ($absolute && substr($url, 0, 1) == '/') {
        // prepend the current path in front of the url
        $url = sp_base_url() . substr($url, 1);
    }
    return $url;
}

/**
 * Get the base URL for the current request.
 * @param bool $reset
 * @return string
 */
function sp_base_url($reset = false) {
    static $base_url = null;
    if ($base_url === null || $reset) {
        $request = _sp_current_request();
        $base_url = ($request->https ? 'https' : 'http') . "://" . $request->host;
        if (($request->https && $request->port != 443) || (!$request->https && $request->port != 80)) {
            $base_url .= ":" . $port;
        }
        $base_url .= '/';
    }
    return $base_url;
}

/**
 * Route a request using the default route. Note that this will *always* match.
 * @param object $request
 * @return array
 */
function sp_default_route($request) {
    // remove the query string from the request url
    $url = $request->url;
    $url = explode('?', $url);
    $url = $url[0];
    
    // read the path to the controller action
    $parts = explode('/', trim($url, '/'));
    if (!$parts[0]) {
        $parts[0] = 'core';
    }
    $parts = array_pad($parts, 3, 'index');
    $params['module'] = array_shift($parts);
    $params['controller'] = array_shift($parts);
    $params['action'] = array_shift($parts);
    
    // read additional params from the url
    $params += sp_route_decode_params($parts);
    
    // read params from get and post
    $params += $request->post;
    $params += $request->get;
    
    return $params;
}

/**
 * Decode an array of params to key-value pairs.
 * @param array $arr
 * @return array
 */
function sp_route_decode_params(array $arr) {
    $out = array ();
    while ($k = array_shift($arr)) {
        $out[rawurldecode($k)] = rawurldecode(array_shift($arr));
    }
    return $out;
}

/**
 * Assemble a URL using the default route. This will *always* assemble a URL.
 * @param array $params
 * @return string
 */
function sp_default_assemble(array $params) {
    $params += array ('module' => 'core', 'controller' => 'index', 'action' => 'index');
    $module = $params['module'];
    $controller = $params['controller'];
    $action = $params['action'];
    unset ($params['module']);
    unset ($params['controller']);
    unset ($params['action']);
    $url = "/{$module}/{$controller}/{$action}";
    if ($params) {
        $url .= sp_route_encode_params($params);
    } else if ($action == 'index') {
        // shorten the URL if possible
        if ($controller == 'index') {
            if ($module == 'core') {
                $url = '/';
            } else {
                $url = "/{$module}";
            }
        } else {
            $url = "/{$module}/{$controller}";
        }
    }
    return $url;
}

/**
 * Encode an array of key-value pairs to a params URL.
 * @param array $params
 * @return string
 */
function sp_route_encode_params(array $params) {
    $url = '';
    foreach ($params as $k => $v) {
        $url .= '/' . rawurlencode($k) . '/' . rawurlencode($v);
    }
    return $url;
}

/**
 * Load a model and the backend for that model.
 * @param string $module
 * @param string $model
 */
function sp_model_load($module, $model) {
    // load the model
    sp_module_require($module, "model/{$model}.php");
    
    // load the backend if possible
    $config = sp_config();
    if (isset ($config['backends'][$module][$model])) {
        sp_module_require($module, "model/backend/{$model}/{$config['backends'][$module][$model]}.php");
    }
}

/**
 * Render a view script and get the output.
 * @param string $module
 * @param string $view
 * @param array $params
 */
function sp_view_get($module, $view, array $params = array ()) {
    ob_start();
    sp_view_render($module, $view, $params);
    return ob_get_clean();
}

/**
 * Render a view script and output directly.
 * @param string $module
 * @param string $view
 * @param array $params
 */
function sp_view_render($module, $view, array $params = array ()) {
    _sp_view_include_helpers($module);
    $__path = SP_BASE . "/modules/{$module}/view/{$view}.phtml";
    extract($params + array ('params' => null, 'module' => null, 'view' => null));
    include $__path;
}

/**
 * Store a value to the appcache.
 * 
 * The value will be automatically serialized. You can delete a value by setting null.
 * 
 * @param string $key
 * @param mixed $value
 * @param int $ttl
 */
function sp_appcache_store($key, $value, $ttl = 0) {
    if (!SP_APPCACHE) {
        return;
    }
    $key = _sp_appcache_key($key);
    if ($value === null) {
        apc_delete($key);
    } else {
        apc_store($key, serialize($value), $ttl);
    }
}

/**
 * Fetch a value from the appcache.
 * @param string $key
 * @return mixed|null
 */
function sp_appcache_fetch($key) {
    if (!SP_APPCACHE) {
        return null;
    }
    $key = _sp_appcache_key($key);
    $value = apc_fetch($key);
    if ($value) {
        return unserialize($value);
    }
}

/**
 * Handle a 404 error.
 * @param object $request
 */
function sp_error_404($request) {
    $obj = new stdClass;
    $obj->request = $request;
    $obj->handled = false;
    sp_broadcast('error_404', $obj);
    if (!$obj->handled) {
        header("HTTP/1.0 404 Not Found");
    }
}

/**
 * Get all listeners for an event.
 * @param string $event
 * @param bool $reset
 * @return array    An array of function names.
 */
function _sp_listeners($event, $reset = false) {
    static $listeners = array ();
    if (!isset ($listeners[$event]) || $reset) {
        $listeners[$event] = array ();
        foreach (sp_modules() as $module => $_) {
            $hook_function = "{$module}_on_{$event}";
            if (is_callable($hook_function)) {
                $listeners[$event][] = $hook_function;
            }
        }
    }
    return $listeners[$event];
}

/**
 * Get the entire config for all environments.
 * @param bool $reset
 * @return array
 */
function _sp_config_all($reset = false) {
    static $config = null;
    if ($config === null || $reset) {
        if (!$reset && $config = sp_appcache_fetch('_sp_config_all')) {
            return $config;
        }
        $config = array ();
        if (file_exists($path = SP_BASE . "/config/config.yml")) {
            $data = file_get_contents($path);
            $yaml = yaml_parse($data);
            if (is_array($yaml)) {
                $config = $yaml;
            }
        }
        sp_appcache_store('_sp_config_all', $config);
    }
    return $config;
}

/**
 * Get a list of all available routes.
 * @param bool $reset
 * @return object   Object containing routes ->ordered (ordered by priority) and ->all (keyed by name).
 */
function _sp_routes($reset = false) {
    static $routes = null;
    if ($routes === null || $reset) {
        if (!$reset && $routes = sp_appcache_fetch('_sp_routes')) {
            return $routes;
        }
        // fetch all routes
        $routes_obj = new stdClass;
        $routes_obj->{'000_default'} = 'sp_default';
        sp_broadcast('routes', $routes_obj);
        $routes_ordered = get_object_vars($routes_obj);
        $routes_all = array ();
        foreach ($routes_ordered as $k => $v) {
            $routes_all[ltrim($k, '0123456789_')] = $v;
        }
        
        // sort the routes
        ksort($routes_ordered);
        
        $routes = new stdClass;
        $routes->ordered = array_reverse($routes_ordered);
        $routes->all = $routes_all;
        sp_appcache_store('_sp_routes', $routes);
    }
    return $routes;
}

/**
 * Get an object representing the current request.
 * @return object
 */
function _sp_current_request() {
    $request = new stdClass;
    $request->url = $_SERVER['REQUEST_URI'];
    $request->host = $_SERVER['HTTP_HOST'];
    $request->port = $_SERVER['SERVER_PORT'];
    $request->https = !empty ($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on';
    $request->query = $_SERVER['QUERY_STRING'];
    $request->get = $_GET;
    $request->post = $_POST;
    $request->method = $_SERVER['REQUEST_METHOD'];
    return $request;
}

/**
 * Rewrite a key to use it in APC.
 * @param string $key
 * @return string
 */
function _sp_appcache_key($key) {
    return "sp_" . md5(SP_BASE . $key);
}

/**
 * Include view helpers.
 */
function _sp_view_include_helpers($module) {
    static $loaded = array ();
    if (isset ($loaded[$module])) {
        return;
    }
    $loaded[$module] = true;
    $modules = sp_modules();
    if (isset ($modules[$module]['view_helpers'])) {
        sp_require($modules[$module]['view_helpers']);
    }
}
