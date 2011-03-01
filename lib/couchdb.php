<?php
define('SP_COUCHDB_AUTH_BASIC', 1);
define('SP_COUCHDB_AUTH_COOKIE', 2);

/**
 * Connect to CouchDB.
 * @param string|null $host
 * @param int|null $port
 * @return mixed    The connector. This might just be a Sag object, but don't count on it.
 */
function sp_couchdb_connect($host = null, $port = null) {
    sp_require('lib/sag/Sag.php');
    
    if ($host === null) {
        $host = 'localhost';
    }
    if ($port === null) {
        $port = 5984;
    } else {
        $port = (int)$port;
    }
    return new Sag($host, $port);
}

/**
 * Set config params for the connection.
 * 
 * Available params:
 * - open_timeout: connection timeout on the socket, in sec.
 * - rw_timeout: read/write timeout for CouchDB, in sec (you may set a fractional number here).
 * 
 * @param mixed $conn
 * @param array $params
 */
function sp_couchdb_set_params($conn, array $params) {
    if (isset ($params['open_timeout'])) {
        $conn->setOpenTimeout($params['open_timeout']);
    }
    if (isset ($params['rw_timeout'])) {
        $timeout = (double)$params['rw_timeout'];
        $timeoutSecs = floor($timeout);
        $timeoutMS = round(1000 * ($timeout - $timeoutSecs));
        $conn->setRWTimeout($timeoutSecs, $timeoutMS);
    }
}

/**
 * Select a database.
 * @param mixed $conn
 * @param string $db
 */
function sp_couchdb_select_db($conn, $db) {
    $conn->setDatabase($db);
}

/**
 * Get the currently selected DB.
 * @param mixed $conn
 * @return string
 */
function sp_couchdb_current_db($conn) {
    return $conn->currentDatabase();
}

/**
 * List the available databases.
 * @param mixed $conn
 * @return array    A list of DB names.
 */
function sp_couchdb_list_dbs($conn) {
    return array_values($conn->getAllDatabases()->body);
}

/**
 * Create a new database.
 * @param mixed $conn
 * @param string $db    Name of the DB that you want to create.
 * @return bool
 */
function sp_couchdb_create_db($conn, $db) {
    return $conn->createDatabase($db)->body->ok;
}

/**
 * Delete a database. USE WITH CAUTION.
 * @param mixed $conn
 * @param string $db    Name of the DB that you want to create.
 * @return bool
 */
function sp_couchdb_delete_db($conn, $db) {
    return $conn->deleteDatabase($db)->body->ok;
}

/**
 * Generate a batch of UUIDs using CouchDB.
 * @param mixed $conn
 * @param int $n    Number of IDs to generate.
 * @return array
 */
function sp_couchdb_generate_uuids($conn, $n = 10) {
    return array_values($conn->generateIDs($n)->body->uuids);
}

/**
 * Log in to CouchDB.
 * @param mixed $conn
 * @param string $user
 * @param string $pass
 * @param int $authType     Authentication type; one of SP_COUCHDB_AUTH_*. Defaults to SP_COUCHDB_AUTH_BASIC.
 */
function sp_couchdb_login($conn, $user, $pass, $authType = null) {
    $sagAuthType = null;
    switch ($authType) {
        case SP_COUCHDB_AUTH_COOKIE:
            $sagAuthType = Sag::$AUTH_COOKIE;
            break;
        default:
            $sagAuthType = Sag::$AUTH_BASIC;
            break;
    }
    $conn->login($user, $pass, $sagAuthType);
}

/**
 * Get a raw URL from the DB and fetch a result object for the body.
 * @param mixed $conn
 * @param string $url
 * @return object
 */
function sp_couchdb_get_url($conn, $url) {
    return $conn->get($url)->body;
}

/**
 * Post to a raw URL from the DB and fetch a result object for the body.
 * @param mixed $conn
 * @param string $url
 * @param array|object $data
 * @return object
 */
function sp_couchdb_post_url($conn, $url, $data) {
    return $conn->post($data, $url)->body;
}

/**
 * Get all documents from the DB.
 * 
 * Available options: see http://wiki.apache.org/couchdb/HTTP_view_API
 * The options group, group_level and reduce are not available when fetching all docs.
 * 
 * @param mixed $conn
 * @param array $options
 * @return object
 */
function sp_couchdb_view_all_docs($conn, array $options = array ()) {
    $invalidOptions = array_intersect_key($options, array (
        'group' => true,
        'group_level' => true,
        'reduce' => true,
    ));
    if ($invalidOptions) {
        $keys = implode(', ', array_keys($invalidOptions));
        throw new RuntimeException("Invalid options for retrieving all documents: {$keys}.");
    }
    return _sp_couchdb_fetch_view($conn, "/_all_docs", $options);
}

