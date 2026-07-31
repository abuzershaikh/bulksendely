<?php
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

function webpush_init(){
    $webPush = new WebPush(['VAPID' => [
            'subject' => get_option("push_subject", ""),
            'publicKey' => get_option("push_public_key", ""),
            'privateKey' => get_option("push_private_key", ""),
        ]
    ]);

    return $webPush;
}

function push_send_one($subscription, $payload, $send_direct = true){
    include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
    $webPush = webpush_init();

    if (is_string($subscription)) {
        $subscription = json_decode($subscription, true);   
    } else if(is_object($subscription)) {
        $subscription = json_encode($subscription);   
        $subscription = json_decode($subscription, true);   
    }

    $notifications = [
        [
            'subscription' => Subscription::create($subscription),
            'payload' => null
        ],
        'payload' => null
    ];

    if($send_direct){
        if (!empty($payload)) {
            $payload_json = json_encode($payload);
        }else{
            $payload_json = null;
        }
    }else{
        $payload_json = null;
    }

    $report = $webPush->sendOneNotification(
        $notifications[0]['subscription'],
        $payload_json
    );

    if( isset($payload["sid"]) && isset($payload["pid"]) && isset($payload["type"]) ){
        if($report->isSuccess()){
            push_stats($payload["type"], $payload["sid"], $payload["pid"], 1, "Processing");
        }else{
            push_stats($payload["type"], $payload["sid"], $payload["pid"], 0, $report->getReason());
        }
    }
    
    return $report;
}

function push_send_multi($pushs, $data, $send_direct = true){
    include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
    $notifications = [];

    foreach ($pushs as $key => $push) {
        $notifications[] = [
            'subscription' => Subscription::create($push["subscription"]),
            'payload' => null
        ];
    }

    if($send_direct){
        if (!empty($payload)) {
            $payload_json = json_encode($payload);
        }else{
            $payload_json = null;
        }
    }else{
        $payload_json = null;
    }

    $webPush = webpush_init();
    foreach ($notifications as $key => $notification) {
        $webPush->queueNotification(
            $notification['subscription'],
            $payload_json
        );
    }

    $result = [];
    foreach ($webPush->flush() as $report) {
        $endpoint = $report->getRequest()->getUri()->__toString();
        if ($report->isSuccess()) {
            $result[] = [
                "endpoint" => $endpoint,
                "status" => 1,
                "message" => ""
            ];
            if(!empty($data))
                push_stats($data["type"], $data["subscriptions"][$endpoint], $data["post_id"], 1, "Processing");
        } else {
            $result[] = [
                "endpoint" => $endpoint,
                "status" => 0,
                "message" =>  $report->getReason()
            ];
            if(!empty($data)){
                push_stats($data["type"], $data["subscriptions"][$endpoint], $data["post_id"], 0, $report->getReason());
            }
        }
    }

    return $result;
}

