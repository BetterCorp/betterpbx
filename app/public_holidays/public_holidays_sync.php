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

	Copyright (c) 2016-2025 BetterCorp (ninja@bettercorp.dev)
	All Rights Reserved.

	Contributor(s):
	BetterCorp <ninja@bettercorp.dev>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('public_holidays_sync')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the title
	$document['title'] = $text['title-public_holidays'];

//include the header
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-public_holidays']." - ".$text['button-sync_holidays']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo "		<a href='public_holidays.php' class='button' alt='".$text['button-back']."'>".$text['button-back']."</a>\n";
	echo "	</div>\n";
	echo "</div>\n";

	echo "<div class='content'>\n";
	echo "	<div class='message'>\n";
	echo "		".$text['message-syncing_holidays']."\n";
	echo "	</div>\n";
	echo "</div>\n";

//sync the holidays
	require_once "resources/classes/holiday_api.php";
	$holiday_api = new holiday_api();
	$holiday_api->sync_holidays();

//redirect to the main page
	header("Location: public_holidays.php");
	exit;

//include the footer
	require_once "resources/footer.php";
?> 