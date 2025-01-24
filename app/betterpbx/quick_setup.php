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
BPPBX_UI::actionBar('title-bpbx-quick-setup', [
  //button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'gateways.php']),
]);

if (isset($_POST['action']) && $_POST['action'] == 'save') {
  //save the settings
}

BPPBX_UI::form('frm', 'quick_setup.php', [
  BPPBX_UI::card([
    '<h3>Domain</h3>',
  ],[
    BPPBX_UI::field('text', 'domain', 'Domain', '', 'The domain to use for the quick setup.'),
    BPPBX_UI::field('text', 'username', 'Username', '', 'The username to use for the quick setup.'),
  ]),
  BPPBX_UI::card([
    '<h3>Gateway</h3>',
  ],[
    BPPBX_UI::field('text', 'server', 'Server', '', 'SIP Server ip/hostname'),
    BPPBX_UI::field('text', 'username', 'Username', '', 'Account Username'),
    BPPBX_UI::field('text', 'password', 'Password', '', 'Account Password'),
    BPPBX_UI::field('select', 'protocol', 'Protocol', '', 'The protocol to use', [
      ['value'=>'udp','label'=>'UDP'],
      ['value'=>'tcp','label'=>'TCP'],
      ['value'=>'tls','label'=>'TLS'],
    ]),
  ]),
  BPPBX_UI::button('submit', 'Save', 'check', 'btn_save', '', ''),
]);


//include the footer
require_once "resources/footer.php";
