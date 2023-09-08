const fs = require("fs");
const AWS = require("@aws-sdk/client-s3");
const path = require("path");
const S3 = new AWS.S3Client(
  JSON.parse(fs.readFileSync("./config.json", "utf8"))
);
const config = JSON.parse(fs.readFileSync("./recording_config.json", "utf8"));

// read dirs in recordings
const domains = fs.readdirSync(config.basePath);

const baseTimeOfWorkingFiles = new Date().getTime() - 1000 * 60 * 60 * 24 * 5;

const work = async () => {
  for (let domain of domains) {
    console.log("checking domain: " + domain);
    const hasDomainDefinition =
      config.domains.filter((x) => x.domain === domain).length > 0;
    /*if (config.domains.filter((x) => x.domain === domain).length <= 0) {
      console.log(" - domain not found in config, skipping");
      continue;
    }*/
    const archivePath = path.join(config.basePath, domain, "archive");
    if (!fs.existsSync(archivePath)) {
      console.log(" - archive path not found, skipping");
      continue;
    }
    const domainConfigArr = config.domains.filter((x) => x.domain === domain);
    const domainConfig = domainConfigArr.length > 0 ? domainConfigArr[0] : null;
    for (let year of fs.readdirSync(archivePath)) {
      const archivePathYear = path.join(archivePath, year);
      for (let month of fs.readdirSync(archivePathYear)) {
        const archivePathMonth = path.join(archivePathYear, month);
        for (let day of fs.readdirSync(archivePathMonth)) {
          const recordings = fs.readdirSync(path.join(archivePathMonth, day));
          console.log(
            `${domain}: Found for ${day}/${month}/${year}: ${recordings.length} recordings`
          );
          for (let recording of recordings) {
            const recordingPath = path.join(archivePathMonth, day, recording);
            const createdDate = fs.statSync(recordingPath).birthtimeMs;
            if (createdDate > baseTimeOfWorkingFiles) {
              console.log(
                ` - ${domain}: skipping: ${recording} because it is too new (${createdDate})`
              );
              continue;
            }
            if (
              hasDomainDefinition &&
              domainConfig !== null &&
              domainConfig.store !== 0
            ) {
              console.log(` - ${domain}: uploading: ${recording}`);
              try {
                const recordingData = fs.readFileSync(recordingPath);
                const recordingKey = `${config.server}/${domain}/${year}/${month}/${day}/${recording}`;
                const uploadParams = {
                  Bucket: config.bucket,
                  Key: recordingKey,
                  Body: recordingData,
                };
                await S3.send(new AWS.PutObjectCommand(uploadParams));
                console.log(
                  " - uploaded: " + recording + " to " + recordingKey
                );
                fs.unlinkSync(recordingPath);
                console.log(" - deleted: " + recording);
              } catch (e) {
                console.error(e);
                console.error(" - failed to upload: " + recording);
              }
            } else {
              console.log(` - ${domain}: deleting: ${recording}`);
              fs.unlinkSync(recordingPath);
              console.log(" - deleted: " + recording);
            }
          }
        }
      }
    }
  }
};
work();
