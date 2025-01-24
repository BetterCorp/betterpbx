<?php

	//application details
		$apps[$x]['name'] = "BetterPBX";
		$apps[$x]['uuid'] = "f0249deb-6794-5f09-ad41-da365a7e8820";
		$apps[$x]['category'] = "";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "1.0";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "Enabled additional features for BetterPBX";
		$apps[$x]['description']['en-gb'] = "Enabled additional features for BetterPBX";
		$apps[$x]['description']['ar-eg'] = "";
		$apps[$x]['description']['de-at'] = "Zeigt Informationen über die CPU, Festplatten, Speicher und anderes an.";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-de'] = "Zeigt Informationen über die CPU, Festplatten, Speicher und anderes an.";
		$apps[$x]['description']['es-cl'] = "Muestra información del sistema como RAM, CPU y Disco Duro";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-fr'] = "Affiche les information sur le sytème comme les informations sur la RAM, la CPU et le Disque Dur.";
		$apps[$x]['description']['he-il'] = "";
		$apps[$x]['description']['it-it'] = "";
		$apps[$x]['description']['ka-ge'] = "აჩვენებს ინფორმაციას CPU-ის, HDD-ის, RAM-ის და ა.შ. შესახებ.";
		$apps[$x]['description']['nl-nl'] = "Laat informatie zien over CPU, HardDisk, RAM en meer.";
		$apps[$x]['description']['pl-pl'] = "";
		$apps[$x]['description']['pt-br'] = "";
		$apps[$x]['description']['pt-pt'] = "Exibe informações do CPU, disco rígido, memória RAM e muito mais.";
		$apps[$x]['description']['ro-ro'] = "";
		$apps[$x]['description']['ru-ru'] = "Отображает на дисплее информацию о Процессоре, Пямяти, Дисковых накопителях и другоую.";
		$apps[$x]['description']['sv-se'] = "";
		$apps[$x]['description']['uk-ua'] = "";

	//permission details
	// QUICK SETUP
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "betterpbx_quick_setup";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

	// DNS
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "betterpbx_dns_setup";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "betterpbx_dns_manage";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

	//default settings
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "357eb21b-fe9a-5058-bbea-c9756547f444";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "BetterPBX";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "dns_api_key";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "test";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "";

?>
