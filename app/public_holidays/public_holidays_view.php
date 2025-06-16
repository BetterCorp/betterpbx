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

//get the country code
	$country_code = $_GET['country_code'];

//get the holidays for this country
	$sql = "SELECT * FROM v_public_holidays ";
	$sql .= "WHERE country_code = :country_code ";
	$sql .= "ORDER BY holiday_date ";
	$parameters['country_code'] = $country_code;
	$database = new database;
	$holidays = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//include the header
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-public_holidays']." - ".$country_code."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo "		<a href='public_holidays.php' class='button' alt='".$text['button-back']."'>".$text['button-back']."</a>\n";
	echo "	</div>\n";
	echo "</div>\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th>".$text['label-holiday_name']."</th>\n";
	echo "	<th>".$text['label-holiday_date']."</th>\n";
	echo "	<th>".$text['label-holiday_type']."</th>\n";
	echo "	<th class='hide-md-dn'>".$text['label-description']."</th>\n";
	echo "</tr>\n";

	if (is_array($holidays) && @sizeof($holidays) != 0) {
		foreach($holidays as $row) {
			echo "<tr class='list-row'>\n";
			echo "	<td>".$row['holiday_name']."</td>\n";
			echo "	<td>".$row['holiday_date']."</td>\n";
			echo "	<td>".$row['holiday_type']."</td>\n";
			echo "	<td class='hide-md-dn'>".$row['description']."</td>\n";
			echo "</tr>\n";
		}
	}
	unset($holidays);

	echo "</table>\n";
	echo "<br />\n";

//include the footer
	require_once "resources/footer.php";
?> 