<?php
/**
 * Connect to a MongoDB server.
 * 
 * Options:
 * - host: defaults to 'localhost'.
 * - port: defaults to the default MongoDB port.
 * - username: defaults to null, meaning no authentication is performed.
 * - password
 * - replica_set: are we connecting to a replica set? Defaults to false.
 * - persistent: use persistent connections? Defaults to true.
 * - db: the database to authenticate to. Defaults to null, in which case Mongo will authenticate against the admin DB.
 * 
 * @param array $options
 * @return mixed    Mongo connection.
 */
function sp_mongodb_connect(array $options = array ()) {
    $options += array (
        'host' => 'localhost',
        'port' => null,
        'username' => null,
        'password' => null,
        'replica_set' => false,
        'persistent' => true,
        'db' => null,
    );
    
    $uri = 'mongodb://';
    if ($options['username'] !== null && $options['password'] !== null) {
        $uri .= "{$options['username']}:{$options['password']}@";
    }
    $uri .= $options['host'];
    if ($options['port'] !== null) {
        $uri .= ":{$options['port']}";
    }
    if ($options['db'] !== null) {
        $uri .= "/{$options['db']}";
    }
    
    return new Mongo($uri);
}

/**
 * Select a DB and return a database object.
 * @param mixed $conn   Mongo connection.
 * @param string $db    Database name.
 * @return mixed    Mongo database
 */
function sp_mongodb_select_db($conn, $db) {
    return $conn->selectDB($db);
}

/**
 * List the names of all the DBs on the server.
 * @param mixed $db    Mongo connection.
 * @return array
 */
function sp_mongodb_list_dbs($conn) {
    $output = array ();
    $data = $conn->listDBs();
    foreach ($data['databases'] as $info) {
        $output[] = $info['name'];
    }
    return $output;
}

/**
 * Create a new DB and return a handle to it.
 * 
 * Since Mongo creates DBs automatically upon first use, this is a shell around sp_mongodb_select_db().
 * 
 * @param mixed $conn   Mongo connection.
 * @param string $db    Database name.
 * @return mixed    DB handle.
 */
function sp_mongodb_create_db($conn, $db) {
    return sp_mongodb_select_db($conn, $db);
}

/**
 * Delete a DB.
 * @param mixed $db    DB handle.
 */
function sp_mongodb_delete_db($db) {
    $db->drop();
}

/**
 * Get the name of the selected DB.
 * @param mixed $db    DB handle.
 * @return string
 */
function sp_mongodb_current_db($db) {
    return (string)$db;
}

/**
 * Select a collection from Mongo.
 * 
 * @param mixed $db    DB handle..
 * @param string $coll Collection name.
 * @return mixed    Handle to collection
 */ 
function sp_mongodb_select_collection($db, $coll) {
    return $db->selectCollection($coll);
}

/**
 * List the names of all the collections in the DB.
 * @param mixed $db   DB handle.
 * @param bool $prefix  Show collection names including their prefix? Defaults to true.
 * @return array
 */
function sp_mongodb_list_collections($db, $prefix = true) {
    $output = array ();
    if ($prefix) {
        foreach ($db->listCollections() as $coll) {
            $output[] = (string)$coll;
        }
    } else {
        $prefix_length = strlen((string)$db) + 1;
        foreach ($db->listCollections() as $coll) {
            $output[] = substr((string)$coll, $prefix_length);
        }
    }
    return $output;
}

/**
 * Create a new collection and return a handle to it.
 * 
 * Since Mongo creates collections automatically upon first use, this is a shell around sp_mongodb_select_collection().
 * 
 * @param mixed $db   Mongo DB.
 * @param string $coll    Collection name.
 * @return mixed    Collection
 */
function sp_mongodb_create_collection($db, $coll) {
    return sp_mongodb_select_collection($db, $coll);
}

/**
 * Delete a collection.
 * @param mixed $coll   Collection handle.
 */
function sp_mongodb_delete_collection($coll) {
    $coll->drop();
}

/**
 * Get the name of the specified collection.
 * @param mixed $coll   Collection handle.
 * @return string
 */
