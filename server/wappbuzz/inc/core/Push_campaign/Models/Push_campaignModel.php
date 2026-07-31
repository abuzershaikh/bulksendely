<?php
namespace Core\Push_campaign\Models;
use CodeIgniter\Model;

class Push_campaignModel extends Model
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
            "position" => 400,
            "label" => __("Web Push Notification"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => __("Campaign management"),
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

    public function get_list( $return_data = true, $pid = 0 )
    {
        $current_page = (int)(post("current_page") - 1);
        $per_page = post("per_page");
        $total_items = post("total_items");
        $keyword = post("keyword");

        $db = \Config\Database::connect();
        $builder = $db->table(TB_STACKPUSH_CAMPAIGNS);
        $builder->select("*");
        
        if( $keyword ){
            $builder->groupStart();
            $array = [
                'name' => $keyword, 
                'desc' => $keyword
            ];
            $builder->orLike($array);
            $builder->groupEnd();
        }


        $builder->where("team_id", TEAM_ID);
        $builder->where("domain_id", PUSH_DOMAIN_ID);
        
        if( !$return_data )
        {
            $result =  $builder->countAllResults();
        }
        else
        {
            $builder->limit($per_page, $per_page*$current_page);
            $query = $builder->get();
            $result = $query->getResult();
            $query->freeResult();
        }
        
        return $result;
    }

    public function chart( $campaign_id = 0 ){
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
            
            $team_id = get_team("id");
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

            $db = \Config\Database::connect();
            $query = $db->query("SELECT DATE(FROM_UNIXTIME(created)) as created, COUNT(id) as count, SUM(number_sent) as sent, SUM(number_delivered) as delivered, SUM(number_action) as clicked FROM ".TB_STACKPUSH_SCHEDULES." WHERE team_id = '".TEAM_ID."' AND domain_id = '".PUSH_DOMAIN_ID."' AND FROM_UNIXTIME(created) > '".$date_since."' AND FROM_UNIXTIME(created) < '".$date_until."' AND campaign_id = ".$campaign_id." GROUP BY DATE(FROM_UNIXTIME(created));");
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
                "clicked" => $new_clicked,
            ];
        }
    }
}
