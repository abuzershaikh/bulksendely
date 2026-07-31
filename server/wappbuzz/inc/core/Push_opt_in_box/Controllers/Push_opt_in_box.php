<?php
namespace Core\Push_opt_in_box\Controllers;

class Push_opt_in_box extends \CodeIgniter\Controller
{
    public function __construct(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: *");
        include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_opt_in_box\Models\Push_opt_in_boxModel();
    }
    
    public function index( $page = false ) {

        $opt_options = [
            "opt_theme" => get_push_option("opt_theme", get_option("push_opt_theme", "box") ),
            "opt_trigger" => get_push_option("opt_trigger", get_option("push_opt_trigger", "on_landing") ),
            "opt_on_scroll" => get_push_option("opt_on_scroll", get_option("push_opt_on_scroll", "20") ),
            "opt_on_inactivity" => get_push_option("opt_on_inactivity", get_option("push_opt_on_inactivity", "7") ),
            "opt_on_pageviews" => get_push_option("opt_on_pageviews", get_option("push_opt_on_pageviews", "3") ),
            "opt_opacity" => get_push_option("opt_opacity", get_option("push_opt_opacity", "0") ),
            "opt_bg" => get_push_option("opt_bg", get_option("push_opt_bg", "#fff") ),
            "opt_delay" => get_push_option("opt_delay", get_option("push_opt_delay", "3") ),
            "opt_position" => get_push_option("opt_position", get_option("push_opt_position", "top") ),
            "opt_allow_btn_bg" => get_push_option("opt_allow_btn_bg", get_option("push_opt_allow_btn_bg", "#00b0ff") ),
            "opt_allow_btn_text" => get_push_option("opt_allow_btn_text", get_option("push_opt_allow_btn_text", "#fff") ),
            "opt_deny_btn_bg" => get_push_option("opt_deny_btn_bg", get_option("push_opt_deny_btn_bg", "#f3f3f3") ),
            "opt_deny_btn_text" => get_push_option("opt_deny_btn_text", get_option("push_opt_deny_btn_text", "#717171") ),
            "opt_title" => get_push_option("opt_title", get_option("push_opt_title", "Get our Latest News and Updates") ),
            "opt_desc" => get_push_option("opt_desc", get_option("push_opt_desc", "Click on Allow to get notifications") ),
            "opt_banner" => get_push_option("opt_banner", get_option("push_opt_banner", "") ),
        ];

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "opt_options" => $opt_options,
        ];

        $data_content = [
            'config'  => $this->config,
            "opt_options" => $opt_options,
            "domain" => unserialize(PUSH_DOMAIN)
        ];

        $data['content'] = view('Core\Push_opt_in_box\Views\content', $data_content);

