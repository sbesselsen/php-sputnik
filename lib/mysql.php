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
    return _sp_mysql_insert($table, $insert, $upsert ? 'upsert' : 'insert', $conn);
}

function sp_mysql_replace($table, array $insert, $conn = null) {
    return _sp_mysql_insert($table, $insert, 'replace', $conn);
}

function sp_mysql_insert_multiple($table, array $fields, array $values, $on_duplicate_key = null, $conn = null) {
    return _sp_mysql_insert_multiple($table, $fields, $values, 'insert', $on_duplicate_key, $conn);
}

function sp_mysql_replace_multiple($table, array $fields, array $values, $conn = null) {
    return _sp_mysql_insert_multiple($table, $fields, $values, 'replace', null, $conn);
}

function _sp_mysql_insert($table, array $insert, $action, $conn = null) {
    $fields = array_keys($insert);
    $values = array (array_values($insert));
    $on_duplicate_key = null;
    if ($action == 'upsert') {
        $sets = array ();
        foreach ($insert as $k => $v) {
            $field = substr($k, 1);
            $sets[] = "{{$field}} = VALUES({{$field}})";
        }
        $on_duplicate_key = 'UPDATE ' . implode(', ', $sets);
    }
    return _sp_mysql_insert_multiple($table, $fields, $values, $action == 'replace' ? 'replace' : 'insert', $on_duplicate_key, $conn);
}

function _sp_mysql_insert_multiple($table, array $fields, array $values, $action, $on_duplicate_key = null, $conn = null) {
    if (!$fields || !$values) {
        return;
    }
    $verb = $action == 'replace' ? "REPLACE" : "INSERT";
    $q = "{$verb} INTO {{$table}} ";
    $q_fields = array ();
    $types = array ();
    $i = 0;
    foreach ($fields as $field) {
        $types[++$i] = substr($field, 0, 1);
        $q_fields[] = "{" . substr($field, 1) . "}";
    }
    $q .= "(" . implode(', ', $q_fields) . ") VALUES ";
    $q_sets = array ();
    $params = array ();
    $j = 0;
    foreach ($values as $row) {
        $j++;
        $i = 0;
        $q_values = array ();
        foreach ($row as $v) {
            $i++;
            $q_values[] = $types[$i] . "value{$j}_{$i}";
            $params["value{$j}_{$i}"] = $v;
        }
        $q_sets[] = "(" . implode(', ', $q_values) . ")";
    }
    $q .= implode(', ', $q_sets);
    if ($on_duplicate_key !== null) {
        $q .= " ON DUPLICATE KEY " . $on_duplicate_key;
    }
    return sp_mysql_query($q, $params, $conn);
}

function _sp_mysql_query_rewrite($q, array $params, $conn) {
    // table and field escaping
    $q = preg_replace('(\{([^}\.]*)\.([^}\.]*?)\})', "`\\1`.`\\2`", $q);
    $q = preg_replace('(\{([^}\.]*)\})', "`\\1`", $q);
    
    // params
    $q = preg_replace_callback('(([!@%#])([a-z0-9_\-A-Z]+)(\[\])?)', function ($match) use ($params, $conn) {
        $v = array_key_exists($match[2], $params) ? $params[$match[2]] : '';
        if ($v === null) {
            return 'NULL';
        }
        $vs = is_array($v) ? $v : array ($v);
        $converted = array ();
        foreach ($vs as $v) {
            switch ($match[1]) {
                case '!':
                    $converted[] = '(' . $v . ')';
                    break;
                case '@':
                    $converted[] = "'" . mysql_real_escape_string($v, $conn) . "'";
                    break;
                case '%':
                    $v = number_format((double)$v, 10, '.', '');
                    $v = preg_replace('((\.[0-9])0+$)', '\\1', $v);
                    $converted[] = $v;
                    break;
                case '#':
                    $converted[] = (int)$v;
                    break;
            }
        }
        if (!empty ($match[3])) {
            return implode(', ', $converted);
        } else {
            return $converted[0];
        }
    }, $q);
    
    return $q;
}