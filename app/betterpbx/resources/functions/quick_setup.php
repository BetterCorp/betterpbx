<?php

function create_domain($domain_name)
{
  $database = database::new();
  //add the domain name
  $domain_enabled = 'true';
  $domain_description = '';
  $domain_uuid = uuid();

  //build the domain array
  $array['domains'][0]['domain_uuid'] = $domain_uuid;
  $array['domains'][0]['domain_name'] = $domain_name;
  $array['domains'][0]['domain_enabled'] = $domain_enabled;
  $array['domains'][0]['domain_description'] = $domain_description;

  //create a copy of the domain array as the database save method empties the array that we still need.
  $domain_array = $array;

  //add the new domain
  $database->app_name = 'domains';
  $database->app_uuid = '8b91605b-f6d2-42e6-a56d-5d1ded01bb44';
  $database->save($array);

  //add dialplans to the domain
  if (file_exists($_SERVER["PROJECT_ROOT"] . "/app/dialplans/app_config.php")) {
    //import the dialplans
    $dialplan = new dialplan;
    $dialplan->import($domain_array['domains']);
    unset($array);

    //add xml for each dialplan where the dialplan xml is empty
    $dialplans = new dialplan;
    $dialplans->source = "details";
    $dialplans->destination = "database";
    $dialplans->context = $domain_name;
    $dialplans->is_empty = "dialplan_xml";
    $array = $dialplans->xml();
  }

  //create the recordings directory for the new domain.
  if (isset($_SESSION['switch']['recordings']['dir']) && !empty($_SESSION['switch']['recordings']['dir'])) {
    if (!file_exists($_SESSION['switch']['recordings']['dir'] . "/" . $domain_name)) {
      mkdir($_SESSION['switch']['recordings']['dir'] . "/" . $domain_name, 0770);
    }
  }

  //create the voicemail directory for the new domain.
  if (isset($_SESSION['switch']['voicemail']['dir']) && !empty($_SESSION['switch']['voicemail']['dir'])) {
    if (!file_exists($_SESSION['switch']['voicemail']['dir'] . "/default/" . $domain_name)) {
      mkdir($_SESSION['switch']['voicemail']['dir'] . "/default/" . $domain_name, 0770);
    }
  }

  $cache = new cache;
  $response = $cache->flush();

  //clear the domains session array to update it
  unset($_SESSION["domains"]);
  unset($_SESSION['domain']);
  unset($_SESSION['switch']);

  return $domain_uuid; 
}

function create_gateway($domain_uuid, $gateway_name, $gateway_server, $gateway_username, $gateway_password, $gateway_protocol)
{
  $database = database::new();
  //build the gateway array
  $gateway_uuid = uuid();
  $array['gateways'][0]['gateway_uuid'] = $gateway_uuid;
  $array['gateways'][0]['domain_uuid'] = $domain_uuid;
  $array['gateways'][0]['gateway'] = $gateway_name;
  $array['gateways'][0]['username'] = $gateway_username;
  $array['gateways'][0]['password'] = $gateway_password;
  $array['gateways'][0]['proxy'] = $gateway_server;
  $array['gateways'][0]['register'] = 'true';
  $array['gateways'][0]['retry_seconds'] = '30';
  $array['gateways'][0]['expire_seconds'] = '800';
  $array['gateways'][0]['channels'] = '';
  $array['gateways'][0]['context'] = 'public';
  $array['gateways'][0]['profile'] = 'external';
  $array['gateways'][0]['enabled'] = 'true';
  $array['gateways'][0]['description'] = '';
  $array['gateways'][0]['caller_id_in_from'] = '';
  $array['gateways'][0]['contact_params'] = '';
  $array['gateways'][0]['register_proxy'] = '';
  $array['gateways'][0]['register_transport'] = $gateway_protocol;

  //save to the data
  $database->app_name = 'gateways';
  $database->app_uuid = '297ab33e-2c2f-8196-552c-f3567d2caaf8';
  $database->save($array);

  //synchronize configuration
  save_gateway_xml();

  //clear the cache
  $cache = new cache;
  $cache->delete("configuration:sofia.conf");

  //rescan the gateway
  $esl = event_socket::create();
  if ($esl->is_connected()) {
    $response = event_socket::api("sofia profile external rescan");
    unset($response);
  }
  unset($esl);

  return $gateway_uuid;
}