function push_stats( $type, $subscriber_id, $post_id, $status, $message = ""){

    $db = \Config\Database::connect();
    $builder = $db->table(TB_STACKPUSH_SCHEDULES);
    $builder->select("*");
    if(is_numeric($post_id)){
        $builder->where("id", $post_id);
    }else{
        $builder->where("ids", $post_id);
    }
    $query = $builder->get();
    $post = $query->getRow();
    $query->freeResult();

    $builder = $db->table(TB_STACKPUSH_SUBSCRIBER);
    $builder->select("*");
    if(is_numeric($subscriber_id)){
        $builder->where("id", $subscriber_id);
    }else{
        $builder->where("ids", $subscriber_id);
    }
    $query = $builder->get();
    $subscriber = $query->getRow();
    $query->freeResult();

    if( !empty($post) &&  !empty($subscriber)){
        $log = db_get("id", TB_STACKPUSH_LOGS, ['subscriber_id' => $subscriber->id, "post_id" => $post->id, "type" => $type]);
        if(empty($log)){
            if((int)$post->expiry_status){

                switch ($post->expiry_by) {
                    case 'hours':
                        $time_expiry = $post->expiry * 60 * 60;
                        break;

                    case 'days':
                        $time_expiry = $post->expiry * 60 * 60 * 24;
                        break;
                    
                    default:
                        $time_expiry = $post->expiry * 60;
                        break;
                }

                $time_expiry = time() + $time_expiry;
            }else{
                $time_expiry = time() + (int)get_option("push_notification_expiry", 24) * 60 * 60;
            }

            db_insert(TB_STACKPUSH_LOGS, [
                "type" => $type,
                "team_id" => $subscriber->team_id,
                "subscriber_id" => $subscriber->id,
                "post_id" => $post->id,
                "expiry" => $time_expiry,
                "status" => $status,
                "result" => $message,
                "created" => time()
            ]);

            $map_item = db_get("*", TB_STACKPUSH_MAP, ["post_id" => $post->id, "subscriber_id" => $subscriber->id]);
            if(!$map_item){
                db_insert(TB_STACKPUSH_MAP, [
                    "post_id" => $post->id,
                    "subscriber_id" => $subscriber->id,
                    "number_sent" => 1,
                    "created" => time()
                ]);
            }else{
                 db_update(TB_STACKPUSH_MAP, [
                    "post_id" => $post->id,
                    "subscriber_id" => $subscriber->id,
                    "number_sent" => (int)$map_item->number_sent+1,
                    "created" => time()
                ], [ "post_id" => $post->id, "subscriber_id" => $subscriber->id ]);
            }
            
            db_update(TB_STACKPUSH_SCHEDULES, [
                "time_post" => (int)$post->time_post + (int)get_option("push_time_post", "1")*60,
                "number_sent" => (int)$post->number_sent + 1,
            ], [ "id" => $post->id ]);

            db_update(TB_STACKPUSH_SUBSCRIBER, [
                "status" => $status,
                "unsubscribe_date" => $status?0:time(), 
                "number_sent" => (int)$subscriber->number_sent + 1,
            ], [ "id" => $subscriber->id ]);
        }

    }
}

function push_delivered_stats( $subscriber_id, $post_id, $status){
    $db = \Config\Database::connect();
    $builder = $db->table(TB_STACKPUSH_SCHEDULES);
    $builder->select("*");
    if(is_numeric($post_id)){
        $builder->where("id", $post_id);
    }else{
        $builder->where("ids", $post_id);
    }
    $query = $builder->get();
    $post = $query->getRow();
    $query->freeResult();

    if( !empty($post) ){
        db_update(TB_STACKPUSH_SCHEDULES, [
            "number_delivered" => $status?(int)$post->number_delivered + 1:(int)$post->number_delivered,
        ], [ "id" => $post->id ]);
    }

    $db = \Config\Database::connect();
    $builder = $db->table(TB_STACKPUSH_SUBSCRIBER);
    $builder->select("*");
    if(is_numeric($subscriber_id)){
        $builder->where("id", $subscriber_id);
    }else{
        $builder->where("ids", $subscriber_id);
    }
    $query = $builder->get();
    $subscriber = $query->getRow();
    $query->freeResult();

    if( !empty($subscriber) ){
        db_update(TB_STACKPUSH_SUBSCRIBER, [
            "number_delivered" => $status?(int)$subscriber->number_delivered + 1:(int)$subscriber->number_delivered,
        ], [ "id" => $subscriber->id ]);
    }

    if( !empty($post) &&  !empty($subscriber)){
        $map_item = db_get("*", TB_STACKPUSH_MAP, ["post_id" => $post->id, "subscriber_id" => $subscriber->id]);
        if($map_item){
            db_update(TB_STACKPUSH_MAP, [
                "number_delivered" => (int)$map_item->number_delivered + 1,
            ], [ "post_id" => $post->id, "subscriber_id" => $subscriber->id ]);
        }
    }
}

