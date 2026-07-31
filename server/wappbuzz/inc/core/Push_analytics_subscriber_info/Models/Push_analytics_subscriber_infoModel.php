<?php
namespace Core\Push_analytics_subscriber_info\Models;
use CodeIgniter\Model;

class Push_analytics_subscriber_infoModel extends Model
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
            "position" => 900,
            "label" => __("Web Push Notification"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => __("Subscriber information analytics"),
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
            $builder = $db->table(TB_STACKPUSH_SUBSCRIBER." as a");
            $builder->select('a.*');
            $builder->groupStart();
            //$builder->where('FROM_UNIXTIME(a.created) >', $date_since);
            //$builder->where('FROM_UNIXTIME(a.created) <', $date_until);
            $builder->where('a.team_id', TEAM_ID);
            $builder->where('a.domain_id', PUSH_DOMAIN_ID);
            $builder->where('a.status', 1);
            $builder->groupEnd();
            if( $keyword ){
                $builder->groupStart();
                $array = [
                    'a.id' => $keyword
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
                $builder->limit($per_page, $per_page*$current_page);
                $query = $builder->get();
                $result = $query->getResult();
                $query->freeResult();
            }
            
            return $result;
        }
    }
}
