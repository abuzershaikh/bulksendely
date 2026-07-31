<?php
namespace Core\Push_analytics_subscribers\Models;
use CodeIgniter\Model;

class Push_analytics_subscribersModel extends Model
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
            "position" => 800,
            "label" => __("Web Push Notification"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => __("Subscribers analytics"),
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

    public function subscriber_chart(){
        $total = 0;
        $subs = db_get("COUNT(id) as count", TB_STACKPUSH_SUBSCRIBER, ["domain_id" => PUSH_DOMAIN_ID, "team_id" => TEAM_ID, "status" => 1]);
        if(!empty($subs)){
            $total = $subs->count;
        }

        $result = false;
        $new = 0;
        $lost = 0;
        $list = [];
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
            
            $subscriber_string = "";
            $unsubscriber_string = "";
            $date_string = "";

            $period = new \DatePeriod(
                 new \DateTime($date_since),
                 new \DateInterval('P1D'),
                 new \DateTime($date_until)
            );

            foreach ($period as $key => $value) {
                $date_list[date_short($value->format('Y-m-d'))] = [0,0];
            }


            //Subscriber
            $query = $db->query("SELECT DATE(FROM_UNIXTIME(created)) as created, COUNT(token) as count, SUM(number_delivered) as delivered, SUM(number_action) as clicked, status, COUNT(status) as count_status  FROM ".TB_STACKPUSH_SUBSCRIBER." WHERE team_id = '".TEAM_ID."' AND domain_id = '".PUSH_DOMAIN_ID."' AND FROM_UNIXTIME(created) > '".$date_since."' AND FROM_UNIXTIME(created) < '".$date_until."' GROUP BY DATE(FROM_UNIXTIME(created));");
            $result = $query->getResult();

            if($result){
                foreach ($result as $key => $value) {
                    if(isset($date_list[date_short($value->created)])){
                        $date_list[date_short($value->created)][0] = $value->count_status;
                    }
                }
            }

            //Unsubscriber
            $query = $db->query("SELECT DATE(FROM_UNIXTIME(created)) as created, COUNT(token) as count, SUM(number_delivered) as delivered, SUM(number_action) as clicked, status, COUNT(status) as count_status  FROM ".TB_STACKPUSH_SUBSCRIBER." WHERE team_id = '".TEAM_ID."' AND domain_id = '".PUSH_DOMAIN_ID."' AND FROM_UNIXTIME(unsubscribe_date) > '".$date_since."' AND FROM_UNIXTIME(unsubscribe_date) < '".$date_until."' AND status = 0 GROUP BY DATE(FROM_UNIXTIME(unsubscribe_date));");
            $result = $query->getResult();

            if($result){
                foreach ($result as $key => $value) {
                    if(isset($date_list[date_short($value->created)])){
                        $date_list[date_short($value->created)][1] = $value->count_status;
                    }
                }
            }

            foreach ($date_list as $date => $value) {
                $subscriber_string .= "{$value[0]},";
                $unsubscriber_string .= "{$value[1]},";
                $date_string .= "'{$date}',";
                $new += $value[0]; 
                $lost += $value[1]; 
            }

            $subscriber_string = "[".substr($subscriber_string, 0, -1)."]";
            $unsubscriber_string = "[".substr($unsubscriber_string, 0, -1)."]";
            $date_string  = "[".substr($date_string, 0, -1)."]";

            return [
                "date_list" => $date_list,
                "date" => $date_string,
                "subscriber_str" => $subscriber_string,
                "unsubscriber_str" => $unsubscriber_string,
                "new" => $new,
                "lost" => $lost,
                "total" => $total,
            ];
        }
    }
}
