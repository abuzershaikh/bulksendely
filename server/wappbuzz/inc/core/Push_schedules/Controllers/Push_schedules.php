<?php
namespace Core\Push_schedules\Controllers;

class Push_schedules extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->model = new \Core\Push_schedules\Models\Push_schedulesModel();
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }
    
    public function index( $type = "", $time = "" ) {
        $query_id = (int)post("query_id");
        if(!in_array($type, ["queue", "published", "all"])) redirect_to( get_module_url("index/all/") );

        $result = $this->model->list($type, $time, $query_id);

        $list_schedules = view('Core\Push_schedules\Views\list', ['result' => $result]);

        if(!is_ajax()){
            $data = [
                "title" => $this->config['name'],
                "desc" => $this->config['desc'],
                "config" => $this->config,
                "content" => view('Core\Push_schedules\Views\calendar', ['result' => $result, 'list_schedules' => $list_schedules])
            ];

            return view('Core\Push_schedules\Views\index', $data);
        }else{
            return $list_schedules;
        }
    }

    public function get($type = "", $method = ""){
        $query_id = (int)post("query_id");
        $posts = $this->model->calendar($type, $query_id);

        if($posts)
        {
            $data = [];
            foreach ($posts as $key => $post)
            {
                $config = find_modules( "push" );

                if($config)
                {
                    $module_name = $config['name'];
                    $module_icon = $config['icon'];
                    $module_color = $config['color'];

                    $data[] = [
                        "id" => 1,
                        "name" => "<i class='{$module_icon}'></i> Notifications ({$post->total})",
                        "startdate" => $post->time_posts,
                        "enddate" => "",
                        "color" => "{$module_color}",
                    ];

                }

            }

            $data = [
                "monthly" => $data
            ];

            echo json_encode($data);

        }
        else
        {
            echo json_encode([ 'monthly' => [] ]);
        }

    }

    public function delete( $ids = "" ){
        if($ids == ""){
            $ids = post("id");
        }
        db_delete( TB_STACKPUSH_SCHEDULES,  [ "ids" => $ids, "team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID ]);
        ms([
            "status" => "success",
            "message" => __("Success")
        ]);

    }
}