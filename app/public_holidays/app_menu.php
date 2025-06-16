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

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//build the menu array
	$array['menu']['public_holidays'] = array(
		'title' => $text['title-public_holidays'],
		'url' => '/app/public_holidays/public_holidays.php',
		'icon' => 'calendar',
		'order' => 1,
		'groups' => array('admin','superadmin')
	);
?> 