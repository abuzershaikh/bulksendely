<?php
namespace Core\Push_analytics_geo_location\Controllers;

class Push_analytics_geo_location extends \CodeIgniter\Controller
{
    public function __construct(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: *");
        include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_analytics_geo_location\Models\Push_analytics_geo_locationModel();
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

        $data['content'] = view('Core\Push_analytics_geo_location\Views\content', $data_content);

        return view('Core\Push\Views\index', $data);
    }

    public function insights() {
        $geo = $this->model->geo_chart();

        $data_content = [
            'geo' => $geo,
        ];

        return view('Core\Push_analytics_geo_location\Views\insights', $data_content);
    }
}