function create_destination($domain_uuid, $type, $destination_number, $destination_uuid, $context, $order)
{
  $database = database::new();
  $dialplan_uuid = uuid();

  if (empty($destination_uuid) || !is_uuid($destination_uuid)) {
    $destination_uuid = uuid();
  }

  //build the destination array
  $array['destinations'][0]['destination_uuid'] = $destination_uuid;
  $array['destinations'][0]['domain_uuid'] = $domain_uuid;
  $array['destinations'][0]['dialplan_uuid'] = $dialplan_uuid;
  $array['destinations'][0]['destination_type'] = $type;
  $array['destinations'][0]['destination_number'] = $destination_number;
  $array['destinations'][0]['destination_context'] = $context;
  $array['destinations'][0]['destination_enabled'] = 'true';
  $array['destinations'][0]['destination_description'] = '';
  $array['destinations'][0]['destination_order'] = $order;

  //build the dialplan array
  $array['dialplans'][0]['app_uuid'] = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4';
  $array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
  $array['dialplans'][0]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][0]['dialplan_name'] = $destination_number;
  $array['dialplans'][0]['dialplan_number'] = $destination_number;
  $array['dialplans'][0]['dialplan_context'] = $context;
  $array['dialplans'][0]['dialplan_continue'] = 'false';
  $array['dialplans'][0]['dialplan_order'] = $order;
  $array['dialplans'][0]['dialplan_enabled'] = 'true';
  $array['dialplans'][0]['dialplan_description'] = '';

  //build the dialplan detail array
  $array['dialplans'][0]['dialplan_details'][0]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][0]['dialplan_details'][0]['dialplan_uuid'] = $dialplan_uuid;
  $array['dialplans'][0]['dialplan_details'][0]['dialplan_detail_tag'] = 'condition';
  $array['dialplans'][0]['dialplan_details'][0]['dialplan_detail_type'] = 'destination_number';
  $array['dialplans'][0]['dialplan_details'][0]['dialplan_detail_data'] = $destination_number;
  $array['dialplans'][0]['dialplan_details'][0]['dialplan_detail_order'] = '010';

  //add the dialplan permission
  $p = permissions::new();
  $p->add("dialplan_add", 'temp');
  $p->add("dialplan_detail_add", 'temp');
  $p->add("dialplan_edit", 'temp');
  $p->add("dialplan_detail_edit", 'temp');

  //save to the data
  $database->app_name = 'destinations';
  $database->app_uuid = '5ec89622-b19c-3559-64f0-afde802ab139';
  $database->save($array);

  //remove the temporary permission
  $p->delete("dialplan_add", 'temp');
  $p->delete("dialplan_detail_add", 'temp');
  $p->delete("dialplan_edit", 'temp');
  $p->delete("dialplan_detail_edit", 'temp');

  //clear the cache
  $cache = new cache;
  $cache->delete("dialplan:".$context);

  return $destination_uuid;
}

function create_extension($domain_uuid, $extension_name, $extension_number, $extension_context, $extension_enabled, $extension_order, $extension_description)
{
  $database = database::new();
  //add the extension
  $extension_uuid = uuid();
  $voicemail_uuid = uuid();

  //build the array
  $array['extensions'][0]['domain_uuid'] = $domain_uuid;
  $array['extensions'][0]['extension_uuid'] = $extension_uuid;
  $array['extensions'][0]['extension'] = $extension_number;
  $array['extensions'][0]['number_alias'] = '';
  $array['extensions'][0]['password'] = generate_password();
  $array['extensions'][0]['provisioning_list'] = '';
  $array['extensions'][0]['vm_password'] = generate_password(6, 1);
  $array['extensions'][0]['accountcode'] = '';
  $array['extensions'][0]['effective_caller_id_name'] = $extension_name;
  $array['extensions'][0]['effective_caller_id_number'] = $extension_number;
  $array['extensions'][0]['outbound_caller_id_name'] = '';
  $array['extensions'][0]['outbound_caller_id_number'] = '';
  $array['extensions'][0]['emergency_caller_id_name'] = '';
  $array['extensions'][0]['emergency_caller_id_number'] = '';
  $array['extensions'][0]['directory_first_name'] = '';
  $array['extensions'][0]['directory_last_name'] = '';
  $array['extensions'][0]['directory_visible'] = 'true';
  $array['extensions'][0]['directory_exten_visible'] = 'true';
  $array['extensions'][0]['limit_max'] = '';
  $array['extensions'][0]['limit_destination'] = '';
  $array['extensions'][0]['voicemail_enabled'] = 'true';
  $array['extensions'][0]['voicemail_uuid'] = $voicemail_uuid;
  $array['extensions'][0]['voicemail_mail_to'] = '';
  $array['extensions'][0]['voicemail_file'] = 'attach';
  $array['extensions'][0]['voicemail_local_after_email'] = 'true';
  $array['extensions'][0]['missed_call_app'] = '';
  $array['extensions'][0]['missed_call_data'] = '';
  $array['extensions'][0]['user_context'] = $extension_context;
  $array['extensions'][0]['toll_allow'] = '';
  $array['extensions'][0]['call_timeout'] = '30';
  $array['extensions'][0]['call_group'] = '';
  $array['extensions'][0]['call_screen_enabled'] = 'false';
  $array['extensions'][0]['user_record'] = '';
  $array['extensions'][0]['auth_acl'] = '';
  $array['extensions'][0]['sip_force_contact'] = '';
  $array['extensions'][0]['sip_force_expires'] = '';
  $array['extensions'][0]['mwi_account'] = '';
  $array['extensions'][0]['sip_bypass_media'] = '';
  $array['extensions'][0]['absolute_codec_string'] = '';
  $array['extensions'][0]['force_ping'] = '';
  $array['extensions'][0]['dial_string'] = '';
  $array['extensions'][0]['enabled'] = $extension_enabled;
  $array['extensions'][0]['description'] = $extension_description;

  //add voicemail
  $array['voicemails'][0]['domain_uuid'] = $domain_uuid;
  $array['voicemails'][0]['voicemail_uuid'] = $voicemail_uuid;
  $array['voicemails'][0]['voicemail_id'] = $extension_number;
  $array['voicemails'][0]['voicemail_password'] = $array['extensions'][0]['vm_password'];
  $array['voicemails'][0]['voicemail_mail_to'] = '';
  $array['voicemails'][0]['voicemail_file'] = 'attach';
  $array['voicemails'][0]['voicemail_local_after_email'] = 'true';
  $array['voicemails'][0]['voicemail_enabled'] = 'true';
  $array['voicemails'][0]['voicemail_description'] = '';

  //grant temporary permissions
  $p = permissions::new();
  $p->add('extension_add', 'temp');
  $p->add('voicemail_add', 'temp');

  //save to the data
  $database->app_name = 'extensions';
  $database->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
  $database->save($array);

  //remove the temporary permission
  $p->delete('extension_add', 'temp');
  $p->delete('voicemail_add', 'temp');

  //clear the cache
  $cache = new cache;
  $cache->delete("directory:".$extension_number."@".$extension_context);

  return $extension_uuid;
}

