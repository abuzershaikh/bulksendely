<?php
if(uri('segment',1) == "push_setttings"){
    $menu_top = [];
    $top_menu_groups = [];
    $bottom_menu_groups = [];

    $module_paths = get_module_paths();
    $settings_data = array();
    $configs = [];
    if(!empty($module_paths))
    {
        if( !empty($module_paths) ){
            foreach ($module_paths as $key => $module_path) {
                $config_path = $module_path . "/Config.php";
                $config_item = include $config_path;

                $model_paths = $module_path . "/Models/";
                $model_files = glob( $model_paths . '*' );

                if ( !empty( $model_files ) )
                {
                    foreach ( $model_files as $model_file )
                    {
                    	$model_content = file_get_contents($model_file);
    		        	if (preg_match("/block_push_settings/i", $model_content))
    					{	
    						include_once $model_file;
    	                    
                            $class = get_module_class($model_file);
                            $data = new $class;
                            
    						$name = explode("\\", $class);
    						$settings_data[ strtolower( $name[2] ) ] = $data->block_push_settings();
    					}
                    }
                }
            }
        }
    }

    if( !empty($settings_data)){
        uasort($settings_data, function($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        $settings_data = array_reverse($settings_data);
        $request->block_push_settings = $settings_data;
    }else{
        $request->block_push_settings = false;
    }
}
