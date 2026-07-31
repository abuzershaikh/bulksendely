<?php
namespace Core\Account_manager\Controllers;

class Account_manager extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Account_manager\Models\Account_managerModel();
    }
    
    public function index( $page = false ) {
        $permissions = $this->model->block_permissions();
        $block_accounts = $permissions['items'];

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "content" => view('Core\Account_manager\Views\content', ['block_accounts' => $block_accounts, "config" => $this->config]),
            "block_accounts" => $block_accounts
        ];

        return view('Core\Account_manager\Views\index', $data);
    }

    public function widget( $params = [] ){
        $team_id = get_team("id");

        if(isset($params['wheres']) && is_array($params['wheres'])){
            $wheres["team_id"] = $team_id;
            $accounts = db_fetch("id,ids,pid,name,pid,category,social_network,avatar,login_type,module", TB_ACCOUNTS, $params['wheres']);
        }elseif( isset($params['accounts']) && is_array($params['accounts']) ){
            $field = "id";
            if( isset($params['field']) && $params['field'] != "" ){
                $field = $params['field'];
            }
            $accounts = get_accounts_by( $params['accounts'], $field );
        }elseif( isset($params['account_id']) && $params['account_id'] != "" ){
            $accounts = db_fetch("id,ids,pid,name,pid,category,social_network,avatar,login_type,module", TB_ACCOUNTS, [ "can_post" => 1, "team_id" => $team_id, "id" => $params['account_id'] ], "social_network", "ASC");
        }else{
            $accounts = db_fetch("id,ids,pid,name,pid,category,social_network,avatar,login_type,module", TB_ACCOUNTS, [ "can_post" => 1, "team_id" => $team_id, "status" => 1 ], "social_network", "ASC");
        }

        permission_accounts($accounts);

        return view('Core\Account_manager\Views\widget', ['accounts' => $accounts]);
    }

    public function widget_multi_select( $params = [] ){
        $team_id = get_team("id");
        $accounts = db_fetch("id,ids,pid,name,pid,category,social_network,avatar", TB_ACCOUNTS, "can_post = 1 AND team_id = '{$team_id}' AND status = 1", "social_network", "ASC");

        $selected_accounts = [];
        if( isset($params['accounts']) && !empty($params['accounts']) ){
            $selected_accounts = $params['accounts'];
        }

        return view('Core\Account_manager\Views\widget_multi_select', ['accounts' => $accounts, "selected_accounts" => $selected_accounts]);
    }

    public function delete(){
        $ids = post('id');
        $team_id = get_team('id');

        if( empty($ids) ){
            ms([
                "status" => "error",
                "message" => __('Please select an item to delete')
            ]);
        }

        if( is_array($ids) ){
            foreach ($ids as $id) {
                $this->deleteAccountWithCascade($id, $team_id);
            }
        }
        elseif( is_string($ids) )
        {
            $this->deleteAccountWithCascade($ids, $team_id);
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    /**
     * Delete an account with cascade deletion for WhatsApp-related data
     */
    private function deleteAccountWithCascade($account_ids, $team_id)
    {
        // Get account info to check if it's a WhatsApp account
        $account = db_get("*", TB_ACCOUNTS, ["ids" => $account_ids, "team_id" => $team_id]);

        if ($account && $account->social_network == 'whatsapp' && !empty($account->token)) {
            // Cascade delete WhatsApp-related data
            if (defined('TB_WHATSAPP_AUTORESPONDER')) {
                db_delete(TB_WHATSAPP_AUTORESPONDER, ['instance_id' => $account->token]);
            }
            if (defined('TB_WHATSAPP_CHATBOT')) {
                db_delete(TB_WHATSAPP_CHATBOT, ['instance_id' => $account->token]);
            }
            if (defined('TB_WHATSAPP_SESSIONS')) {
                db_delete(TB_WHATSAPP_SESSIONS, ['instance_id' => $account->token]);
            }
            if (defined('TB_WHATSAPP_WEBHOOK')) {
                db_delete(TB_WHATSAPP_WEBHOOK, ['instance_id' => $account->token]);
            }
            if (defined('TB_WHATSAPP_MESSAGES')) {
                db_delete(TB_WHATSAPP_MESSAGES, ['instance_id' => $account->token]);
            }
            if (defined('TB_WHATSAPP_SUBSCRIBERS')) {
                db_delete(TB_WHATSAPP_SUBSCRIBERS, ['instance_id' => $account->token]);
            }

            // Logout from WhatsApp server
            $access_token = get_team('ids');
            wa_get_curl("logout", ["instance_id" => $account->token, "access_token" => $access_token]);
        }

        // Delete the account record
        db_delete(TB_ACCOUNTS, ['ids' => $account_ids]);
    }
}