function push_click_stats( $type, $subscriber_id, $post_id){
    $post = db_get("*", TB_STACKPUSH_SCHEDULES, ["ids" => $post_id ]);
    if( !empty($post) ){
        db_update(TB_STACKPUSH_SCHEDULES, [
            "number_action" => (int)$post->number_action + 1,
            "number_box" => $type==0?(int)$post->number_box + 1:(int)$post->number_box,
            "number_btn_left" => $type==1?(int)$post->number_btn_left + 1:(int)$post->number_btn_left,
            "number_btn_right" => $type==2?(int)$post->number_btn_right + 1:(int)$post->number_btn_right,
        ], [ "id" => $post->id ]);
    }

    $subscriber = db_get("*", TB_STACKPUSH_SUBSCRIBER, ["ids" => $subscriber_id]);
    if( !empty($subscriber) ){
        db_update(TB_STACKPUSH_SUBSCRIBER, [
            "number_action" => (int)$subscriber->number_action + 1,
            "number_box" => $type==0?(int)$subscriber->number_box + 1:(int)$subscriber->number_box,
            "number_btn_left" => $type==1?(int)$subscriber->number_btn_left + 1:(int)$subscriber->number_btn_left,
            "number_btn_right" => $type==2?(int)$subscriber->number_btn_right + 1:(int)$subscriber->number_btn_right,
        ], [ "id" => $subscriber->id ]);
    }

    if( !empty($post) &&  !empty($subscriber)){
        $map_item = db_get("*", TB_STACKPUSH_MAP, ["post_id" => $post->id, "subscriber_id" => $subscriber->id]);
        if($map_item){
            db_update(TB_STACKPUSH_MAP, [
                "number_action" => (int)$map_item->number_action + 1,
                "number_box" => $type==0?(int)$map_item->number_box + 1:(int)$map_item->number_box,
                "number_btn_left" => $type==1?(int)$map_item->number_btn_left + 1:(int)$map_item->number_btn_left,
                "number_btn_right" => $type==2?(int)$map_item->number_btn_right + 1:(int)$map_item->number_btn_right,
            ], [ "post_id" => $post->id, "subscriber_id" => $subscriber->id ]);
        }
    }
}

function push_addToSegment( $segment_id, $sub_id, $domain_id, $team_id){
    $db = \Config\Database::connect();
    $builder = $db->table(TB_TEAM);
    $builder->select("id");
    if(is_numeric($team_id)){
        $builder->where("id", $team_id);
    }else{
        $builder->where("ids", $team_id);
    }
    $query = $builder->get();
    $team = $query->getRow();
    $query->freeResult();

    if(empty($team)) return false;
    $builder = $db->table(TB_STACKPUSH_DOMAINS);
    $builder->select("id");
    $builder->groupStart();
    if(is_numeric($domain_id)){
        $builder->where("id", $domain_id);
    }else{
        $builder->where("ids", $domain_id);
    }
    $builder->groupEnd();
    $builder->where("team_id", $team->id);
    $query = $builder->get();
    $domain = $query->getRow();
    $query->freeResult();

    if(empty($domain)) return false;

    $builder = $db->table(TB_STACKPUSH_SUBSCRIBER);
    $builder->select("id");
    $builder->groupStart();

    if(is_numeric($sub_id)){
        $builder->where("sub_id", $sub_id);
        $builder->orWhere("id", $sub_id);
    }else{
        $builder->where("ids", $sub_id);
    }
    
    $builder->groupEnd();
    $builder->where("team_id", $team->id);
    $builder->where("domain_id", $domain->id);
    $query = $builder->get();
    $subscriber = $query->getRow();
    $query->freeResult();

    if(empty($subscriber)) return false;

    $builder = $db->table(TB_STACKPUSH_SEGMENTATION);
    $builder->select("id");
    $builder->where("id", (int)$segment_id);
    $builder->where("team_id", $team->id);
    $builder->where("domain_id", $domain->id);
    $query = $builder->get();
    $segment = $query->getRow();
    $query->freeResult();

    if(empty($segment)) return false;


    $check_segment = db_get("*", TB_STACKPUSH_SEGMENTATION_MAP, ["segment_id" => $segment->id, "subscriber_id" => $subscriber->id]);

    if(empty($check_segment)){
        $data = [
            "segment_id" => $segment->id,
            "subscriber_id" => $subscriber->id,
            "created" => time()
        ];

        db_insert(TB_STACKPUSH_SEGMENTATION_MAP, $data);
    }


    return true;
}

