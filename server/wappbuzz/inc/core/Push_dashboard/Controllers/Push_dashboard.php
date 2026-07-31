<?php
namespace Core\Push_dashboard\Controllers;

class Push_dashboard extends \CodeIgniter\Controller
{
    public function __construct(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: *");
        include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_dashboard\Models\Push_dashboardModel();
    }
    
    public function index( $page = false ) {
        $block_push_dashboard = get_blocks("block_push_dashboard", false, true);

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            'config' => $this->config
        ];

        $info = $this->model->get_info();

        $data_content = [
            'config' => $this->config,
            'info' => $info,
            'block_push_dashboard' => $block_push_dashboard
        ];
        $data['content'] = view('Core\Push_dashboard\Views\content', $data_content);

        return view('Core\Push\Views\index', $data);
    }

    public function insights() {
        $chart = $this->model->chart();
        $data_content = [
            'chart' => $chart,
            'config' => $this->config
        ];

        return view('Core\Push_dashboard\Views\insights', $data_content);
    }
}