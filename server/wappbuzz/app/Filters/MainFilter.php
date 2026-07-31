<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class MainFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {   
        $path = trim($request->getUri()->getPath(), '/');
        if (
            stripos($path, 'Android_api/') === 0 ||
            stripos($path, 'android_api/') === 0 ||
            stripos($path, 'admin_api/') === 0 ||
            stripos($path, 'api/') === 0 ||
            $path === 'session/health'
        ) {
            return;
        }

        if(DEMO){
            $url = current_url();
            if(is_ajax()){
                if(
                    strpos($url, "/remove_assign") ||
                    strpos($url, "/do_assign") ||
                    strpos($url, "/do_import") || 
                    strpos($url, "/oauth_unofficial") || 
                    strpos($url, "/do_") ||
                    strpos($url, "/upload") ||
                    strpos($url, "/delete") ||
                    strpos($url, "/status/") || 
                    strpos($url, "/oauth_unofficial") || 
                    strpos($url, "/save")

                ){
                    ms([
                        "status" => "error",
                        "message" => __("This feature disabled in demo version")
                    ]);
                }
            }else{
                if(
                    (strpos($url, "/export"))
                ){
                    redirect_to( base_url("platform") );
                }
            }
        }

        /*
        * 
        */
        $db = \Config\Database::connect();

        $platform = (int)get_session("platform");
        $domain_id = (int)get_session("push_domain");
        
        if(!$platform){
            $platform_info = db_get("*", TB_PLATFORM, [], "is_default", "DESC");
            if($platform_info){
                $platform = $platform_info->platform_id;
            }
        }
        
        defined('PLATFORM') || define('PLATFORM', $platform);
        defined('TEAM_IDS') || define('TEAM_IDS', get_session("team_id")); 
        defined('USER_IDS') || define('USER_IDS', get_session('uid')); 

        $team_info = db_get("*", TB_TEAM, ["ids" => TEAM_IDS]);
        if($team_info){
            defined('TEAM_ID') || define('TEAM_ID', $team_info->id); 
            $team_info = serialize($team_info);
        }else{
            defined('TEAM_ID') || define('TEAM_ID', false); 
            $team_info = false;
        }

        defined('TEAM_INFO') || define('TEAM_INFO', $team_info); 


        $user_info = db_get("*", TB_USERS, ["ids" => USER_IDS]);
        

        if($user_info){
            defined('USER_ID') || define('USER_ID', $user_info->id); 
            $user_info = serialize($user_info);
        }else{
             defined('USER_ID') || define('USER_ID', false); 
            $user_info = false;

        }
        defined('USER_INFO') || define('USER_INFO', $user_info); 

        defined('TB_OPTIONS') || define('TB_OPTIONS', 'sp_options');
        
        $builder = $db->table(TB_OPTIONS);
        $builder->select('name,value');
        $query = $builder->get();
        $data_options = $query->getResult();
        $query->freeResult();

        if($data_options){
            $new_data_options = [];
            foreach ($data_options as $option_item) {
                $new_data_options[$option_item->name] = $option_item->value;
            }
            $data_options = serialize($new_data_options);
        }else{
            $data_options = false;
        }

        defined('SYSTEM_OPTIONS') || define('SYSTEM_OPTIONS', $data_options); 
        if(!is_ajax()){
            $current_module = find_modules( uri("segment", 1) );
            if( $current_module && isset($current_module["platform"]) ){
                if( $current_module["platform"] ){
                    if(PLATFORM != $current_module['platform']){
                        $platform_item = db_get("*", TB_PLATFORM, ["platform_id" => PLATFORM]);

                        if(!empty($platform_item)){
                            redirect_to( base_url( $platform_item->default_page ) );
                        }else{
                            redirect_to( base_url() );
                        }
                    }
                }
            }
        }
        //

        $module_paths = get_module_paths();

        if( !empty($module_paths) ){
            foreach ($module_paths as $key => $module_path) {
                $filter_file = $module_path . "/Filters/beforeFilter.php";
                if( file_exists( $filter_file ) ){
                    include_once $filter_file;
                }
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $path = trim($request->getUri()->getPath(), '/');
        if (
            stripos($path, 'Android_api/') === 0 ||
            stripos($path, 'android_api/') === 0 ||
            stripos($path, 'admin_api/') === 0 ||
            stripos($path, 'api/') === 0 ||
            $path === 'session/health'
        ) {
            return;
        }

        $module_paths = get_module_paths();

        if( !empty($module_paths) ){
            foreach ($module_paths as $key => $module_path) {
                $filter_file = $module_path . "/Filters/afterFilter.php";
                if( file_exists( $filter_file ) ){
                    include_once $filter_file;
                }
            }
        }
    }
}
