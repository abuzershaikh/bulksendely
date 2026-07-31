<?php
namespace Core\Push_request\Controllers;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class Push_request extends \CodeIgniter\Controller
{
    public function __construct(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: *");
        include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_request\Models\Push_requestModel();
    }
    
    public function start( $team_id = "",  $domain_id = "") {
        if($team_id == "" || $domain_id == ""){
            ms([
                "status" => false,
                "message" => __("User key and domain key is required")
            ]); 
        }

        $team = db_get("*", TB_TEAM, [ "ids" => $team_id ]);

        if(empty($team)){
            ms([
                "status" => false,
                "message" => __("User key invalid")
            ]);
        }

        $domain_item = db_get("*", TB_STACKPUSH_DOMAINS, ["ids" => $domain_id]);

        if(empty($domain_item)){
            ms([
                "status" => false,
                "message" => __("Domain key does not exist")
            ]);
        }

        $push_subscibers = check_push_subscibers($team->id);     

        if($push_subscibers["percent"] == 100){
            ms([
                "status" => false,
                "message" => __("You have reached the maximum number of subscribers allowed in your current plan. To continue adding new subscribers, please upgrade your plan.")
            ]);
        }   

        ms([
            "serverKey" => get_option("push_public_key", ""),
            "optin" =>  view_cell('\Core\Push_opt_in_box\Controllers\Push_opt_in_box::popup', [ "domain_id" => $domain_item->id, "team_id" => $domain_item->team_id ]),
            "optin_ios" =>  view_cell('\Core\Push_website_settings\Controllers\Push_website_settings::ios_popup', [ "domain_id" => $domain_item->id, "team_id" => $domain_item->team_id ]),
            "optin_opts" =>  [
                "opt_theme" => get_push_option("opt_theme", get_option("push_opt_theme", "box"), $domain_item->id),
                "opt_trigger" => get_push_option("opt_trigger", get_option("push_opt_trigger", "on_landing"), $domain_item->id),
                "opt_on_scroll" => get_push_option("opt_on_scroll", get_option("push_opt_on_scroll", "20"), $domain_item->id),
                "opt_on_inactivity" => get_push_option("opt_on_inactivity", get_option("push_opt_on_inactivity", "7"), $domain_item->id),
                "opt_on_pageviews" => get_push_option("opt_on_pageviews", get_option("push_opt_on_pageviews", "3"), $domain_item->id),
                "opt_opacity" => get_push_option("opt_opacity", get_option("push_opt_opacity", "0"), $domain_item->id),
                "opt_bg" => get_push_option("opt_bg", get_option("push_opt_bg", "#fff"), $domain_item->id),
                "opt_delay" => get_push_option("opt_delay", get_option("push_opt_delay", "3"), $domain_item->id),
                "opt_position" => get_push_option("opt_position", get_option("push_opt_position", "top"), $domain_item->id),
                "opt_allow_btn_bg" => get_push_option("opt_allow_btn_bg", get_option("push_opt_allow_btn_bg", "#00b0ff"), $domain_item->id),
                "opt_allow_btn_text" => get_push_option("opt_allow_btn_text", get_option("push_opt_allow_btn_text", "#fff"), $domain_item->id),
                "opt_deny_btn_bg" => get_push_option("opt_deny_btn_bg", get_option("push_opt_deny_btn_bg", "#f3f3f3"), $domain_item->id),
                "opt_deny_btn_text" => get_push_option("opt_deny_btn_text", get_option("push_opt_deny_btn_text", "#717171"), $domain_item->id),
                "opt_title" => get_push_option("opt_title", get_option("push_opt_title", "Get our Latest News and Updates"), $domain_item->id),
                "opt_desc" => get_push_option("opt_desc", get_option("push_opt_desc", "Click on Allow to get notifications"), $domain_item->id),
                "opt_banner" => get_push_option("opt_banner", get_option("push_opt_banner", ""), $domain_item->id),
                "widget_status" => get_push_option("widget_status", get_option("push_widget_status", 1), $domain_id),
                "widget_bottom" => get_push_option("widget_bottom", get_option("push_widget_bottom", 15), $domain_id),
                "widget_right" => get_push_option("widget_right", get_option("push_widget_right", 15), $domain_id),
                "widget_left" => get_push_option("widget_left", get_option("push_widget_left", 15), $domain_id),
                "widget_bg" => get_push_option("widget_bg", get_option("push_widget_bg", "#0055ff"), $domain_id),
                "widget_icon" =>  get_push_option("widget_icon", get_option("push_widget_icon", base_url("inc/core/Push_widget/Assets/img/bell_icon.png") ), $domain_id),
                "widget_position" => get_push_option("widget_position", get_option("push_widget_position", "right"), $domain_id)
            ],
            "cookie_prefix" => $domain_item->ids
        ]);
    }

