<?php
namespace Core\Push_segmentation_audience_creator\Models;
use CodeIgniter\Model;

class Push_segmentation_audience_creatorModel extends Model
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
            "position" => 500,
            "label" => __("Web Push Notification"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => __("Auto segmentation"),
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
        $current_page = (int)(post("current_page") - 1);
        $per_page = post("per_page");
        $total_items = post("total_items");
        $keyword = post("keyword");

        $db = \Config\Database::connect();
        $builder = $db->table(TB_STACKPUSH_AUDIENCE_CREATOR." as a");
        $builder->groupStart();
        $builder->where("a.team_id", TEAM_ID);
        $builder->where("a.domain_id", PUSH_DOMAIN_ID);
        $builder->groupEnd();

        $builder->select('a.*');
        if( $keyword ){
            $builder->groupStart();
            $array = [
                'a.name' => $keyword, 
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

    public function get_view_list( $return_data = true, $ids = "" )
    {
        $item = db_get("*", TB_STACKPUSH_AUDIENCE_CREATOR, ["ids" => $ids, "team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID]);

        if(!$item) return false;

        $audience_query = $item->query;

        $current_page = (int)(post("current_page") - 1);
        $per_page = post("per_page");
        $total_items = post("total_items");
        $keyword = post("keyword");

        $db = \Config\Database::connect();
        $builder = $db->table( " ( ".$audience_query." ) " . " as a");
        $builder->groupStart();
        $builder->where("a.team_id", TEAM_ID);
        $builder->where("a.domain_id", PUSH_DOMAIN_ID);
        $builder->groupEnd();

        $builder->select('a.*');
        if( $keyword ){
            $builder->groupStart();
            $array = [
                'a.token' => $keyword, 
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
