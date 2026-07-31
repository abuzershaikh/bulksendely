<?php

namespace Core\Whatsapp_api\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Whatsapp_api extends Controller
{
    use ResponseTrait;

    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->model = new \Core\Whatsapp_api\Models\Whatsapp_apiModel();
    }

    public function index($page = false)
    {
        if (!permission("whatsapp_api")) {
            redirect_to(base_url());
        }

        $account = post("account") ?? '609ACF283XXXX';

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
        ];

        $team_id = get_team("id");
        $accounts = db_fetch("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "category" => "profile", "login_type" => 2, "team_id" => $team_id, "status" => 1], "created", "ASC");
        permission_accounts($accounts);

        $data_content = [
            "config" => $this->config,
            "accounts" => $accounts,
            "account" => $account
        ];

        $data['content'] = view('Core\Whatsapp_api\Views\content', $data_content);

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function get_team($ids = "")
    {
        if ($ids == "") {
            $ids = post("access_token");
        }

        if (!$ids) {
            ms([
                "status" => "error",
                "message" => __("Access token is required")
            ]);
        }

        $ids = addslashes($ids);
        $item = db_get("*", TB_TEAM, ["ids" => $ids]);
        if (!$item) {
            ms([
                "status" => "error",
                "message" => __("Access token does not exist")
            ]);
        }
        return $item;
    }

    public function get_instance_id($instance_id = "")
    {
        if ($instance_id == "") {
            $instance_id = post("instance_id");
        }

        if (!$instance_id) {
            ms([
                "status" => "error",
                "message" => __("Instance ID is required")
            ]);
        }

        return addslashes($instance_id);
    }

    public function create_instance()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $permissions = json_decode($team->permissions);

        //Check limit number 
        check_number_account("whatsapp", "profile", $team->id);

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "status" => 0]);

        if (!$session) {
            $instance_id = strtoupper(uniqid());
            db_insert(TB_WHATSAPP_SESSIONS, [
                "ids" => ids(),
                "instance_id" => $instance_id,
                "team_id" => $team_id,
                "data" => NULL,
                "status" => 0
            ]);
        } else {
            $instance_id = $session->instance_id;
        }

        return $this->respond([
            "status" => "success",
            "message" => __("Instance ID generated successfully"),
            "instance_id" => $instance_id,
            "data" => [
                "instance_id" => $instance_id
            ]
        ], 200);
    }

    public function instances()
    {
        $team = self::get_team();
        $team_id = $team->id;

        $db = \Config\Database::connect();
        $sessions = db_fetch("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id]);
        $accounts = db_fetch("*", TB_ACCOUNTS, [
            "social_network" => "whatsapp",
            "category" => "profile",
            "team_id" => $team_id
        ]);

        $accountByToken = [];
        if (is_array($accounts)) {
            foreach ($accounts as $account) {
                if (!empty($account->token)) {
                    $accountByToken[(string) $account->token] = $account;
                }
            }
        }

        $items = [];
        $currentInstances = 0;
        if (is_array($sessions)) {
            foreach ($sessions as $session) {
                $account = $accountByToken[(string) ($session->instance_id ?? '')] ?? null;
                $accountStatus = $account ? (int) ($account->status ?? 0) : 0;
                $connected = ((int) ($session->status ?? 0) === 1) && $accountStatus === 1;
                if ($connected) {
                    $currentInstances++;
                }

                $items[] = [
                    "instance_id" => (string) ($session->instance_id ?? ''),
                    "linkedNumber" => $account->phone ?? $account->wid ?? $account->user ?? $account->name ?? $account->username ?? null,
                    "linkedName" => $account->name ?? $account->fullname ?? $account->username ?? null,
                    "connected" => $connected,
                    "healthy" => $connected,
                    "wsState" => $connected ? "OPEN" : (((int) ($session->status ?? 0) === 0) ? "CONNECTING" : "OPEN"),
                    "account_status" => $accountStatus,
                    "login_type" => $account ? (int) ($account->login_type ?? 0) : 0,
                ];
            }
        }

        return $this->respond([
            "status" => "success",
            "data" => [
                "max_instances" => 10,
                "current_instances" => $currentInstances,
                "instances" => $items,
            ]
        ], 200);
    }

    public function active_instance()
    {
        $team = self::get_team();
        $team_id = $team->id;

        $sessions = db_fetch("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id]);
        $accounts = db_fetch("*", TB_ACCOUNTS, [
            "social_network" => "whatsapp",
            "category" => "profile",
            "team_id" => $team_id
        ]);

        $accountByToken = [];
        if (is_array($accounts)) {
            foreach ($accounts as $account) {
                if (!empty($account->token)) {
                    $accountByToken[(string) $account->token] = $account;
                }
            }
        }

        if (is_array($sessions)) {
            foreach ($sessions as $session) {
                $account = $accountByToken[(string) ($session->instance_id ?? '')] ?? null;
                $accountStatus = $account ? (int) ($account->status ?? 0) : 0;
                $connected = ((int) ($session->status ?? 0) === 1) && $accountStatus === 1;
                if (!$connected) {
                    continue;
                }

                return $this->respond([
                    "status" => "success",
                    "data" => [
                        "instance_id" => (string) ($session->instance_id ?? ''),
                        "wid" => $account->phone ?? $account->wid ?? $account->user ?? $account->name ?? $account->username ?? null,
                        "user" => $account->phone ?? $account->wid ?? $account->user ?? $account->name ?? $account->username ?? null,
                        "name" => $account->name ?? $account->fullname ?? $account->username ?? null,
                        "wsState" => "OPEN",
                        "connected" => true,
                        "healthy" => true,
                    ]
                ], 200);
            }
        }

        return $this->respond([
            "status" => "success",
            "data" => null
        ], 200);
    }

    public function instance()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $instance_id = self::get_instance_id();

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);
        if (!$session) {
            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")], 404);
        }

        $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team_id, "token" => $instance_id]);
        if (!$account) {
            return $this->respond([
                "status" => "error",
                "message" => __("Account does not exist")
            ], 404);
        }

        return $this->respond([
            "status" => "success",
            "data" => [
                "id" => $account->phone ?? $account->wid ?? $account->user ?? $account->name ?? $account->username ?? $account->token,
                "name" => $account->name ?? $account->fullname ?? $account->username ?? null,
                "instance_id" => $instance_id,
                "wsState" => ((int) ($session->status ?? 0) === 1) ? "OPEN" : "CONNECTING",
                "connected" => (int) ($session->status ?? 0) === 1 && (int) ($account->status ?? 0) === 1,
                "healthy" => (int) ($session->status ?? 0) === 1 && (int) ($account->status ?? 0) === 1,
            ]
        ], 200);
    }

    public function cleanup_instances()
    {
        $team = self::get_team();
        $team_id = $team->id;

        $sessions = db_fetch("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id]);
        if (is_array($sessions)) {
            foreach ($sessions as $session) {
                if ((int) ($session->status ?? 0) === 0) {
                    db_delete(TB_WHATSAPP_SESSIONS, ["id" => $session->id]);
                }
            }
        }

        return $this->respond([
            "status" => "success",
            "message" => __("Cleanup completed")
        ], 200);
    }

    public function get_paircode()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id();

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);
        if (!$session) {
            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")], 404);
        }

        $phone = preg_replace('/[^0-9+]/', '', (string) post("phone"));
        if ($phone === "") {
            return $this->respond(["status" => "error", "message" => __("Phone number is required")], 400);
        }

        $countryCode = preg_replace('/[^0-9]/', '', (string) post("code"));
        $result = wa_get_curl("get_paircode", [
            "instance_id" => $instance_id,
            "access_token" => $access_token,
            "phone" => $phone,
            "code" => $countryCode
        ]);

        if ($result == "") {
            return $this->respond([
                "status" => "error",
                "message" => __("Cannot connect to WhatsApp server. Please try again later")
            ], 502);
        }

        if (($result->status ?? "error") !== "success") {
            return $this->respond([
                "status" => "error",
                "message" => $result->message ?? __("Failed to generate pairing code")
            ], 400);
        }

        return $this->respond([
            "status" => "success",
            "message" => __("Pairing code generated"),
            "code" => $result->code ?? null,
            "pairing_code" => $result->code ?? null,
            "data" => [
                "instance_id" => $instance_id,
                "code" => $result->code ?? null,
                "pairing_code" => $result->code ?? null,
                "qrcode" => $result->base64 ?? $result->qrcode ?? null,
            ]
        ], 200);
    }

    public function session_health()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $instance_id = self::get_instance_id();

        // Primary: proxy to Node.js for accurate real-time status
        $health = wa_get_curl("session/health", [
            "instance_id" => $instance_id,
            "access_token" => (string)($team->ids ?? '')
        ]);

        if (is_object($health) && ($health->status ?? '') === 'success' && !empty($health->health)) {
            return $this->respond([
                "status" => "success",
                "health" => [
                    "healthy" => !empty($health->health->healthy),
                    "name" => $health->health->name ?? $health->health->user ?? null,
                    "wid" => $health->health->wid ?? null,
                    "user" => $health->health->user ?? null,
                    "reason" => $health->health->reason ?? null,
                    "wsState" => $health->health->wsState ?? null,
                    "code" => $health->health->code ?? null,
                ]
            ], 200);
        }

        // Fallback: DB check if Node is unreachable
        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);
        if (!$session) {
            return $this->respond([
                "status" => "error",
                "message" => __("Instance ID Invalidated")
            ], 404);
        }

        $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team_id, "token" => $instance_id]);
        $accountStatus = $account ? (int) ($account->status ?? 0) : 0;
        $healthy = ((int) ($session->status ?? 0) === 1) && $accountStatus === 1;

        return $this->respond([
            "status" => "success",
            "health" => [
                "healthy" => $healthy,
                "name" => $account->name ?? $account->fullname ?? $account->username ?? null,
                "wid" => $account->phone ?? $account->wid ?? $account->user ?? $account->name ?? $account->username ?? null,
                "user" => $account->phone ?? $account->wid ?? $account->user ?? $account->name ?? $account->username ?? null,
                "reason" => $healthy ? null : __("Connecting"),
                "wsState" => $healthy ? "OPEN" : "CONNECTING",
                "code" => $healthy ? "OPEN" : "CONNECTING",
            ]
        ], 200);
    }

    public function get_qrcode()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id();

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$session) {

            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
        }

        if ($session->status == 1) {
            return $this->respond(["status" => "error", "message" => __("Instance ID has been used")]);
        }

        $result = wa_get_curl("get_qrcode", ["instance_id" => $instance_id, "access_token" => $access_token]);

        if ($result == "") {
            return $this->respond(["status" => "error", "message" => __("Cannot connect to WhatsApp server. Please try again later")]);
        }

        return $this->respond((array)$result, 200);
    }
     public function send_pedido(){
        $json = file_get_contents('php://input');

        if(!empty($json)){
            $json = json_decode($json);
        }

        $instance_id = $_GET["instance_id"];
        $access_token = $_GET["access_token"];
        $message = post("body");
        $number = post("phone_number");
        $filename = post("filename");
        $media_url = post("media_url");

        if( !empty($json) && isset($json->phone_number) ) $number = $json->phone_number;
        if( !empty($json) && isset($json->media_url) ) $media_url = $json->media_url;
        if( !empty($json) && isset($json->filename) ) $filename = $json->filename;
        if( !empty($json) && isset($json->body) ) $message = $json->body;
        if( !empty($json) && isset($json->instance_id) ) $instance_id = $json->instance_id;
        if( !empty($json) && isset($json->access_token) ) $access_token = $json->access_token;        

	    $message = str_replace("\\n", "%0D%0A", $message);
        $message = urldecode($message);
        $message = str_replace("\\n", "%0D%0A", $message);
        $message = urldecode($message);

        $response = wa_post_curl("send_message", [
            "instance_id" => $instance_id, 
            "access_token" => $access_token
        ], [
            "media_url" => $media_url,
            "chat_id" => $number."@c.us",
            "caption" => $message,
            "filename" => $filename
        ] );

        ms((array)$response);
    }

    public function set_webhook()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id();

        if (post("enable") == "") {
            return $this->respond(["status" => "error", "message" => __("Enable field is required")]);
        }

        if (post("webhook_url") == "") {
            return $this->respond(["status" => "error", "message" => __("Webhook URL is required")]);
        }

        $status = post("enable") == "true" ? 1 : 0;
        $webhook_url = addslashes(post("webhook_url"));

        if (!filter_var($webhook_url, FILTER_VALIDATE_URL)) {
            return $this->respond(["status" => "error", "message" => __("Webhook URL is not a valid URL")]);
        }

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$session) {
            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
        }

        if ($session->status == 0) {
            return $this->respond(["status" => "error", "message" => __("This instance ID has not been activated yet")]);
        }

        $webhook = db_get("*", TB_WHATSAPP_WEBHOOK, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$webhook) {
            db_insert(TB_WHATSAPP_WEBHOOK, [
                [
                    "ids" => ids(),
                    "instance_id" => $instance_id,
                    "team_id" => $team_id,
                    "webhook_url" => $webhook_url,
                    "status" => $status
                ]
            ]);
        } else {
            db_update(TB_WHATSAPP_WEBHOOK, [
                "webhook_url" => $webhook_url,
                "status" => $status
            ], [
                "instance_id" => $instance_id,
                "team_id" => $team_id
            ]);
        }

        return $this->respond(["status" => "success", "message" => __("Webhook URI Saved")]);
    }

    public function reboot()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id();

        if (!$instance_id) {
            return $this->respond(["status" => "error", "message" => "Instance ID Invalidated"]);
        }

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$session) {
            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
        }

        if ($session->status == 0) {
            return $this->respond(["status" => "error", "message" => __("This instance ID has not been activated yet")]);
        }

        $result = wa_get_curl("logout", ["instance_id" => $instance_id, "access_token" => $access_token]);

        if ($result == "") {
            return $this->respond(["status" => "error", "message" => __("Cannot connect to WhatsApp server. Please try again later")]);
        }

        return $this->respond((array)$result);
    }

    public function reset_instance()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id();

        $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team_id, "token" => $instance_id]);

        if (empty($account)) {
            return $this->respond(["status" => "error", "message" => __("Account does not exist")]);
        }

        $result = wa_get_curl("logout", ["instance_id" => $instance_id, "access_token" => $access_token]);
        if ($result == "") {
            return $this->respond(["status" => "error", "message" => __("Cannot connect to WhatsApp server. Please try again later")]);
        }

        db_delete(TB_ACCOUNTS, ["id" => $account->id]);
        db_delete(TB_WHATSAPP_AUTORESPONDER, ["instance_id" => $instance_id]);
        db_delete(TB_WHATSAPP_CHATBOT, ["instance_id" => $instance_id]);
        db_delete(TB_WHATSAPP_SESSIONS, ["instance_id" => $instance_id]);
        db_delete(TB_WHATSAPP_WEBHOOK, ["instance_id" => $instance_id]);
        // Cascade delete messages and subscribers for this WhatsApp account
        db_delete(TB_WHATSAPP_MESSAGES, ["instance_id" => $instance_id]);
        db_delete(TB_WHATSAPP_SUBSCRIBERS, ["instance_id" => $instance_id]);

        return $this->respond(["status" => "success", "message" => "Reset Instance ID was successful"]);
    }

    public function reconnect()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id();

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$session) {
            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
        }

        if ($session->status == 0) {
            return $this->respond(["status" => "error", "message" => __("This instance ID has not been activated yet")]);
        }

        $result = wa_get_curl("instance", ["instance_id" => $instance_id, "access_token" => $access_token]);
        if ($result == "") {
            return $this->respond(["status" => "error", "message" => __("Cannot connect to WhatsApp server. Please try again later")]);
        }

        return $this->respond((array)$result);
    }

    public function get_groups()
    {
        $team_id = "";
        $instance_id = "";
        $access_token = "";

        $team = self::get_team($access_token);
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id($instance_id);

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$session) {
            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
        }

        if ($session->status == 0) {
            return $this->respond(["status" => "error", "message" => __("This instance ID has not been activated yet")]);
        }

        $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team_id, "token" => $instance_id]);

        if (!$account) {
            return $this->respond(["status" => "error", "message" => __("Account does not exist")]);
        }

        if ($account->status == 0) {
            return $this->respond(["status" => "error", "message" => "This WhatsApp account relogin required"]);
        }

        $result = wa_get_curl("get_groups", ["instance_id" => $instance_id, "access_token" => $access_token]);

        $groups = [];
        if (isset($result->data)) {
            foreach ($result->data as $key => $value) {
                $groups[] = [
                    "id" => $value->id,
                    "name" => $value->name,
                    "size" => $value->size
                ];
            }
        }

        //return $this->respond((array)$response);
        ///get_groups
        return $this->respond((array)["status" => "success", "message" => "Success", 'data' => $groups]);
    }

    public function send()
    {
        $json = file_get_contents('php://input');

        if (!empty($json)) {
            $json = json_decode($json);
        }

        $team_id = "";
        $instance_id = "";
        $access_token = "";
        $type = post("type");
        $message = post("message");
        $filename = post("filename");
        $media_url = post("media_url");
        $number = post("number");
        $buttons = post("buttons");
        $footer = post("footer");
        $sections = post("sections");
        $buttonText = post("buttonText");

        if (!empty($json) && isset($json->media_url)) $media_url = $json->media_url;
        if (!empty($json) && isset($json->filename)) $filename = $json->filename;
        if (!empty($json) && isset($json->message)) $message = $json->message;
        if (!empty($json) && isset($json->type)) $type = $json->type;
        if (!empty($json) && isset($json->instance_id)) $instance_id = $json->instance_id;
        if (!empty($json) && isset($json->access_token)) $access_token = $json->access_token;
        if (!empty($json) && isset($json->number)) $number = $json->number;
        if (!empty($json) && isset($json->buttons)) $buttons = $json->buttons;
        if (!empty($json) && isset($json->footer)) $footer = $json->footer;
        if (!empty($json) && isset($json->sections)) $sections = $json->sections;
        if (!empty($json) && isset($json->buttonText)) $buttonText = $json->buttonText;

        $team = self::get_team($access_token);
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id($instance_id);

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$session) {
            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
        }

        if ($session->status == 0) {
            return $this->respond(["status" => "error", "message" => __("This instance ID has not been activated yet")]);
        }

        $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team_id, "token" => $instance_id]);

        if (!$account) {
            return $this->respond(["status" => "error", "message" => __("Account does not exist")]);
        }

        if ($account->status == 0) {
            return $this->respond(["status" => "error", "message" => "This WhatsApp account relogin required"]);
        }

        if ($number == "" || !is_numeric($number)) {
            return $this->respond(["status" => "error", "message" => __("Phone number is required")]);
        }

        // Handle different message types
        $chat_id = $number . "@s.whatsapp.net";

        // Check if this is a button message
        if ($type == "button" && !empty($buttons)) {
            if ($message == "") {
                return $this->respond(["status" => "error", "message" => __("Message text is required for button messages")]);
            }

            // Convert buttons array to the format expected by WhatsApp API
            $templateButtons = [];
            $buttonIndex = 0;

            foreach ($buttons as $button) {
                if (is_object($button)) {
                    $button = (array)$button;
                }

                $displayText = "";
                if (isset($button['buttonText'])) {
                    if (is_object($button['buttonText'])) {
                        $displayText = $button['buttonText']->displayText ?? "";
                    } elseif (is_array($button['buttonText'])) {
                        $displayText = $button['buttonText']['displayText'] ?? "";
                    }
                }

                if ($displayText != "") {
                    $templateButtons[] = [
                        "index" => $buttonIndex,
                        "quickReplyButton" => [
                            "displayText" => $displayText,
                            "id" => $button['buttonId'] ?? "btn_" . $buttonIndex
                        ]
                    ];
                    $buttonIndex++;
                }
            }

            if (empty($templateButtons)) {
                return $this->respond(["status" => "error", "message" => __("At least one valid button is required")]);
            }

            $params = [
                "chat_id" => $chat_id,
                "caption" => $message,
                "footer" => $footer ?? "",
                "templateButtons" => $templateButtons
            ];

            $response = wa_post_curl("send_message", [
                "instance_id" => $instance_id,
                "access_token" => $access_token,
                "type" => 2  // Type 2 for button messages
            ], $params);

            return $this->respond((array)$response);
        }

        // Check if this is a list message
        if ($type == "list" && !empty($sections)) {
            if ($message == "") {
                return $this->respond(["status" => "error", "message" => __("Message text is required for list messages")]);
            }

            // Convert sections array to the format expected by WhatsApp API
            $listSections = [];

            foreach ($sections as $section) {
                if (is_object($section)) {
                    $section = (array)$section;
                }

                $sectionRows = [];
                if (isset($section['rows']) && is_array($section['rows'])) {
                    foreach ($section['rows'] as $row) {
                        if (is_object($row)) {
                            $row = (array)$row;
                        }

                        $sectionRows[] = [
                            "title" => $row['title'] ?? "",
                            "description" => $row['description'] ?? "",
                            "rowId" => $row['rowId'] ?? ""
                        ];
                    }
                }

                $listSections[] = [
                    "title" => $section['title'] ?? "",
                    "rows" => $sectionRows
                ];
            }

            if (empty($listSections)) {
                return $this->respond(["status" => "error", "message" => __("At least one section with rows is required")]);
            }

            $params = [
                "chat_id" => $chat_id,
                "description" => $message,
                "buttonText" => $buttonText ?? "View Options",
                "footer" => $footer ?? "",
                "sections" => $listSections
            ];

            $response = wa_post_curl("send_message", [
                "instance_id" => $instance_id,
                "access_token" => $access_token,
                "type" => 3  // Type 3 for list messages
            ], $params);

            return $this->respond((array)$response);
        }

        // Default: Handle as regular text/media message
        if ($media_url == "" && $message == "") {
            return $this->respond(["status" => "error", "message" => __("Please enter media url or message")]);
        }

        $response = wa_post_curl("send_message", [
            "instance_id" => $instance_id,
            "access_token" => $access_token
        ], [
            "media_url" => $media_url,
            "chat_id" => $chat_id,
            "caption" => $message,
            "filename" => $filename
        ]);

        return $this->respond((array)$response);
    }

    public function send_group()
    {
        $json = file_get_contents('php://input');

        if (!empty($json)) {
            $json = json_decode($json);
        }

        $team_id = 0;
        $instance_id = "";
        $access_token = "";
        $type = post("type");
        $message = post("message");
        $filename = post("filename");
        $media_url = post("media_url");
        $number = post("group_id");

        if (!empty($json) && isset($json->media_url)) $media_url = $json->media_url;
        if (!empty($json) && isset($json->filename)) $filename = $json->filename;
        if (!empty($json) && isset($json->message)) $message = $json->message;
        if (!empty($json) && isset($json->type)) $type = $json->type;
        if (!empty($json) && isset($json->instance_id)) $instance_id = $json->instance_id;
        if (!empty($json) && isset($json->access_token)) $access_token = $json->access_token;

        $team = self::get_team($access_token);
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id($instance_id);

        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);

        if (!$session) {
            return $this->respond(["status" => "error", "message" => __("Instance ID Invalidated")]);
        }

        if ($session->status == 0) {
            return $this->respond(["status" => "error", "message" => __("This instance ID has not been activated yet")]);
        }

        $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team_id, "token" => $instance_id]);

        if (!$account) {
            return $this->respond(["status" => "error", "message" => __("Account does not exist")]);
        }

        if ($account->status == 0) {
            return $this->respond(["status" => "error", "message" => "This WhatsApp account relogin required"]);
        }

        $response = wa_post_curl("send_message", [
            "instance_id" => $instance_id,
            "access_token" => $access_token
        ], [
            "media_url" => $media_url,
            "chat_id" => $number,
            "caption" => $message,
            "filename" => $filename
        ]);

        return $this->respond((array)$response);
    }



    public function logout()
    {
        $team = self::get_team();
        $team_id = $team->id;
        $access_token = $team->ids;
        $instance_id = self::get_instance_id();

        $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team_id, "token" => $instance_id]);
        if ($account) {
            wa_get_curl("logout", ["instance_id" => $instance_id, "access_token" => $access_token]);
            db_delete(TB_ACCOUNTS, ["id" => $account->id]);
        }

        db_delete(TB_WHATSAPP_SESSIONS, ["team_id" => $team_id, "instance_id" => $instance_id]);
        db_delete(TB_WHATSAPP_WEBHOOK, ["team_id" => $team_id, "instance_id" => $instance_id]);
        db_delete(TB_WHATSAPP_AUTORESPONDER, ["team_id" => $team_id, "instance_id" => $instance_id]);
        db_delete(TB_WHATSAPP_CHATBOT, ["team_id" => $team_id, "instance_id" => $instance_id]);

        return $this->respond([
            "status" => "success",
            "message" => __("Logged out")
        ], 200);
    }
}
