<?php
namespace Core\Push_campaign\Controllers;

class Push_campaign extends \CodeIgniter\Controller
{
    public function __construct(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: *");
        include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_campaign\Models\Push_campaignModel();
    }
    
    public function index( $page = false ) {
        $result = db_fetch("*", TB_STACKPUSH_CAMPAIGNS, [], "created", "ASC");

        $data = [
            "result" => $result,
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "config" => $this->config,
        ];

        $start = 0;
        $limit = 1;

        $pager = \Config\Services::pager();
        $total = $this->model->get_list(false);

        $datatable = [
            "responsive" => true,
            "columns" => [
                "id" => __("ID"),
                "content" => __("Name"),
                "status" => __("Status")
            ],
            "total_items" => $total,
            "per_page" => 50,
            "current_page" => 1,

        ];

        $data_content = [
            'start' => $start,
            'limit' => $limit,
            'total' => $total,
            'pager' => $pager,
            'datatable'  => $datatable,
            'config' => $this->config
        ];

        $data['content'] = view('Core\Push_campaign\Views\list', $data_content);

        return view('Core\Push\Views\index', $data);
    }

    public function ajax_list($ids = ""){
        $total_items = $this->model->get_list(false);
        $result = $this->model->get_list(true);
        $data = [
            "result" => $result
        ];
        ms( [
            "total_items" => $total_items,
            "data" => view('Core\Push_campaign\Views\ajax_list', $data)
        ] );
    }

    public function analytic($ids = ""){
        $campaign = db_get("*", TB_STACKPUSH_CAMPAIGNS, ["ids" => $ids, "domain_id" => PUSH_DOMAIN_ID, "team_id" => TEAM_ID]);

        if(empty($campaign)) redirect_to( get_module_url() );

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "config" => $this->config,
        ];

        $chart = $this->model->chart();

        $data_content = [
            'chart' => $chart,
            'config' => $this->config,
            'campaign' => $campaign
        ];

        $data['content'] = view('Core\Push_campaign\Views\analytic', $data_content);

        return view('Core\Push\Views\index', $data);
    }

    public function insights($campaign_id = 0) {
        $chart = $this->model->chart($campaign_id);
        $data_content = [
            'chart' => $chart,
            'config' => $this->config
        ];

        return view('Core\Push_campaign\Views\insights', $data_content);
    }

    public function popup_add_campaign($ids = ""){
        $result = db_get("*", TB_STACKPUSH_CAMPAIGNS, [ "ids" => $ids ]);
        $data = [
            "result" => $result
        ];

        return view('Core\Push_campaign\Views\popup_add_campaign', $data);
    }

    public function save($ids = ""){
        $name = post("name");
        $desc = post("desc");
        $status = (int)post("status");

        $item = db_get("*", TB_STACKPUSH_CAMPAIGNS, ["ids" => $ids, "team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID]);

        if (!$this->validate([
            'name' => 'required'
        ])) {
            ms([
                "status" => "error",
                "message" => __("Name is required")
            ]);
        }

        $data = [
            "team_id" => TEAM_ID,
            "domain_id" => PUSH_DOMAIN_ID,
            "name" => $name,
            "desc" => $desc,
            "status" => $status,
            "changed" => time(),
        ];

        if( empty($item) ){
            $data['ids'] = ids();
            $data['created'] = time();

            db_insert(TB_STACKPUSH_CAMPAIGNS, $data);
        }else{
            db_update(TB_STACKPUSH_CAMPAIGNS, $data, [ "id" => $item->id ]);
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    public function delete( $ids = '' ){
        if($ids == ''){
            $ids = post('ids');
        }

        if( empty($ids) ){
            ms([
                "status" => "error",
                "message" => __('Please select an item to delete')
            ]);
        }

        if( is_array($ids) )
        {
            foreach ($ids as $id) 
            {
                db_delete(TB_STACKPUSH_CAMPAIGNS, ['ids' => $id]);
            }
        }
        elseif( is_string($ids) )
        {
            db_delete(TB_STACKPUSH_CAMPAIGNS, ['ids' => $ids]);
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }
}