function create_ring_group($domain_uuid, $ring_group_name, $ring_group_number, $ring_group_members)
{
  $database = database::new();
  //add the ring group
  $ring_group_uuid = uuid();
  $dialplan_uuid = uuid();

  //build the ring group array
  $array["ring_groups"][0]["ring_group_uuid"] = $ring_group_uuid;
  $array["ring_groups"][0]["domain_uuid"] = $domain_uuid;
  $array['ring_groups'][0]["ring_group_name"] = $ring_group_name;
  $array['ring_groups'][0]["ring_group_extension"] = $ring_group_number;
  $array['ring_groups'][0]["ring_group_greeting"] = '';
  $array['ring_groups'][0]["ring_group_strategy"] = 'simultaneous';
  $array["ring_groups"][0]["ring_group_call_timeout"] = '30';
  $array["ring_groups"][0]["ring_group_caller_id_name"] = '';
  $array["ring_groups"][0]["ring_group_caller_id_number"] = '';
  $array["ring_groups"][0]["ring_group_cid_name_prefix"] = '';
  $array["ring_groups"][0]["ring_group_cid_number_prefix"] = '';
  $array["ring_groups"][0]["ring_group_distinctive_ring"] = '';
  $array["ring_groups"][0]["ring_group_ringback"] = '${us-ring}';
  $array["ring_groups"][0]["ring_group_call_forward_enabled"] = 'false';
  $array["ring_groups"][0]["ring_group_follow_me_enabled"] = 'false';
  $array["ring_groups"][0]["ring_group_enabled"] = 'true';
  $array["ring_groups"][0]["ring_group_description"] = '';
  $array["ring_groups"][0]["dialplan_uuid"] = $dialplan_uuid;

  //add destinations
  if (is_array($ring_group_members)) {
    $x = 0;
    foreach ($ring_group_members as $member) {
      $array["ring_groups"][0]["ring_group_destinations"][$x]["ring_group_uuid"] = $ring_group_uuid;
      $array['ring_groups'][0]["ring_group_destinations"][$x]["ring_group_destination_uuid"] = uuid();
      $array['ring_groups'][0]["ring_group_destinations"][$x]["destination_number"] = $member;
      $array['ring_groups'][0]["ring_group_destinations"][$x]["destination_delay"] = '0';
      $array['ring_groups'][0]["ring_group_destinations"][$x]["destination_timeout"] = '30';
      $array['ring_groups'][0]["ring_group_destinations"][$x]["destination_prompt"] = '';
      $array['ring_groups'][0]["ring_group_destinations"][$x]["destination_enabled"] = 'true';
      $array['ring_groups'][0]["ring_group_destinations"][$x]["domain_uuid"] = $domain_uuid;
      $x++;
    }
  }

  //build the dialplan array
  $dialplan_xml = "<extension name=\"".xml::sanitize($ring_group_name)."\" continue=\"\" uuid=\"".xml::sanitize($dialplan_uuid)."\">\n";
  $dialplan_xml .= "  <condition field=\"destination_number\" expression=\"^".xml::sanitize($ring_group_number)."$\">\n";
  $dialplan_xml .= "    <action application=\"ring_ready\" data=\"\"/>\n";
  $dialplan_xml .= "    <action application=\"set\" data=\"ring_group_uuid=".xml::sanitize($ring_group_uuid)."\"/>\n";
  $dialplan_xml .= "    <action application=\"lua\" data=\"app.lua ring_groups\"/>\n";
  $dialplan_xml .= "  </condition>\n";
  $dialplan_xml .= "</extension>\n";

  $array["dialplans"][0]["domain_uuid"] = $domain_uuid;
  $array["dialplans"][0]["dialplan_uuid"] = $dialplan_uuid;
  $array["dialplans"][0]["dialplan_name"] = $ring_group_name;
  $array["dialplans"][0]["dialplan_number"] = $ring_group_number;
  $array["dialplans"][0]["dialplan_context"] = "\${domain_name}";
  $array["dialplans"][0]["dialplan_continue"] = "false";
  $array["dialplans"][0]["dialplan_xml"] = $dialplan_xml;
  $array["dialplans"][0]["dialplan_order"] = "101";
  $array["dialplans"][0]["dialplan_enabled"] = "true";
  $array["dialplans"][0]["dialplan_description"] = '';
  $array["dialplans"][0]["app_uuid"] = "1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2";

  //add the dialplan permission
  $p = permissions::new();
  $p->add("dialplan_add", "temp");
  $p->add("dialplan_edit", "temp");

  //save to the data
  $database->app_name = 'ring_groups';
  $database->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2';
  $database->save($array);

  //remove the temporary permission
  $p->delete("dialplan_add", "temp");
  $p->delete("dialplan_edit", "temp");

  //clear the cache
  $cache = new cache;
  $cache->delete("dialplan:\${domain_name}");

  return $ring_group_uuid;
}

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
    $message = "The domain does not point to the server (current points to " . $ip . "). \nCheck the domain is pointing to one of the following IPs: \n" . implode("\n", $ipsOnServer);
    message::add($message, 'negative', 5000);
    return false;
  }
  unset($ipsOnServer);

  $database = database::new();
  $sql = "select COUNT(*) from v_domains where lower(domain_name) = :domain_name";
  $existingDomains = $database->select($sql, ['domain_name' => $domain], 'column');
  unset($sql);
  if ($existingDomains > 0) {
    $message = "The domain already exists.";
    message::add($message, 'negative', 5000);
    return false;
  }
  unset($existingDomains);

  $domain_uuid = create_domain($domain);
  if (!$domain_uuid) {
    message::add("Failed to create the domain.", 'negative', 5000);
    return false;
  }
  message::add("Domain created successfully.", 'positive', 5000);

  $extensions = [];
  for ($i = 0; $i < $data['extension_count']; $i++) {
    $extension_number = $data['extension_start'] + $i;
    $extensions[] = [
      $extension_number => create_extension($domain_uuid, $data['extension_name'], $extension_number, $data['extension_context'], 'true', '100', ''),
    ];
  }
  message::add("Extensions created successfully.", 'positive', 5000);

  $gateway_uuid = create_gateway($domain_uuid, $data['phone_number'], $data['server'], $data['username'], $data['password'], $data['protocol']);
  if (!$gateway_uuid) {
    message::add("Failed to create the gateway.", 'negative', 5000);
    return false;
  }
  message::add("Gateway created successfully.", 'positive', 5000);

  $ring_group_uuid = create_ring_group($domain_uuid, $data['ring_group_name'], $data['ring_group_number'], $extensions);
  if (!$ring_group_uuid) {
    message::add("Failed to create the ring group.", 'negative', 5000);
    return false;
  }
  message::add("Ring group created successfully.", 'positive', 5000);

  $destinationIntl_uuid = create_destination($domain_uuid, 'inbound', $data['phone_number'], $ring_group_uuid, 'public', $data['domain'], '100');
  if (!$destinationIntl_uuid) {
    message::add("Failed to create the international destination.", 'negative', 5000);
    return false;
  }
  message::add("International destination created successfully.", 'positive', 5000);

  $destinationLocal_uuid = create_destination($domain_uuid, 'inbound', $data['phone_number_local'], $ring_group_uuid, 'public', $data['domain'], '100');
  if (!$destinationLocal_uuid) {
    message::add("Failed to create the local destination.", 'negative', 5000);
    return false;
  }
  message::add("Local destination created successfully.", 'positive', 5000);

  unset($database);
  message::add("Successfully created the tenant.", 'positive', 5000);
  return true;
}
