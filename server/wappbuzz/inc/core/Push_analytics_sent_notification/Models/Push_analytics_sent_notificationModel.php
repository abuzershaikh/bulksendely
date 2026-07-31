<?php
namespace Core\Push_analytics_sent_notification\Models;
use CodeIgniter\Model;

class Push_analytics_sent_notificationModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function block_push_permissions($path = ""){
        return [
            "position" => 1100
        ];
    }

    public function block_plans(){
        return [
            "tab" => 17,
            "position" => 700,
            "label" => __("Web Push Notification"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => __("Sent notification analytics"),
                ],
            ]
        ];
    }

    public function block_push(){
        return array(
            "position" => isset($this->config['parent']['position'])?$this->config['parent']['position']:10000,
            "config" => $this->config
        );
    }

    public function get_list( $return_data = true )
    {
        $daterange = post("daterange")!=NULL?addslashes(post("daterange")):"";
        if( $daterange != "" ){
            $daterange = explode(",", $daterange);
        }else{
            $daterange = [date("Y-m-d", time() - 28*24*60*60), date("Y-m-d")];
        }

        if(count($daterange) == 2){
            $date_list = array();
            $date_since = $daterange[0]." 00:00:00";
            $date_until = $daterange[1]." 23:59:59";

            $current_page = (int)(post("current_page") - 1);
            $per_page = post("per_page");
            $total_items = post("total_items");
            $keyword = post("keyword");

            $db = \Config\Database::connect();
            $builder = $db->table(TB_STACKPUSH_SCHEDULES." as a");
            $builder->join(TB_STACKPUSH_CAMPAIGNS." as b", "a.campaign_id = b.id", "LEFT");
            $builder->select('a.*,b.name as campaign_name,b.ids as campaign_ids');
            $builder->groupStart();
            $builder->where('FROM_UNIXTIME(a.created) >', $date_since);
            $builder->where('FROM_UNIXTIME(a.created) <', $date_until);
            $builder->where('a.team_id', TEAM_ID);
            $builder->where('a.domain_id', PUSH_DOMAIN_ID);
            $builder->where('a.type !=', 2);
            $builder->groupEnd();
            if( $keyword ){
                $builder->groupStart();
                $array = [
                    'a.title' => $keyword, 
                    'a.message' => $keyword,
                ];
                $builder->orLike($array);
                $builder->groupEnd();
            }
            
            if( !$return_data )
            {
                $result =  $builder->countAllResults();
            }
            else
            {
                $builder->orderBy("id", "DESC");
                $builder->limit($per_page, $per_page*$current_page);
                $query = $builder->get();
                $result = $query->getResult();
                $query->freeResult();
            }
            
            return $result;
        }
    }

    public function chart(){
        $result = false;
        $new_sent = 0;
        $new_delivered = 0;
        $new_clicked = 0;
        $db = \Config\Database::connect();
        $daterange = post("daterange")!=NULL?addslashes(post("daterange")):"";
        if( $daterange != "" ){
            $daterange = explode(",", $daterange);
        }else{
            $daterange = [date("Y-m-d", time() - 28*24*60*60), date("Y-m-d")];
        }

        if(count($daterange) == 2){
            $date_list = array();
            $date_since = $daterange[0]." 00:00:00";
            $date_until = $daterange[1]." 23:59:59";
            
            $sent_string = "";
            $delivered_string = "";
            $clicked_string = "";
            $date_string = "";

            $period = new \DatePeriod(
                 new \DateTime($date_since),
                 new \DateInterval('P1D'),
                 new \DateTime($date_until)
            );

            foreach ($period as $key => $value) {
                $date_list[date_short($value->format('Y-m-d'))] = [0,0,0];
            }

            $query = $db->query("SELECT DATE(FROM_UNIXTIME(created)) as created, COUNT(id) as count, SUM(number_sent) as sent, SUM(number_delivered) as delivered, SUM(number_action) as clicked FROM ".TB_STACKPUSH_SCHEDULES." WHERE team_id = '".TEAM_ID."' AND domain_id = '".PUSH_DOMAIN_ID."' AND FROM_UNIXTIME(created) > '".$date_since."' AND FROM_UNIXTIME(created) < '".$date_until."' AND type != 2 GROUP BY DATE(FROM_UNIXTIME(created)) AND status != 0;");
            $result = $query->getResult();

            if($query->getResult()){
                foreach ($query->getResult() as $key => $value) {
                    if(isset($date_list[date_short($value->created)])){
                        $date_list[date_short($value->created)] = [(int)$value->sent, (int)$value->delivered, (int)$value->clicked];
                    }
                }
            }

            foreach ($date_list as $date => $value) {
                $sent_string .= "{$value[0]},";
                $delivered_string .= "{$value[1]},";
                $clicked_string .= "{$value[2]},";
                $date_string .= "'{$date}',";
                $new_sent += $value[0]; 
                $new_delivered += $value[1]; 
                $new_clicked += $value[2]; 
            }

            $sent_string = "[".substr($sent_string, 0, -1)."]";
            $delivered_string = "[".substr($delivered_string, 0, -1)."]";
            $clicked_string = "[".substr($clicked_string, 0, -1)."]";
            $date_string  = "[".substr($date_string, 0, -1)."]";

            return [
                "date" => $date_string,
                "sent_str" => $sent_string,
                "delivered_str" => $delivered_string,
                "clicked_str" => $clicked_string,
                "sent" => $new_sent,
                "delivered" => $new_delivered,
                "clicked" => $new_clicked
            ];
        }
    }

    public function notification_chart( $post_id = 0 ){
        $db = \Config\Database::connect();

        //GET POSTS
        $builder = $db->table(TB_STACKPUSH_SCHEDULES." as a");
        $builder->join(TB_STACKPUSH_MAP." as b", "a.id = b.post_id", "LEFT");
        $builder->join(TB_STACKPUSH_SUBSCRIBER." as c", "c.id = b.subscriber_id", "LEFT");
        $builder->select('a.*, sum(b.number_sent) as sent, sum(b.number_delivered) as delivered, sum(b.number_action) as clicked');

        $builder->where('a.team_id', TEAM_ID);
        $builder->where('a.domain_id', PUSH_DOMAIN_ID);
        $builder->where('a.type != ', 2);

        $builder->groupStart();
        $builder->where('a.id', $post_id);
        $builder->orWhere('a.ids', $post_id);
        $builder->groupEnd();

        $query = $builder->get();
        $post = $query->getRow();
        $query->freeResult();



        $subscriber_ids = [];
        if( empty($post) || $post->id == ""){
            return false;
        }

        //DEVICE
        $device_data = [
            "desktop" => 0,
            "mobile" => 0,
            "tablet" => 0,
            "total" => 0,

            "desktop_clicked" => 0,
            "mobile_clicked" => 0,
            "tablet_clicked" => 0,
            "total_clicked" => 0,
        ];

        $builder = $db->table(TB_STACKPUSH_SUBSCRIBER." as a");
        $builder->join(TB_STACKPUSH_MAP." as b", "b.subscriber_id = a.id", "LEFT");
        $builder->select('a.device, COUNT(device) as count, sum(b.number_sent) as sent, sum(b.number_delivered) as delivered, sum(b.number_action) as clicked');
        $builder->where('a.team_id', TEAM_ID);
        $builder->where('a.domain_id', PUSH_DOMAIN_ID);
        $builder->where('b.post_id', $post->id);
        $builder->groupBy('a.device');
        $query = $builder->get();
        $devices = $query->getResult();
        $query->freeResult();

        if($devices){
            foreach ($devices as $key => $value) {
                switch ($value->device) {
                    case 'desktop':
                        $device_data['desktop'] = (int)$value->count;
                        $device_data['total'] += (int)$value->count;

                        $device_data['desktop_clicked'] = (int)$value->clicked;
                        $device_data['total_clicked'] += (int)$value->clicked;
                        break;

                    case 'mobile':
                        $device_data['mobile'] = (int)$value->count;
                        $device_data['total'] += $value->count;

                        $device_data['mobile_clicked'] = (int)$value->clicked;
                        $device_data['total_clicked'] += (int)$value->clicked;
                        break;

                    case 'tablet':
                        $device_data['tablet'] = $value->count;
                        $device_data['total'] += $value->count;

                        $device_data['tablet_clicked'] = $value->clicked;
                        $device_data['total_clicked'] += $value->clicked;
                        break;
                }
            }
            
        }

        $device_data['percent_desktop'] = $device_data['total']!=0?round( $device_data['desktop']/$device_data['total']*100 ):0;
        $device_data['percent_mobile'] = $device_data['total']!=0?round( $device_data['mobile']/$device_data['total']*100 ):0;
        $device_data['percent_tablet'] = $device_data['total']!=0?round( $device_data['tablet']/$device_data['total']*100 ):0;

        $device_data['percent_desktop_clicked'] = $device_data['total_clicked']!=0?round( $device_data['desktop_clicked']/$device_data['total_clicked']*100 ):0;
        $device_data['percent_mobile_clicked'] = $device_data['total_clicked']!=0?round( $device_data['mobile_clicked']/$device_data['total_clicked']*100 ):0;
        $device_data['percent_tablet_clicked'] = $device_data['total_clicked']!=0?round( $device_data['tablet_clicked']/$device_data['total_clicked']*100 ):0;
        
        $device_columns = "Desktop,Mobile,Tablet";
        //END DEVICE

        //OS
        $builder = $db->table(TB_STACKPUSH_SUBSCRIBER." as a");
        $builder->join(TB_STACKPUSH_MAP." as b", "b.subscriber_id = a.id", "LEFT");
        $builder->select('a.os, COUNT(os) as count, sum(b.number_sent) as sent, sum(b.number_delivered) as delivered, sum(b.number_action) as clicked');
        $builder->where('a.team_id', TEAM_ID);
        $builder->where('a.domain_id', PUSH_DOMAIN_ID);
        $builder->where('b.post_id', $post->id);
        $builder->groupBy('a.os');
        $query = $builder->get();
        $os = $query->getResult();
        $query->freeResult();

        $os_data = [
            "windows" => 0,
            "android" => 0,
            "mac" => 0,
            "linux" => 0,
            "total" => 0,
            "windows_clicked" => 0,
            "android_clicked" => 0,
            "mac_clicked" => 0,
            "linux_clicked" => 0,
            "total_clicked" => 0,
        ];

        if($os){
            foreach ($os as $key => $value) {
                switch ($value->os) {
                    case 'windows':
                        $os_data['windows'] = (int)$value->count;
                        $os_data['total'] += (int)$value->count;

                        $os_data['windows_clicked'] = (int)$value->clicked;
                        $os_data['total_clicked'] += (int)$value->clicked;
                        break;

                    case 'android':
                        $os_data['android'] = (int)$value->count;
                        $os_data['total'] += (int)$value->count;

                        $os_data['android_clicked'] = (int)$value->clicked;
                        $os_data['total_clicked'] += (int)$value->clicked;
                        break;

                    case 'mac':
                        $os_data['mac'] = (int)$value->count;
                        $os_data['total'] += (int)$value->count;

                        $os_data['mac_clicked'] = (int)$value->clicked;
                        $os_data['total_clicked'] += (int)$value->clicked;
                        break;

                    case 'linux':
                        $os_data['linux'] = (int)$value->count;
                        $os_data['total'] += (int)$value->count;

                        $os_data['linux_clicked'] = (int)$value->clicked;
                        $os_data['total_clicked'] += (int)$value->clicked;
                        break;
                }
            }
            
        }

        $os_data['percent_windows'] = $os_data['total']!=0?round( $os_data['windows']/$os_data['total']*100 ):0;
        $os_data['percent_android'] = $os_data['total']!=0?round( $os_data['android']/$os_data['total']*100 ):0;
        $os_data['percent_mac'] = $os_data['total']!=0?round( $os_data['mac']/$os_data['total']*100 ):0;
        $os_data['percent_linux'] = $os_data['total']!=0?round( $os_data['linux']/$os_data['total']*100 ):0;

        $os_data['percent_windows_clicked'] = $os_data['total_clicked']!=0?round( $os_data['windows_clicked']/$os_data['total_clicked']*100 ):0;
        $os_data['percent_android_clicked'] = $os_data['total_clicked']!=0?round( $os_data['android_clicked']/$os_data['total_clicked']*100 ):0;
        $os_data['percent_mac_clicked'] = $os_data['total_clicked']!=0?round( $os_data['mac_clicked']/$os_data['total_clicked']*100 ):0;
        $os_data['percent_linux_clicked'] = $os_data['total_clicked']!=0?round( $os_data['linux_clicked']/$os_data['total_clicked']*100 ):0;
        
        $os_columns = "Windows,Android,Mac,Linux";
        //END OS

        //BROWSER
        $builder = $db->table(TB_STACKPUSH_SUBSCRIBER." as a");
        $builder->join(TB_STACKPUSH_MAP." as b", "b.subscriber_id = a.id", "LEFT");
        $builder->select('a.browser, COUNT(browser) as count, sum(b.number_sent) as sent, sum(b.number_delivered) as delivered, sum(b.number_action) as clicked');
        $builder->where('a.team_id', TEAM_ID);
        $builder->where('a.domain_id', PUSH_DOMAIN_ID);
        $builder->where('b.post_id', $post->id);
        $builder->groupBy('a.browser');
        $query = $builder->get();
        $browser = $query->getResult();
        $query->freeResult();

        $browser_data = [
            "chrome" => 0,
            "firefox" => 0,
            "safari" => 0,
            "opera" => 0,
            "edge" => 0,
            "total" => 0,
            "chrome_clicked" => 0,
            "firefox_clicked" => 0,
            "safari_clicked" => 0,
            "opera_clicked" => 0,
            "edge_clicked" => 0,
            "total_clicked" => 0,
        ];

        if($browser){
            foreach ($browser as $key => $value) {
                switch ($value->browser) {
                    case 'chrome':
                        $browser_data['chrome'] = (int)$value->count;
                        $browser_data['total'] += (int)$value->count;

                        $browser_data['chrome_clicked'] = (int)$value->clicked;
                        $browser_data['total_clicked'] += (int)$value->clicked;
                        break;

                    case 'firefox':
                        $browser_data['firefox'] = (int)$value->count;
                        $browser_data['total'] += (int)$value->count;

                        $browser_data['firefox_clicked'] = (int)$value->clicked;
                        $browser_data['total_clicked'] += (int)$value->clicked;
                        break;

                    case 'safari':
                        $browser_data['safari'] = (int)$value->count;
                        $browser_data['total'] += (int)$value->count;

                        $browser_data['safari_clicked'] = (int)$value->clicked;
                        $browser_data['total_clicked'] += (int)$value->clicked;
                        break;

                    case 'opera':
                        $browser_data['opera'] = (int)$value->count;
                        $browser_data['total'] += (int)$value->count;

                        $browser_data['opera_clicked'] = (int)$value->clicked;
                        $browser_data['total_clicked'] += (int)$value->clicked;
                        break;

                    case 'edge':
                        $browser_data['edge'] = (int)$value->count;
                        $browser_data['total'] += (int)$value->count;

                        $browser_data['edge_clicked'] = (int)$value->clicked;
                        $browser_data['total_clicked'] += (int)$value->clicked;
                        break;
                }
            }
            
        }

        $browser_data['percent_chrome'] = $browser_data['total']!=0?round( $browser_data['chrome']/$browser_data['total']*100 ):0;
        $browser_data['percent_firefox'] = $browser_data['total']!=0?round( $browser_data['firefox']/$browser_data['total']*100 ):0;
        $browser_data['percent_safari'] = $browser_data['total']!=0?round( $browser_data['safari']/$browser_data['total']*100 ):0;
        $browser_data['percent_opera'] = $browser_data['total']!=0?round( $browser_data['opera']/$browser_data['total']*100 ):0;
        $browser_data['percent_edge'] = $browser_data['total']!=0?round( $browser_data['edge']/$browser_data['total']*100 ):0;

        $browser_data['percent_chrome_clicked'] = $browser_data['total_clicked']!=0?round( $browser_data['chrome_clicked']/$browser_data['total_clicked']*100 ):0;
        $browser_data['percent_firefox_clicked'] = $browser_data['total_clicked']!=0?round( $browser_data['firefox_clicked']/$browser_data['total_clicked']*100 ):0;
        $browser_data['percent_safari_clicked'] = $browser_data['total_clicked']!=0?round( $browser_data['safari_clicked']/$browser_data['total_clicked']*100 ):0;
        $browser_data['percent_opera_clicked'] = $browser_data['total_clicked']!=0?round( $browser_data['opera_clicked']/$browser_data['total_clicked']*100 ):0;
        $browser_data['percent_edge_clicked'] = $browser_data['total_clicked']!=0?round( $browser_data['edge_clicked']/$browser_data['total_clicked']*100 ):0;
        
        $browser_columns = "Chrome,Firefox,safari,Opera,Edge";
        //END BROWSER

        //COUNTRY
        $builder = $db->table(TB_STACKPUSH_SUBSCRIBER." as a");
        $builder->join(TB_STACKPUSH_MAP." as b", "b.subscriber_id = a.id", "LEFT");
        $builder->select('a.country, sum(b.number_sent) as sent, sum(b.number_delivered) as delivered, sum(b.number_action) as clicked');
        $builder->where('a.team_id', TEAM_ID);
        $builder->where('a.domain_id', PUSH_DOMAIN_ID);
        $builder->where('b.post_id', $post->id);
        $builder->groupBy('a.country');
        $query = $builder->get();
        $countries = $query->getResult();
        $query->freeResult();

        $geo_stats = "";

        if($countries){
            foreach ($countries as $key => $value) {
                $countries[$key]->country_name = list_countries($value->country);
                $countries[$key]->ctr =  $value->delivered!=0?round((int)$value->clicked/(int)$value->delivered*100, 2):0;
                $geo_stats.="{'code':'".$value->country."','value':".(int)$value->clicked."},";
            }
        }

        return [
            "post" => $post,
            "countries" => $countries,
            "geo_stats" => "[".substr($geo_stats, 0, -1)."]",
            "browser_data" => $browser_data,
            "browser_columns" => $browser_columns,
            "os_data" => $os_data,
            "os_columns" => $os_columns,
            "device_data" => $device_data,
            "device_columns" => $device_columns,
        ];
    }
}
