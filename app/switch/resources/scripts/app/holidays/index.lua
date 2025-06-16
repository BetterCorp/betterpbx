--	FusionPBX
--	Version: MPL 1.1

--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://mozilla.org/MPL/

--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.

--	The Original Code is FusionPBX

--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Portions created by the Initial Developer are Copyright (C) 2014
--	the Initial Developer. All Rights Reserved.

--include config.lua
	require "resources.functions.config";

--connect to the database
	local Database = require "resources.functions.database";
	dbh = Database.new('system');

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--get the variables
	domain_name = session:getVariable("domain_name");
	domain_uuid = session:getVariable("domain_uuid");
	context = session:getVariable("context");
	destination_number = session:getVariable("destination_number");

--check if it's a holiday
	local sql = [[
		SELECT name, date_year, date_month, date_day, country_code
		FROM v_public_holidays 
		WHERE date_year = :year
		AND date_month = :month
		AND date_day = :day
		AND enabled = 'true'
	]];
	
	local current_date = os.date("*t");
	local parameters = {
		year = current_date.year,
		month = string.format("%02d", current_date.month),
		day = string.format("%02d", current_date.day)
	};
	
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[holidays] SQL: " .. sql .. "\n");
	end
	local holiday_found = false;
	dbh:query(sql, parameters, function(row)
		holiday_found = true;
		holiday_name = row.name;
		holiday_date = row.date_year .. "-" .. row.date_month .. "-" .. row.date_day;
		holiday_country = row.country_code;
	end);

--set the holiday variable
	if (holiday_found) then
		session:setVariable("holiday", "true");
		session:setVariable("holiday_name", holiday_name);
		session:setVariable("holiday_date", holiday_date);
		session:setVariable("holiday_country", holiday_country);
	else
		session:setVariable("holiday", "false");
	end

--close the database connection
	dbh:release(); 