/**
 * Get all documents from the DB.
 * 
 * Available options: see http://wiki.apache.org/couchdb/HTTP_view_API
 * The options group, group_level and reduce are not available when fetching all docs.
 * 
 * @param mixed $conn
 * @param array $options
 * @return object
 */
function sp_couchdb_view($conn, $design, $view, array $options = array ()) {
    return _sp_couchdb_fetch_view($conn, "/_design/{$design}/_view/{$view}", $options);
}

/**
 * Get a single document.
 * @param mixed $conn
 * @param string $id
 * @return object
 */
function sp_couchdb_get_doc($conn, $id) {
    return sp_couchdb_get_url($conn, "/{$id}");
}

/**
 * Post a new document.
 * @param mixed $conn
 * @param array|object $doc
 * @return object   Object containing the id and the rev for the newly created document.
 */
function sp_couchdb_post_doc($conn, $doc) {
    $result = sp_couchdb_post_url($conn, "/", $doc);
    if (!$result->ok) {
        throw new RuntimeException("Error while posting a document");
    }
    unset ($result->ok);
    return $result;
}

/**
 * Bulk put/post documents.
 * @param mixed $conn
 * @param array $docs   Array of documents.
 * @param bool $transaction Whether to treat the transactions as "all or nothing" or not. Defaults to false.
 * @return array    Associative array of id/rev objects for each of the updated or created doc IDs.
 */
function sp_couchdb_bulk_docs($conn, array $docs, $transaction = false) {
    $result = $conn->bulk($docs);
    $results = array ();
    foreach ($result->body as $row) {
        $results[$row->id] = $row;
    }
    return $results;
}

/**
 * Put a document, i.e. overwrite it.
 * @param mixed $conn
 * @param string $id
 * @param array|object $doc
 * @return object   Object containing the id and the rev for the newly created revision.
 */
function sp_couchdb_put_doc($conn, $id, $doc) {
    $result = $conn->put($id, $doc)->body;
    if (!$result->ok) {
        throw new RuntimeException("Error while putting a document");
    }
    unset ($result->ok);
    return $result;
}

/**
 * Delete a document.
 * @param mixed $conn
 * @param string $id
 * @param string $rev
 * @return object   Object containing the id and the rev for the newly created revision.
 */
function sp_couchdb_delete_doc($conn, $id, $rev) {
    $result = $conn->delete($id, $rev)->body;
    if (!$result->ok) {
        throw new RuntimeException("Error while deleting document '{$id}'");
    }
    unset ($result->ok);
    return $result;
}

/**
 * Add or update an attachment for a document.
 * @param mixed $conn
 * @param string $name
 * @param string $data
 * @param string $contentType
 * @param string $id
 * @param string $rev
 * @return object   Object containing the id and the rev for the newly created revision.
 */
function sp_couchdb_put_attachment($conn, $name, $data, $contentType, $id, $rev) {
    $result = $conn->setAttachment($name, $data, $contentType, $id, $rev)->body;
    if (!$result->ok) {
        throw new RuntimeException("Error while putting attachment '{$name}'");
    }
    unset ($result->ok);
    return $result;
}

/**
 * Delete an attachment from a document.
 * @param mixed $conn
 * @param string $name
 * @param string $data
 * @param string $contentType
 * @param string $id
 * @param string $rev
 * @return object   Object containing the id and the rev for the newly created revision.
 */
function sp_couchdb_delete_attachment($conn, $name, $id, $rev) {
    $result = $conn->delete("{$id}/{$name}", $rev)->body;
    if (!$result->ok) {
        throw new RuntimeException("Error while putting attachment '{$name}'");
    }
    unset ($result->ok);
    return $result;
}

function _sp_couchdb_fetch_view($conn, $path, array $options) {
    $post = array ();
    if (isset ($options['keys'])) {
        $post['keys'] = $options['keys'];
        unset ($options['keys']);
    }
    $url = $path;
    if ($options) {
        $url .= "?" . http_build_query(array_map('json_encode', $options));
    }
    if ($post) {
        return sp_couchdb_post_url($conn, $url, $post);
    } else {
        return sp_couchdb_get_url($conn, $url);
    }
}

/**
 * Call a view.
 * @param Sag $sag
 * @param string $design
 * @param string $view
 * @param array $params
 */
function core_model_couch_view(Sag $sag, $design, $view, array $params = array ()) {
    foreach (array ('key', 'startkey', 'endkey') as $k) {
        if (isset ($params[$k])) {
            $params[$k] = json_encode($params[$k]);
        }
    }
    return $sag->get("_design/{$design}/_view/{$view}?" . http_build_query($params));
}
