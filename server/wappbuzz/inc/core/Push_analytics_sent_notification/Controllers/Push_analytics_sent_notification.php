<?php
namespace Core\Push_analytics_sent_notification\Controllers;

class Push_analytics_sent_notification extends \CodeIgniter\Controller
{
    public function __construct(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: *");
        include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_analytics_sent_notification\Models\Push_analytics_sent_notificationModel();
        $this->per_page = 50;
        $this->fields = [
            "id" => __("ID"),
            "title" => __("Title"),
            "message" => __("Message"),
            "custom_fields" => ["ids", "url"]
        ];
    }

    public function index( $page = false, $ids = "" ) {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            'config' => $this->config
        ];

        switch ($page) {
            case 'notification':
                $result = $this->model->notification_chart($ids);
                if(!$result) redirect_to( get_module_url() );

                $data_content = [
                    'config' => $this->config,
                    'result' => $result,
                    'domain' => unserialize(PUSH_DOMAIN)
                ];

                $data['content'] = view('Core\Push_analytics_sent_notification\Views\notification', $data_content);
                break;
            
            default:
                $data_content = [
                    'config' => $this->config
                ];
                $data['content'] = view('Core\Push_analytics_sent_notification\Views\list', $data_content);
                break;
        }

        

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
            "data" => view('Core\Push_analytics_sent_notification\Views\ajax_list', $data)
        ] );
    }

    public function insights() {
        $start = 0;
        $limit = 1;

        $pager = \Config\Services::pager();
        $total = $this->model->get_list(false);
        $chart = $this->model->chart();

        $datatable = [
            "responsive" => true,
            "columns" => [
                "id" => __("ID"),
                "title" => __("Notification"),
                "number_sent" => __("Sent"),
                "number_delivered" => __("Delivered"),
                "number_action" => __("Clicked"),
            ],
            "total_items" => $total,
            "per_page" => 30,
            "current_page" => 1,

        ];

        $chart = $this->model->chart();
        $data_content = [
            'chart' => $chart,
            'start' => $start,
            'limit' => $limit,
            'total' => $total,
            'pager' => $pager,
            'datatable'  => $datatable,
            'config' => $this->config
        ];

        return view('Core\Push_analytics_sent_notification\Views\insights', $data_content);
    }
}