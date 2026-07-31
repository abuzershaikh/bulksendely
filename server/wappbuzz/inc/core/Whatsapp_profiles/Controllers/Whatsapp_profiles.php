<?php
namespace Core\Whatsapp_profiles\Controllers;

class Whatsapp_profiles extends \CodeIgniter\Controller
{
    public function __construct(){
        $reflect = new \ReflectionClass(get_called_class());
        $this->module = strtolower( $reflect->getShortName() );
        $this->config = include realpath( __DIR__."/../Config.php" );
        $this->whatsapp_server_url = get_option('whatsapp_server_url', '');

        if($this->whatsapp_server_url == ""){
            redirect_to( base_url("social_network_settings/index/".$this->config['parent']['id']) ); 
        }
    }
    
    public function index() {
        redirect_to( get_module_url("oauth") );
    }

    public function oauth($instance_id = false){
        $team_id = get_team("id");
        $content_data = [ "config" => $this->config ];

        $account = db_get("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "category" => "profile", "token" => $instance_id, "team_id" => $team_id]);
        $accounts = db_fetch("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "category" => "profile", "team_id" => $team_id, "status" => 0]);
        $content_data['accounts'] = $accounts;

        if(empty($account)){
            $session = db_get("*", TB_WHATSAPP_SESSIONS,["status" => 0, "team_id" => $team_id]);
            if(empty($session)){
                $instance_id = strtoupper(uniqid());
                db_delete(TB_WHATSAPP_SESSIONS, ["status" => 0, "team_id" => $team_id]);
                db_insert( TB_WHATSAPP_SESSIONS, [
                    "ids" => ids(),
                    "instance_id" => $instance_id,
                    "team_id" => $team_id,
                    "data" => NULL,
                    "status" => 0
                ] );

                $content_data['instance_id'] = $instance_id;
            }else{
                $content_data['instance_id'] = $session->instance_id;
            }
        }else{
            db_update(TB_WHATSAPP_SESSIONS, [ 'status' => 0], [ 'instance_id' => $account->token ]);
            $content_data['instance_id'] = $instance_id;
        }
        
        $content_data["has_pair"] = false;
        $content_data["pair_code"] = "";
        $content_data["error_msg"] = "";
        $content_data["has_error"] = false;
        
        if(isset($_POST['phone'])){
            $account = db_get("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "category" => "profile", "token" => $instance_id, "team_id" => $team_id]);
            $access_token = get_team("ids");
            if($account){
                $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "status" => 0]);
                if($session){
                    if($session->instance_id != $instance_id){
                        db_update( TB_WHATSAPP_SESSIONS, [
                            "instance_id" => $instance_id,
                            "status" => 0
                        ], [ 'id' => $session->id ] );
                    }else{
                        db_insert( TB_WHATSAPP_SESSIONS, [
                            "ids" => ids(),
                            "instance_id" => $instance_id,
                            "team_id" => $team_id,
                            "data" => NULL,
                            "status" => 0] );
                    }
                }
            }else{
                if(!check_number_account("whatsapp", "profile", false, false)){
                    return false;
                    $content_data["has_pair"] = false;
                }
            }
            
            $results = wa_get_curl("get_qrcode", [ "instance_id" => $instance_id, "access_token" => $access_token ]);
            $result = wa_get_curl("get_paircode", [ "instance_id" => $_POST['instance_id'], "access_token" => $access_token, "phone" => $_POST['phone'] ]);
            
            if(isset($results) && isset($result) && $result->status == "success"){
                
                $content_data["has_pair"] = true;
                $content_data["pair_code"] = $result->code;
                $content_data["has_error"] = false;
            }else if(isset($result) && $result->status == "error"){
                $content_data["error_msg"] = $result->message;
                
                $content_data["has_error"] = true;
                $content_data["has_pair"] = true;
            }else{
                $content_data["has_error"] = true;
                $content_data["has_pair"] = false;
                $content_data["error_msg"] = __("Cannot connect to WhatsApp server. Please make sure the WhatsApp server running."). "</br>" . __("You can follow by documentation at <a href='#' target='_blank'>here</a>");
            }
        }

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "config" => $this->config,
            "content" => view('Core\Whatsapp_profiles\Views\oauth', $content_data)
        ];

        return view('Core\Whatsapp_profiles\Views\index', $data);
    }

