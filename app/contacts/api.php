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
	Portions created by the Initial Developer are Copyright (C) 2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
require_once "resources/require.php";
require_once "resources/check_auth.php";

//check permissions
if (!permission_exists('contact_view') || !permission_exists('contact_all')) {
	echo "access denied";
	exit;
}

$database = new database;
$database->domain_uuid = $_SESSION['domain_uuid'];
$database->app_name = 'contacts';
$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
$tables = array(
	'v_contact_relations',
	'v_contact_attachments',
	'v_contact_times',
	'v_contact_notes',
	'v_contact_phones',
	'v_contact_addresses',
	'v_contact_emails',
	'v_contact_urls',
	'v_contact_settings'
);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST)) {
	try {
		header('Content-Type: text/html');
		//header('Content-Type: application/json');
		echo "<html><head><title>Importing Contacts</title></head><body>";
		echo "loading import<br /><br />";
		$json = file_get_contents('php://input');
		$data = json_decode($json);
		if (!is_array($data)) {
			echo "error: invalid json";
			exit;
		}
		echo json_encode(($data));
		echo '<br /><br /><br />';

		// insert into the db
		$array = array();
		$array['contacts'] = array();
		foreach ($data as $contact_index => $contact) {
			if ($contact->contact_type === 'user') {
				echo "Ignoring user contact.<br />";
				continue;
			}
			echo 'Adding contact.X.<br />';
			$contact_uuid = uuid();
			$newObject = array();
			foreach ($contact as $key => $value) {
				if (in_array('v_'.$key, $tables)) continue;
				/*if (is_null($value)) {
					$newObject[$key] = null;//'';
					continue;
				}*/
				$newObject[$key] = $value;
			}
			$newObject['last_mod_date'] = 'now()';
			$newObject['last_mod_user'] = $_SESSION['user_uuid'];
			$newObject['contact_uuid'] = $contact_uuid;
			$newObject['domain_uuid'] = $_SESSION['domain_uuid'];
			$database->uuid($contact_uuid);
			echo 'Adding contact for ' . $_SESSION['domain_uuid'] . '.<br />';

			echo 'Adding contact - items.<br />';
			foreach ($tables as $table) {
				$table_name = substr($table, 2);
				if (!isset($contact->{$table_name})) {
					echo ' - [' . $table_name . '] not set.<br />';
					continue;
				}
				if (!is_array($contact->{$table_name})) {
					echo ' - [' . $table_name . '] not array.<br />';
					continue;
				}
				if (sizeof($contact->{$table_name}) === 0) {
					echo ' - [' . $table_name . '] no data.<br />';
					continue;
				}
				$i = 1;
				echo 'Adding contact - items [' . $table_name . '].<br />';
				$newObject[$table_name] = array();
				foreach ($contact->{$table_name} as $table_data_key => $table_data) {
					$tableObject = array();
					echo 'Adding contact [' . $table_name . '] ' . $i . '/' . sizeof($contact->{$table_name}) . '<br />';
					foreach ($table_data as $key => $value) {
						/*if (is_null($value)) {
							$tableObject[$key] = null;//'';
							continue;
						}*/
						$tableObject[$key] = $value;
					}
					$tableObject[database::singular($table_name)."_uuid"] = uuid();
					$tableObject['contact_uuid'] = $contact_uuid;
					$tableObject['domain_uuid'] = $_SESSION['domain_uuid'];
					$newObject[$table_name][] = $tableObject;
					echo 'Added contact [' . $table_name . ']<br />';
					$i++;
				}
			}
			echo 'Saving contact<br /><br />';
			$array = array();
			$array['contacts'] = array($newObject);
			echo json_encode(($array));
			echo '<br /><br />';
			//$array['contacts'][] = $newObject;
			$saveResult = $database->save($array, true);
			echo json_encode(($saveResult));
			echo json_encode($database->message);
			if ($saveResult !== true) {
				throw new Exception('Failed to save data');
			}
			echo '<br /><br />Added contact<br />';
		}
		unset($array);
		echo '<br /><br /><br />';
	} catch (Exception $err) {
		$database->db->rollBack();
		var_dump($err);
	} catch (PDOException $err) {
		$database->db->rollBack();
		var_dump($err);
	}

	unset($array);
	echo "<body></html>";
	die;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET) && $_GET["a"] === 'get') {
	$sql = "select * from v_contacts where (domain_uuid = :domain_uuid) ";
	//$sql = "select * from v_contacts";
	//$sql = "select * from v_contacts";
	$parameters = array();
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$contact_array = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	if (is_array($contact_array)) {
		$contacts = array();
		foreach ($contact_array as $contact_index => $contact) {
			if ($contact->v_contacts->contact_type === 'user') {
				continue;
			}
			$newcontact = array();
			foreach ($contact as $contact_field => $contact_field_value) {
				$newcontact[$contact_field] = $contact_field_value;
			}
			$contacts[] = $newcontact;
		}
		unset($contact_array);

		foreach ($tables as $table) {
			foreach ($contacts as $contact_index => $contact) {
				$sql = "select * from " . $table . " where (contact_uuid = :contact_uuid) ";
				//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
				$parameters['contact_uuid'] = $contact['contact_uuid'];
				$contact_array = $database->select($sql, $parameters, 'all');
				if (sizeof($contact_array) > 0) {
					$contacts[$contact_index][substr($table, 2)] = $contact_array;
				}
				unset($contact_array);
			}
		}
		header('Content-Type: application/json');
		echo json_encode($contacts);
		exit;
	}
}

