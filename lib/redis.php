<?php
/**
 * Connect to a Redis instance.
 * @param string $host
 * @param int $port
 * @param int $timeout; 0 means no timeout
 * @return mixed Redis handle.
 */
function sp_redis_connect($host = '127.0.0.1', $port = 6379, $timeout = 0) {
    $redis = new Redis();
    $redis->connect($host, $port, $timeout);
    return $redis;
}

/**
 * Connect to a Redis instance using a persistent connection.
 * @param string $host
 * @param int $port
 * @param int $timeout; 0 means no timeout
 * @return mixed Redis handle.
 */
function sp_redis_pconnect($host = '127.0.0.1', $port = 6379, $timeout = 0) {
    $redis = new Redis();
    $redis->pconnect($host, $port, $timeout);
    return $redis;
}

/**
 * Close connection to Redis.
 * @param mixed $redis
 */
function sp_redis_close($redis) {
    $redis->close();
}

/**
 * Set the value of a Redis param.
 * @param mixed $redis
 * @param string $param
 * @param mixed $value
 */
function sp_redis_set_param($redis, $param, $value) {
    $redis->setOption($param, $value);
}

/**
 * Get the value of a Redis param.
 * @param mixed $redis
 * @param string $param
 * @return mixed
 */
function sp_redis_get_param($redis, $param) {
    return $redis->getOption($param);
}

/**
 * Make sure the connection to Redis is still up.
 * @param mixed $redis
 */
function sp_redis_ping($redis) {
    $redis->ping();
}

/**
 * Watch one or more keys.
 * @param mixed $redis
 * @param string|array $keys
 */
function sp_redis_watch($redis, $keys) {
    if (is_array($keys)) {
        foreach ($keys as $key) {
            $redis->watch($key);
        }
    } else {
        $redis->watch($key);
    }
}

/**
 * Unwatch one or more keys.
 * @param mixed $redis
 * @param string|array $keys
 */
function sp_redis_unwatch($redis, $keys) {
    if (is_array($keys)) {
        foreach ($keys as $key) {
            $redis->unwatch($key);
        }
    } else {
        $redis->unwatch($key);
    }
}

/**
 * Start a transactional block.
 * @param mixed $redis
 * @return mixed    Transaction.
 */
function sp_redis_start_multi($redis) {
    return $redis->multi();
}

/**
 * Execute multiple commands.
 * @param mixed $multi
 * @return bool
 */
function sp_redis_exec_multi($multi) {
    return $multi->exec();
}

/**
 * Discard a transaction.
 * @param mixed $multi
 * @return bool
 */
function sp_redis_discard_multi($multi) {
    return $multi->discard();
}