    public function delivered(){
        $clicked_on = (int)post("clicked_on");
        $type = post("type");
        $os = post("os");
        $browser = post("browser");
        $domain_id = post("domain_id");
        $post_id = post("pid");
        $subscriber_id = post("sid");

        push_delivered_stats( $subscriber_id, $post_id, 1);  

        ms([
            "status" => true,
            "message" => __("Success")
        ]);
    }

    public function getNotification(){
        $domain_id = post("domain_id");
        $team_id = post("client_id");
        $auth = post("token");
        $key = post("key");
        $endpoint = post("endpoint");
        $endpoint_full = post("endpoint_full");

        $team = db_get("id", TB_TEAM, ["ids" => $team_id]);
        
        if(!$team){ 
            ms([
                "status" => false,
                "message" => __("Error 1")
            ]); 
        }

        $domain = db_get("id", TB_STACKPUSH_DOMAINS, ["team_id" => $team->id, "ids" => $domain_id]);

        if(!$domain){ 
            ms([
                "status" => false,
                "message" => __("Error 2")
            ]); 
        }


        $subscriber = db_get("id,ids", TB_STACKPUSH_SUBSCRIBER, [ "sub_id" => $auth, "domain_id" => $domain->id, "team_id" => $team->id]);

        if(empty($subscriber)){
            ms([
                "status" => false,
                "message" => __("Error 3")
            ]); 
        }

        //GET POST 
        $log = db_get("*", TB_STACKPUSH_LOGS, [ "team_id" => $team->id, "subscriber_id" => $subscriber->id, "status" => 1], "id", "DESC");

        if(empty($log)){
            ms([
                "status" => false,
                "message" => __("Error 4")
            ]); 
        }

        db_update(TB_STACKPUSH_LOGS, ["status" => 2, "result" => "Ok"], ["id" => $log->id]);
        //db_update(TB_STACKPUSH_LOGS, ["status" => 3, "result" => "Unknown Error"], ["status" => 1]);
        $post = db_get("*", TB_STACKPUSH_SCHEDULES, ["id" => $log->post_id]);

        if($post->icon){
            $icon = get_file_url($post->icon);
        }else{
            $icon = get_file_url($domain->icon);
        }

        if($post){

            $payload = [
                "title" => $post->title,                
                "message" => $post->message,                
                "icon" => $icon,                
                "url" => push_build_url_utm($post->url, $post->utm_status, $post->utm),                
                "tid" => $post->team_id,                
                "image" => $post->large_image_status?get_file_url($post->large_image):"",                
                "badge_icon" => $post->icon,
                "requireInteraction" => (int)$post->auto_hide?0:1,
                "sid" => $subscriber->ids,
                "pid" => $post->ids,
                "tag" => rand(1000000,9999999),
            ];

            if($post->actions != ""){
                $actions = json_decode($post->actions);

                if(!empty($actions)){
                    if ( count($actions) == 1 ) {
                        $payload['action1'] = $actions[0];
                    } else if( count($actions) == 2 ){
                        $payload['action1'] = $actions[0];
                        $payload['action2'] = $actions[1];
                    }
                }
            }

            push_delivered_stats($subscriber->id ,$post->id, 1);

            ms([
                "notification" => $payload,
                "status" => 200
            ]);
        }
    }

    public function error(){}

