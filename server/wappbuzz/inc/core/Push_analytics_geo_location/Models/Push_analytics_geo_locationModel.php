<?php
namespace Core\Push_analytics_geo_location\Models;
use CodeIgniter\Model;

class Push_analytics_geo_locationModel extends Model
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
            "position" => 1000,
            "label" => __("Web Push Notification"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => __("Geo location analytics"),
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

    public function geo_chart(){
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

            $query = $db->query("SELECT country, COUNT(country) as count, SUM(number_delivered) as delivered, SUM(number_action) as clicked FROM ".TB_STACKPUSH_SUBSCRIBER." WHERE team_id = '".TEAM_ID."' AND domain_id = '".PUSH_DOMAIN_ID."' AND FROM_UNIXTIME(created) > '".$date_since."' AND FROM_UNIXTIME(created) < '".$date_until."' AND status = 1 GROUP BY country");
            $result = $query->getResult();
            $stats = "";

            if($result){
                foreach ($result as $key => $value) {
                    $result[$key]->country_name = list_countries($value->country);
                    $result[$key]->ctr =  $value->delivered!=0?round((int)$value->clicked/(int)$value->delivered*100, 2):0;
                    $stats.="{'code':'".$value->country."','value':".(int)$value->clicked."},";
                }
            }

            return [
                "list" => $result,
                "chart" => "[".substr($stats, 0, -1)."]"
            ];
        }
    }
}
