<?php
namespace Core\Push_website_settings\Controllers;

class Push_website_settings extends \CodeIgniter\Controller
{
    public function __construct(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: *");
        include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_website_settings\Models\Push_website_settingsModel();
    }
    
    public function index( $page = false ) {

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        $result = db_get("*", TB_STACKPUSH_DOMAINS, ["id" => PUSH_DOMAIN_ID, "team_id" => TEAM_ID]);

        $data_content = [
            'config'  => $this->config,
            'result' => $result
        ];

        $data['content'] = view('Core\Push_website_settings\Views\content', $data_content);

        return view('Core\Push\Views\index', $data);
    }

    public function ios_popup($params = []){
        if(empty($params) || !is_array($params) || !isset($params['domain_id']) || !isset($params['team_id'])) return false;
        
        $domain_id = $params['domain_id'];
        $team_id = $params['team_id'];
        $domain = db_get("*", TB_STACKPUSH_DOMAINS, ["id" => $domain_id, "team_id" => $team_id]);
        if(!$domain) return false;

        $data = [
            "domain" => $domain
        ];

        return view('Core\Push_website_settings\Views\ios_popup', $data);
    }

    public function save(){
        $icon = post("icon");
        $badge = post("badge");
        $utm_source = post("utm_source");
        $utm_medium = post("utm_medium");
        $utm_name = post("utm_name");

        validate('null', __('Default Icon'), $icon);
        validate('null', __('Badge Icon'), $badge);

        $data = [
            "icon" => remove_file_path($icon),
            "badge" => remove_file_path($badge),
            "utm_source" => $utm_source,
            "utm_medium" => $utm_medium,
            "utm_name" => $utm_name,
        ];

        db_update(TB_STACKPUSH_DOMAINS, $data, ["id" => PUSH_DOMAIN_ID]);

        return ms([
            "status" => "success",
            "message" => __("Success")
        ]);
    }

    public function save_ios(){
        $ios_status = (int)post("ios_status");
        $ios_short_name = post("ios_short_name");
        $ios_name = post("ios_name");
        $ios_start_url = post("ios_start_url");
        $ios_bg_color = post("ios_bg_color");
        $ios_theme_color = post("ios_theme_color");
        $ios_icon = post("ios_icon");
        $ios_popup_bg_color = post("ios_popup_bg_color");
        $ios_popup_text_color = post("ios_popup_text_color");
        $ios_popup_text = post("ios_popup_text");

        validate('null', __('Short name'), $ios_short_name);
        validate('null', __('Name'), $ios_name);
        validate('null', __('Start URL'), $ios_start_url);
        validate('null', __('Background color'), $ios_bg_color);
        validate('null', __('IOS Icon'), $ios_icon);
        validate('null', __('HomeScreen popup Background Color'), $ios_popup_bg_color);
        validate('null', __('HomeScreen popup Text Color'), $ios_popup_text_color);
        validate('null', __('HomeScreen popup Text'), $ios_popup_text);

        $data = [
            "ios_status" => $ios_status,
            "ios_icon" => remove_file_path($ios_icon),
            "ios_short_name" => $ios_short_name,
            "ios_name" => $ios_name,
            "ios_start_url" => $ios_start_url,
            "ios_bg_color" => $ios_bg_color,
            "ios_theme_color" => $ios_theme_color,
            "ios_popup_bg_color" => $ios_popup_bg_color,
            "ios_popup_text_color" => $ios_popup_text_color,
            "ios_popup_text" => $ios_popup_text,
        ];

        db_update(TB_STACKPUSH_DOMAINS, $data, ["id" => PUSH_DOMAIN_ID]);

        return ms([
            "status" => "success",
            "message" => __("Success")
        ]);
    }

    public function save_macos(){
        $safari_website_push_id = post("safari_website_push_id");
        $safari_p12_certificate = post("safari_p12_certificate");
        $safari_website_name = post("safari_website_name");
        $safari_allow_subdomains = post("safari_allow_subdomains");
        $safari_website_icon = post("safari_website_icon");

        validate('null', __('Website Push ID'), $safari_website_push_id);
        validate('null', __('p12 certificate'), $safari_p12_certificate);
        validate('null', __('Website name'), $safari_website_name);
        validate('null', __('Website_icon'), $safari_website_icon);

        $data = [
            "safari_website_push_id" => $safari_website_push_id,
            "safari_p12_certificate" => remove_file_path($safari_p12_certificate),
            "safari_website_name" => $safari_website_name,
            "safari_allow_subdomains" => $safari_allow_subdomains,
            "safari_website_icon" => remove_file_path($safari_website_icon),
        ];

        db_update(TB_STACKPUSH_DOMAINS, $data, ["id" => PUSH_DOMAIN_ID]);

        return ms([
            "status" => "success",
            "message" => __("Success")
        ]);
    }
}