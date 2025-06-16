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
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";

//initialize the database object
$database = new database;

//add multi-lingual support
$language = new text;
$text = $language->get();

//set the content type
header('Content-Type: application/json');

//function to send error response
function send_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

//get the HTTP method
$method = $_SERVER['REQUEST_METHOD'];

//check permissions based on method
switch ($method) {
    case 'GET':
        if (!permission_exists('extension_view')) {
            send_error('Access denied', 403);
        }
        break;
    case 'POST':
        if (!permission_exists('extension_add')) {
            send_error('Access denied', 403);
        }
        break;
    case 'PATCH':
        if (!permission_exists('extension_edit')) {
            send_error('Access denied', 403);
        }
        break;
    default:
        send_error('Method not allowed', 405);
}

//get the input data
$data = json_decode(file_get_contents('php://input'), true);

//handle GET request - list extensions
if ($method == 'GET') {
    //build the query
    $sql = "select e.*, ";
    $sql .= "( ";
    $sql .= "	select device_uuid ";
    $sql .= "	from v_device_lines ";
    $sql .= "	where domain_uuid = e.domain_uuid ";
    $sql .= "	and user_id = e.extension ";
    $sql .= "	limit 1 ";
    $sql .= ") AS device_uuid ";
    $sql .= "from v_extensions as e ";
    $sql .= "where true ";

    //add domain filter unless showing all extensions
    if (!(!empty($_GET['show']) && $_GET['show'] == "all" && permission_exists('extension_all'))) {
        $sql .= "and e.domain_uuid = :domain_uuid ";
        $parameters['domain_uuid'] = $_SESSION['domain_uuid'];
    }

    //add search filter if provided
    if (!empty($_GET['search'])) {
        $search = strtolower($_GET['search']);
        $sql .= "and ( ";
        $sql .= " lower(extension) like :search ";
        $sql .= " or lower(number_alias) like :search ";
        $sql .= " or lower(effective_caller_id_name) like :search ";
        $sql .= " or lower(effective_caller_id_number) like :search ";
        $sql .= " or lower(outbound_caller_id_name) like :search ";
        $sql .= " or lower(outbound_caller_id_number) like :search ";
        $sql .= " or lower(emergency_caller_id_name) like :search ";
        $sql .= " or lower(emergency_caller_id_number) like :search ";
        $sql .= " or lower(directory_first_name) like :search ";
        $sql .= " or lower(directory_last_name) like :search ";
        if (permission_exists("extension_call_group")) {
            $sql .= " or lower(call_group) like :search ";
        }
        $sql .= " or lower(user_context) like :search ";
        $sql .= " or lower(enabled) like :search ";
        $sql .= " or lower(description) like :search ";
        $sql .= ") ";
        $parameters['search'] = '%'.$search.'%';
    }

    //get the extensions
    $extensions = $database->select($sql, $parameters ?? null, 'all');
    if (is_array($extensions)) {
        echo json_encode(['status' => 'success', 'data' => $extensions]);
    }
    else {
        echo json_encode(['status' => 'success', 'data' => []]);
    }
    exit;
}

//handle POST request - create extension
if ($method == 'POST') {
    //validate the data
    if (empty($data['extension'])) {
        send_error('Extension number is required');
    }

    //check if extension already exists
    $extension = new extension;
    if ($extension->exists($_SESSION['domain_uuid'], $data['extension'])) {
        send_error('Extension already exists');
    }

    //prepare the array
    $array['extensions'][0] = $data;
    $array['extensions'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
    $array['extensions'][0]['extension_uuid'] = uuid();

    //set a default password if not provided
    if (empty($array['extensions'][0]['password'])) {
        $array['extensions'][0]['password'] = generate_password($_SESSION["extension"]["password_length"]["numeric"], $_SESSION["extension"]["password_strength"]["numeric"]);
    }

    //save the extension
    $database->app_name = 'extensions';
    $database->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
    $database->save($array);

    //clear the cache
    $cache = new cache;
    $cache->delete("directory:".$data['extension']."@".$_SESSION['domain_name']);

    //send response
    echo json_encode(['status' => 'success', 'data' => $array['extensions'][0]]);
    exit;
}

//handle PATCH request - update extension
if ($method == 'PATCH') {
    //check for extension_uuid
    if (empty($_GET['extension_uuid']) || !is_uuid($_GET['extension_uuid'])) {
        send_error('Invalid extension UUID');
    }

    //get the existing extension
    $sql = "select * from v_extensions ";
    $sql .= "where extension_uuid = :extension_uuid ";
    $sql .= "and domain_uuid = :domain_uuid ";
    $parameters['extension_uuid'] = $_GET['extension_uuid'];
    $parameters['domain_uuid'] = $_SESSION['domain_uuid'];
    $extension = $database->select($sql, $parameters, 'row');
    if (!$extension) {
        send_error('Extension not found', 404);
    }

    //merge the updates with existing data
    $array['extensions'][0] = array_merge($extension, $data);
    $array['extensions'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
    $array['extensions'][0]['extension_uuid'] = $_GET['extension_uuid'];

    //save the extension
    $database->app_name = 'extensions';
    $database->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
    $database->save($array);

    //clear the cache
    $cache = new cache;
    $cache->delete("directory:".$extension['extension']."@".$_SESSION['domain_name']);

    //send response
    echo json_encode(['status' => 'success', 'data' => $array['extensions'][0]]);
    exit;
}
