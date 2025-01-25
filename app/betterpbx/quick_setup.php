<?php
/*
	BetterPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is BetterPBX

	The Initial Developer of the Original Code is
	Mitchell R <github.com/mrinc>
	Portions created by the Initial Developer are Copyright (C) 2024-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mitchell R <github.com/mrinc>
*/

//includes files
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/classes/ui.php";
require_once "resources/functions/quick_setup.php";

//check permissions
if (permission_exists('betterpbx_quick_setup')) {
  //access granted
} else {
  echo "access denied";
  exit;
}

//add multi-lingual support
$language = new text;
$text = $language->get();

$document['title'] = $text['title-bpbx-quick-setup'];
require_once "resources/header.php";

//display the page
echo BPPBX_UI::actionBar('title-bpbx-quick-setup', [
  //button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'gateways.php']),
]);

function reset_data()
{
  return [
    'domain' => '',
    'server' => '',
    'username' => '',
    'password' => '',
    'protocol' => 'udp',
    'phone_number' => '',
    'phone_number_local' => '',
    'extension_start' => '1000',
    'extension_count' => '1',
    'ring_group_name' => 'DEFAULT RG 5000',
    'ring_group_number' => '5000',
  ];
}
$data = reset_data();

$fields = [];
$fieldStage = '';

if (isset($_POST['action']) && $_POST['action'] == 'save') {
	if (!BPPBX_UI::token_validate()) {
		message::add($text['message-invalid_token'],'negative');
		exit;
	}
  $data = $_POST;
  $result = quick_setup($data);
  if ($result == true) {
    $data = reset_data();
  } else if ($result != false) {
    $fieldStage = $result['stage'];
    foreach ($result as $field => $value) {
      $fields[] = [ BPPBX_UI::hidden_input($field, $value) ];
    }
    $fields[] = [ '<script>setTimeout(() => { document.getElementById("quick_tenant_form").submit(); }, 0);</script>' ];
  }
}


echo BPPBX_UI::form('quick_tenant_form', 'quick_setup.php', [
  BPPBX_UI::token_input(),
  (count($fields) > 0 
  ? BPPBX_UI::card([], array_merge([
    "<div class='alert alert-info'>Busy: ".$fieldStage."</div>"
    ], $fields)) 
  : ''),
  BPPBX_UI::row('col-12 col-md-3 ' . (count($fields) > 0 ? 'd-none' : ''), [
    BPPBX_UI::card([
      '<h3>Domain</h3>',
    ], [
      BPPBX_UI::field('text', 'domain', 'Domain', $data['domain'], 'The domain to use for the quick setup.', [], ['required' => 'required']),
    ]),
    BPPBX_UI::card([
      '<h3>Gateway</h3>',
    ], [
      BPPBX_UI::field('text', 'server', 'Server', $data['server'], 'SIP Server ip/hostname', [], ['required' => 'required']),
      BPPBX_UI::field('text', 'username', 'Username', $data['username'], 'Account Username', [], ['required' => 'required']),
      BPPBX_UI::field('password', 'password', 'Password', $data['password'], 'Account Password', [], ['required' => 'required']),
      BPPBX_UI::field('select', 'protocol', 'Protocol', $data['protocol'], 'The protocol to use', [
        ['value' => 'udp', 'label' => 'UDP'],
        ['value' => 'tcp', 'label' => 'TCP'],
        ['value' => 'tls', 'label' => 'TLS'],
      ], ['required' => 'required']),
      BPPBX_UI::field('text', 'phone_number', 'Phone Number', $data['phone_number'], 'Phone Number (Intl Format)', [], ['required' => 'required']),
      BPPBX_UI::field('text', 'phone_number_local', 'Phone Number Local', $data['phone_number_local'], 'Phone Number (Local Format)', [], ['required' => 'required']),
    ]),
    BPPBX_UI::card([
      '<h3>Extensions</h3>',
    ], [
      BPPBX_UI::field('number', 'extension_count', 'Extension Count', $data['extension_count'], 'Extension Number', [], ['required' => 'required']),
      BPPBX_UI::field('number', 'extension_start', 'Extension Start', $data['extension_start'], 'Extension Start Number', [], ['required' => 'required']),
    ]),
    BPPBX_UI::card([
      '<h3>Ring Group</h3>',
    ], [
      BPPBX_UI::field('text', 'ring_group_name', 'Ring Group Name', $data['ring_group_name'], 'Ring Group Name', [], ['required' => 'required']),
      BPPBX_UI::field('number', 'ring_group_number', 'Ring Group Number', $data['ring_group_number'], 'Ring Group Number', [], ['required' => 'required']),
    ]),
  ]),
  BPPBX_UI::button('submit', 'Create Tenant', '', 'btn_save', '', '')
]);


//include the footer
require_once "resources/footer.php";