$document['title'] = 'Contacts Import/Export';
require_once "resources/header.php";
echo "<div class='action_bar' id='action_bar'>\n";
echo "	<div class='heading'><b>Contacts Import/Export</b></div>\n";
echo "	<div class='actions'>\n";
echo button::create(['type' => 'button', 'label' => $text['button-import'], 'icon' => $_SESSION['theme']['button_icon_import'], 'id' => 'btn_import', 'collapse' => 'hide-sm-dn', 'style' => 'margin-right: 15px;']);
echo button::create(['type' => 'button', 'label' => $text['button-export'], 'icon' => $_SESSION['theme']['button_icon_export'], 'id' => 'btn_export', 'collapse' => 'hide-sm-dn']);
echo "	</div>\n";
echo "	<div style='clear: both;'></div>\n";
echo "</div>\n";
echo modal::create(['id' => 'modal-import', 'message' => 'Are you sure you want to import?', 'type' => 'import', 'actions' => button::create(['type' => 'button', 'label' => 'Upload', 'icon' => 'check', 'id' => 'btn_import_confirm', 'style' => 'float: right; margin-left: 15px;', 'collapse' => 'never', 'onclick' => "modal_close(); do_import_popup();"])]);
echo '<input style="display: none;" type="file" id="file-upload" accept=".fusionpbx" />';
echo '<script>';
echo 'function uploadFile() { console.log("Upload contacts"); ';
echo 'const inputElement = document.getElementById("file-upload");';
echo 'if (inputElement === null) return alert("no file to upload");';
echo 'let elemFiles = (inputElement).files;';
echo 'if (elemFiles.length !== 1) return alert("no file to upload");';
echo 'let file = elemFiles[0];';
echo 'let callback = (data) => {';
echo 'try {';
echo 'let decodedAS = (new TextDecoder().decode(data.content));';
echo 'let decodedAD = atob(decodedAS);';
echo 'let decoded = JSON.parse(decodedAD);';
echo 'window._tempdecodedimport = decoded;';
echo 'let list = [ `${decoded.length} contacts` ];';
echo 'document.querySelector("#modal-import .modal-message").innerHTML = "Are you sure you want to import the following:<br /><br />"+list.join("<br />");';
echo 'modal_open("modal-import");';
echo '} catch (e) {';
echo 'alert("Invalid FusionPBX file");';
echo '} ';
echo '}; ';
echo 'let reader = new FileReader();';
echo 'reader.onload = function (self) {';
echo 'if (self.target.readyState != 2) return;';
echo 'if (self.target.error) {';
echo 'alert("Error while reading file");';
echo 'return;';
echo '}';
echo 'callback({';
echo 'name: file.name,';
echo 'size: file.size,';
echo 'type: file.type,';
echo 'content: self.target.result';
echo '});';
echo '};';
echo 'reader.readAsArrayBuffer(file);';
echo '}';
echo 'function do_import_popup() {';
echo 'fetch("api.php", {method: "POST",';
echo 'mode: "cors", cache: "no-cache", credentials: "same-origin", ';
echo 'headers: { "Content-Type": "application/json"},';
echo 'body: JSON.stringify(window._tempdecodedimport)}).then(()=>{ window.location = "contacts.php"; }).catch(x=>alert("An error occured"))';
echo '}';
echo 'function download(filename, text) {';
echo 'let element = document.createElement("a");';
echo 'element.setAttribute("href", "data:text/plain;charset=utf-8," + encodeURIComponent(text));';
echo 'element.setAttribute("download", filename);';
echo 'element.style.display = "none";';
echo 'document.body.appendChild(element);';
echo 'element.click();';
echo 'document.body.removeChild(element);';
echo '}';
echo 'const triggerDownload = (xdata) => {';
echo 'download("contacts-export-' . $_SESSION['domain_uuid'] . '.fusionpbx", btoa(JSON.stringify(xdata)));';
echo '}; ';
echo '(()=>{';
echo 'document.getElementById("btn_import").addEventListener("click", async () => {';
echo 'document.getElementById("file-upload").click()';
echo '});';
echo 'document.getElementById("btn_export").addEventListener("click", async () => {';
echo 'let data = await (await fetch("api.php?a=get")).json();';
echo 'triggerDownload(data);';
echo '});';
echo 'document.getElementById("file-upload").addEventListener("change", async () => { uploadFile(); ';
echo '});';
echo '})();';
echo '</script>';

//include the footer
require_once "resources/footer.php";
