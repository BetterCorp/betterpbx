<?php

require_once dirname(__DIR__, 4) . "/resources/require.php";

function create_domain($database, $domain_name)
{
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
  //unset($_SESSION["domains"]);
  //unset($_SESSION['domain']);
  //unset($_SESSION['switch']);

  return $domain_uuid;
}

function create_gateway($database, $domain_uuid, $gateway_name, $gateway_server, $gateway_username, $gateway_password, $gateway_protocol)
{
  // Create a new instance of the gateways class
  $gateway = new gateways();

  // Use the add method to create a new gateway
  return $gateway->add($domain_uuid, $gateway_name, $gateway_server, $gateway_username, $gateway_password, $gateway_protocol);
}

function create_inbound_destination($domain_uuid, $number, $destination_number, $context, $order)
{
  // Create a new instance of the destinations class
  $destination = new destinations();

  $dialplan_uuid = uuid();

  // Prepare additional parameters
  $additional_params = [
    'destination_type' => 'inbound',
    'dialplan_uuid' => $dialplan_uuid,
    'destination_number_regex' => '^(' . $number . ')$',
    'destination_order' => $order,
    'destination_enabled' => 'true',
    'destination_description' => '',
    'destination_type_voice' => "1",
    'destination_app' => "transfer",
    'destination_data' => "{$number} XML {$context}",
    'destination_actions' => json_encode([
      [
        'destination_app' => 'transfer',
        'destination_data' => "{$destination_number} XML {$context}"
      ]
    ]),
  ];

  $app_uuid = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4';

  $x = 0;
  $array['dialplans'][$x]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_uuid'] = $dialplan_uuid;
  $array['dialplans'][$x]['app_uuid'] = $app_uuid;
  $array['dialplans'][$x]['dialplan_name'] = $number;
  $array['dialplans'][$x]['dialplan_number'] = $number;
  $array['dialplans'][$x]['dialplan_order'] = '100';
  $array['dialplans'][$x]['dialplan_continue'] = 'false';
  $array['dialplans'][$x]['dialplan_destination'] = 'false';
  $array['dialplans'][$x]['dialplan_context'] = 'public';
  $array['dialplans'][$x]['dialplan_enabled'] = 'true';
  $array['dialplans'][$x]['dialplan_description'] = 'Inbound';
  $y = 0;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = '${sip_to_user}';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = '^(' . $number . ')$';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = '20';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
  $y++;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'transfer';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $destination_number . ' XML ' . $context;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = '40';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

  // Use the add method to create a new destination
  $destinationUUID = $destination->add($domain_uuid, $number, 'public', $additional_params);


  // Save the dialplan to the database
  $p = permissions::new();
  $p->add("dialplan_add", "temp");
  $p->add("dialplan_detail_add", "temp");

  //save to the data
  $database = new database;
  $database->app_name = 'outbound_routes';
  $database->app_uuid = $app_uuid;
  $database->save($array);
  $message = $database->message;
  unset($array);

  //update the dialplan xml
  $dialplans = new dialplan;
  $dialplans->source = "details";
  $dialplans->destination = "database";
  $dialplans->uuid = $dialplan_uuid;
  $dialplans->xml();
  unset($dialplans);

  $p->delete("dialplan_add", "temp");
  $p->delete("dialplan_detail_add", "temp");


  return $destinationUUID;
}

function create_extension($domain_uuid, $extension_name, $extension_number, $extension_context, $extension_enabled, $extension_description)
{
  //add the extension
  $extension_uuid = uuid();

  //ensure extension number is properly formatted
  $extension_number = (string)$extension_number;
  if (empty($extension_number)) {
    throw new Exception("Extension number cannot be empty");
  }

  //get the password length and strength
  $password_length = $_SESSION["extension"]["password_length"]["numeric"] ?? 10;
  $password_strength = $_SESSION["extension"]["password_strength"]["numeric"] ?? 4;
  $password = generate_password($password_length, $password_strength);

  //create extension using the extension class
  $ext = new extension;
  $ext->domain_uuid = $domain_uuid;
  $ext->extension_uuid = $extension_uuid;
  $ext->extension = $extension_number;
  $ext->number_alias = '';
  $ext->password = $password;
  $ext->effective_caller_id_name = $extension_name;
  $ext->effective_caller_id_number = $extension_number;
  $ext->user_context = $extension_context;
  $ext->enabled = $extension_enabled;
  $ext->description = $extension_description;

  //add voicemail
  $ext->voicemail_password = generate_password($password_length, $password_strength);
  $ext->voicemail_enabled = 'true';

  //save the extension
  $ext->add();
  $ext->voicemail();
  $ext->xml();

  return $extension_uuid;
}