/**
 * Run the get command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_get($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'get'), $args);
}

/**
 * Run the set command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_set($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'set'), $args);
}

/**
 * Run the setex command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_setex($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'setex'), $args);
}

/**
 * Run the setnx command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_setnx($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'setnx'), $args);
}

/**
 * Run the getSet command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_getset($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'getSet'), $args);
}

/**
 * Run the randomKey command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_randomkey($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'randomKey'), $args);
}

/**
 * Run the renameKey command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_renamekey($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'renameKey'), $args);
}

/**
 * Run the renameNx command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_renamenx($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'renameNx'), $args);
}

/**
 * Run the getMultiple command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_getmultiple($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'getMultiple'), $args);
}

/**
 * Run the exists command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_exists($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'exists'), $args);
}

/**
 * Run the delete command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_delete($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'delete'), $args);
}

/**
 * Run the incr command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_incr($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'incr'), $args);
}

/**
 * Run the incrBy command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_incrby($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'incrBy'), $args);
}

/**
 * Run the decr command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_decr($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'decr'), $args);
}

/**
 * Run the decrBy command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_decrby($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'decrBy'), $args);
}

/**
 * Run the type command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_type($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'type'), $args);
}

/**
 * Run the append command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_append($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'append'), $args);
}

/**
 * Run the getRange command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_getrange($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'getRange'), $args);
}

/**
 * Run the setRange command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_setrange($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'setRange'), $args);
}

/**
 * Run the getBit command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_getbit($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'getBit'), $args);
}

/**
 * Run the setBit command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_setbit($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'setBit'), $args);
}

/**
 * Run the strlen command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_strlen($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'strlen'), $args);
}

/**
 * Run the getKeys command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_getkeys($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'getKeys'), $args);
}

/**
 * Run the sort command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_sort($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sort'), $args);
}

/**
 * Run the sortAsc command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_sortasc($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sortAsc'), $args);
}

/**
 * Run the sortAscAlpha command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_sortascalpha($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sortAscAlpha'), $args);
}

/**
 * Run the sortDesc command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_sortdesc($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sortDesc'), $args);
}

/**
 * Run the sortDescAlpha command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_sortdescalpha($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sortDescAlpha'), $args);
}

/**
 * Run the lPush command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_lpush($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'lPush'), $args);
}

/**
 * Run the rPush command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_rpush($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'rPush'), $args);
}

/**
 * Run the lPushx command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_lpushx($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'lPushx'), $args);
}

/**
 * Run the rPushx command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_rpushx($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'rPushx'), $args);
}

/**
 * Run the lPop command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_lpop($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'lPop'), $args);
}

/**
 * Run the rPop command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_rpop($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'rPop'), $args);
}

/**
 * Run the blPop command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_blpop($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'blPop'), $args);
}

/**
 * Run the brPop command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_brpop($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'brPop'), $args);
}

/**
 * Run the lSize command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_lsize($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'lSize'), $args);
}

/**
 * Run the lRemove command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_lremove($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'lRemove'), $args);
}

/**
 * Run the listTrim command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_listtrim($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'listTrim'), $args);
}

/**
 * Run the lGet command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_lget($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'lGet'), $args);
}

/**
 * Run the lGetRange command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_lgetrange($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'lGetRange'), $args);
}

/**
 * Run the lSet command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_lset($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'lSet'), $args);
}

/**
 * Run the lInsert command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_linsert($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'lInsert'), $args);
}

/**
 * Run the sAdd command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_sadd($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sAdd'), $args);
}

/**
 * Run the sSize command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_ssize($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sSize'), $args);
}

/**
 * Run the sRemove command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_sremove($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sRemove'), $args);
}

/**
 * Run the sMove command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_smove($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sMove'), $args);
}

/**
 * Run the sPop command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_spop($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sPop'), $args);
}

/**
 * Run the sRandMember command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_srandmember($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sRandMember'), $args);
}

/**
 * Run the sContains command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_scontains($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sContains'), $args);
}

/**
 * Run the sMembers command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_smembers($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sMembers'), $args);
}

/**
 * Run the sInter command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_sinter($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sInter'), $args);
}

/**
 * Run the sInterStore command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_sinterstore($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sInterStore'), $args);
}

/**
 * Run the sUnion command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_sunion($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sUnion'), $args);
}

/**
 * Run the sUnionStore command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_sunionstore($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sUnionStore'), $args);
}

/**
 * Run the sDiff command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_sdiff($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sDiff'), $args);
}

/**
 * Run the sDiffStore command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_sdiffstore($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sDiffStore'), $args);
}

/**
 * Run the setTimeout command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_settimeout($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'setTimeout'), $args);
}

/**
 * Run the save command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_save($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'save'), $args);
}

/**
 * Run the bgSave command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_bgsave($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'bgSave'), $args);
}

/**
 * Run the lastSave command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_lastsave($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'lastSave'), $args);
}

/**
 * Run the flushDB command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_flushdb($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'flushDB'), $args);
}

/**
 * Run the flushAll command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_flushall($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'flushAll'), $args);
}

/**
 * Run the dbSize command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_dbsize($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'dbSize'), $args);
}

/**
 * Run the auth command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_auth($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'auth'), $args);
}

/**
 * Run the ttl command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_ttl($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'ttl'), $args);
}

/**
 * Run the persist command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_persist($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'persist'), $args);
}

/**
 * Run the info command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_info($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'info'), $args);
}

/**
 * Run the select command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_select($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'select'), $args);
}

/**
 * Run the move command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_move($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'move'), $args);
}

/**
 * Run the bgrewriteaof command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_bgrewriteaof($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'bgrewriteaof'), $args);
}

/**
 * Run the slaveof command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_slaveof($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'slaveof'), $args);
}

/**
 * Run the object command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_object($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'object'), $args);
}

/**
 * Run the mset command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_mset($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'mset'), $args);
}

/**
 * Run the msetnx command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_msetnx($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'msetnx'), $args);
}

/**
 * Run the rpoplpush command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_rpoplpush($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'rpoplpush'), $args);
}

/**
 * Run the zAdd command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zadd($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zAdd'), $args);
}

/**
 * Run the zDelete command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zdelete($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zDelete'), $args);
}

/**
 * Run the zRange command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zrange($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zRange'), $args);
}

/**
 * Run the zReverseRange command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zreverserange($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zReverseRange'), $args);
}

/**
 * Run the zRangeByScore command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zrangebyscore($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zRangeByScore'), $args);
}

/**
 * Run the zRevRangeByScore command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zrevrangebyscore($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zRevRangeByScore'), $args);
}

/**
 * Run the zCount command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zcount($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zCount'), $args);
}

/**
 * Run the zDeleteRangeByScore command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zdeleterangebyscore($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zDeleteRangeByScore'), $args);
}

/**
 * Run the zDeleteRangeByRank command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zdeleterangebyrank($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zDeleteRangeByRank'), $args);
}

/**
 * Run the zCard command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zcard($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zCard'), $args);
}

/**
 * Run the zScore command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zscore($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zScore'), $args);
}

/**
 * Run the zRank command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zrank($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zRank'), $args);
}

/**
 * Run the zRevRank command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zrevrank($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zRevRank'), $args);
}

/**
 * Run the zInter command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zinter($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zInter'), $args);
}

/**
 * Run the zUnion command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zunion($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zUnion'), $args);
}

/**
 * Run the zIncrBy command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zincrby($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zIncrBy'), $args);
}

/**
 * Run the expireAt command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_expireat($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'expireAt'), $args);
}

/**
 * Run the hGet command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_hget($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'hGet'), $args);
}

/**
 * Run the hSet command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_hset($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'hSet'), $args);
}

/**
 * Run the hSetNx command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_hsetnx($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'hSetNx'), $args);
}

/**
 * Run the hDel command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_hdel($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'hDel'), $args);
}

/**
 * Run the hLen command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_hlen($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'hLen'), $args);
}

/**
 * Run the hKeys command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_hkeys($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'hKeys'), $args);
}

/**
 * Run the hVals command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_hvals($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'hVals'), $args);
}

/**
 * Run the hGetAll command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_hgetall($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'hGetAll'), $args);
}

/**
 * Run the hExists command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_hexists($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'hExists'), $args);
}

/**
 * Run the hIncrBy command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_hincrby($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'hIncrBy'), $args);
}

/**
 * Run the hMset command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_hmset($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'hMset'), $args);
}

/**
 * Run the hMget command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_hmget($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'hMget'), $args);
}

/**
 * Run the multi command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_multi($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'multi'), $args);
}

/**
 * Run the discard command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_discard($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'discard'), $args);
}

/**
 * Run the exec command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_exec($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'exec'), $args);
}

/**
 * Run the pipeline command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_pipeline($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'pipeline'), $args);
}

/**
 * Run the publish command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_publish($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'publish'), $args);
}

/**
 * Run the subscribe command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_subscribe($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'subscribe'), $args);
}

/**
 * Run the unsubscribe command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_unsubscribe($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'unsubscribe'), $args);
}

/**
 * Run the open command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_open($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'open'), $args);
}

/**
 * Run the popen command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_popen($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'popen'), $args);
}

/**
 * Run the lLen command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_llen($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'lLen'), $args);
}

/**
 * Run the sGetMembers command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_sgetmembers($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sGetMembers'), $args);
}

/**
 * Run the mget command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_mget($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'mget'), $args);
}

/**
 * Run the expire command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_expire($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'expire'), $args);
}

/**
 * Run the zunionstore command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zunionstore($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zunionstore'), $args);
}

/**
 * Run the zinterstore command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zinterstore($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zinterstore'), $args);
}

/**
 * Run the zRemove command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zremove($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zRemove'), $args);
}

/**
 * Run the zRem command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zrem($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zRem'), $args);
}

/**
 * Run the zRemoveRangeByScore command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zremoverangebyscore($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zRemoveRangeByScore'), $args);
}

/**
 * Run the zRemRangeByScore command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zremrangebyscore($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zRemRangeByScore'), $args);
}

/**
 * Run the zRemRangeByRank command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zremrangebyrank($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zRemRangeByRank'), $args);
}

/**
 * Run the zSize command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zsize($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zSize'), $args);
}

/**
 * Run the substr command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_substr($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'substr'), $args);
}

/**
 * Run the rename command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_rename($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'rename'), $args);
}

/**
 * Run the del command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_del($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'del'), $args);
}

/**
 * Run the keys command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_keys($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'keys'), $args);
}

/**
 * Run the lrem command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_lrem($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'lrem'), $args);
}

/**
 * Run the ltrim command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_ltrim($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'ltrim'), $args);
}

/**
 * Run the lindex command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_lindex($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'lindex'), $args);
}

/**
 * Run the lrange command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_lrange($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'lrange'), $args);
}

/**
 * Run the scard command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_scard($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'scard'), $args);
}

/**
 * Run the srem command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_srem($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'srem'), $args);
}

/**
 * Run the sismember command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_sismember($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'sismember'), $args);
}

/**
 * Run the zrevrange command. (Params according to Redis documentation.)
 * @param mixed $redis
 * @return mixed
 */
function sp_redis_zrevrange($redis) {
    $args = func_get_args();
    array_shift($args);
    return call_user_func_array(array ($redis, 'zrevrange'), $args);
}