function sp_mongodb_current_collection($coll) {
    return (string)$coll;
}

/**
 * List info about all indexes in a collection.
 * @param mixed $coll   Collection handle.
 * @return array    (string name => array info)
 */
function sp_mongodb_list_indexes($coll) {
    $output = array ();
    foreach ($coll->getIndexInfo() as $index) {
        $output[$index['name']] = $index;
    }
    return $output;
}

/**
 * Create an index if it does not exist.
 * 
 * Specify key fields like this:
 * - array ('field' => 1) for an ascending index (useful for sorting)
 * - array ('field' => -1) for a descending index
 * - array ('field' => '2d') for geospatial indexing
 * 
 * You can specify multiple fields for the index (even for geospatial indexes, but I think you should put the loc field first).
 * 
 * Options:
 * - unique: boolean
 * - drop_dups: boolean; if true, then if creating a unique index, records will be removed to make the index unique
 * - background: boolean; whether to index in the background
 * - safe: boolean; whether to throw an exception if something goes wrong. This also has consequences for replica sets.
 *                  (see http://www.php.net/manual/en/mongocollection.ensureindex.php)
 * - name: string; an optional name for the index
 * - timeout: int; timeout in ms; only works when the 'safe' option is set
 * 
 * @param mixed $coll   Collection handle.
 * @param array $fields     Fields to index.
 * @param array $options    Optional.
 * @return bool
 */
function sp_mongodb_ensure_index($coll, array $fields, array $options = array ()) {
    if (isset ($options['drop_dups'])) {
        $options['dropDops'] = $options['drop_dups'];
        unset ($options['drop_dups']);
    }
    return $coll->ensureIndex($fields, $options);
}

/**
 * Delete an index from a collection.
 * @param mixed $coll   Collection handle.
 * @param string|array $fields  Fields or name of the index.
 * @return bool
 */
function sp_mongodb_delete_index($coll, $fields) {
    $output = $coll->deleteIndex($fields);
    return _sp_mongodb_cmd_result(true, $output, (string)$coll->db);
}

/**
 * Delete all indexes from a collection.
 * @param mixed $coll   Collection handle.
 * @return bool
 */
function sp_mongodb_delete_indexes($coll) {
    $output = $coll->deleteIndexes();
    return _sp_mongodb_cmd_result(true, $output, (string)$coll->db);
}

/**
 * Perform a command on a DB and fetch the results.
 * 
 * @param mixed $db
 * @param array $command
 * @param array $options
 * @return array|null
 */
function sp_mongodb_command($db, array $command) {
    $output = $db->command($command);
    if (_sp_mongodb_cmd_result(true, $output, (string)$db)) {
        return $output;
    }
}

/**
 * Perform a map/reduce query.
 * 
 * Params:
 * - map: a map function (string or MongoCode).
 * - reduce: a reduce function (string or MongoCode).
 * - finalize: optional function to each result row.
 * - query: optional array of query params.
 * - sort: array of sort fields and the sort direction.
 * - limit: number of objects to return. Defaults to null (no limit).
 * - verbose: if you enable this, you will receive statistics on execution time etc.
 * - out: how to output the query result. You have the following options:
 *     - string: save the output to a collection, overwriting the collection if it already exists.
 *     - array ('merge' => string): merge the output into a collection if it exists, create the collection otherwise.
 *     - array ('reduce' => string): use the reduce function to merge records into the collection.
 *     - array ('inline' => 1): directly output results into the 'results' field of the output. Only for small result sets.
 * 
 * @param mixed $coll       Collection reference.
 * @param array $mapreduce    Params.
 * @return array|string Inline results, or the name of the collection where the results have been placed.
 */
