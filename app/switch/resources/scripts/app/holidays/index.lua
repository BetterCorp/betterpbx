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
		SELECT holiday_name, holiday_date, country_code
		FROM v_holidays 
		WHERE holiday_date = CURRENT_DATE
		AND holiday_enabled = 'true'
	]];
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[holidays] SQL: " .. sql .. "\n");
	end
	local holiday_found = false;
	dbh:query(sql, {}, function(row)
		holiday_found = true;
		holiday_name = row.holiday_name;
		holiday_date = row.holiday_date;
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