        return view('Core\Push_opt_in_box\Views\index', $data);
    }

    public function popup($params = []){
        
        if(empty($params) || !is_array($params) || !isset($params['domain_id']) || !isset($params['team_id'])) return false;
        
        $domain_id = $params['domain_id'];
        $team_id = $params['team_id'];
        $domain = db_get("*", TB_STACKPUSH_DOMAINS, ["id" => $domain_id, "team_id" => $team_id]);
        if(!$domain) return false;

        $opt_options = [
            "opt_theme" => get_push_option("opt_theme", get_option("push_opt_theme", "box"), $domain_id),
            "opt_trigger" => get_push_option("opt_trigger", get_option("push_opt_trigger", "on_landing"), $domain_id),
            "opt_on_scroll" => get_push_option("opt_on_scroll", get_option("push_opt_on_scroll", "20"), $domain_id),
            "opt_on_inactivity" => get_push_option("opt_on_inactivity", get_option("push_opt_on_inactivity", "7"), $domain_id),
            "opt_on_pageviews" => get_push_option("opt_on_pageviews", get_option("push_opt_on_pageviews", "3"), $domain_id),
            "opt_opacity" => get_push_option("opt_opacity", get_option("push_opt_opacity", "0"), $domain_id),
            "opt_bg" => get_push_option("opt_bg", get_option("push_opt_bg", "#fff"), $domain_id),
            "opt_delay" => get_push_option("opt_delay", get_option("push_opt_delay", "3"), $domain_id),
            "opt_position" => get_push_option("opt_position", get_option("push_opt_position", "top"), $domain_id),
            "opt_allow_btn_bg" => get_push_option("opt_allow_btn_bg", get_option("push_opt_allow_btn_bg", "#00b0ff"), $domain_id),
            "opt_allow_btn_text" => get_push_option("opt_allow_btn_text", get_option("push_opt_allow_btn_text", "#fff"), $domain_id),
            "opt_deny_btn_bg" => get_push_option("opt_deny_btn_bg", get_option("push_opt_deny_btn_bg", "#f3f3f3"), $domain_id),
            "opt_deny_btn_text" => get_push_option("opt_deny_btn_text", get_option("push_opt_deny_btn_text", "#717171"), $domain_id),
            "opt_title" => get_push_option("opt_title", get_option("push_opt_title", "Get our Latest News and Updates"), $domain_id),
            "opt_desc" => get_push_option("opt_desc", get_option("push_opt_desc", "Click on Allow to get notifications"), $domain_id),
            "opt_banner" => get_push_option("opt_banner", get_option("push_opt_banner", ""), $domain_id),
            "widget_status" => get_push_option("widget_status", get_option("push_widget_status", 1), $domain_id),
            "widget_bottom" => get_push_option("widget_bottom", get_option("push_widget_bottom", 15), $domain_id),
            "widget_right" => get_push_option("widget_right", get_option("push_widget_right", 15), $domain_id),
            "widget_left" => get_push_option("widget_left", get_option("push_widget_left", 15), $domain_id),
            "widget_bg" => get_push_option("widget_bg", get_option("push_widget_bg", "#0055ff"), $domain_id),
            "widget_icon" => get_push_option("widget_icon", get_option("push_widget_icon", base_url("inc/core/Push_widget/Assets/img/bell_icon.png") ), $domain_id),
            "widget_position" => get_push_option("widget_position", get_option("push_widget_position", "right"), $domain_id)
        ];

        $data = [
            "opt_options" => $opt_options,
            "domain" => $domain
        ];

        switch ( $opt_options["opt_theme"] ) {
            case 'none':
                return view('Core\Push_opt_in_box\Views\themes\none', $data);
                break;

            case 'box':
                return view('Core\Push_opt_in_box\Views\themes\box', $data);
                break;

            case 'popup':
                return view('Core\Push_opt_in_box\Views\themes\popup', $data);
                break;

            case 'mini':
                return view('Core\Push_opt_in_box\Views\themes\mini', $data);
                break;

            case 'bar':
                return view('Core\Push_opt_in_box\Views\themes\bar', $data);
                break;

            case 'native':
                return view('Core\Push_opt_in_box\Views\themes\native', $data);
                break;
            
            default:
                return view('Core\Push_opt_in_box\Views\themes\box', $data);
                break;
        }
    }

    public function save(){
        $data = $this->request->getPost();

        if(is_array($data)){
            if(!isset($data['opt_banner'])){
                $data['opt_banner'] = "";
            }
            
            foreach ($data as $key => $value) {
                if($key != 'csrf'){
                    if(is_string($value)){
                        update_push_option( $key, trim( htmlspecialchars( $value ) ), PUSH_DOMAIN_ID);
                    }elseif(is_array($value) && !empty($value) ){
                        update_push_option( $key, trim( htmlspecialchars( $value[0] ) ), PUSH_DOMAIN_ID);
                    }
                }
            }
        }

        ms([
            "status"  => "success",
            "message" => __('Success'),
        ]);
    }

    public function reset(){
        update_push_option("opt_theme", get_option("push_opt_theme", "box"), PUSH_DOMAIN_ID);
        update_push_option("opt_trigger", get_option("push_opt_trigger", "on_landing"), PUSH_DOMAIN_ID);
        update_push_option("opt_on_scroll", get_option("push_opt_on_scroll", "20"), PUSH_DOMAIN_ID);
        update_push_option("opt_on_inactivity", get_option("push_opt_on_inactivity", "7"), PUSH_DOMAIN_ID);
        update_push_option("opt_on_pageviews", get_option("push_opt_on_pageviews", "3"), PUSH_DOMAIN_ID);
        update_push_option("opt_opacity", get_option("push_opt_opacity", "0"), PUSH_DOMAIN_ID);
        update_push_option("opt_bg", get_option("push_opt_bg", "#fff"), PUSH_DOMAIN_ID);
        update_push_option("opt_delay", get_option("push_opt_delay", "3"), PUSH_DOMAIN_ID);
        update_push_option("opt_position", get_option("push_opt_position", "top"), PUSH_DOMAIN_ID);
        update_push_option("opt_allow_btn_bg", get_option("push_opt_allow_btn_bg", "#00b0ff"), PUSH_DOMAIN_ID);
        update_push_option("opt_allow_btn_text", get_option("push_opt_allow_btn_text", "#fff"), PUSH_DOMAIN_ID);
        update_push_option("opt_deny_btn_bg", get_option("push_opt_deny_btn_bg", "#f3f3f3"), PUSH_DOMAIN_ID);
        update_push_option("opt_deny_btn_text", get_option("push_opt_deny_btn_text", "#717171"), PUSH_DOMAIN_ID);
        update_push_option("opt_title", get_option("push_opt_title", "Get our Latest News and Updates"), PUSH_DOMAIN_ID);
        update_push_option("opt_desc", get_option("push_opt_desc", "Click on Allow to get notifications"), PUSH_DOMAIN_ID);

        ms([
            "status"  => "success",
            "message" => __('Success'),
        ]);
    }
}