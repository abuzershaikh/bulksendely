<?php
namespace Core\Push\Controllers;

class Push extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push\Models\PushModel();
    }

    public function menu(){
        $modules = $this->model->get_modules();
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "modules" => $modules
        ];
        return view('Core\Push\Views\menu', $data);
    }
    
    public function index( $page = false ) {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        switch ( $page ) {
            case 'update':
                $ids = uri('segment', 4);
                $team_id = get_team("id");
                

                $data['content'] = view('Core\Push\Views\update', ["config" => $this->config ]);
                break;

            default:
                $total = $this->model->get_list(false);

                $datatable = [
                    "total_items" => $total,
                    "per_page" => 30,
                    "current_page" => 1
                ];

                $data_content = [
                    'total' => $total,
                    'datatable'  => $datatable,
                    'config'  => $this->config,
                ];

                $data['content'] = view('Core\Push\Views\list', $data_content);
                break;
        }


        return view('Core\Push\Views\index', $data);
    }

    public function ajax_list(){
        $total_items = $this->model->get_list(false);
        $result = $this->model->get_list(true);
        $data = [
            "result" => $result,
            "config" => $this->config
        ];
        ms( [
            "total_items" => $total_items,
            "data" => view('Core\Push\Views\ajax_list', $data)
        ] );
    }

    public function go($domain_ids = ""){
        $website = post("website");

        if($website){
            $domain_ids = $website;
        }

        if ($domain_ids == "") {
            ms([
                "status" => "error",
                "message" => _("Please select a domain")
            ]);
        }

        $domain = db_get("*", TB_STACKPUSH_DOMAINS, ["ids" => $domain_ids, "team_id" => TEAM_ID]);

        if(empty($domain)){
            ms([
                "status" => "error",
                "message" => _("The specified domain either does not exist")
            ]);
        }

        set_session(["push_domain" => $domain->id]);

        ms([
            "status" => "success",
        ]);
    }

    public function save( $ids = "" ){
        $website = post("website");
        $icon = post("icon");
        create_folder( FCPATH."cdn/" );
        create_folder( FCPATH."cdn/sw/" );
        create_folder( FCPATH."cdn/integrate/" );

        if(!checkdnsrr($website,"MX") && !checkdnsrr($website,"A") && !checkdnsrr($website,"CNAME")){
            ms([
                "status" => "error",
                "message" => __("Website missing or incorrect")
            ]);
        }

        if($icon == ""){
            ms([
                "status" => "error",
                "message" => __("Please enter website icon")
            ]);
        }

        $check_exist = db_get("*", TB_STACKPUSH_DOMAINS, ['domain' => $website, "team_id" => TEAM_ID]);

        if($check_exist){
            ms([
                "status" => "error",
                "message" => __("The website already exists")
            ]);
        }

        if($ids == ""){
            $ids = ids();
        }

        //Create integrate file
        $integrate_file = base_url("cdn/integrate.js");

        $data = [
            "team_id" => TEAM_ID,
            "domain" => $website,
            "icon" => remove_file_path($icon),
            "badge" => remove_file_path( get_option("push_website_badge_icon", get_module_path( __DIR__, "../Push/Assets/img/badge_icon.png") ) ), 
            "ios_icon" => remove_file_path($icon), 
            "ios_popup_text" => __("Add <strong>{app_name} App</strong> to your Home Screen to get regular updates. Tap Share {share_icon} and then <strong>Add to Home Screen</strong> {add_icon}", false),
            "safari_website_icon" => remove_file_path($icon),
            "sw_file" => "/cdn/sw/sw_{$ids}.js",
            "integrate_file" => "/cdn/integrate.js",
        ];

        $item = db_get("*", TB_STACKPUSH_DOMAINS, ['ids' => $ids, 'team_id' => TEAM_ID]);
        if( empty($item) ){
            $count_website = db_get("count(*) as count", TB_STACKPUSH_DOMAINS, ['team_id' => TEAM_ID])->count;
            $total_website = (int)permission("push_total_websites");
            if($count_website >= $total_website){
                ms([
                    "status" => "error",
                    "message" => sprintf(__("Currently, your plan can only add up to %s websites"), $total_website )
                ]);
            }

            $data["ids"] = $ids;
            $data["created"] = time();

            $push_id = db_insert(TB_STACKPUSH_DOMAINS, $data);
            $this->create_welcome_notification($push_id);
        }else{
            db_update(TB_STACKPUSH_DOMAINS, $data, ["ids" => $ids]);
            $push_id = $item->ids;
        }

        set_session(["push_domain" => $push_id]);

        return ms([
            "status" => "success",
            "message" => __("Success")
        ]);
    }

    public function create_welcome_notification($domain_id = ""){
        if( (int)get_option("push_welcome_status", 1) ){
            $domain = db_get("*", TB_STACKPUSH_DOMAINS, ["id" => $domain_id, "team_id" => TEAM_ID]);

            $data = [
                "campaign_id" => 0,
                "title" => get_option("push_welcome_text", "Welcome"),
                "message" => get_option("push_welcome_message", "Thanks for subscribing to us!"),
                "url" => "https://".$domain->domain."/",
                "icon" => remove_file_path($domain->icon),
                "large_image" => get_option("push_welcome_large_image", "")!=""?remove_file_path(get_option("push_welcome_large_image", "")):"",
                "large_image_status" => get_option("push_welcome_large_image", "")!=""?1:"",
                "utm" => '{"source":"","medium":"","name":""}',
                "utm_status" => 0,
                "auto_hide" => 0,
                "actions" => '[]',
                "expiry" => 1,
                "expiry_by" => "days",
                "expiry_status" => 0,
                "audience_status" => 0,
                "audience_id" => 0,
                "segment_id" => 0,
                "country" => "",
                "device" => "",
                "os" => "",
                "browser" => "",
                "time_post" => time(),
                "delay" => json_encode([
                    "days" => 0,
                    "hours" => 0,
                    "mins" => 0
                ]),
                "status" => 1,
                "changed" => time(),
                
                "created" => time(),
                "ids" => ids(),
                "type" => 2,
                "domain_id" => $domain->id,
                "team_id" => TEAM_ID
            ];

            db_insert(TB_STACKPUSH_SCHEDULES, $data);
        }
    }

    public function download_sw_file(){

        $domain = db_get("*", TB_STACKPUSH_DOMAINS, ["id" => PUSH_DOMAIN_ID, "team_id" => TEAM_ID]);
        if($domain){
            create_folder( FCPATH."cdn/" );
            create_folder( FCPATH."cdn/sw/" );

            $sw_payload_file = base_url("cdn/sw_payload.js");
            $sw_request = base_url("push_request")."/";

            $sw_data = "var comm_url = '$sw_request';
var default_title = '';
var default_message = '';
var default_icon = '".get_file_url($domain->icon)."';
var default_url = 'https://{$domain->domain}';
var client_id = '".TEAM_IDS."';
var domain_id = '$domain->ids';
importScripts('$sw_payload_file');";

            //Create SW file
            $sw_file = FCPATH."/cdn/sw/sw_{$domain->ids}.js";
            file_put_contents($sw_file, $sw_data);


            if(file_exists($sw_file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: text/javascript');
                header("Cache-Control: no-cache, must-revalidate");
                header("Expires: 0");
                header('Content-Disposition: attachment; filename="sw.js"');
                header('Content-Length: ' . filesize($sw_file));
                header('Pragma: public');
                flush();
                readfile($sw_file);
            }else{
                redirect_to( base_url("push_integrate") );
            }
        }else{
            redirect_to( base_url("push_integrate") );
        }
    }


public function download_manifest_file(){
        $domain = db_get("*", TB_STACKPUSH_DOMAINS, ["id" => PUSH_DOMAIN_ID, "team_id" => TEAM_ID]);
        if($domain  && $domain->ios_status && $domain->ios_name != "" && $domain->ios_short_name != "" && $domain->ios_start_url != "" && $domain->ios_bg_color != "" && $domain->ios_theme_color != "" && $domain->ios_icon != ""){


            create_folder( FCPATH."cdn/" );
            create_folder( FCPATH."cdn/manifest/" );

            $manifest_data = '{
    "name": "'.$domain->ios_name.'",
    "short_name": "'.$domain->ios_short_name.'",
    "start_url": "'.$domain->ios_start_url.'",
    "id": "'.$domain->ios_start_url.'",
    "display": "standalone",
    "background_color": "'.$domain->ios_bg_color.'",
    "theme_color": "'.$domain->ios_theme_color.'",
    "icons": [{ "src": "'.$domain->ios_icon.'", "sizes": "192x192" }]
}';

            //Create manifest file
            $manifest_file = FCPATH."/cdn/manifest/manifest_{$domain->ids}.json";
            file_put_contents($manifest_file, $manifest_data);

            if(file_exists($manifest_file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/json');
                header("Cache-Control: no-cache, must-revalidate");
                header("Expires: 0");
                header('Content-Disposition: attachment; filename="manifest.json"');
                header('Content-Length: ' . filesize($manifest_file));
                header('Pragma: public'); 
                flush();
                readfile($manifest_file);
            }else{
                redirect_to( base_url("push_ios_settings") );
            }
        }else{
            redirect_to( base_url("push_ios_settings") );
        }
    }

    public function delete( $ids = "" ){
        if($ids == ""){
            $ids = post("id");
        }

        db_delete( TB_STACKPUSH_DOMAINS,  [ "ids" => $ids, "team_id" => TEAM_ID, "ids" => $ids ]);

        ms([
            "status" => "success",
            "message" => __("Success")
        ]);

    }
 
    public function sidebar(){
        $team_id = get_team("id");
        $domains = db_fetch("*", TB_STACKPUSH_DOMAINS, [ "team_id" => $team_id ]);
        $modules = $this->model->get_modules();
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "modules" => $modules,
            "domains" => $domains
        ];
        return view('Core\Push\Views\sidebar', $data);
    }

    public function cron(){
        //Update Log - Send Failed When Time Wait Is Expired
        db_update( TB_STACKPUSH_LOGS, [ "status" => 0, "result" => __("Send failed") ], [ "expiry != " => 0, "expiry <= " => time(), "status" => 1 ]);
        db_update( TB_STACKPUSH_LOGS, [ "status" => 0, "result" => __("Send failed") ], [ "try_times >=" => (int)get_option("push_try_times", 1), "status" => 1 ]);

        $posts = $this->model->get_posts();

        if(!$posts){ 
            _ec("Empty schedule");
            exit(0);
        }

        foreach ($posts as $key => $post) {
            $actions = json_decode($post->actions);
            if ($post->type == 3) {
                $subscriptions = $this->model->get_subscriptions_ab($post->id, $post->pid);
                if(is_numeric($subscriptions) && $subscriptions == -1){
                    db_update(TB_STACKPUSH_SCHEDULES, ["status" => 2], ["pid" => $post->pid, "type" => 3]);
                }
            }else{
                $subscriptions = $this->model->get_subscriptions($post->id);
                if(empty($subscriptions)){
                    db_update(TB_STACKPUSH_SCHEDULES, ["status" => 2], ["id" => $post->id]);
                }
            }

            if(!empty($subscriptions) && is_array($subscriptions)){
                $pushs = [];
                $subscription_arr = [];
                $subscription_arr_id = [];
                foreach ($subscriptions as $key => $subscription) {
                    $token = json_decode( $subscription->token, true);
                    $subscription_arr_id[$token['endpoint']] = $subscription->id;

                    $payload = [
                        "title" => $post->title,                
                        "message" => $post->message,                
                        "icon" => get_file_url($post->icon),                
                        "url" => $post->url,                
                        "tid" => $post->team_id,                
                        "image" => get_file_url($post->large_image),                
                        "badge_icon" => $post->icon,
                        "requireInteraction" => (int)$post->auto_hide,
                        "sid" => $subscription->ids,
                        "pid" => $post->ids,
                        "tag" => rand(1000000,9999999),
                    ];

                    if(!empty($actions)){
                        if ( count($actions) == 1 ) {
                            $payload['action1'] = $actions[0];
                        } else if( count($actions) == 2 ){
                            $payload['action1'] = $actions[0];
                            $payload['action2'] = $actions[1];
                        }
                    }

                    $pushs[] = [
                        "subscription" => $token,
                        "payload" => $payload
                    ]; 
                }

                $data = ['subscriptions' => $subscription_arr_id, "post_id" => $post->id, "type" => $post->type];

                $result = push_send_multi($pushs, $data);
            }
        }

        echo __("Success");

    }
}