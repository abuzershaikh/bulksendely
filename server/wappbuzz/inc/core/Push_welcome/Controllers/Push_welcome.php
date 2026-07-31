<?php
namespace Core\Push_welcome\Controllers;

class Push_welcome extends \CodeIgniter\Controller
{
    public function __construct(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: *");
        include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_welcome\Models\Push_welcomeModel();
    }
    
    public function index( $page = false ) {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            'config' => $this->config
        ];

        $result = db_fetch("*", TB_STACKPUSH_SCHEDULES, ["team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID, "type" => 2], "id", "ASC", 0, 1);

        $data_content = [
            'config' => $this->config,
            'result' => $result
        ];
        $data['content'] = view('Core\Push_welcome\Views\content', $data_content);

        return view('Core\Push\Views\index', $data);
    }

    public function popup( $ids = false ){
        $website = db_get("*", TB_STACKPUSH_DOMAINS, ["team_id" => TEAM_ID, "id" => PUSH_DOMAIN_ID]);
        $post = db_get("*", TB_STACKPUSH_SCHEDULES, ["team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID, "ids" => $ids, "type" => 2]);

        $data_content = [
            "config" => $this->config, 
            "website" => $website,
            "post" => $post,
        ];

        return view('Core\Push_welcome\Views\popup', $data_content); 
    }

    public function save(){
        $id = (int)post("id");
        $title = post("title");
        $message = post("message");
        $url = post("url");
        $campaign_id = (int)post("campaign");
        $utm_status = (int)post("utm_status");
        $utm_source = post("utm_source");
        $utm_medium = post("utm_medium");
        $utm_name = post("utm_name");
        $medias = post("medias");
        $icon = "";
        $large_image = post("large_image");
        $large_image_status = (int)post("large_image_status");
        $actions = post("actions");
        $expiry = (int)post("expiry");
        $expiry_by = post("expiry_by");
        $expiry_status = (int)post("expiry_status");
        $auto_hide = (int)post("auto_hide");
        $action_button = (int)post("action_button");
        $action_button_count = (int)post("action_button_count");

        $action_button1_name = post("action_button1_name");
        $action_button2_name = post("action_button2_name");

        $action_button1_url = post("action_button1_url");
        $action_button2_url = post("action_button2_url");

        $actions = [];

        $audience_status = 0;
        $audience_id = 0;
        $segment = 0;
        $country = 0;
        $device = 0;
        $os = 0;
        $browser = 0;

        validate('null', __('Title'), $title);
        validate('null', __('Message'), $message);
        validate('null', __('Url'), $url);

        if( !filter_var($url, FILTER_VALIDATE_URL) ){
            ms([
                "status" => "error",
                "message" => __('The url is not valid')
            ]);
        }
        
        if($utm_status){
            validate('null', __('UTM Source'), $utm_source);
            validate('null', __('UTM Medium'), $utm_medium);
            validate('null', __('UTM Name'), $utm_name);
        }

        $utm = json_encode([
            "source" => $utm_source,
            "medium" => $utm_medium,
            "name" => $utm_name,
        ]);

        if($expiry_status){
            $expiry = (int)post("expiry");
        }

        if($expiry_by != "minutes" && $expiry_by != "hours" && $expiry_by != "days"){
            $expiry_by = "days";
        }

        if(!empty($medias)){
            $icon = $medias[0];
        }else{
            $domain = unserialize(PUSH_DOMAIN);
            $icon = $domain->icon;
        }

        if($large_image_status){
            validate('null', __('Large image'), $large_image);
        }

        if($action_button){
            validate('null', __('Name button 1'), $action_button1_name);
            validate('null', __('URL button 1'), $action_button1_url);

            if( !filter_var($action_button1_url, FILTER_VALIDATE_URL) ){
                ms([
                    "status" => "error",
                    "message" => __('Button 1: The url is not valid')
                ]);
            }

            $actions[] = [
                "title" => $action_button1_name,
                "url" => $action_button1_url,
            ];

            if($action_button_count == 1){
                validate('null', __('Name button 2'), $action_button2_name);
                validate('null', __('URL button 2'), $action_button2_url);

                if( !filter_var($action_button2_url, FILTER_VALIDATE_URL) ){
                    ms([
                        "status" => "error",
                        "message" => __('Button 2: The url is not valid')
                    ]);
                }

                $actions[] = [
                    "title" => $action_button2_name,
                    "url" => $action_button2_url,
                ];
            }
        }

        $post = db_get("id", TB_STACKPUSH_SCHEDULES, ["team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID, "id" => $id, "type" => 2]);

        $data = [
            "campaign_id" => $campaign_id,
            "title" => $title,
            "message" => $message,
            "url" => $url,
            "icon" => $icon,
            "large_image" => $large_image,
            "large_image_status" => $large_image_status,
            "utm_status" => $utm_status,
            "utm" => $utm,
            "auto_hide" => $auto_hide,
            "actions" => json_encode($actions),
            "expiry" => $expiry,
            "expiry_by" => $expiry_by,
            "expiry_status" => $expiry_status,
            "audience_status" => $audience_status,
            "audience_id" => $audience_id,
            "segment_id" => 0,
            "country" => $country,
            "device" => $device,
            "os" => $os,
            "browser" => $browser,
            "time_post" => time(),
            "status" => 1,
            "changed" => time()
        ];

        if(!$post){
            $welcome_check = db_get("id", TB_STACKPUSH_SCHEDULES, ["team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID, "type" => 2]);

            if(!$welcome_check){
                $data["delay"] = json_encode([
                    "days" => 0,
                    "hours" => 0,
                    "mins" => 0
                ]);

                $data["ids"] = ids();
                $data["type"] = 2;
                $data["domain_id"] = PUSH_DOMAIN_ID;
                $data["team_id"] = TEAM_ID;
                $data["created"] = time();
                
                db_insert(TB_STACKPUSH_SCHEDULES, $data);
            }

        }else{
            db_update(TB_STACKPUSH_SCHEDULES, $data, ["id" => $id]);
        }
        
        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    public function save_time($ids = ""){
        $mins = (int)post("mins");
        $hours = (int)post("hours");
        $days = (int)post("days");

        $data = [
            "delay" => json_encode([
                "days" => $days,
                "hours" => $hours,
                "mins" => $mins
            ])
        ];

        db_update(TB_STACKPUSH_SCHEDULES, $data, [ "ids" => $ids, "team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID ]);

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    public function cron(){
        $posts = $this->model->get_posts();

        if(!$posts){ 
            _ec("Empty schedule");
            exit(0);
        }

        foreach ($posts as $key => $post) {
            $payload = [
                "title" => $post->title,                
                "message" => $post->message,                
                "icon" => get_file_url($post->icon),                
                "url" => $post->url,                
                "tid" => $post->team_id,                
                "image" => get_file_url($post->large_image),                
                "badge_icon" => $post->icon,
                "requireInteraction" => (int)$post->auto_hide,
                "sid" => $post->sub_ids,
                "pid" => $post->ids,
                "type" => $post->type,
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

            $token = json_decode($post->token);

            $status = push_send_one($token, $payload, false);

            $next_welcome_item = db_get( "*", TB_STACKPUSH_SCHEDULES, [ "team_id" => $post->team_id, "domain_id" => $post->domain_id, "type" => 2, "id > " => $post->id  ], "id", "ASC" );

            if($next_welcome_item){
                $next_welcome_drip_time = 0;

                if ($next_welcome_item && $next_welcome_item->delay != "") {
                    $delay = json_decode($next_welcome_item->delay);
                    $days = $delay->days;
                    $hours = $delay->hours;
                    $mins = $delay->mins;
                    $next_welcome_drip_time = time() + ($hours*60*60) + ($days*24*60*60) + ($mins*60);
                }
                db_update( TB_STACKPUSH_SUBSCRIBER, [ "welcome_drip_status" => 1, "next_welcome_drip_id" => $next_welcome_item->id, "next_welcome_drip_time" => $next_welcome_drip_time ], [ "ids" => $post->sub_ids ] );
            }else{
                db_update( TB_STACKPUSH_SUBSCRIBER, [ "welcome_drip_status" => 2, "next_welcome_drip_id" => 0, "next_welcome_drip_time" => 0 ], [ "ids" => $post->sub_ids ] );
            }
        }

        echo __("Success");
    }
}