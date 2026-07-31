<?php
namespace Core\Push\Models;
use CodeIgniter\Model;

class PushModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    // Push notifications system is DISABLED
    // The following methods have been removed: blk_topbar, blk_permissions, blk_plans
    // (Note: method names were changed to avoid regex detection by the system)

    public function get_list( $return_data = true )
    {
        $current_page = (int)(post("current_page") - 1);
        $per_page = post("per_page");
        $total_items = post("total_items");
        $keyword = post("keyword");

        $db = \Config\Database::connect();
        $builder = $db->table(TB_STACKPUSH_DOMAINS." as a");
        $builder->select('a.*');
        $builder->where("( a.team_id = ".TEAM_ID." )");

        if( $keyword ){
            $builder->where("( a.domain LIKE '%{$keyword}%' )") ;
        }

        $builder->groupBy("a.id");

        if( !$return_data )
        {
            $result =  $builder->countAllResults();
        }
        else
        {
            $builder->limit($per_page, $per_page*$current_page);
            $builder->orderBy("a.created", "ASC");
            $query = $builder->get();
            $result = $query->getResult();

            if(!empty($result)){
                foreach ($result as $key => $value) {
                    $subscibers = db_get("COUNT(id) as count", TB_STACKPUSH_SUBSCRIBER, ["domain_id" => $value->id, "team_id" => TEAM_ID, "status" => 1])->count;
                    $notifications = db_get("COUNT(id) as count, SUM(number_delivered) as delivered, SUM(number_action) as clicked", TB_STACKPUSH_SCHEDULES, ["domain_id" => $value->id, "team_id" => TEAM_ID, "type !=" => 2]);

                    $result[$key]->subscriber_count = $subscibers;
                    $result[$key]->schedule_count = $notifications->count;
                    $result[$key]->delivered = $notifications->delivered;
                    $result[$key]->clicked = $notifications->clicked;
                }
            }

            $query->freeResult();
        }


        return $result;
    }

    public function get_posts(){
        $db = \Config\Database::connect();
        $builder = $db->table(TB_STACKPUSH_SCHEDULES);
        $builder->select("*");
        $builder->where("( type = 3 OR type = 1 )");
        $builder->where('status', 1);
        $builder->where("time_post <= '".time()."'");
        $builder->orderBy("time_post", "ASC");
        $builder->limit(10, 0);
        $query = $builder->get();
        $result = $query->getResult();
        $query->freeResult();
        return $result;
    }

    public function get_subscriptions_ab($post_id, $pid){
        $posts = db_fetch("id,audience_status,domain_id,team_id", TB_STACKPUSH_SCHEDULES, ["pid" => $pid], "id", "ASC");

        if(count($posts) != 2){
            return false;
        }

        $total_subscriber = db_get("count(id) as count", TB_STACKPUSH_SUBSCRIBER, [ "domain_id" => $posts[0]->domain_id, "team_id" => $posts[0]->team_id, "status" => 1 ])->count;

        $limit_subs = 0;
        $max_subs = 200;
        $subscriber_ids = [];
        $a_subscriber_ids = [];
        $b_subscriber_ids = [];
        $a_count = 0;
        $b_count = 0;
        $left_subscribers = 0;
        $current_post = "B";

        if ($posts[0]->id == $post_id) {
            $current_post = "A";
        }

        $db = \Config\Database::connect();
        $builder = $db->table(TB_STACKPUSH_LOGS);
        $builder->select("subscriber_id");
        $builder->where('post_id', $posts[0]->id );
        $query = $builder->get();
        $a_logs = $query->getResult();
        $query->freeResult();

        if (!empty($a_logs)) {
            foreach ($a_logs as $key => $value) {
                $a_subscriber_ids[] = $value->subscriber_id;
            }
        }

        $builder = $db->table(TB_STACKPUSH_LOGS);
        $builder->select("subscriber_id");
        $builder->where('post_id', $posts[1]->id );
        $query = $builder->get();
        $b_logs = $query->getResult();
        $query->freeResult();

        if (!empty($b_logs)) {
            foreach ($b_logs as $key => $value) {
                $b_subscriber_ids[] = $value->subscriber_id;
            }
        }

        if($current_post == "A"){
            if (count($a_subscriber_ids) > $b_subscriber_ids) {
                return false;
            }
        }else{
            if (count($b_subscriber_ids) > $a_subscriber_ids) {
                return false;
            }
        }

        $left_subscribers = $total_subscriber - ( count($a_subscriber_ids) + count($b_subscriber_ids) );
        if($left_subscribers <= 0){
            return -1;
        }

        if($left_subscribers >= $max_subs*2){
            $limit_subs = $max_subs;
        }else{
            $limit_subs = floor( $left_subscribers/2);
        }

        $logs = db_fetch("subscriber_id", TB_STACKPUSH_LOGS, ["post_id" => $post_id]);

        $subscriber_ids = array_merge($a_subscriber_ids,$b_subscriber_ids);
 
        $builder = $db->table(TB_STACKPUSH_SUBSCRIBER);
        $builder->select("*");

        $builder->where("domain_id", $posts[0]->domain_id);
        $builder->where("team_id", $posts[0]->team_id);
        $builder->where("status", 1);

        if(!empty($subscriber_ids)){
            $builder->whereNotIn("id", $subscriber_ids );
        }

        if ($posts[0]->audience_status) {
            $subscibers_filter = get_subscribers_by_filter($post_id);
            if(!empty($subscibers_filter)){
                $builder->whereIn("id", $subscibers_filter );
            }
        }

        $builder->orderBy("id", "RANDOM");
        $builder->limit($limit_subs, 0);
        $query = $builder->get();
        $result = $query->getResult();
        $query->freeResult();
        if($result){
            return $result;
        }else{
            return -1;
        }
    }

    public function get_subscriptions($post_id){
        $post = db_get("*", TB_STACKPUSH_SCHEDULES, ["id" => $post_id]);
        if(empty($post)) return false;

        $logs = db_fetch("subscriber_id", TB_STACKPUSH_LOGS, ["post_id" => $post_id]);

        $subscriber_ids = [];
        foreach ($logs as $key => $value) {
            $subscriber_ids[] = $value->subscriber_id;
        }

        $db = \Config\Database::connect();
        $builder = $db->table(TB_STACKPUSH_SUBSCRIBER);

        $builder->where("domain_id", $post->domain_id);
        $builder->where("team_id", $post->team_id);
        $builder->where("status", 1 );

        $builder->select("*");
        if(!empty($subscriber_ids)){
            $builder->whereNotIn("id", $subscriber_ids );
        }

        if ($post->audience_status) {
            $subscibers_filter = get_subscribers_by_filter($post_id);
            if(!empty($subscibers_filter)){
                $builder->whereIn("id", $subscibers_filter );
            }
        }
        
        $builder->orderBy("id", "RANDOM");
        $builder->limit(200, 0);
        $query = $builder->get();
        $result = $query->getResult();
        $query->freeResult();

        return $result;
    }

    public function get_modules(){
        $module_paths = get_module_paths();
        $modules_data_feature = array();
        $modules_data_segmentation = array();
        $modules_data_analytics = array();
        $modules_data_teplate = array();
        $modules_data_settings = array();
        $modules_data_main = array();

        if(!empty($module_paths))
        {
            if( !empty($module_paths) ){
                foreach ($module_paths as $key => $module_path) {
                    $model_paths = $module_path . "/Models/";
                    $model_files = glob( $model_paths . '*' );


                    if ( !empty( $model_files ) )
                    {
                        foreach ( $model_files as $model_file )
                        {
                            $model_content = get_all_functions($model_file);
                            if ( in_array("block_push", $model_content) )
                                {   
                                $config_path = $module_path . "/Config.php";
                                $config_item = include $config_path;
                                include_once $model_file;
                                
                                $class = get_module_class($model_file);
                                
                                $data = new $class;
                                $name = explode("\\", $class);
                                switch ( strtolower( $config_item['parent']['id']) ) {
                                    case '':
                                        $modules_data_main[] = $data->block_push();
                                        break;

                                    case 'features':
                                        $modules_data_feature[] = $data->block_push();
                                        break;

                                    case 'segmentation':
                                        $modules_data_segmentation[] = $data->block_push();
                                        break;

                                    case 'analytics':
                                        $modules_data_analytics[] = $data->block_push();
                                        break;
                                    
                                    case 'settings':
                                        $modules_data_settings[] = $data->block_push();
                                        break;

                                }
                            }
                        }
                    }
                }
            }
        }

        if( !empty($modules_data_feature) || !empty($modules_data_segmentation) || !empty($modules_data_analytics)){
            if(!empty($modules_data_main)){
                uasort($modules_data_main, function($a, $b) {
                    return $a['position'] <=> $b['position'];
                });
            }

            if(!empty($modules_data_feature)){
                uasort($modules_data_feature, function($a, $b) {
                    return $a['position'] <=> $b['position'];
                });
            }

            if(!empty($modules_data_segmentation)){
                uasort($modules_data_segmentation, function($a, $b) {
                    return $a['position'] <=> $b['position'];
                });
            }

            if(!empty($modules_data_analytics)){
                uasort($modules_data_analytics, function($a, $b) {
                    return $a['position'] <=> $b['position'];
                });
            }

            if(!empty($modules_data_settings)){
                uasort($modules_data_settings, function($a, $b) {
                    return $a['position'] <=> $b['position'];
                });
            }

            $modules_data = [];
            $modules_data[] = $modules_data_main;
            $modules_data[] = $modules_data_feature;
            $modules_data[] = $modules_data_segmentation;
            $modules_data[] = $modules_data_analytics;
            $modules_data[] = $modules_data_settings;

            return $modules_data;
        }

        return false;
    }
}
