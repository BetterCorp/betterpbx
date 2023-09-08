--holidays:start
holidays_table = {}
holidays_table[0] = {
	name = "Christmas",
	year = 2023,
	month = 12,
	day = 25,
	notes = ""
}
--holidays:end

--make sure the session is ready
	if ( session:ready() ) then
		local json = require "resources.functions.lunajson"

		--get the variables
			pin_number = session:getVariable("pin_number");
			sounds_dir = session:getVariable("sounds_dir");

		--connect to the database
			local Database = require "resources.functions.database";
			dbh = Database.new('system');

		--include json library
			if (debug["sql"]) then
				json = require "resources.functions.lunajson"
			end
	end
