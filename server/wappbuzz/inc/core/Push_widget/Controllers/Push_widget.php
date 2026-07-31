<?php
namespace Core\Push_widget\Controllers;

class Push_widget extends \CodeIgniter\Controller
{
    public function __construct(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: *");
        include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_widget\Models\Push_widgetModel();
    }
    
    public function index( $page = false ) {
        $widget_options = [
            "widget_status" => get_push_option("widget_status", get_option("push_widget_status", 1) ),
            "widget_bottom" => get_push_option("widget_bottom", get_option("push_widget_bottom", 15) ),
            "widget_right" => get_push_option("widget_right", get_option("push_widget_right", 15) ),
            "widget_left" => get_push_option("widget_left", get_option("push_widget_left", 15) ),
            "widget_bg" => get_push_option("widget_bg", get_option("push_widget_bg", "#0055ff") ),
            "widget_icon" =>  get_push_option("widget_icon", get_option("push_widget_icon", base_url("inc/core/Push_widget/Assets/img/bell_icon.png") ) ),
            "widget_position" => get_push_option("widget_position", get_option("push_widget_position", "right") ),
        ];

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "widget_options" => $widget_options
        ];

        $data_content = [
            'config'  => $this->config,
            "widget_options" => $widget_options
        ];

        $data['content'] = view('Core\Push_widget\Views\content', $data_content);

        return view('Core\Push_widget\Views\index', $data);
    }

    public function save(){
        $data = $this->request->getPost();

        if(is_array($data)){
            foreach ($data as $key => $value) {
                if($key != 'csrf'){
                    update_push_option( $key, trim( htmlspecialchars( $value ) ), PUSH_DOMAIN_ID);
                }
            }
        }

        ms([
            "status"  => "success",
            "message" => __('Success'),
        ]);
    }

    public function reset(){
        update_push_option("widget_status", get_option("push_widget_status", 1), PUSH_DOMAIN_ID);
        update_push_option("widget_bottom", get_option("push_widget_bottom", 15), PUSH_DOMAIN_ID);
        update_push_option("widget_right", get_option("push_widget_right", 15), PUSH_DOMAIN_ID);
        update_push_option("widget_left", get_option("push_widget_left", 15), PUSH_DOMAIN_ID);
        update_push_option("widget_bg", get_option("push_widget_bg", "#0055ff"), PUSH_DOMAIN_ID);
        update_push_option("widget_icon", base_url("inc/core/Push_widget/Assets/img/bell_icon.png"), PUSH_DOMAIN_ID);
        update_push_option("widget_position", get_option("push_widget_position", "right"), PUSH_DOMAIN_ID);

        ms([
            "status"  => "success",
            "message" => __('Success'),
        ]);
    }
}