function sp_mongodb_mapreduce($coll, array $mapreduce) {
    $db = $coll->db;
    $command = array ('mapreduce' => substr((string)$coll, strlen((string)$db) + 1));
    $command += $mapreduce;
    
    foreach (array_diff(array ('map', 'reduce'), array_keys($command)) as $k) {
        throw new Exception("The '$k' param is required.");
    }
    foreach (array_intersect_key($command, array ('map' => null, 'reduce' => null, 'finalize' => null)) as $k => $f) {
        if (!$f instanceof MongoCode) {
            $command[$k] = new MongoCode($f);
        }
    }
    $raw = sp_mongodb_command($db, $command);
    if (isset ($raw['results'])) {
        return $raw['results'];
    }
    return $raw['result'];
}

/**
 * Find records in the collection.
 * @param mixed $coll   Collection handle.
 * @param array $query  Optional query params.
 * @param array $params    Params to apply directly to the cursor. See sp_mongodb_configure_cursor().
                           You can also set a 'fields' param to fetch (or exclude) specified fields.
 * @return mixed    Mongo cursor.
 */
function sp_mongodb_find($coll, array $query = array (), array $params = array ()) {
    if (isset ($params['fields'])) {
        $fields = $params['fields'];
        unset ($params['fields']);
    } else {
        $fields = array ();
    }
    if ($cursor = $coll->find($query, $fields)) {
        if ($params) {
            sp_mongodb_configure_cursor($cursor, $params);
        }
        return $cursor;
    }
}

/**
 * Find one record in the collection.
 * @param mixed $coll   Collection handle.
 * @param array $query  Optional query params.
 * @param array $fields Fields to return output of the result set.
 * @return array|null
 */
function sp_mongodb_find_one($coll, array $query = array (), array $fields = array ()) {
    return $coll->findOne($query, $fields);
}

/**
 * Perform a group operation on a collection.
 * 
 * Options:
 * - condition: selection conditions
 * - finalize: finalize function
 * 
 * @param mixed $coll       Collection handle.
 * @param mixed $keys       Field to group by, or a function that returns that key.
 * @param array $initial    Initial value for the aggregation.
 * @param mixed $reduce     Reduce function.
 * @param array $options
 * @return array    (groups => array, count => int, keys => int)
 */
function sp_mongodb_group($coll, $keys, array $initial, $reduce, array $options = array ()) {
    if (!$reduce instanceof MongoCode) {
        $reduce = new MongoCode($reduce);
    }
    if (isset ($options['finalize']) && !$options['finalize'] instanceof MongoCode) {
        $options['finalize'] = new MongoCode($options['finalize']);
    }
    $output = $coll->group($keys, $initial, $reduce, $options);
    if (_sp_mongodb_cmd_result(true, $output, (string)$coll->db)) {
        $output['groups'] = $output['retval'];
        unset ($output['retval']);
        unset ($output['ok']);
        return $output;
    }
}

/**
 * Count the number of results in a cursor.
 * @param mixed $cursor
 * @param bool $limit  Take limit and skip into account? Defaults to false.
 * @return int
 */
function sp_mongodb_count_cursor($cursor, $limit = false) {
    return $cursor->count($limit);
}

/**
 * Set options for a cursor.
 * 
 * Options:
 * - fields: array (string field => bool); use true to add a field to the result set, false to exclude it.
 * - limit: int; limit the number of results you receive.
 * - skip: int; skip a number of results.
 * - sort: array (string field => int direction); fields to sort on and direction (1 = asc, -1 = desc)
 * - batch_size: int
 * - hint: array; hint the DB which indexes to use.
 * - partial: bool; if not all shards can be contacted, is it OK to return just a part of the result set?
 * - slave_okay: bool; route reads to slaves if possible?
 * - snapshot: bool; use snapshot mode for the query? You can't unset this once snapshot mode has been set.
 * - timeout: int; timeout in ms
 * 
 * @param mixed $cursor
 * @param array $options
 * @return mixed Cursor
 */
