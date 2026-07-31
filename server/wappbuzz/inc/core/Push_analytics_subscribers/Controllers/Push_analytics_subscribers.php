<?php
namespace Core\Push_analytics_subscribers\Controllers;

class Push_analytics_subscribers extends \CodeIgniter\Controller
{
    public function __construct(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: *");
        include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_analytics_subscribers\Models\Push_analytics_subscribersModel();
    }
    
    public function index( $page = false ) {
        $subscriber = $this->model->subscriber_chart();

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "config" => $this->config,
        ];

        $data_content = [
            'config' => $this->config,
            'subscriber' => $subscriber,
        ];

        $data['content'] = view('Core\Push_analytics_subscribers\Views\content', $data_content);

        return view('Core\Push\Views\index', $data);
    }

    public function insights() {
        $subscriber = $this->model->subscriber_chart();

        $data_content = [
            'subscriber' => $subscriber,
        ];

        return view('Core\Push_analytics_subscribers\Views\insights', $data_content);
    }
}