    public function subscriber($team_ids = ""){
        $result =  file_get_contents('php://input');

        $geoip = geoip();

        if(!$result){
            ms([
                "status" => false,
                "message" => __("Error")
            ]); 
        }

        $result = json_decode($result);

        if($geoip['city'] != ""){
            $location = $geoip['city'].", ".$geoip['country'];
        }else{
            $location = $geoip['country'];
        }

        $country = $geoip['country_code'];
        $timezone = $geoip['timezone'];
        $type = get_data($result,"type");
        $os = get_data($result,"os");
        $osVer = get_data($result,"osVer");
        $browser = get_data($result,"browser");
        $browserVer = get_data($result,"browserVer");
        $browserMajor = get_data($result,"browserMajor");
        $language = get_data($result,"language");
        $resoln_width = get_data($result,"resoln_width");
        $resoln_height = get_data($result,"resoln_height");
        $color_depth = get_data($result,"color_depth");
        $engine = get_data($result,"engine");
        $userAgent = get_data($result,"userAgent");
        $referrer = get_data($result,"referrer");

        if(!empty($referrer)){
            $referrer = urldecode( $referrer );
        }

        $domain_name = get_domain( $referrer );
        $endpoint = get_data($result,"endpoint");
        $publicKey = get_data($result,"key");
        $authToken = get_data($result,"token");

        $token = [
            "endpoint" => $endpoint,
            'publicKey' => $publicKey,
            'authToken' => $authToken,
            "keys" => [
                'p256dh' => $publicKey,
                'auth' => $authToken,
            ]
        ];
        
        $team = db_get("id", TB_TEAM, "ids = '{$team_ids}'");
        
        if(!$team){ 
            ms([
                "status" => false,
                "message" => __("Error 1")
            ]); 
        }

        $domain = db_get("id", TB_STACKPUSH_DOMAINS, ["team_id" => $team->id, "domain" => $domain_name]);

        if(!$domain){ 
            ms([
                "status" => false,
                "message" => __("Error 2")
            ]); 
        }

        $sub = db_get("id,ids,total_visits", TB_STACKPUSH_SUBSCRIBER, ["token" => json_encode($token)]);

        if(!$sub){
            $sub_ids = ids();
            $welcome_item = db_get("*", TB_STACKPUSH_SCHEDULES, ["type" => 2, "domain_id" => $domain->id, "team_id" => $team->id, "status" => 1], "id", "ASC");

            $next_welcome_drip_id = 0;
            $next_welcome_drip_time = 0;
            $welcome_drip_joined = 0;
            $welcome_drip_status = 2;

            if ($welcome_item && $welcome_item->delay != "") {
                $next_welcome_drip_id = $welcome_item->id;
                $welcome_drip_status = 1;
                $delay = json_decode($welcome_item->delay);
                $days = $delay->days;
                $hours = $delay->hours;
                $mins = $delay->mins;
                $welcome_drip_joined = 1;
                $next_welcome_drip_time = time() + ($hours*60*60) + ($days*24*60*60) + ($mins*60);
            }

            $data = [
                "ids" => $sub_ids,
                "team_id" => $team->id,
                "domain_id" => $domain->id,
                "token" => json_encode($token),
                "sub_id" => $authToken,
                "timezone" => $timezone,
                "country" => $country,
                "location" => $location,
                "device" => $type,
                "os" => $os,
                "browser" => $browser,
                "language" => $language,
                "resolution" => $resoln_width."x".$resoln_height,
                "subscription_url" => urldecode($referrer),
                "total_visits" => 1,
                "first_visit" => time(),
                "last_visit" => time(),
                "last_visited_url" => urldecode($referrer),
                "welcome_drip_joined" => $welcome_drip_joined,
                "next_welcome_drip_id" => $next_welcome_drip_id,
                "next_welcome_drip_time" => $next_welcome_drip_time,
                "welcome_drip_status" => $welcome_drip_status,
                "updated" => time(),
                "created" => time()
            ];

            db_insert(TB_STACKPUSH_SUBSCRIBER, $data);
            
        }else{
            $sub_ids = $sub->ids;
            $data = [
                "sub_id" => $authToken,
                "timezone" => $timezone,
                "country" => $country,
                "location" => $location,
                "device" => $type,
                "os" => $os,
                "browser" => $browser,
                "language" => $language,
                "resolution" => $resoln_width."x".$resoln_height,
                "subscription_url" => $domain_name,
                "total_visits" => (int)$sub->total_visits + 1,
                "last_visit" => time(),
                "last_visited_url" => urldecode($referrer),
                "updated" => time()
            ];

            db_update(TB_STACKPUSH_SUBSCRIBER, $data, ["id" => $sub->id]);

            $logs = db_fetch("*", TB_STACKPUSH_LOGS, [ "subscriber_id" => $sub->id, "status" => 1 ], "id", "ASC", 0, 3);
            if(!empty($logs)){
                foreach ($logs as $key => $log) {
                    push_send_one($token, false, false);
                    db_update(TB_STACKPUSH_LOGS, [ "try_times" => (int)$log->try_times + 1 ], ["id" => $log->id ]);
                }
            }
        }
        
        ms([
            "status" => true,
            "message" => __("Success")
        ]);
    }

    public function track_clicked(){
        $clicked_on = (int)post("clicked_on");
        $type = post("type");
        $os = post("os");
        $browser = post("browser");
        $domain_id = post("domain_id");
        $post_id = post("pid");
        $subscriber_id = post("sid");

        push_click_stats($clicked_on, $subscriber_id, $post_id);  

        ms([
            "status" => true,
            "message" => __("Success")
        ]);
    }
   
    public function send_to_action($action = ""){
        $type = post("type");
        $os = post("os");
        $browser = post("browser");
        $domain_id = post("domain_id");
        $team_id = post("client_id");
        $sub_id = post("sub_id");
        
        $result = false;

        switch ($action) {
            case 'addToSegment':
                $segment_id = post("segment_id");
                $result = push_addToSegment($segment_id, $sub_id, $domain_id, $team_id);
                break;
            
            default:
                
                break;
        }

        if($result){
            ms([
                "status" => true,
                "message" => __("Success")
            ]);
        }else{
            ms([
                "status" => false,
                "message" => __("Error")
            ]);
        }
    }
}