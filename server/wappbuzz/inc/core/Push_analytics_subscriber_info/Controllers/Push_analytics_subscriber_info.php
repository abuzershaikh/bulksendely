<?php
namespace Core\Push_analytics_subscriber_info\Controllers;

class Push_analytics_subscriber_info extends \CodeIgniter\Controller
{
    public function __construct(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: *");
        include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_analytics_subscriber_info\Models\Push_analytics_subscriber_infoModel();
    }
    
    public function index( $page = false ) {
        $data = [
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
            'datatable'  => $datatable,
            'config' => $this->config
        ];

        $data['content'] = view('Core\Push_analytics_subscriber_info\Views\content', $data_content);

        return view('Core\Push\Views\index', $data);
    }

    public function ajax_list(){
        $total_items = $this->model->get_list(false);
        $result = $this->model->get_list(true);
        $actions = get_blocks("block_action_user", false);
        $data = [
            "result" => $result,
            "actions" => $actions,
            "total_items" => $total_items,
        ];
        ms( [
            "total_items" => $total_items,
            "data" => view('Core\Push_analytics_subscriber_info\Views\ajax_list', $data)
        ] );
    }
}