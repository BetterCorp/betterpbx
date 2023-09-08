<?php
require 'resources/s3/aws-autoloader.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

if (!class_exists('s3')) {
  class s3
  {
    private $conf;

    public function __construct()
    {
      //set the include path
      $conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
      set_include_path(parse_ini_file($conf[0])['document.root']);

      //parset the config.conf file
      $this->conf = parse_ini_file($conf[0]);
    }
  }
}
