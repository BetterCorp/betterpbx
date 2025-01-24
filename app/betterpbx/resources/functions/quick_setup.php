<?php

function quick_setup($data)
{
  $success = false;
  $domain = $data['domain'];
  $ip = gethostbyname($domain);
  $serverIp = $_SERVER['SERVER_ADDR'];
  if ($ip != $serverIp) {
    message::add("The domain does not point to the server.", 'negative', 5000);
    return false;
  }
  message::add("Successfully created the tenant.", 'positive', 5000);
  return true;
}
?>