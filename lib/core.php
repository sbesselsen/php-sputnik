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
    
    // load the config
    $config = sp_config();
    
    // load all modules
    $modules = sp_modules();
    
    // initialize the modules
    foreach ($modules as $module => $info) {
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
 * Include a file from a module and return the output.
 * @param string $module
 * @param string $file
 * @return mixed
 */
function sp_module_require($module, $file) {
    return sp_require("modules/{$module}/{$file}");
}

/**
 * Include a file and return the output.
 * @param string $file
 * @param bool $from_base    Evaluate the path from the base_path? Defaults to true.
 * @return mixed
 */
function sp_require($path, $from_base = true) {
    if ($from_base) {
        $path = SP_BASE . '/' . $path;
    }
    return include $path;
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
    sp_module_require($params['module'], $params['controller'] . '_controller.php');
    $f = "{$params['module']}_page_{$params['controller']}_{$params['action']}";
    return $f($request);
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
    foreach ($routes as $route => $f) {
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
 * @param bool $absolute    Generate an absolute URL? Defaults to false.
 * @return string
 */
function sp_assemble(array $params, $absolute = false) {
    $routes = _sp_routes();
    $url = null;
    foreach ($routes as $route => $f) {
        $assemble_function = "{$f}_assemble";
        if ($url = $assemble_function($params)) {
            break;
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
    $parts = explode('/', substr($url, 1));
    if (!$parts[0]) {
        $parts[0] = 'core';
    }
    $parts = array_pad($parts, 3, 'index');
    $params['module'] = array_shift($parts);
    $params['controller'] = array_shift($parts);
    $params['action'] = array_shift($parts);
    
    // read additional params from the url
    $urlParams = array ();
    while ($k = array_shift($parts)) {
        $urlParams[rawurldecode($k)] = rawurldecode(array_shift($parts));
    }
    $params += $urlParams;
    
    // read params from get and post
    $params += $request->post;
    $params += $request->get;
    
    return $params;
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
        foreach ($params as $k => $v) {
            $url .= '/' . rawurlencode($k) . '/' . rawurlencode($v);
        }
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
 * Render a view script and get the output.
 * @param string $view
 * @param array $params
 */
function sp_view_get($module, $view, array $params = array ()) {
    ob_start();
    sp_view_render($view, $params);
    return ob_get_clean();
}

/**
 * Render a view script and output directly.
 * @param string $view
 * @param array $params
 */
function sp_view_render($module, $view, array $params = array ()) {
    extract($params, EXTR_SKIP);
    include SP_BASE . "/modules/{$module}/view/$view.phtml";
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
        if (file_exists($configPath = SP_BASE . "/config/config.yaml")) {
            $configData = file_get_contents($configPath);
            $yaml = yaml_parse($configData);
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
 * @return array
 */
function _sp_routes($reset = false) {
    static $routes = null;
    if ($routes === null || $reset) {
        // fetch all routes
        $routes_obj = new stdClass;
        $routes_obj->{'000_default'} = 'sp_default';
        sp_broadcast('routes', $routes_obj);
        $routes = get_object_vars($routes_obj);
        
        // sort the routes
        ksort($routes);
        $routes = array_reverse($routes);
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
    return "sp_" . md5("sputnik{$key}");
}
