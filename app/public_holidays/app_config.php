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

//set the include path
	$conf['search']['public_holidays'] = true;
	$conf['menu']['public_holidays'] = true;

//create the database table
	$sql = "CREATE TABLE IF NOT EXISTS v_public_holidays (
		public_holiday_uuid uuid PRIMARY KEY DEFAULT gen_random_uuid(),
		country_code varchar(2) NOT NULL,
		holiday_name varchar(255) NOT NULL,
		holiday_date date NOT NULL,
		holiday_type varchar(50),
		description text,
		created timestamp with time zone DEFAULT now(),
		updated timestamp with time zone DEFAULT now(),
		UNIQUE(country_code, holiday_date, holiday_name)
	);";
	if (isset($this->database)) {
		$this->database->exec(check_sql($sql));
	}
	unset($sql);

//create the index
	$sql = "CREATE INDEX IF NOT EXISTS idx_public_holidays_country_code ON v_public_holidays(country_code);";
	if (isset($this->database)) {
		$this->database->exec(check_sql($sql));
	}
	unset($sql);

	$sql = "CREATE INDEX IF NOT EXISTS idx_public_holidays_holiday_date ON v_public_holidays(holiday_date);";
	if (isset($this->database)) {
		$this->database->exec(check_sql($sql));
	}
	unset($sql);
?> 