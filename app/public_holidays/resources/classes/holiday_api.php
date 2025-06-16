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

class holiday_api {
	public $api_url = 'https://date.nager.at/api/v3/PublicHolidays/';
	public $database;
	public $years;

	public function __construct() {
		$this->database = new database;
		$this->years = array(
			date('Y') - 1,  // Previous year
			date('Y'),      // Current year
			date('Y') + 1,  // Next year
			date('Y') + 2,  // Next year + 2
			date('Y') + 3   // Next year + 3
		);
	}

	public function sync_holidays() {
		//get list of supported countries
		$countries = $this->get_supported_countries();
		
		foreach ($countries as $country_code) {
			foreach ($this->years as $year) {
				$this->sync_country_holidays($country_code, $year);
			}
		}
	}

	private function get_supported_countries() {
		//List of common country codes - you can expand this list
		return array(
			'US', 'GB', 'CA', 'AU', 'NZ', 'DE', 'FR', 'IT', 'ES', 'PT',
			'NL', 'BE', 'CH', 'AT', 'SE', 'NO', 'DK', 'FI', 'IE', 'JP',
      'ZA'
		);
	}

	private function sync_country_holidays($country_code, $year) {
		//fetch holidays from API
		$url = $this->api_url . $year . '/' . $country_code;
		$response = file_get_contents($url);
		
		if ($response === false) {
			return false;
		}

		$holidays = json_decode($response, true);
		
		if (!is_array($holidays)) {
			return false;
		}

		//insert new holidays (using ON CONFLICT DO NOTHING for immutability)
		foreach ($holidays as $holiday) {
			$sql = "INSERT INTO v_public_holidays ";
			$sql .= "(country_code, holiday_name, holiday_date, holiday_type, description) ";
			$sql .= "VALUES ";
			$sql .= "(:country_code, :holiday_name, :holiday_date, :holiday_type, :description) ";
			$sql .= "ON CONFLICT (country_code, holiday_date, holiday_name) DO NOTHING";
			
			$parameters['country_code'] = $country_code;
			$parameters['holiday_name'] = $holiday['name'];
			$parameters['holiday_date'] = $holiday['date'];
			$parameters['holiday_type'] = $holiday['type'];
			$parameters['description'] = isset($holiday['description']) ? $holiday['description'] : null;
			
			$this->database->execute($sql, $parameters);
			unset($parameters);
		}

		return true;
	}
}
?> 