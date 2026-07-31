<?php
$db = db_connect();
$db->query('REPAIR TABLE `'.TB_WHATSAPP_SCHEDULES.'`;');
$db->query('REPAIR TABLE `'.TB_WHATSAPP_CONTACTS.'`;');

if (!function_exists('wa_safe_write_config')) {
	function wa_safe_write_config($config_file, $config)
	{
		$payload = '<?php return ' . var_export($config, true) . ';';
		$current = file_exists($config_file) ? @file_get_contents($config_file) : false;
		if ($current === $payload) {
			return true;
		}

		$result = @file_put_contents($config_file, $payload, LOCK_EX);
		return $result !== false;
	}
}

$module_paths = get_module_paths();
if(!empty($module_paths))
{

	$whatsapp_modules = [
		"Whatsapp_profile" => [
			"path" => "inc/core/Whatsapp_profile",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1090,
		        'name' => 'WA Profiles'
		    ]
		],
		"Whatsapp_autoresponder" => [
			"path" => "inc/core/Whatsapp_autoresponder",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1080,
		        'name' => 'WA Autoresponder'
		    ]
		],

		"Whatsapp_callresponder" => [
			"path" => "inc/core/Whatsapp_callresponder",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1075,
		        'name' => 'WA Call Responder'
		    ]
		],
		"Whatsapp_chatbot" => [
			"path" => "inc/core/Whatsapp_chatbot",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1070,
		        'name' => 'WA Chatbot'
		    ]
		],

		"Whatsapp_send_message" => [
			"path" => "inc/core/Whatsapp_send_message",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1065,
		        'name' => 'WA Send Message'
		    ]
		],
		"Whatsapp_bulk" => [
			"path" => "inc/core/Whatsapp_bulk",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1060,
		        'name' => 'WA Bulk messaging'
		    ]
		],
		"Whatsapp_api" => [
			"path" => "inc/core/Whatsapp_api",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1050,
		        'name' => 'WA Rest api'
		    ]
		],
		"Whatsapp_evo_profile" => [
			"path" => "inc/core/Whatsapp_evo_profile",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1050,
		        'name' => 'WA Evolution api'
		    ]
		],
		"Whatsapp_export_participants" => [
			"path" => "inc/core/Whatsapp_export_participants",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1040,
		        'name' => 'WA Export participants'
		    ]
		],
		"Whatsapp_list_message_template" => [
			"path" => "inc/core/Whatsapp_list_message_template",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1030,
		        'name' => 'WA List message template'
		    ]
		],
		"Whatsapp_poll_template" => [
			"path" => "inc/core/Whatsapp_poll_template",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1025,
		        'name' => 'WA Poll message template'
		    ]
		],
		"Whatsapp_button_template" => [
			"path" => "inc/core/Whatsapp_button_template",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1020,
		        'name' => 'WA Button template'
		    ]
		],
		"Whatsapp_contact" => [
			"path" => "inc/core/Whatsapp_contact",
			"config" => [
		        'tab' => 2,
		        'type' => 'top',
		        'position' => 1010,
		        'name' => 'WA Contact'
		    ]
		]
	];

    foreach ($module_paths as $module_path) 
    {
    	foreach($whatsapp_modules as $whatsapp_module) {

    		$config_file = $module_path."/Config.php";

        	if (file_exists($config_file)) {

        		$config = include $config_file;

		        if(is_array($config) && isset($config['id']) && $config['id'] == "whatsapp"){

		        	if(get_option('wa_menu_type', 0)){
		        		$config['name'] = "Whatsapp";
		        		unset($config['menu']);
		        		wa_safe_write_config($config_file, $config);
		        	}else{
		        		$config['name'] = "Report";
		        		$config['menu'] = [
					        'tab' => 2,
					        'type' => 'top',
					        'position' => 1000,
					        'name' => 'Whatsapp'
					    ];
					    wa_safe_write_config($config_file, $config);
		        	}
		        }

		        $res = strpos($module_path, $whatsapp_module['path']);
		        
	        	if ($res !== false){
	        	
	        		if(is_array($config) && !isset($config['menu'])){
	        			if(get_option('wa_menu_type', 0)){
		        			if ( strpos($module_path, "inc/core/Whatsapp_profiles") === false ) {
			        			$config['menu'] = $whatsapp_module['config'];
			        			$config['show_plan'] = false;
			        			wa_safe_write_config($config_file, $config);
		        			}
		        		}
	        		}else{
	        			if(!get_option('wa_menu_type', 0)){
		        			unset($config['menu']);
		        			$config['show_plan'] = false;
		        			wa_safe_write_config($config_file, $config);
		        		}
	        		}
	        	}
	        }

        }
    }
}