function create_ring_group($database, $domain_uuid, $ring_group_name, $ring_group_number, $ring_group_members)
{
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
  $dialplan_xml = "<extension name=\"" . xml::sanitize($ring_group_name) . "\" continue=\"\" uuid=\"" . xml::sanitize($dialplan_uuid) . "\">\n";
  $dialplan_xml .= "  <condition field=\"destination_number\" expression=\"^" . xml::sanitize($ring_group_number) . "$\">\n";
  $dialplan_xml .= "    <action application=\"ring_ready\" data=\"\"/>\n";
  $dialplan_xml .= "    <action application=\"set\" data=\"ring_group_uuid=" . xml::sanitize($ring_group_uuid) . "\"/>\n";
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

function create_outbound_dialplan($domain_uuid, $domain_name, $gateway_uuid, $dialplan_expression)
{
  $dialplan1_uuid = uuid();
  $dialplan2_uuid = uuid();
  $app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3';

  $x = 0;
  $array['dialplans'][$x]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_uuid'] = $dialplan1_uuid;
  $array['dialplans'][$x]['app_uuid'] = $app_uuid;
  $array['dialplans'][$x]['dialplan_name'] = 'call_direction-outbound-ZA-Default';
  $array['dialplans'][$x]['dialplan_order'] = '22';
  $array['dialplans'][$x]['dialplan_continue'] = 'true';
  $array['dialplans'][$x]['dialplan_context'] = $domain_name;
  $array['dialplans'][$x]['dialplan_enabled'] = 'true';
  $array['dialplans'][$x]['dialplan_description'] = '';
  $y = 1;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan1_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = '${user_exists}';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'false';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
  $y++;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan1_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = '${call_direction}';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = '^$';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
  $y++;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan1_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'destination_number';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $dialplan_expression;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
  $y++;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan1_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'export';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'call_direction=outbound';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_inline'] = 'true';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
  $y++;
  $x++;

  $array['dialplans'][$x]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_uuid'] = $dialplan2_uuid;
  $array['dialplans'][$x]['app_uuid'] = $app_uuid;
  $array['dialplans'][$x]['dialplan_name'] = 'Outbound-ZA-Default';
  $array['dialplans'][$x]['dialplan_order'] = '100';
  $array['dialplans'][$x]['dialplan_continue'] = 'false';
  $array['dialplans'][$x]['dialplan_context'] = $domain_name;
  $array['dialplans'][$x]['dialplan_enabled'] = 'true';
  $array['dialplans'][$x]['dialplan_description'] = '';
  $y = 1;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan2_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = '${user_exists}';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'false';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
  $y++;

  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan2_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'destination_number';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $dialplan_expression;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

  $y++;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan2_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'sip_h_accountcode=${accountcode}';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'false';

  $y++;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan2_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'export';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'call_direction=outbound';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_inline'] = 'true';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

  $y++;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan2_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'unset';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'call_timeout';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';
  $y++;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan2_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'hangup_after_bridge=true';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

  $y++;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan2_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'effective_caller_id_name=${outbound_caller_id_name}';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

  $y++;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan2_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'effective_caller_id_number=${outbound_caller_id_number}';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

  $y++;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan2_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'inherit_codec=true';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

  $y++;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan2_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'ignore_display_updates=true';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

  $y++;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan2_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'callee_id_number=27$2';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

  $y++;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan2_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'continue_on_fail=1,2,3,6,18,21,27,28,31,34,38,41,42,44,58,88,111,403,501,602,607,809';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

  $y++;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
  $array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $domain_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $dialplan2_uuid;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'bridge';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'sofia/gateway/' . $gateway_uuid . '/27$2';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
  $array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_enabled'] = 'true';

  // Save the dialplan to the database
  $p = permissions::new();
  $p->add("dialplan_add", "temp");
  $p->add("dialplan_detail_add", "temp");

  //save to the data
  $database = new database;
  $database->app_name = 'outbound_routes';
  $database->app_uuid = $app_uuid;
  $database->save($array);
  $message = $database->message;
  unset($array);

  //update the dialplan xml
  $dialplans = new dialplan;
  $dialplans->source = "details";
  $dialplans->destination = "database";
  $dialplans->uuid = $dialplan1_uuid;
  $dialplans->xml();
  unset($dialplans);

  $dialplans = new dialplan;
  $dialplans->source = "details";
  $dialplans->destination = "database";
  $dialplans->uuid = $dialplan2_uuid;
  $dialplans->xml();
  unset($dialplans);

  // Clear the cache
  $cache = new cache;
  $cache->delete("dialplan:public");
  $cache->delete("dialplan:" . $domain_name);

  $p->delete("dialplan_add", "temp");
  $p->delete("dialplan_detail_add", "temp");

  return [$dialplan1_uuid, $dialplan2_uuid];
}

function quick_setup($data)
{
  if (!isset($data['stage']) || empty($data['stage'])) {
    $data['stage'] = 'domain';
  }
  $domain = $data['domain'];
  if ($data['stage'] == 'domain') {
    return [
      'stage' => 'extension',
      'domain_uuid' => $domain_uuid,
    ];
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
  }

  try {
    $qcdatabase = new database;

    if ($data['stage'] == 'domain') {
      $sql = "select COUNT(*) from v_domains where lower(domain_name) = :domain_name";
      $existingDomains = $qcdatabase->select($sql, ['domain_name' => $domain], 'column');
      unset($sql);
      if ($existingDomains > 0) {
        $message = "The domain already exists.";
        message::add($message, 'negative', 5000);
        return false;
      }
      unset($existingDomains);

      $domain_uuid = create_domain($qcdatabase, $domain);
      if (!$domain_uuid) {
        throw new Exception("Failed to create the domain.");
      }
      message::add("Domain created successfully.", 'positive', 5000);
      return [
        'stage' => 'extension',
        'domain_uuid' => $domain_uuid,
      ];
    }

    $domain_uuid = $data['domain_uuid'];

    if ($data['stage'] == 'extension') {
      $extensions = [];
      $data['extension_start'] = intval($data['extension_start']);
      $data['extension_count'] = intval($data['extension_count']);
      for ($i = 0; $i < $data['extension_count']; $i++) {
        $extension_number = $data['extension_start'] + $i;
        $extensions[$extension_number] = create_extension($domain_uuid, $extension_number, $extension_number, $domain, 'true', '');
        message::add("Extension (" . $extension_number . ") created successfully.", 'positive', 5000);
      }
      message::add("Created (" . $data['extension_count'] . ") extensions successfully.", 'positive', 5000);
      return [
        'stage' => 'gateway',
        'domain_uuid' => $domain_uuid,
        'extensions' => json_encode($extensions),
      ];
    }

    $extensions = json_decode($data['extensions'], true);

    if ($data['stage'] == 'gateway') {
      $gateway_uuid = create_gateway($qcdatabase, $domain_uuid, $data['phone_number'], $data['server'], $data['username'], $data['password'], $data['protocol']);
      if (!$gateway_uuid) {
        throw new Exception("Failed to create the gateway.");
      }
      message::add("Gateway created successfully.", 'positive', 5000);
      return [
        'stage' => (isset($data['ring_group_enabled']) && $data['ring_group_enabled'] == 'true')
          ? 'ring_group'
          : 'destination_intl',
        'domain_uuid' => $domain_uuid,
        'extensions' => json_encode($extensions),
        'gateway_uuid' => $gateway_uuid,
      ];
    }

    $gateway_uuid = $data['gateway_uuid'];

    if ($data['stage'] == 'ring_group') {
      $data['ring_group_number'] = intval($data['ring_group_number']);
      $extensionNumbers = [];
      foreach ($extensions as $extension_number => $extension_uuid) {
        $extensionNumbers[] = $extension_number;
      }
      $ring_group_uuid = create_ring_group($qcdatabase, $domain_uuid, $data['ring_group_name'], $data['ring_group_number'], $extensionNumbers);
      if (!$ring_group_uuid) {
        throw new Exception("Failed to create the ring group.");
      }
      message::add("Ring group created successfully.", 'positive', 5000);
      return [
        'stage' => 'destination_intl',
        'domain_uuid' => $domain_uuid,
        'extensions' => json_encode($extensions),
        'gateway_uuid' => $gateway_uuid,
        'ring_group_uuid' => $ring_group_uuid,
      ];
    }

    $ring_group_uuid = $data['ring_group_uuid'];

    if ($data['stage'] == 'destination_intl') {
      if (isset($data['phone_number']) && !empty($data['phone_number'])) {
        $destinationIntl_uuid = create_inbound_destination($domain_uuid, $data['phone_number'], $data['ring_group_number'], $domain, '100');
        if (!$destinationIntl_uuid) {
          throw new Exception("Failed to create the international destination.");
        }
        message::add("International destination created successfully.", 'positive', 5000);
      } else {
        $destinationIntl_uuid = '-';
        message::add("No international destination created.", 'warning', 5000);
      }
      return [
        'stage' => (isset($data['phone_number_local']) && !empty($data['phone_number_local']))
          ? 'destination_local'
          : 'outbound_routes',
        'domain_uuid' => $domain_uuid,
        'extensions' => json_encode($extensions),
        'gateway_uuid' => $gateway_uuid,
        'ring_group_uuid' => $ring_group_uuid,
        'destination_intl_uuid' => $destinationIntl_uuid,
      ];
    }

    $destinationIntl_uuid = $data['destination_intl_uuid'];

    if ($data['stage'] == 'destination_local') {
      if (isset($data['phone_number_local']) && !empty($data['phone_number_local'])) {
        $destinationLocal_uuid = create_inbound_destination($domain_uuid, $data['phone_number_local'], $data['ring_group_number'], $domain, '100');
        if (!$destinationLocal_uuid) {
          throw new Exception("Failed to create the local destination.");
        }
        message::add("Local destination created successfully.", 'positive', 5000);
      } else {
        $destinationLocal_uuid = '-';
        message::add("No local destination created.", 'warning', 5000);
      }
      return [
        'stage' => 'outbound_routes',
        'domain_uuid' => $domain_uuid,
        'extensions' => json_encode($extensions),
        'gateway_uuid' => $gateway_uuid,
        'ring_group_uuid' => $ring_group_uuid,
        'destination_intl_uuid' => $destinationIntl_uuid,
        'destination_local_uuid' => $destinationLocal_uuid,
      ];
    }

    $destinationLocal_uuid = $data['destination_local_uuid'];

    if ($data['stage'] == 'outbound_routes') {
      $outbound_routes_uuid = create_outbound_dialplan($domain_uuid, $domain, $gateway_uuid, '^(\+27|0027|27|0)([0-9]{9,12})$');
      if (!$outbound_routes_uuid) {
        throw new Exception("Failed to create the outbound dialplan.");
      }
      message::add("Outbound dialplan created successfully.", 'positive', 5000);
      return [
        'stage' => 'start_gateways',
        'domain_uuid' => $domain_uuid,
        'extensions' => json_encode($extensions),
        'gateway_uuid' => $gateway_uuid,
        'ring_group_uuid' => $ring_group_uuid,
        'destination_intl_uuid' => $destinationIntl_uuid,
        'destination_local_uuid' => $destinationLocal_uuid,
        'outbound_routes_uuid' => $outbound_routes_uuid,
      ];
    }

    $outbound_routes_uuid = $data['outbound_routes_uuid'];

    if ($data['stage'] == 'start_gateways') {
      $gateway = new gateways();
      $gateway->rescan([[
        'checked' => 'true',
        'uuid' => $gateway_uuid,
      ]]);
      $gateway->start([[
        'checked' => 'true',
        'uuid' => $gateway_uuid,
      ]]);
      message::add("Gateways started successfully.", 'positive', 5000);
      return [
        'stage' => 'done',
        'domain_uuid' => $domain_uuid,
        'extensions' => json_encode($extensions),
        'gateway_uuid' => $gateway_uuid,
        'ring_group_uuid' => $ring_group_uuid,
        'destination_intl_uuid' => $destinationIntl_uuid,
        'destination_local_uuid' => $destinationLocal_uuid,
        'outbound_routes_uuid' => $outbound_routes_uuid,
      ];
    }

    $outbound_routes_uuid = $data['outbound_routes_uuid'];

    unset($database);
    message::add("Successfully created the tenant.", 'positive', 5000);
    return true;
  } catch (Exception $e) {
    // Rollback transaction on error
    // if ($database->db && $database->db->inTransaction()) {
    //   $database->db->rollBack();
    // }
    message::add($e->getMessage(), 'negative', 5000);
    return false;
  }
}
