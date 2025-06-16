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
	if (permission_exists('public_holidays_view')) {
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

//sync holidays
	if (isset($_GET['action']) && $_GET['action'] == 'sync') {
		//call the holiday API and update the database
		$holiday_api = new holiday_api();
		$holiday_api->sync_holidays();
		header("Location: public_holidays.php");
		exit;
	}

//get the list of countries and their holidays
	$sql = "SELECT DISTINCT country_code, COUNT(*) as holiday_count ";
	$sql .= "FROM v_public_holidays ";
	$sql .= "GROUP BY country_code ";
	$sql .= "ORDER BY country_code ";
	$database = new database;
	$countries = $database->select($sql, null, 'all');
	unset($sql);

//include the header
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-public_holidays']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo "		<a href='public_holidays.php?action=sync' class='button' alt='".$text['button-sync']."'>".$text['button-sync']."</a>\n";
	echo "	</div>\n";
	echo "</div>\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th>".$text['label-country']."</th>\n";
	echo "	<th>".$text['label-holiday_count']."</th>\n";
	echo "	<th class='hide-md-dn'>".$text['label-description']."</th>\n";
	echo "</tr>\n";

	if (is_array($countries) && @sizeof($countries) != 0) {
		foreach($countries as $row) {
			echo "<tr class='list-row' href='public_holidays_view.php?country_code=".urlencode($row['country_code'])."'>\n";
			echo "	<td>".$row['country_code']."</td>\n";
			echo "	<td>".$row['holiday_count']."</td>\n";
			echo "	<td class='hide-md-dn'>".$text['description-public_holidays']."</td>\n";
			echo "</tr>\n";
		}
	}
	unset($countries);

	echo "</table>\n";
	echo "<br />\n";

//include the footer
	require_once "resources/footer.php";
?> 