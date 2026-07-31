<?php
namespace Core\Whatsapp_send_message\Controllers;

class Whatsapp_send_message extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Whatsapp_send_message\Models\Whatsapp_send_messageModel();
    }
    
    public function index( $page = false ) {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        $team_id = get_team("id");
        $accounts = db_fetch("*", TB_ACCOUNTS, [ "social_network" => "whatsapp", "category" => "profile", "login_type" => 2, "team_id" => $team_id, "status" => 1], "created", "ASC");
        permission_accounts($accounts);

        $data_content = [
            "config" => $this->config,
            "accounts" => $accounts
        ];

        $data['content'] = view('Core\Whatsapp_send_message\Views\content', $data_content );

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function info() {
        $team_id = get_team("id");
        $access_token = get_team("ids");
        $ids = post("account");
        $account = db_get("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "login_type" => 2, "ids" => $ids, "team_id" => $team_id]);

        if(!empty($account) || $ids == "all"){
            $result = false;
            if( !empty($account) ){
                $result = db_get("*", TB_WHATSAPP_AUTORESPONDER, [ "instance_id" => $account->token, "team_id" => $team_id ]);
            }

            $data = [
                "status" => "success",
                "result" => false,
                "account" => $account,
                "access_token" => $access_token,
            ];

        }else{
            $data = [
                "status" => "error",
                "message" => "WhatsApp account does not exist. Please try again or re-login your WhatsApp account"
            ];

        }

        return view('Core\Whatsapp_send_message\Views\info', $data);
    }

    public function send(){
        $team_id = get_team("id");
        
        $medias = post("medias");
    
        $caption = post('caption');
        $instance_id = post('instance_id');
        
        $send_to = post('send_to');
        $type = (int)post("type");
        $template = 0;
        $btn_msg = (int)post("btn_msg");
        $list_msg = (int)post("list_msg");
        $account = false;
        $access_token = get_team("ids");

        

        if($instance_id != ""){
            $account = db_get("*", TB_ACCOUNTS, ["token" => $instance_id, "team_id" => $team_id]);

            if(empty($account)){
                ms([
                    "status" => "error",
                    "message" => __('Profile does not exist')
                ]);
            }
        }

        switch ($type) {
            case 1:
                if( permission("whatsapp_send_media") ){
                    if(!is_array($medias) && $caption == ""){
                        ms([
                            "status" => "error",
                            "message" => __('Please enter a caption or add a media')
                        ]);
                    }
                }else{
                    validate('null', __('Caption'), $caption);
                }
                break;

            case 2:
                if($btn_msg == 0){
                    ms([
                        "status" => "error",
                        "message" => __('Please select a button message option')
                    ]);
                }
                $template = $btn_msg;
                break;

            case 3:
                if($list_msg == 0){
                    ms([
                        "status" => "error",
                        "message" => __('Please select a list message option')
                    ]);
                }

                $template = $list_msg;
                break;

            case 4:
                if($btn_msg == 0){
                    ms([
                        "status" => "error",
                        "message" => __('Please select a poll message option')
                    ]);
                }
                $template = $btn_msg;
                break;
            
            default:
                if($btn_msg == 0){
                    ms([
                        "status" => "error",
                        "message" => __('Invalid input data')
                    ]);
                }
                break;
        }

        if(!empty($medias) && permission("whatsapp_send_media")){
            foreach ($medias as $key => $value) {
                $medias[$key] = get_file_url($value);
            }

            $media = $medias[0];
        }else{
            $media = NULL;
        }


        if(!empty($advance_options) && isset($advance_options['shortlink'])){
            $shortlink_by = shortlink_by(['advance_options' => [ 'shortlink' => $advance_options['shortlink'] ]]);
            $caption = shortlink($caption, $shortlink_by);
        }

        if(!empty($account)){
            if(isset($media) && $media != NULL && $template == 0){
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https://'.$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST'];
                $path = str_replace($protocol.'/writeable/', '', $media);
                $info = db_get("*", TB_FILES, ["file" => $path ]);
                if(isset($info) && $info->detect == "pdf" && $info->detect == "doc" && $info->detect == "csv" && $info->detect == "other"){
                    $params = [
                        "chat_id" => $send_to."@s.whatsapp.net",
                        "type" => 1,
                        "caption" => $caption,
                        "media_url" => $media,
                        "filename" => $info->name
                    ];
                }else{
                    $params = [
                        "chat_id" => $send_to."@s.whatsapp.net",
                        "type" => $type,
                        "caption" => $caption,
                        "media_url" => $media
                    ];
                }
            }else{
                if($template == 0){
                    $params = [
                        "chat_id" => $send_to."@s.whatsapp.net",
                        "caption" => $caption
                    ];
                }else{
                    $params = [
                        "chat_id" => $send_to."@s.whatsapp.net",
                        "template" => $template
                    ];
                }
            }
            
            $result = wa_post_curl("direct_send_message", ["instance_id" => $instance_id, "access_token" => $access_token, "type" => $type], $params);
            
            
            if(isset($result) && $result->status == "success"){
                //ms($result);
                ms(["status" => "success","message" => "Success"]);
            }else{
                //ms($result);
                ms(["status" => "error","message" => "Cannot send Message"]);
            }
        }else{
            ms([
            "status" => "error",
            "message" => __('Relogin required')
        ]);
        }
    }
}