<?php
$c = new ReflectionClass('Redis');

$methods = array ();
foreach ($c->getMethods() as $method) {
    $methods[strtolower($method->getName())] = $method->getName();
}

$disabledMethods = array ('__construct', 'connect', 'pconnect', 'ping', 'watch', 'unwatch', 'close', 'setoption', 'getoption');
foreach ($methods as $f => $m) {
    if (in_array($f, $disabledMethods)) {
        continue;
    }
    echo <<<CMD
/**
 * Run the $m command. (Params according to Redis documentation.)
 * @param mixed \$redis
 * @return mixed
 */
function sp_redis_$f(\$redis) {
    \$args = func_get_args();
    array_shift(\$args);
    return call_user_func_array(array (\$redis, '$m'), \$args);
}


CMD;
}