function sp_mongodb_configure_cursor($cursor, array $options) {
    foreach ($options as $k => $v) {
        switch ($k) {
            case 'batch_size':
                $cursor->batchSize((int)$v);
                break;
            case 'fields':
                $cursor->fields($v);
                break;
            case 'hint':
                $cursor->hint($v);
                break;
            case 'limit':
                $cursor->limit((int)$v);
                break;
            case 'skip':
                $cursor->skip((int)$v);
                break;
            case 'partial':
                $cursor->partial((bool)$v);
                break;
            case 'sort':
                $cursor->sort($v);
                break;
            case 'slave_okay':
                $cursor->slaveOkay((bool)$v);
                break;
            case 'snapshot':
                if ($v) {
                    $cursor->snapshot();
                }
                break;
            case 'timeout':
                $cursor->timeout((int)$v);
                break;
        }
    }
    return $cursor;
}

/**
 * Insert data into a collection.
 * 
 * Options:
 * - safe: boolean; wait for the insert to succeed before continuing? Defaults to false.
 * - fsync: boolean; immediately write the data to disc? Defaults to false.
 * - timeout: int; timeout in ms (only used in conjunction with safe = true)
 * 
 * @param mixed $coll   Collection handle.
 * @param array $doc    The new document.
 * @param array $options
 */
function sp_mongodb_insert($coll, array $doc, array $options = array ()) {
    $output = $coll->insert($doc, $options);
    if (!isset ($options['safe']) || !$options['safe']) {
        return $output;
    } else {
        return _sp_mongodb_cmd_result(true, $output, (string)$coll->db);
    }
}

/**
 * Insert or update data into a collection.
 * 
 * If the document is already from the DB, update it; otherwise insert it.
 * 
 * Options:
 * - safe: boolean; wait for the insert to succeed before continuing? Defaults to false.
 * - fsync: boolean; immediately write the data to disc? Defaults to false.
 * - timeout: int; timeout in ms (only used in conjunction with safe = true)
 * 
 * @param mixed $coll   Collection handle.
 * @param array $doc    The new document.
 * @param array $options
 */
function sp_mongodb_save($coll, array $doc, array $options = array ()) {
    $output = $coll->save($doc, $options);
    if (!isset ($options['safe']) || !$options['safe']) {
        return $output;
    } else {
        return _sp_mongodb_cmd_result(true, $output, (string)$coll->db);
    }
}

/**
 * Update data in a collection.
 * 
 * By default, this will update just one record, but you have a setting to update all matching documents.
 * Also note the upsert option. Read the Mongo documentation for all options.
 * 
 * Options:
 * - upsert: boolean; defaults to false. If set to true, if no documents match, a new one will be created from
 *                    the criteria and the data.
 * - multiple: boolean; defaults to false.
 * - safe: boolean; wait for the insert to succeed before continuing? Defaults to false.
 * - fsync: boolean; immediately write the data to disc? Defaults to false.
 * - timeout: int; timeout in ms (only used in conjunction with safe = true)
 * 
 * @param mixed $coll       Collection handle.
 * @param array $criteria   Selection criteria.
 * @param array $updates    Updates.
 * @param array $options
 */
function sp_mongodb_update($coll, array $criteria, array $updates, array $options = array ()) {
    $output = $coll->update($criteria, $updates, $options);
    if (!isset ($options['safe']) || !$options['safe']) {
        return $output;
    } else {
        return _sp_mongodb_cmd_result(true, $output, (string)$coll->db);
    }
}

/**
 * Remove data from a collection.
 * 
 * Options:
 * - just_one: boolean; remove just one record? Defaults to false.
 * - safe: boolean; wait for the remove to succeed before continuing? Defaults to false.
 * - fsync: boolean; immediately write the data to disc? Defaults to false.
 * - timeout: int; timeout in ms (only used in conjunction with safe = true)
 * 
 * @param mixed $coll   Collection handle.
 * @param array $criteria   Criteria for which records to remove.
 * @param array $options
 */
function sp_mongodb_delete($coll, array $criteria = array (), array $options = array ()) {
    if (isset ($options['just_one'])) {
        $options['justOne'] = $options['just_one'];
        unset ($options['just_one']);
    }
    $output = $coll->remove($criteria, $options);
    if (!isset ($options['safe']) || !$options['safe']) {
        return $output;
    } else {
        return _sp_mongodb_cmd_result(true, $output, (string)$coll->db);
    }
}

