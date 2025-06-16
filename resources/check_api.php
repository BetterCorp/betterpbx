<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
require_once dirname(__DIR__) . "/resources/require.php";

//add the required includes
require_once "resources/classes/permissions.php";
require_once "resources/classes/groups.php";

//start session if not started
if (!isset($_SESSION)) {
    session_start();
}

//initialize response array
$response = array();

//get the HTTP headers
$headers = getallheaders();

//validate API key from Authorization header
$auth_header = $headers['Authorization'] ?? '';
if (empty($auth_header) || !preg_match('/^Key\s+(.+)$/', $auth_header, $matches)) {
    header("HTTP/1.1 401 Unauthorized");
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Missing or invalid Authorization header']);
    exit;
}

//extract the API key
$api_key = $matches[1];

//initialize the database connection
$database = new database;

//get domain from header or resolve from hostname
$domain_uuid = $headers['Domain'] ?? null;
$domain_name = null;

if (empty($domain_uuid)) {
    //get domain name from HTTP host
    $domain_name = $_SERVER['HTTP_HOST'];
    if (substr_count($domain_name, '.') > 1) {
        $domain_array = explode('.', $domain_name);
        if (count($domain_array) > 2) {
            unset($domain_array[0]);
            $domain_name = implode('.', $domain_array);
        }
    }

    //lookup domain uuid
    $sql = "select domain_uuid, domain_name from v_domains ";
    $sql .= "where domain_name = :domain_name ";
    $parameters['domain_name'] = $domain_name;
    $row = $database->select($sql, $parameters, 'row');
    unset($parameters);

    if ($row) {
        $domain_uuid = $row['domain_uuid'];
        $domain_name = $row['domain_name'];
    }
    else {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Could not resolve domain']);
        exit;
    }
}
else {
    //validate provided domain uuid
    if (!is_uuid($domain_uuid)) {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid Domain header format']);
        exit;
    }

    //lookup domain name
    $sql = "select domain_name from v_domains ";
    $sql .= "where domain_uuid = :domain_uuid ";
    $parameters['domain_uuid'] = $domain_uuid;
    $domain_name = $database->select($sql, $parameters, 'column');
    unset($parameters);

    if (empty($domain_name)) {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid domain']);
        exit;
    }
}

//look up the user by API key
$sql = "select u.*, d.domain_name ";
$sql .= "from v_users as u, v_domains as d ";
$sql .= "where u.api_key = :api_key ";
$sql .= "and u.domain_uuid = :domain_uuid ";
$sql .= "and u.domain_uuid = d.domain_uuid ";
$parameters['api_key'] = $api_key;
$parameters['domain_uuid'] = $domain_uuid;
$user = $database->select($sql, $parameters, 'row');
unset($parameters);

//validate the user
if (empty($user)) {
    header("HTTP/1.1 401 Unauthorized");
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid API key']);
    exit;
}

//check if user is enabled
if ($user['user_enabled'] != 'true') {
    header("HTTP/1.1 403 Forbidden");
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'User account disabled']);
    exit;
}

//set session variables
$_SESSION["domain_uuid"] = $domain_uuid;
$_SESSION["domain_name"] = $user['domain_name'];
$_SESSION["user_uuid"] = $user['user_uuid'];
$_SESSION["username"] = $user['username'];

//get the groups assigned to the user
$sql = "select ";
$sql .= "	g.group_name, g.group_level ";
$sql .= "from ";
$sql .= "	v_group_users as u, ";
$sql .= "	v_groups as g ";
$sql .= "where ";
$sql .= "	u.domain_uuid = :domain_uuid ";
$sql .= "	and u.user_uuid = :user_uuid ";
$sql .= "	and u.group_uuid = g.group_uuid ";
$parameters['domain_uuid'] = $domain_uuid;
$parameters['user_uuid'] = $user['user_uuid'];
$groups = $database->select($sql, $parameters, 'all');
unset($parameters);

//set the groups and permissions in session
if (is_array($groups)) {
    foreach($groups as $row) {
        $_SESSION["groups"][] = $row["group_name"];
        //set the highest level
        if ($row['group_level'] > $_SESSION["user"]["group_level"]) {
            $_SESSION["user"]["group_level"] = $row['group_level'];
        }
    }
}

//get the permissions assigned to the groups that the user is a member of
$permissions = new permissions;
$_SESSION["permissions"] = $permissions->get_group_permissions($groups);

//set the domains
$domain = new domains();
$domain->session();

//set response headers
header('Content-Type: application/json');
header('Domain: ' . $domain_uuid);
echo json_encode([
    'status' => 'success', 
    'message' => 'Authentication successful',
    'domain' => [
        'uuid' => $domain_uuid,
        'name' => $domain_name
    ]
]);
exit;
