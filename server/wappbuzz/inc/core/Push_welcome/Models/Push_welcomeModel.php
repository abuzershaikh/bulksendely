<?php
namespace Core\Push_welcome\Models;
use CodeIgniter\Model;

class Push_welcomeModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function block_push_settings($path = ""){
        return [
            "position" => 8900,
            "menu" => view( 'Core\Push_welcome\Views\settings\menu', [ 'config' => $this->config ] ),
            "content" => view( 'Core\Push_welcome\Views\settings\content', [ 'config' => $this->config ] )
        ];
    }

    public function block_push_permissions($path = ""){
        return [
            "position" => 1100
        ];
    }

    public function block_push_quicks($path = ""){
        return [
            "position" => 1100
        ];
    }

    public function block_plans(){
        return [
            "tab" => 17,
            "position" => 200,
            "label" => __("Web Push Notification"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => $this->config['name'],
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

    public function get_posts(){
        $logs = db_fetch("id", TB_STACKPUSH_LOGS, ["status" => 1, "type" => 2]);
        $sub_ids = [];

        if($logs){
            foreach ($logs as $key => $log) {
                $sub_ids[] = $log->id;
            }
        }

        $db = \Config\Database::connect();
        $builder = $db->table(TB_STACKPUSH_SUBSCRIBER." as a");
        $builder->select("b.*, a.token, a.id as sub_id, a.ids as sub_ids");
        $builder->join(TB_STACKPUSH_SCHEDULES." as b", "a.next_welcome_drip_id = b.id", "LEFT");
        $builder->where("a.welcome_drip_status", 1);
        $builder->where("a.next_welcome_drip_time <=", time());
        $builder->where("a.next_welcome_drip_id !=", 0);
        if (!empty($sub_ids)) {
            $builder->whereNotIn("b.id", $sub_ids);
        }
        $builder->orderBy("id", "ASC");
        $builder->limit(100, 0);
        $query = $builder->get();
        $result = $query->getResult();
        $query->freeResult();
        return $result;
    }
}
