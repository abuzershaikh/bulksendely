<?php
namespace Core\Push_segmentation_manual\Controllers;

class Push_segmentation_manual extends \CodeIgniter\Controller
{
    public function __construct(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: *");
        include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_segmentation_manual\Models\Push_segmentation_manualModel();
    }
    
    public function index( $page = false, $ids = "" ) {

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            'config' => $this->config
        ];

        switch ($page) {

            case 'view':
                $result = db_get("*", TB_STACKPUSH_SEGMENTATION, ["ids" => $ids, "team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID]);

                if(empty($result)){
                    redirect_to( get_module_url() );
                }

                $start = 0;
                $limit = 1;

                $pager = \Config\Services::pager();
                $total = $this->model->get_view_list(false, $ids);

                $datatable = [
                    "responsive" => true,
                    "columns" => [
                        "id" => __("ID"),
                        "token" => __("Subs ID"),
                        "first_visit" => __("First Session"),
                        "device" => __("Device"),
                        "os" => __("OS"),
                        "browser" => __("Browser"),
                        "resolution" => __("Resolution"),
                        "location" => __("Location"),
                        "language" => __("Language"),
                        "timezone" => __("Timezone"),
                        "subscription_url" => __("Subscription Url"),
                        "last_visit" => __("Last Session"),
                        "last_url_visit" => __("Last URL Visited"),
                    ],
                    "total_items" => $total,
                    "per_page" => 20,
                    "current_page" => 1,

                ];

                $data_content = [
                    'start' => $start,
                    'limit' => $limit,
                    'total' => $total,
                    'pager' => $pager,
                    'ids'   => $ids,
                    'result'=> $result,
                    'datatable'  => $datatable,
                    'config' => $this->config
                ];

                $data['content'] = view('Core\Push_segmentation_manual\Views\view', $data_content);
                break;
            
            default:
                
                $start = 0;
                $limit = 1;

                $pager = \Config\Services::pager();
                $total = $this->model->get_list(false);

                $datatable = [
                    "responsive" => true,
                    "columns" => [
                        "id" => __("Audience ID"),
                        "name" => __("Name"),
                        "ids" => __("ID"),
                    ],
                    "total_items" => $total,
                    "per_page" => 20,
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

                $data['content'] = view('Core\Push_segmentation_manual\Views\content', $data_content);
                break;
        }

        return view('Core\Push\Views\index', $data);
    }

    public function ajax_list(){
        $total_items = $this->model->get_list(false);
        $result = $this->model->get_list(true);
        $data = [
            "result" => $result,
            "total_items" => $total_items,
        ];
        ms( [
            "total_items" => $total_items,
            "data" => view('Core\Push_segmentation_manual\Views\ajax_list', $data)
        ] );
    }

    public function ajax_view_list($ids = ""){
        $total_items = $this->model->get_view_list(false, $ids);
        $result = $this->model->get_view_list(true, $ids);
        $data = [
            "result" => $result,
            "total_items" => $total_items,
        ];
        ms( [
            "total_items" => $total_items,
            "data" => view('Core\Push_segmentation_audience_creator\Views\ajax_view_list', $data)
        ] );
    }

    public function popup_code($ids = ""){
        $result = db_get("*", TB_STACKPUSH_SEGMENTATION, [ "ids" => $ids ]);

        

        $data = [
            "result" => $result,
            'config' => $this->config
        ];

        return view('Core\Push_segmentation_manual\Views\popup_code', $data);
    }

    public function popup_add_segmentation($ids = ""){
        $result = db_get("*", TB_STACKPUSH_SEGMENTATION, [ "ids" => $ids ]);
        $data = [
            "result" => $result,
            'config' => $this->config
        ];

        return view('Core\Push_segmentation_manual\Views\popup_add_segmentation', $data);
    }

    public function save($ids = ""){
        $team_id = get_team("id");
        $name = post("name");

        $item = db_get("*", TB_STACKPUSH_SEGMENTATION, ["ids" => $ids]);

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
            "changed" => time(),
        ];

        if( empty($item) ){
            $data['ids'] = ids();
            $data['created'] = time();

            db_insert(TB_STACKPUSH_SEGMENTATION, $data);
        }else{
            db_update(TB_STACKPUSH_SEGMENTATION, $data, [ "id" => $item->id ]);
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
                $item = db_get("id", TB_STACKPUSH_SEGMENTATION, ["ids" => $id, "team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID]);
                if($item){
                    db_delete(TB_STACKPUSH_SEGMENTATION, ['id' => $item->id, "team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID]);
                    db_delete(TB_STACKPUSH_SEGMENTATION_MAP, ['segment_id' => $item->id]);
                }
            }
        }
        elseif( is_string($ids) )
        {
            $item = db_get("id", TB_STACKPUSH_SEGMENTATION, ["ids" => $ids, "team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID]);
            if($item){
                db_delete(TB_STACKPUSH_SEGMENTATION, ['id' => $item->id, "team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID]);
                db_delete(TB_STACKPUSH_SEGMENTATION_MAP, ['segment_id' => $item->id]);
            }
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);

    }
}