/**
 * Get raw information about a query.
 * @param mixed $cursor
 * @return array
 */
function sp_mongodb_explain_query($cursor) {
    return $cursor->explain();
}

/**
 * Get raw information about the cursor.
 * @param mixed $cursor
 * @return array
 */
function sp_mongodb_describe_cursor($cursor) {
    $info = $cursor->info();
    $info['dead'] = $cursor->dead();
    return $info;
}

/**
 * Count records in the collection.
 * @param mixed $coll   Collection reference.
 * @param array $query  Query fields (optional).
 * @param int $limit
 * @param int $skip
 * @return int
 */
function sp_mongodb_count($coll, array $query = array (), $limit = 0, $skip = 0) {
    return $coll->count($query, $limit, $skip);
}

/**
 * Create a block of Javascript code that Mongo can execute.
 * @param string $code
 * @param array $scope
 * @return mixed
 */
function sp_mongo_create_code($code, array $scope = array ()) {
    return new MongoCode($code, $scope);
}

/**
 * Get a reference to the specified document.
 * @return array DB reference.
 */
function sp_mongodb_create_db_ref($coll, array $doc) {
    return $coll->createDBRef($doc);
}

/**
 * Get a document from a reference.
 * @param mixed $dbOrColl
 * @param array DB reference.
 * @return array|null    Document.
 */
function sp_mongodb_get_db_ref($dbOrColl, array $ref) {
    return $dbOrColl->getDBRef($ref);
}

/**
 * Get the raw output from the last command.
 * @return array|null
 */
function sp_mongodb_last_command_result() {
    return _sp_mongodb_cmd_result();
}

/**
 * Get the error on the last command on this DB, if any.
 * @param mixed DB reference.
 * @return Exception|null
 */
function sp_mongodb_last_error($db) {
    $err = $db->lastError();
    if ($err['err']) {
        return new Exception($err['err'], $err['n']);
    } else if ($err = _sp_mongodb_cmd_error((string)$db)) {
        return new Exception($err['message'], $err['code']);
    }
}

/**
 * Throw an exception if there is a last_error.
 * @param mixed DB reference.
 */
function sp_mongodb_check_error($db) {
    if ($ex = sp_mongodb_last_error($db)) {
        throw $ex;
    }
}

/**
 * Set a param on a database or a collecion.
 * 
 * Available params:
 * - slave_okay: boolean
 * - profiling_level: int
 * 
 * @param mixed $dbOrColl
 * @param string $param     Name of the parameter.
 * @param mixed $value
 */
function sp_mongodb_set_param($dbOrColl, $param, $value) {
    switch ($param) {
        case 'slave_okay':
            $dbOrColl->setSlaveOkay((bool)$value);
            break;
        case 'profiling_level':
            $dbOrColl->setProfilingLevel((int)$value);
            break;
    }
}

/**
 * Set a param on a database or a collecion.
 * 
 * Available params:
 * - slave_okay: boolean
 * - profiling_level: int
 * 
 * @param mixed $dbOrColl
 * @param string $param     Name of the parameter.
 * @return mixed
 */
function sp_mongodb_get_param($dbOrColl, $param) {
    switch ($param) {
        case 'slave_okay':
            return $dbOrColl->getSlaveOkay();
        case 'profiling_level':
            return $dbOrColl->getProfilingLevel();
    }
}

function _sp_mongodb_cmd_error($dbname, $err = null) {
    static $errors = array ();
    if ($err === null) {
        return isset ($errors[$dbname]) ? $errors[$dbname] : null;
    } else if (!$err) {
        unset ($errors[$dbname]);
    } else {
        $errors[$dbname] = $err;
    }
}

function _sp_mongodb_cmd_result($set = false, $value = null, $dbname = null) {
    static $result = null;
    if ($set) {
        $result = $value;
        if (isset ($result['ok']) && !$result['ok']) {
            _sp_mongodb_cmd_error($dbname, array ('message' => $result['errmsg'], 'code' => null));
            return false;
        } else {
            _sp_mongodb_cmd_error($dbname, false);
            return true;
        }
    }
    return $result;
}