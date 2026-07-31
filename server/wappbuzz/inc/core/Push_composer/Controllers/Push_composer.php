<?php
namespace Core\Push_composer\Controllers;

class Push_composer extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_composer\Models\Push_composerModel();
    }
    
    public function index( $ids = false ) {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        $campaigns = db_fetch("*", TB_STACKPUSH_CAMPAIGNS, ["team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID, "status"=> 1], "id", "ASC");
        $segments = db_fetch("*", TB_STACKPUSH_SEGMENTATION, ["team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID], "id", "ASC");
        $audiences = db_fetch("*", TB_STACKPUSH_AUDIENCE_CREATOR, ["team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID], "id", "ASC");
        $website = db_get("*", TB_STACKPUSH_DOMAINS, ["team_id" => TEAM_ID, "id" => PUSH_DOMAIN_ID]);
        $post = db_get("*", TB_STACKPUSH_SCHEDULES, ["team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID, "ids" => $ids, "type" => 1]);

        $data_content = [
            "config" => $this->config,
            "campaigns" => $campaigns,
            "segments" => $segments,
            "audiences" => $audiences,
            "website" => $website,
            "post" => $post,
        ];

        $data['content'] = view('Core\Push_composer\Views\content', $data_content );

        return view('Core\Push\Views\index', $data);
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
        $post_by = (int)post("post_by");
        $time_post = post("time_post");
        $time_posts = post("time_posts");
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

        $audience_status = (int)post("audience_status");
        $audience_id = (int)post("audience_id");
        $segment_id = (int)post("segment_id");
        $country = post("country");
        $device = post("device");
        $os = post("os");
        $browser = post("browser");
        $time_now = time();

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

        $data = [
            "type" => 1,
            "domain_id" => PUSH_DOMAIN_ID,
            "team_id" => TEAM_ID,
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
            "segment_id" => $segment_id,
            "country" => $country,
            "device" => $device,
            "os" => $os,
            "browser" => $browser,
            "start_time" => (int)timestamp_sql( $time_post ),
            "status" => 1,
            "changed" => time()
        ];

        $post = db_get("id", TB_STACKPUSH_SCHEDULES, ["team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID, "id" => $id, "type" => 1]);

        switch ($post_by) {
            case 2:
                validate('empty', __('Please select at least a time post'), $time_posts);
                $time_posts = array_unique($time_posts);

                foreach ($time_posts as $time) {
                    $data["ids"] = ids();
                    $data["created"] = time();
                    $data["time_post"] = (int)timestamp_sql( $time );
                    $data["start_time"] = (int)timestamp_sql( $time );
                    db_insert(TB_STACKPUSH_SCHEDULES, $data);
                }
                break;

            case 3:
                $data['status'] = 0;
                $data['time_post'] = NULL;
                $data["start_time"] = NULL;
                
                if(!$post){
                    $data["ids"] = ids();
                    $data["created"] = time();
                    db_insert(TB_STACKPUSH_SCHEDULES, $data);
                }else{
                    db_update(TB_STACKPUSH_SCHEDULES, $data, ["id" => $id]);
                }
                break;
            
            default:
                validate('null', __('Time post'), $time_post);
                $time_post = (int)timestamp_sql( $time_post );

                if($time_post <= $time_now){
                    ms([
                        "status" => "error",
                        "message" => __("Time post must be greater than current time")
                    ]);
                }

                $data['time_post'] = $time_post;

                if(!$post){
                    $data["ids"] = ids();
                    $data["created"] = time();
                    db_insert(TB_STACKPUSH_SCHEDULES, $data);
                }else{
                    db_update(TB_STACKPUSH_SCHEDULES, $data, ["id" => $id]);
                }
                
                break;
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }
}