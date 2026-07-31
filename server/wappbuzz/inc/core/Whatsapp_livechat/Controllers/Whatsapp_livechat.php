<?php
namespace Core\Whatsapp_livechat\Controllers;

class Whatsapp_livechat extends \CodeIgniter\Controller
{
    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->model = new \Core\Whatsapp_livechat\Models\Whatsapp_livechatModel();
    }

    public function index($page = false, $ids = false)
    {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        $team_id = get_team("id");

        // Get WhatsApp accounts for this team
        $accounts = db_fetch("*", TB_ACCOUNTS, [
            "team_id" => $team_id,
            "social_network" => "whatsapp",
            "category" => "profile",
            "login_type" => 2,
            "status" => 1
        ], "created", "ASC");
        permission_accounts($accounts);

        $data_content = [
            'accounts' => $accounts,
            'config' => $this->config,
        ];

        $data['content'] = view('Core\Whatsapp_livechat\Views\content', $data_content);

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function info()
    {
        $team_id = get_team("id");
        $access_token = get_team("ids");
        $ids = post("account");
        $account = db_get("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "login_type" => 2, "ids" => $ids, "team_id" => $team_id]);

        if (!empty($account)) {
            // Get chat history from database
            $chats = $this->model->get_chats($account->token);

            $data = [
                "status" => "success",
                "account" => $account,
                "access_token" => $access_token,
                "chats" => $chats,
                "config" => $this->config,
                "whatsapp_server_url" => get_option('whatsapp_server_url', ''),
            ];
        } else {
            $data = [
                "status" => "error",
                "message" => __("WhatsApp account does not exist. Please try again or re-login your WhatsApp account")
            ];
        }

        return view('Core\Whatsapp_livechat\Views\info', $data);
    }

    public function get_messages()
    {
        // Check if Live Chat is enabled
        if (get_option('whatsapp_livechat_status', 0) != 1) {
            ms([
                "status" => "error",
                "message" => __("Live Chat is disabled"),
                "disabled" => true
            ]);
        }

        $team_id = get_team("id");
        $instance_id = post("instance_id");
        $remote_jid = post("remote_jid");

        // Debug logging
        log_message('debug', 'get_messages called - team_id: ' . $team_id . ', instance_id: ' . $instance_id . ', remote_jid: ' . $remote_jid);

        $account = db_get("*", TB_ACCOUNTS, ["token" => $instance_id, "team_id" => $team_id]);

        if (empty($account)) {
            log_message('debug', 'get_messages - Account not found for token: ' . $instance_id);
            ms([
                "status" => "error",
                "message" => __("Account not found")
            ]);
        }

        $messages = $this->model->get_messages($instance_id, $remote_jid);
        log_message('debug', 'get_messages - Found ' . count($messages) . ' messages');

        // Mark messages as read when viewing them
        $this->model->mark_as_read($instance_id, $remote_jid);

        ms([
            "status" => "success",
            "messages" => $messages
        ]);
    }

    public function get_contacts()
    {
        // Check if Live Chat is enabled
        if (get_option('whatsapp_livechat_status', 0) != 1) {
            ms([
                "status" => "error",
                "message" => __("Live Chat is disabled"),
                "disabled" => true
            ]);
        }

        $team_id = get_team("id");
        $instance_id = post("instance_id");

        $account = db_get("*", TB_ACCOUNTS, ["token" => $instance_id, "team_id" => $team_id]);

        if (empty($account)) {
            ms([
                "status" => "error",
                "message" => __("Account not found")
            ]);
        }

        $chats = $this->model->get_chats($instance_id);

        ms([
            "status" => "success",
            "contacts" => $chats
        ]);
    }

    public function send()
    {
        // Check if Live Chat is enabled
        if (get_option('whatsapp_livechat_status', 0) != 1) {
            ms([
                "status" => "error",
                "message" => __("Live Chat is disabled"),
                "disabled" => true
            ]);
        }

        $team_id = get_team("id");
        $access_token = get_team("ids");
        $instance_id = post("instance_id");
        $remote_jid = post("remote_jid");
        $message = post("message");
        $medias = post("medias");

        $account = db_get("*", TB_ACCOUNTS, ["token" => $instance_id, "team_id" => $team_id]);

        if (empty($account)) {
            ms([
                "status" => "error",
                "message" => __("Account not found")
            ]);
        }

        validate('null', __('Recipient'), $remote_jid);

        // Prepare media if present
        $media = null;
        if (!empty($medias) && is_array($medias) && permission("whatsapp_send_media")) {
            foreach ($medias as $key => $value) {
                $medias[$key] = get_file_url($value);
            }
            $media = $medias[0];
        }

        // Validate: need either message or media
        if (empty($message) && empty($media)) {
            ms([
                "status" => "error",
                "message" => __("Please enter a message or attach a media file")
            ]);
        }

        // Send message via backend API
        if ($media) {
            $params = [
                "chat_id" => $remote_jid,
                "type" => 1,
                "caption" => $message ?: "",
                "media_url" => $media
            ];
        } else {
            $params = [
                "chat_id" => $remote_jid,
                "caption" => $message
            ];
        }

        $result = wa_post_curl("direct_send_message", ["instance_id" => $instance_id, "access_token" => $access_token, "type" => 1], $params);

        if (isset($result) && $result->status == "success") {
            // Save the sent message to the database for persistence
            $media_type = null;
            if ($media) {
                // Detect media type from URL
                $extension = strtolower(pathinfo(parse_url($media, PHP_URL_PATH), PATHINFO_EXTENSION));
                $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
                $video_extensions = ['mp4', 'avi', 'mov', 'wmv', 'webm', 'mkv', '3gp'];
                $audio_extensions = ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'opus'];

                if (in_array($extension, $image_extensions)) {
                    $media_type = 'imageMessage';
                } elseif (in_array($extension, $video_extensions)) {
                    $media_type = 'videoMessage';
                } elseif (in_array($extension, $audio_extensions)) {
                    $media_type = 'audioMessage';
                } else {
                    $media_type = 'documentMessage';
                }
            }

            // Save to database
            $this->model->save_sent_message($instance_id, $remote_jid, $message, $media, $media_type);

            ms([
                "status" => "success",
                "message" => __("Message sent successfully")
            ]);
        } else {
            $error_msg = isset($result->message) ? $result->message : __("Failed to send message");
            ms([
                "status" => "error",
                "message" => $error_msg
            ]);
        }
    }

    /**
     * Toggle the Live Chat system enable/disable status
     */
    public function toggle_status()
    {
        // Check if user is admin or has permission
        $team_id = get_team("id");
        if (empty($team_id)) {
            ms([
                "status" => "error",
                "message" => __("Unauthorized access")
            ]);
        }

        $status = post("status");
        $status = ($status == 1) ? 1 : 0;

        // Save to options table
        try {
            $db = \Config\Database::connect();

            // Check if option exists
            $existing = db_get("*", TB_OPTIONS, ["name" => "whatsapp_livechat_status"]);

            if ($existing) {
                // Update existing
                $db->table(TB_OPTIONS)->where("name", "whatsapp_livechat_status")->update(["value" => $status]);
            } else {
                // Insert new
                $db->table(TB_OPTIONS)->insert([
                    "name" => "whatsapp_livechat_status",
                    "value" => $status
                ]);
            }

            ms([
                "status" => "success",
                "message" => $status ? __("Live Chat enabled") : __("Live Chat disabled"),
                "livechat_status" => $status
            ]);
        } catch (\Exception $e) {
            log_message('error', 'toggle_status error: ' . $e->getMessage());
            ms([
                "status" => "error",
                "message" => __("Failed to update status")
            ]);
        }
    }

    /**
     * Get the current Live Chat status
     */
    public function get_status()
    {
        $status = get_option('whatsapp_livechat_status', 0);
        ms([
            "status" => "success",
            "livechat_status" => (int)$status
        ]);
    }
}