    public function get_qrcode($instance_id = false){
        $team_id = get_team("id");
        $access_token = get_team("ids");

        $account = db_get("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "category" => "profile", "token" => $instance_id, "team_id" => $team_id]);
        if($account){
            $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "status" => 0]);
            if($session){
                if($session->instance_id != $instance_id){
                    db_update( TB_WHATSAPP_SESSIONS, [
                        "instance_id" => $instance_id,
                        "status" => 0
                    ], [ 'id' => $session->id ] );
                }
            }else{
                db_insert( TB_WHATSAPP_SESSIONS, [
                    "ids" => ids(),
                    "instance_id" => $instance_id,
                    "team_id" => $team_id,
                    "data" => NULL,
                    "status" => 0
                ] );
            }
        }else{
            if(!check_number_account("whatsapp", "profile", false, false)){
                return false;
            }
        }

        // Avoid forced resets here; they can interrupt the auth handshake right after scan.
        $result = wa_get_curl("get_qrcode", [ "instance_id" => $instance_id, "access_token" => $access_token ]);
        if($result == ""){
            ms([
                "status" => "error",
                "message" => __("Cannot connect to WhatsApp server. Please make sure the WhatsApp server running."). "</br>" . __("You can follow by documentation at <a href='#' target='_blank'>here</a>")
            ]);
        }

        if( $result->status == "error" ){
            ms([
                "status" => "error",
                "message" => __( $result->message )
            ]);
        }else{
            ms($result);
        }
    }

    public function check_login($instance_id = ""){
        $team_id = get_team("id");
        $whatsapp_session = db_get("*", TB_WHATSAPP_SESSIONS, ["status" => 1, "team_id" => $team_id, "instance_id" => $instance_id]);
        
        if($whatsapp_session){

            $profile = false;
            if($whatsapp_session->data != ""){
                $profile = json_decode($whatsapp_session->data);
            }

            $account = db_get("*", TB_ACCOUNTS, ["token" => $instance_id, "team_id" => $team_id]);

            if(!$account){
                $account = db_get("*", TB_ACCOUNTS, ["pid" => $profile->id, "team_id" => $team_id]);
            }

            if($account){
                $avatar = save_img( $account->avatar, WRITEPATH.'avatar/' );
                db_update(TB_ACCOUNTS, ["avatar" => $avatar], ['id' => $account->id]);
                
                ms([
                    "status" => "success",
                    "message" => __("Success")
                ]);
            }
        }

        ms([
            "status" => "error",
            "message" => __("Unsuccess")
        ]);
    }

    public function delete(){
        $ids = post('id');
        $team_id = get_team('id');
        $access_token = get_team('ids');

        if( empty($ids) ){
            ms([
                "status" => "error",
                "message" => __('Please select an item to delete')
            ]);
        }

        if( is_array($ids) ){
            foreach ($ids as $id) {

                $account = db_get("*", TB_ACCOUNTS, ["ids" => $id, "team_id" => $team_id]);
                if($account){
                    db_delete(TB_ACCOUNTS, ['ids' => $id]);
                    db_delete(TB_WHATSAPP_AUTORESPONDER, ['instance_id' => $account->token]);
                    db_delete(TB_WHATSAPP_CHATBOT, ['instance_id' => $account->token]);
                    db_delete(TB_WHATSAPP_SESSIONS, ['instance_id' => $account->token]);
                    db_delete(TB_WHATSAPP_WEBHOOK, ['instance_id' => $account->token]);
                    // Cascade delete messages and subscribers for this WhatsApp account
                    db_delete(TB_WHATSAPP_MESSAGES, ['instance_id' => $account->token]);
                    db_delete(TB_WHATSAPP_SUBSCRIBERS, ['instance_id' => $account->token]);
                    wa_get_curl("logout", [ "instance_id" => $account->token, "access_token" => $access_token ]);
                }
            }
        }
        elseif( is_string($ids) )
        {
            $account = db_get("*", TB_ACCOUNTS, ["ids" => $ids, "team_id" => $team_id]);
            if($account){ // Fixed: was "if(!$account)" which is a bug
                db_delete(TB_ACCOUNTS, ['ids' => $ids]);
                db_delete(TB_WHATSAPP_AUTORESPONDER, ['instance_id' => $account->token]);
                db_delete(TB_WHATSAPP_CHATBOT, ['instance_id' => $account->token]);
                db_delete(TB_WHATSAPP_SESSIONS, ['instance_id' => $account->token]);
                db_delete(TB_WHATSAPP_WEBHOOK, ['instance_id' => $account->token]);
                // Cascade delete messages and subscribers for this WhatsApp account
                db_delete(TB_WHATSAPP_MESSAGES, ['instance_id' => $account->token]);
                db_delete(TB_WHATSAPP_SUBSCRIBERS, ['instance_id' => $account->token]);
                wa_get_curl("logout", [ "instance_id" => $account->token, "access_token" => $access_token ]);
            }
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }
}
