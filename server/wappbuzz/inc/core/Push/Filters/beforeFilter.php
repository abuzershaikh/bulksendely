<?php
platfrom([
    "ids" => ids(),
    "platform_id" => 3,
    "name" => __("Web Push Notification"),
    "default_page" => "push",
    "icon" => "fad fa-bell-on",
    "color" => "#0089cf",
    "status" => 1
]);

$current_domain_id = get_session("push_domain");
defined('PUSH_DOMAIN_ID') || define('PUSH_DOMAIN_ID', $current_domain_id);

$domain = db_get("*", TB_STACKPUSH_DOMAINS, ["id" => PUSH_DOMAIN_ID, "team_id" => TEAM_ID]);

if( get_session("uid") && get_session("team_id") && PLATFORM == 3 && uri("segment", 1) != "push_setttings" && uri("segment", 1) != "auth"){
    if(get_option("push_subject", "") == "" || get_option("push_public_key", "") == "" || get_option("push_private_key", "") == ""){
        redirect_to( base_url("push_setttings/index/push_setttings") );
    }
}

if($domain){
    defined('PUSH_DOMAIN') || define('PUSH_DOMAIN', serialize($domain));
}else{
    if($current_domain_id && PLATFORM == 3){
        remove_session(["push_domain"]);
        redirect_to( base_url("push") );
    }
    
    defined('PUSH_DOMAIN') || define('PUSH_DOMAIN', false);
    $module = find_modules( uri("segment", 1) );

    if($module && isset($module['platform']) && $module['platform'] == 3 ){

        if( uri("segment", 1) != "push" && uri("segment", 2) != "cron" && (!isset($module['login_required']) || (isset($module['login_required']) && $module['login_required']))){
            redirect_to( base_url("push") );
        }
        
    }
}

if(uri('segment',1) == "plans"){
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
                        if (preg_match("/block_push_permissions/i", $model_content))
                        {   
                            include_once $model_file;
                            
                            $class = get_module_class($model_file);
                            $data = new $class;
                            
                            $name = explode("\\", $class);
                            $config_item["html"] = $data->block_push_permissions();
                            $configs[] = $config_item;
                        }
                    }
                }
            }
        }
    }

    $menus = [];
    $top_tabs = [];

    if( ! empty($configs) ){

        $menus = $configs;

        if( count($menus) >= 2 ){
            usort($menus, function($a, $b) {
                if( isset($a['position']) &&  isset($b['position']) )
                    return $a['position'] <=> $b['position'];
            });
        }

        //TOP TAB
        foreach ($menus as $row) {
            if(isset($row['parent'])){
                $tab = $row['parent']['id'];
                $top_tabs[$tab][] = $row;
            }else{
                $tab = $row['id'];
                $top_tabs[$tab][] = $row;
            }
        }
    }

    $request->block_push_permissions = $top_tabs;
}
