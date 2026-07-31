<?php
namespace Core\Push_drafts\Models;
use CodeIgniter\Model;

class Push_draftsModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function block_push(){
        return array(
            "position" => isset($this->config['parent']['position'])?$this->config['parent']['position']:10000,
            "config" => $this->config
        );
    }

    public function list($category, $return_data = true)
	{	
		$current_page = (int)(post("current_page") - 1);
        $per_page = post("per_page");
        $total_items = post("total_items");
        $keyword = post("keyword");

		$status = 0;
		$team_id = get_team("id");
		$db = \Config\Database::connect();
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
			a.large_image,
			a.created
		");

		$builder->join(TB_STACKPUSH_DOMAINS." as b", "a.domain_id = b.id");
		
		$builder->where("a.status", 0);
		$builder->where("a.domain_id", PUSH_DOMAIN_ID);
		$builder->where("a.team_id", TEAM_ID);

		if( !$return_data )
        {
            $result =  $builder->countAllResults();
        }
        else
        {
            $builder->limit($per_page, $per_page*$current_page);
            $builder->orderBy("a.id", "ASC");
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

        }
        
		return $result;
	}
}