if(!function_exists("get_push_domain")){
    function get_push_domain( $field = "ids", $tid = 0){
        $domain_id = (int)get_session("push_domain");

        if($domain_id == 0){
            return false;
        }

        if($tid == 0){
            $tid =  get_team('id');
        }
        
        $domain = db_get("*", TB_STACKPUSH_DOMAINS, ['id' => $domain_id, 'team_id' => $tid]);

        if($domain && isset($domain->$field)){
            return $domain->$field;
        }

        return false;
    }
}

if( ! function_exists("get_push_option") ){
    function get_push_option($key, $value = "", $domain = false){
        if($domain){
            $domain_id = $domain;
        }else{
            $domain_id = (int)get_session("push_domain");
        }
        
        $option = db_get("value", "sp_stackpush_options", ["name" => $key, "domain_id" => $domain_id]);
        if(empty($option)){
            db_insert("sp_stackpush_options", [ "name" => $key, "value" => $value, "domain_id" => $domain_id ] );
            return $value;
        }else{
            return $option->value;
        }
    }
}

if( ! function_exists("update_push_option") ){
    function update_push_option($key, $value, $domain = false){
        if($domain){
            $domain_id = $domain;
        }else{
            $domain_id = (int)get_session("push_domain");
        }
        
        $option = db_get("value", "sp_stackpush_options", ["name" => $key, "domain_id" => $domain_id]);
        if(empty($option)){
            db_insert( "sp_stackpush_options", [ "name" => $key, "value" => $value, "domain_id" => $domain_id ] );
        }else{
            db_update( "sp_stackpush_options", [ "value" => $value ], [ "name" => $key, "domain_id" => $domain_id ] );
        }
    }
}

if( ! function_exists("push_build_url_utm") ){
    function push_build_url_utm($url, $utm_status, $utm_data){
        if($utm_status){
            $utm_arr = false;
            if(is_string($utm_data)){
                $utm_arr = json_decode($utm_data, true);
            }

            if(is_object($utm_data)){
                $utm_arr = (object)$utm_data;
            }

            if(!empty($utm_arr) && isset($utm_arr["source"]) && isset($utm_arr["medium"]) && isset($utm_arr["name"])){
                $params = [
                    "utm_source" => $utm_arr["source"],
                    "utm_medium" => $utm_arr["medium"],
                    "utm_campaign" => $utm_arr["name"],
                ];

                $query = parse_url($url, PHP_URL_QUERY);

                if ($query) {
                    $url .= '&'.http_build_query($params);
                } else {
                    $url .= '?'.http_build_query($params);;
                }

                return $url;
            }

            return $url;
        }

        return $url;
        
    }
}

function check_push_subscibers( $team_id = "" ){
    $total_subscribers = (int)permission("push_total_subscribers", $team_id);
    $count_subscribers = db_get("count(*) as count", TB_STACKPUSH_SUBSCRIBER , [ "team_id" => $team_id, "status" => 1 ] )->count;

    if($count_subscribers >= $total_subscribers){
        $left_subs = 0;
        $percent = 100;
        $left_percent = 0; 
        $total_sub = $count_subscribers; 
    }else{
        $left_subs = $total_subscribers - $count_subscribers;
        $percent = round($count_subscribers/$total_subscribers * 100, 2); 
        $left_percent = 100 - $percent; 
        $total_sub = $count_subscribers; 
    }

    return [
        "total" => $total_sub,
        "left" => $left_subs,
        "percent" => $percent,
        "left_percent" => $left_percent,
    ];
}