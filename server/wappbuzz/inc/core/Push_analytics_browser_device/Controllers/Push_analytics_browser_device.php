<?php
namespace Core\Push_analytics_browser_device\Controllers;

class Push_analytics_browser_device extends \CodeIgniter\Controller
{
    public function __construct(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: *");
        include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_analytics_browser_device\Models\Push_analytics_browser_deviceModel();
        $this->model = new \Core\Push_analytics_browser_device\Models\Push_analytics_browser_deviceModel();
    }
    
    public function index( $page = false ) {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "config" => $this->config,
        ];

        $data_content = [
            'config' => $this->config,
        ];

        $data['content'] = view('Core\Push_analytics_browser_device\Views\content', $data_content);

        return view('Core\Push\Views\index', $data);
    }

    public function insights() {
        $device = $this->model->device_chart();
        $browser = $this->model->browser_chart();
        $os = $this->model->os_chart();

        $data_content = [
            'device' => $device,
            'browser' => $browser,
            'os' => $os,
        ];

        return view('Core\Push_analytics_browser_device\Views\insights', $data_content);
    }
}