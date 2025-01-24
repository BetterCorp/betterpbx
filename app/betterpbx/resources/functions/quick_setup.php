<?php

function quick_setup($data)
{
  $domain = $data['domain'];
  $ip = gethostbyname($domain);
  $output = shell_exec('ip a');
  $lines = explode("\n", $output);
  $ipsOnServer = [];
  
  foreach ($lines as $line) {
    if (preg_match('/inet\s+([0-9.]+)/', $line, $matches)) {
      $ipsOnServer[] = $matches[1];
    }
  }

  if (!in_array($ip, $ipsOnServer)) {
    $message = "The domain does not point to the server (current points to ".$ip."). \nCheck the domain is pointing to one of the following IPs: \n".implode("\n", $ipsOnServer);
    message::add($message, 'negative', 5000);
    return false;
  }
  message::add("Successfully created the tenant.", 'positive', 5000);
  return true;
}
?>