<?php
function sp_featureswitch_check($feature) {
    static $features = array ();
    if (!isset ($features[$feature])) {
        $config = _sp_featureswitch_config();
        if (isset ($config[$feature])) {
            $check = $config[$feature];
            if (is_array($check)) {
                if (isset ($check['part_of'])) {
                    $features[$feature] = sp_featureswitch_check($check['part_of']);
                } else {
                    $features[$feature] = true;
                    if (isset ($check['ip_whitelist'])) {
                        $features[$feature] = $features[$feature] &&
                            _sp_featureswitch_check_iplist($check['ip_whitelist']);
                    }
                    if (isset ($check['ip_blacklist'])) {
                        $features[$feature] = $features[$feature] && 
                            !_sp_featureswitch_check_iplist($check['ip_blacklist']);
                    }
                }
            } else {
                $features[$feature] = (bool)$check;
            }
        } else {
            $features[$feature] = true;
        }
    }
    return $features[$feature];
}

/**
 * Load a featureswitch configuration.
 * @param array $config
 */
function sp_featureswitch_config(array $config) {
    _sp_featureswitch_config($config);
}

function _sp_featureswitch_config($c = null) {
    static $config = array ();
    if ($c !== null) {
        $config = $c;
    }
    return $config;
}

function _sp_featureswitch_check_iplist($iplist) {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!is_array($iplist)) {
        $iplist = array ($iplist);
    }
    foreach ($iplist as $check_ip) {
        if (_sp_featureswitch_check_ip($ip, $check_ip)) {
            return true;
        }
    }
}

function _sp_featureswitch_check_ip($ip, $check) {
    $parts = explode('/', $check);
    if (sizeof($parts) == 2) {
        $check_ip = $parts[0];
        $check_range = $parts[1];
    } else {
        $check_ip = $check;
        $check_range = 32;
    }
    $ip = ip2long($ip);
    $check_ip = ip2long($check_ip);
    $range = bindec(str_pad(str_repeat('1', $check_range), 32, '0'));
    return (($ip & $range) == ($check_ip & $range));
}
