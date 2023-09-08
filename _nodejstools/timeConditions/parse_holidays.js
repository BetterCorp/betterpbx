const fs = require("fs");
const https = require("https");
const HTMLParser = require("node-html-parser");
const moment = require("moment");

(async () => {
  const getHolidays = async (year) => {
    let resp = await new Promise((resolve, reject) => {
      let options = {
        method: "GET",
        hostname: "holidayapi.com",
        path: "/countries/za/" + year,
        headers: {
          authority: "holidayapi.com",
          accept:
            "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
          "accept-language": "en-US,en;q=0.9",
          "cache-control": "max-age=0",
          cookie:
            "cf_clearance=",
          dnt: "1",
          referer: "https://holidayapi.com/countries",
          "sec-ch-ua":
            '"Chromium";v="112", "Google Chrome";v="112", "Not:A-Brand";v="99"',
          "sec-ch-ua-mobile": "?0",
          "sec-ch-ua-platform": '"Linux"',
          "sec-fetch-dest": "document",
          "sec-fetch-mode": "navigate",
          "sec-fetch-site": "same-origin",
          "sec-fetch-user": "?1",
          "upgrade-insecure-requests": "1",
          "user-agent":
            "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36",
        },
        maxRedirects: 20,
      };

      let req = https.request(options, function (res) {
        let chunks = [];

        res.on("data", function (chunk) {
          chunks.push(chunk);
        });

        res.on("end", function (chunk) {
          let body = Buffer.concat(chunks);
          resolve(body.toString());
        });

        res.on("error", function (error) {
          console.error(error);
          reject(error);
        });
      });

      req.end();
    });
    const htmlDoc = HTMLParser.parse(resp);
    const tbody = htmlDoc.querySelectorAll("#holidays tbody tr");
    let holidays = [];
    for (let tr of tbody) {
      let cols = tr.querySelectorAll("td");
      let parsedDate = moment(year + " " + cols[0].text, "YYYY MMMM Do");
      let holiday = {
        dates: cols[0].text,
        date: parsedDate.format("YYYY-MM-DD"),
        dateFull: {
          year: Number.parseInt(parsedDate.format("YYYY")),
          month: Number.parseInt(parsedDate.format("M")),
          day: Number.parseInt(parsedDate.format("D")),
        },
        weekday: cols[1].text,
        name: cols[2].text.replace(/\n/gm, ""),
        notes: cols[3].text.replace(/\n/gm, ""),
      };
      holidays.push(holiday);
    }
    return holidays;
  };
  let holidays = await getHolidays(2023);
  holidays = holidays.concat(await getHolidays(2024));
  holidays = holidays.concat(await getHolidays(2025));
  //holidays = holidays.concat(await getHolidays(2026));
  //holidays = holidays.concat(await getHolidays(2027));
  //holidays = holidays.concat(await getHolidays(2028));
  //holidays = holidays.concat(await getHolidays(2029));
  //holidays = holidays.concat(await getHolidays(2030));
  fs.writeFileSync("holidays.json", JSON.stringify(holidays, null, 2));
  let luaCode = fs
    .readFileSync("time_conditions_base.lua", "utf8")
    .toString()
    .split("\n");

  let luaHolidaysArr = [
    '--holidays:start',
    'holidays_table_length = ' + holidays.length,
    'holidays_table = {}',
  ];

  for (let i = 0 ; i < holidays.length ; i++) {
    luaHolidaysArr.push(`holidays_table[${i}] = {`);
    luaHolidaysArr.push(`  name = "${holidays[i].name}",`);
    luaHolidaysArr.push(`  year = ${holidays[i].dateFull.year},`);
    luaHolidaysArr.push(`  month = ${holidays[i].dateFull.month},`);
    luaHolidaysArr.push(`  day = ${holidays[i].dateFull.day},`);
    luaHolidaysArr.push(`  notes = "${holidays[i].notes.trim()}"`);
    luaHolidaysArr.push(`}`);
  }

  luaHolidaysArr.push('--holidays:end');

  let start = luaCode.indexOf("--holidays:start");
  let end = luaCode.indexOf("--holidays:end");
  luaCode.splice(start, end);
  luaCode.splice(start, 0, ...luaHolidaysArr);
  fs.writeFileSync("time_conditions.lua", luaCode.join("\n"));
})();
