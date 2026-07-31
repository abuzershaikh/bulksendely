<?php
namespace Core\Push_schedules\Models;
use CodeIgniter\Model;

class Push_schedulesModel extends Model
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function block_push_quicks($path = ""){
        return [
        	"position" => 3000
        ];
    }

    public function block_push(){
        return array(
            "position" => isset($this->config['parent']['position'])?$this->config['parent']['position']:10000,
            "config" => $this->config
        );
    }

    public function calendar($type)
	{
		$db = \Config\Database::connect();

		$builder = $db->table(TB_STACKPUSH_SCHEDULES);
        $builder->select("from_unixtime(start_time,'%Y-%m-%d') as time_posts, COUNT(start_time) as total");
        $builder->where("status != ", 0);
        $builder->where("team_id", TEAM_ID);
        $builder->where("domain_id", PUSH_DOMAIN_ID);
        $builder->where("type != ", 2);
		$builder->groupBy("time_posts");
		$builder->orderBy("time_posts", "DESC");
		$query = $builder->get();
		$result = $query->getResult();
		$query->freeResult();

		return $result;
	}

	public function list($type, $time)
	{	
		$db = \Config\Database::connect();

		$time_check = explode("-", $time);

		if( count($time_check) != 3 || !checkdate( (int)$time_check[1], (int)$time_check[2], (int)$time_check[0]) ) return false;

		$date_start = $time . " 00:00:00";
		$date_end = $time . " 23:59:59";

		$builder = $db->table(TB_STACKPUSH_SCHEDULES." as a");
		$builder->select("
			from_unixtime(a.start_time,'%Y-%m-%d %H:%i:%s') as time_posts, 
			a.pid, 
			a.start_time, 
			a.team_id, 
			a.domain_id, 
			a.type,
			a.id,
			a.ids,
			a.title,
			a.message,
			a.url,
			a.number_sent,
			a.number_delivered,
			a.number_action,
			a.number_box,
			a.number_btn_left,
			a.number_btn_right,
			a.status,
			b.domain,
			b.icon as domain_icon,
			a.icon as post_icon,
			a.large_image
		");
		
		$builder->join(TB_STACKPUSH_DOMAINS." as b", "a.domain_id = b.id");

		$builder->having(" ( a.status != 0 AND from_unixtime(a.start_time,'%Y-%m-%d %H:%i:%s') >= '{$date_start}' AND from_unixtime(a.start_time,'%Y-%m-%d %H:%i:%s') <= '{$date_end}' AND a.team_id = '".TEAM_ID."' AND a.domain_id = '".PUSH_DOMAIN_ID."' AND a.type != 2 ) ");
		
		$builder->orderBy("a.start_time ASC");
		$query = $builder->get();
		$result = $query->getResult();
		$query->freeResult();

		if( $result ){
			foreach ($result as $key => $value) {
				$config = find_modules( "push" );

				if($config)
				{
					$result[$key]->module_name = $config['name'];
					$result[$key]->icon = $config['icon'];
					$result[$key]->color = $config['color'];

				}else{

					$result[$key]->module_name = "";
					$result[$key]->icon = "";
					$result[$key]->color = "";
				}
			}
		}

		return $result;
	}
}
