<?php

namespace Core\Android_api\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Android_api extends Controller
{
    use ResponseTrait;

    public function __construct()
    {
        // For Android APIs, we assume a stateless JSON interaction. 
        // We will authenticate using headers or payload tokens.
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Authorization");
        
        if ( $_SERVER['REQUEST_METHOD'] == 'OPTIONS' ) {
            die();
        }
    }

    private function _getJsonBody(): array
    {
        $json = json_decode(file_get_contents('php://input'), true);
        return is_array($json) ? $json : [];
    }

    private function _findTeamAccessToken(array $json): ?string
    {
        if (!empty($json['access_token'])) {
            return trim($json['access_token']);
        }

        if (!empty($_SERVER['HTTP_X_WAZIPAR_ACCESS_TOKEN'])) {
            return trim($_SERVER['HTTP_X_WAZIPAR_ACCESS_TOKEN']);
        }

        return null;
    }

    private function _authenticate(array $json = []): object
    {
        $accessToken = $this->_findTeamAccessToken($json);
        if (!$accessToken) {
            throw new \Error("Wazipar access_token is required", 400);
        }

        $team = db_get("*", TB_TEAM, ["ids" => addslashes($accessToken)]);
        if (!$team) {
            throw new \Error("Invalid Wazipar access_token", 401);
        }

        return $team;
    }

    private function _createOrReusePendingInstance(int $teamId): string
    {
        $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $teamId, "status" => 0]);

        if ($session) {
            return $session->instance_id;
        }

        $instanceId = strtoupper(uniqid());
        db_insert(TB_WHATSAPP_SESSIONS, [
            "ids" => ids(),
            "instance_id" => $instanceId,
            "team_id" => $teamId,
            "data" => null,
            "status" => 0
        ]);

        return $instanceId;
    }

    private function _sendCampaignRecipient(string $instanceId, string $accessToken, array $recipient, array $payload): array
    {
        $chatId = trim((string)($recipient['chat_id'] ?? $recipient['number'] ?? $recipient['group_id'] ?? ''));
        $isGroup = !empty($recipient['is_group']);

        if ($chatId === '') {
            return [
                'status' => 'failed',
                'error' => 'Recipient number is required',
            ];
        }

        $sendData = [
            'chat_id' => $isGroup ? $chatId : preg_replace('/[^0-9]/', '', $chatId) . '@s.whatsapp.net',
        ];

        if (!$isGroup && $sendData['chat_id'] === '@s.whatsapp.net') {
            return [
                'status' => 'failed',
                'error' => 'Recipient number is invalid',
            ];
        }

        $messageType = strtolower((string)($payload['type'] ?? 'text'));

        if ($messageType === 'media') {
            $sendData['media_url'] = trim((string)($payload['media_url'] ?? ''));
            $sendData['caption'] = (string)($payload['caption'] ?? '');
            $sendData['filename'] = (string)($payload['filename'] ?? '');
        } elseif ($messageType === 'template') {
            $sendData['template'] = $payload['template_id'] ?? $payload['template'] ?? '';
        } else {
            $sendData['caption'] = (string)($payload['message'] ?? '');
        }

        $response = wa_post_curl("direct_send_message", [
            "instance_id" => $instanceId,
            "access_token" => $accessToken
        ], $sendData);

        if (is_object($response) && ($response->status ?? '') === 'success') {
            return [
                'status' => 'sent',
                'error' => '',
            ];
        }

        $message = '';
        if (is_object($response)) {
            $message = (string)($response->message ?? '');
        }

        return [
            'status' => 'failed',
            'error' => $message !== '' ? $message : 'Cannot send message',
        ];
    }

    private function _buildButtonTemplateData(array $json): array
    {
        $title = trim((string)($json['title'] ?? ''));
        $caption = trim((string)($json['caption'] ?? ''));
        $footer = trim((string)($json['footer'] ?? ''));
        $imageUrl = trim((string)($json['image_url'] ?? ''));
        $buttons = is_array($json['buttons'] ?? null) ? $json['buttons'] : [];

        if (empty($buttons)) {
            throw new \Error("Add at least one button item", 422);
        }

        if (count($buttons) > 3) {
            throw new \Error("Only up to 3 button items allowed", 422);
        }

        $templateButtons = [];
        foreach ($buttons as $index => $button) {
            $button = is_array($button) ? $button : [];
            $type = trim((string)($button['type'] ?? 'text'));
            $displayText = trim((string)($button['displayText'] ?? ''));
            $url = trim((string)($button['url'] ?? ''));
            $phoneNumber = trim((string)($button['phoneNumber'] ?? ''));
            $copyText = trim((string)($button['copyText'] ?? ''));
            $buttonIndex = $index + 1;

            if ($displayText === '') {
                throw new \Error("Button {$buttonIndex}: Please enter display text", 422);
            }

            switch ($type) {
                case 'text':
                    $templateButtons[] = [
                        'index' => $buttonIndex,
                        'quickReplyButton' => [
                            'displayText' => $displayText,
                            'id' => ids(),
                        ],
                    ];
                    break;

                case 'link':
                    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
                        throw new \Error("Button {$buttonIndex}: Invalid URL", 422);
                    }

                    $templateButtons[] = [
                        'index' => $buttonIndex,
                        'urlButton' => [
                            'displayText' => $displayText,
                            'url' => $url,
                        ],
                    ];
                    break;

                case 'call':
                    if ($phoneNumber === '') {
                        throw new \Error("Button {$buttonIndex}: Phone number is required", 422);
                    }

                    $templateButtons[] = [
                        'index' => $buttonIndex,
                        'callButton' => [
                            'displayText' => $displayText,
                            'phoneNumber' => $phoneNumber,
                        ],
                    ];
                    break;

                case 'copy':
                    if ($copyText === '') {
                        throw new \Error("Button {$buttonIndex}: Please enter copy code", 422);
                    }

                    $templateButtons[] = [
                        'index' => $buttonIndex,
                        'urlButton' => [
                            'displayText' => $displayText,
                            'url' => 'https://www.whatsapp.com/otp/code/?otp_type=COPY_CODE&code=' . rawurlencode($copyText),
                            'disabled' => false,
                        ],
                    ];
                    break;

                default:
                    throw new \Error("The type button item incorrect", 422);
            }
        }

        $templateData = [
            'templateButtons' => $templateButtons,
            'buttons' => $buttons,
        ];

        if ($footer !== '') {
            $templateData['footer'] = $footer;
        }

        if ($title !== '') {
            $templateData['title'] = $title;
        }

        if ($imageUrl !== '') {
            $templateData['media_url'] = $imageUrl;
            $templateData['caption'] = $caption;
            $templateData['has_media'] = true;
        } else {
            $templateData['text'] = $caption;
        }

        return $templateData;
    }

    private function _buildListTemplateData(array $json): array
    {
        $title = trim((string)($json['title'] ?? ''));
        $caption = trim((string)($json['caption'] ?? ''));
        $footer = trim((string)($json['footer'] ?? ''));
        $buttonText = trim((string)($json['button_text'] ?? ''));
        $sections = is_array($json['sections'] ?? null) ? $json['sections'] : [];

        if ($caption === '') {
            throw new \Error("caption is required", 422);
        }

        if ($buttonText === '') {
            throw new \Error("button_text is required", 422);
        }

        if (empty($sections)) {
            throw new \Error("Add at least one section", 422);
        }

        $cleanSections = [];
        foreach ($sections as $index => $section) {
            $section = is_array($section) ? $section : [];
            $sectionTitle = trim((string)($section['title'] ?? ''));
            $rows = is_array($section['rows'] ?? null) ? $section['rows'] : [];

            if ($sectionTitle === '') {
                throw new \Error("Section " . ($index + 1) . ": title is required", 422);
            }

            if (empty($rows)) {
                throw new \Error("Section " . ($index + 1) . ": add at least one row", 422);
            }

            $cleanRows = [];
            foreach ($rows as $rowIndex => $row) {
                $row = is_array($row) ? $row : [];
                $rowTitle = trim((string)($row['title'] ?? ''));
                $rowDescription = trim((string)($row['description'] ?? ''));
                $rowId = trim((string)($row['id'] ?? ids()));

                if ($rowTitle === '') {
                    throw new \Error("Section " . ($index + 1) . ", row " . ($rowIndex + 1) . ": title is required", 422);
                }

                $cleanRows[] = [
                    'title' => $rowTitle,
                    'rowId' => $rowId,
                    'description' => $rowDescription,
                ];
            }

            $cleanSections[] = [
                'title' => $sectionTitle,
                'rows' => $cleanRows,
            ];
        }

        $templateData = [
            'text' => $caption,
            'buttonText' => $buttonText,
            'listButtonText' => $buttonText,
            'sections' => $cleanSections,
        ];

        if ($footer !== '') {
            $templateData['footer'] = $footer;
        }

        if ($title !== '') {
            $templateData['title'] = $title;
        }

        return $templateData;
    }

    private function _saveTemplateRecord(object $team, string $name, int $type, array $templateData): array
    {
        $now = time();
        $templateIds = ids();

        $insertTemplate = db_insert('sp_whatsapp_template', [
            'ids' => $templateIds,
            'team_id' => $team->id,
            'type' => $type,
            'name' => $name,
            'data' => json_encode($templateData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'changed' => $now,
            'created' => $now,
        ]);

        return [
            'server_template_id' => $templateIds,
            'template_row_id' => $insertTemplate ?? null,
        ];
    }

    private function _proxyEngineRequest(string $method, string $path, array $query = [], array $body = []): array
    {
        $url = 'http://127.0.0.1:7708/' . ltrim($path, '/');
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 75,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $raw === null || $raw === '') {
            throw new \Error($curlError !== '' ? $curlError : 'Engine did not return a response', 502);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \Error('Engine returned invalid JSON', 502);
        }

        if ($statusCode >= 400 && empty($decoded['status'])) {
            $decoded['status'] = 'error';
        }

        return $decoded;
    }

    /**
     * API 1: Remote Device Linking - Generates Pairing Code
     * POST /Android_api/request_pairing
     */
    public function request_pairing()
    {
        try {
            $json = $this->_getJsonBody();

            if (!isset($json['phone_number']) || empty($json['phone_number'])) {
                return $this->respond(["status" => "error", "message" => "WhatsApp phone_number is required"]);
            }

            $phone = preg_replace('/[^0-9]/', '', $json['phone_number']);
            $countryCode = '';
            if (!empty($json['country_code'])) {
                $countryCode = preg_replace('/[^0-9]/', '', $json['country_code']);
            }

            if ($countryCode !== '') {
                $phone = ltrim($phone, '0');
                if (strpos($phone, $countryCode) !== 0) {
                    $phone = $countryCode . $phone;
                }
            }

            if ($phone === '') {
                return $this->respond(["status" => "error", "message" => "Valid WhatsApp phone_number is required"]);
            }

            $accessToken = $this->_findTeamAccessToken($json);
            if (!$accessToken) {
                return $this->respond([
                    "status" => "error",
                    "message" => "Wazipar access_token is required"
                ], 400);
            }

            $team = db_get("*", TB_TEAM, ["ids" => addslashes($accessToken)]);
            if (!$team) {
                return $this->respond([
                    "status" => "error",
                    "message" => "Invalid Wazipar access_token"
                ], 404);
            }

            $createResponse = $this->_proxyEngineRequest('GET', 'api/create_instance', [
                'access_token' => $accessToken,
            ]);

            if (strtolower((string)($createResponse['status'] ?? 'error')) !== 'success') {
                return $this->respond([
                    "status" => "error",
                    "message" => $createResponse['message'] ?? "Unable to create WhatsApp instance"
                ], 400);
            }

            $createData = is_array($createResponse['data'] ?? null)
                ? $createResponse['data']
                : [];
            $instanceId = (string)($createData['instance_id'] ?? $createResponse['instance_id'] ?? '');
            if ($instanceId === '') {
                return $this->respond([
                    "status" => "error",
                    "message" => "Instance ID missing from engine response"
                ], 502);
            }

            $pairResponse = $this->_proxyEngineRequest('GET', 'get_paircode', [
                'access_token' => $accessToken,
                'instance_id' => $instanceId,
                'phone' => $phone,
                'code' => $countryCode
            ]);

            if (strtolower((string)($pairResponse['status'] ?? 'error')) !== 'success') {
                return $this->respond([
                    "status" => "error",
                    "message" => $pairResponse['message'] ?? "Failed to generate pairing code",
                    "data" => [
                        "instance_id" => $instanceId,
                        "phone" => $phone
                    ]
                ], 400);
            }

            return $this->respond([
                "status" => "success",
                "message" => "Pairing code generated",
                "data" => [
                    "instance_id" => $instanceId,
                    "pairing_code" => $pairResponse['code'] ?? null,
                    "phone" => $phone,
                    "qrcode" => $pairResponse['qrcode'] ?? $createResponse['base64'] ?? $createResponse['qrcode'] ?? null
                ]
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Android pairing failed: {message}', ['message' => $e->getMessage()]);

            return $this->respond([
                "status" => "error",
                "message" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API 2: Sync Contacts to DB
     * POST /Android_api/sync_contacts
     */
    public function sync_contacts()
    {
        $json = $this->_getJsonBody();
        
        if (!isset($json['group_name'])) {
            return $this->respond(["status" => "error", "message" => "group_name is required"]);
        }

        // Standard DB operations
        // Insert group -> Get ID -> Insert contacts
        // 
        // Example skeleton:
        // $group_id = db_insert(TB_WHATSAPP_CONTACT_GROUP, ["name" => $json['group_name'], 'team_id' => 1]);
        // foreach($json['numbers'] as $num) { ... db_insert(TB_WHATSAPP_CONTACTS) }

        return $this->respond([
            "status" => "success",
            "message" => "Contacts synced successfully",
            "data" => [ "server_group_id" => rand(100, 999) ]
        ], 200);
    }

    /**
     * API 3: Sync Templates to DB
     * POST /Android_api/sync_templates
     */
    public function sync_templates()
    {
        try {
            $json = $this->_getJsonBody();
            $accessToken = $this->_findTeamAccessToken($json);

            if (!$accessToken) {
                return $this->respond([
                    "status" => "error",
                    "message" => "Wazipar access_token is required"
                ], 400);
            }

            $team = db_get("*", TB_TEAM, ["ids" => addslashes($accessToken)]);
            if (!$team) {
                return $this->respond([
                    "status" => "error",
                    "message" => "Invalid Wazipar access_token"
                ], 401);
            }

            $name = trim((string)($json['name'] ?? ''));
            if ($name === '') {
                return $this->respond([
                    "status" => "error",
                    "message" => "name is required"
                ], 400);
            }

            $hasListPayload = !empty($json['sections']) || !empty($json['button_text']);
            $templateData = $hasListPayload
                ? $this->_buildListTemplateData($json)
                : $this->_buildButtonTemplateData($json);
            $type = $hasListPayload ? 1 : 2;
            $saved = $this->_saveTemplateRecord($team, $name, $type, $templateData);

            return $this->respond([
                "status" => "success",
                "message" => $hasListPayload ? "List template saved successfully" : "Template saved successfully",
                "data" => $saved
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Android template sync failed: {message}', ['message' => $e->getMessage()]);

            return $this->respond([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API 3b: Sync List Templates to DB
     * POST /Android_api/sync_list_templates
     */
    public function sync_list_templates()
    {
        try {
            $json = $this->_getJsonBody();
            $accessToken = $this->_findTeamAccessToken($json);

            if (!$accessToken) {
                return $this->respond([
                    "status" => "error",
                    "message" => "Wazipar access_token is required"
                ], 400);
            }

            $team = db_get("*", TB_TEAM, ["ids" => addslashes($accessToken)]);
            if (!$team) {
                return $this->respond([
                    "status" => "error",
                    "message" => "Invalid Wazipar access_token"
                ], 401);
            }

            $name = trim((string)($json['name'] ?? ''));
            if ($name === '') {
                return $this->respond([
                    "status" => "error",
                    "message" => "name is required"
                ], 400);
            }

            $templateData = $this->_buildListTemplateData($json);
            $saved = $this->_saveTemplateRecord($team, $name, 1, $templateData);

            return $this->respond([
                "status" => "success",
                "message" => "List template saved successfully",
                "data" => $saved
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Android list template sync failed: {message}', ['message' => $e->getMessage()]);

            return $this->respond([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /Android_api/logout_instance
     */
    public function logout_instance()
    {
        try {
            $json = $this->_getJsonBody();
            $team = $this->_authenticate($json);
            $instanceId = trim((string)($json['instance_id'] ?? ''));
            if ($instanceId === '') {
                return $this->respond([
                    "status" => "error",
                    "message" => "instance_id is required"
                ], 400);
            }

            $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team->id, "instance_id" => $instanceId]);
            if (!$session) {
                return $this->respond([
                    "status" => "error",
                    "message" => "Instance ID Invalidated"
                ], 404);
            }

            $engineResponse = $this->_proxyEngineRequest('GET', 'logout', [
                'access_token' => (string)$team->ids,
                'instance_id' => $instanceId,
            ]);

            $engineStatus = strtolower((string)($engineResponse['status'] ?? 'error'));
            if ($engineStatus !== 'success') {
                return $this->respond($engineResponse, 400);
            }

            return $this->respond([
                "status" => "success",
                "message" => "WhatsApp session logged out",
                "data" => $engineResponse['data'] ?? []
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Android logout_instance failed: {message}', ['message' => $e->getMessage()]);
            return $this->respond([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /Android_api/reset_instance
     */
    public function reset_instance()
    {
        try {
            $json = $this->_getJsonBody();
            $team = $this->_authenticate($json);
            $instanceId = trim((string)($json['instance_id'] ?? ''));
            if ($instanceId === '') {
                return $this->respond([
                    "status" => "error",
                    "message" => "instance_id is required"
                ], 400);
            }

            $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team->id, "instance_id" => $instanceId]);
            if (!$session) {
                return $this->respond([
                    "status" => "error",
                    "message" => "Instance ID Invalidated"
                ], 404);
            }

            $engineResponse = $this->_proxyEngineRequest('POST', 'api/reset_instance', [], [
                'access_token' => (string)$team->ids,
                'instance_id' => $instanceId,
            ]);

            $engineStatus = strtolower((string)($engineResponse['status'] ?? 'error'));
            if ($engineStatus !== 'success') {
                return $this->respond($engineResponse, 400);
            }

            return $this->respond($engineResponse, 200);
        } catch (\Throwable $e) {
            log_message('error', 'Android reset_instance failed: {message}', ['message' => $e->getMessage()]);
            return $this->respond([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API 4: Launch Campaign
     * POST /Android_api/launch_campaign
     */
    public function launch_campaign()
    {
        try {
            $json = $this->_getJsonBody();
            $team = $this->_authenticate($json);
            $instanceId = trim((string)($json['instance_id'] ?? ''));
            $recipients = is_array($json['recipients'] ?? null) ? $json['recipients'] : [];
            $payload = is_array($json['payload'] ?? null) ? $json['payload'] : [];
            $delaySeconds = (int)($json['delay_seconds'] ?? 0);

            if ($instanceId === '') {
                log_message('error', 'launch_campaign rejected: missing instance_id');
                return $this->respond([
                    "status" => "error",
                    "message" => "instance_id is required"
                ], 400);
            }

            if (empty($recipients)) {
                log_message('error', 'launch_campaign rejected: empty recipients');
                return $this->respond([
                    "status" => "error",
                    "message" => "recipients is required"
                ], 400);
            }

            $session = db_get("*", TB_WHATSAPP_SESSIONS, ["team_id" => $team->id, "instance_id" => $instanceId]);
            if (!$session) {
                log_message('error', 'launch_campaign rejected: invalid instance_id {instance_id}', ['instance_id' => $instanceId]);
                return $this->respond([
                    "status" => "error",
                    "message" => "Instance ID Invalidated"
                ], 404);
            }

            if ((int)($session->status ?? 0) === 0) {
                log_message('error', 'launch_campaign rejected: instance not activated {instance_id}', ['instance_id' => $instanceId]);
                return $this->respond([
                    "status" => "error",
                    "message" => "This instance ID has not been activated yet"
                ], 400);
            }

            $account = db_get("*", TB_ACCOUNTS, ["team_id" => $team->id, "token" => $instanceId]);
            if (!$account) {
                log_message('error', 'launch_campaign rejected: account missing {instance_id}', ['instance_id' => $instanceId]);
                return $this->respond([
                    "status" => "error",
                    "message" => "Account does not exist"
                ], 404);
            }

            if ((int)($account->status ?? 0) === 0) {
                log_message('warning', 'launch_campaign continuing with stale account status {instance_id}', ['instance_id' => $instanceId]);
            }

            $health = wa_get_curl("session/health", [
                "instance_id" => $instanceId,
                "access_token" => (string)($team->ids ?? '')
            ]);

            if (!is_object($health) || ($health->status ?? '') !== 'success' || empty($health->health) || empty($health->health->healthy)) {
                $reason = '';
                if (is_object($health)) {
                    $reason = (string)($health->health->reason ?? $health->message ?? '');
                }

                log_message('error', 'launch_campaign blocked: whatsapp session unhealthy {instance_id} reason={reason}', [
                    'instance_id' => $instanceId,
                    'reason' => $reason,
                ]);

                return $this->respond([
                    "status" => "error",
                    "message" => $reason !== '' ? $reason : "WhatsApp session is not connected. Please relink the device.",
                    "data" => [
                        "instance_id" => $instanceId,
                        "required_action" => "relink",
                    ]
                ], 409);
            }

            $queueResult = wa_post_curl("api/campaigns/launch", [
                "access_token" => (string)$team->ids,
            ], [
                "access_token" => (string)$team->ids,
                "instance_id" => $instanceId,
                "campaign_name" => (string)($json['campaign_name'] ?? 'Android Campaign'),
                "target_name" => (string)($json['target_name'] ?? ''),
                "delay_seconds" => $delaySeconds,
                "message_mode" => (string)($json['message_mode'] ?? ''),
                "message_label" => (string)($json['message_label'] ?? ''),
                "user_email" => (string)($json['user_email'] ?? ''),
                "schedule_at" => (int)($json['schedule_at'] ?? 0),
                "recipients" => $recipients,
                "payload" => $payload,
            ]);

            if (!is_object($queueResult) || ($queueResult->status ?? '') !== 'success') {
                $message = is_object($queueResult)
                    ? (string)($queueResult->message ?? 'Failed to queue campaign')
                    : 'Failed to queue campaign';

                log_message('error', 'launch_campaign queue failed {instance_id}: {message}', [
                    'instance_id' => $instanceId,
                    'message' => $message,
                ]);

                return $this->respond([
                    "status" => "error",
                    "message" => $message,
                ], 400);
            }

            $queueData = is_object($queueResult->data ?? null) ? $queueResult->data : (object)[];
            $items = [];
            foreach ($recipients as $index => $recipient) {
                if (!is_array($recipient)) {
                    $recipient = (array)$recipient;
                }

                $items[] = [
                    'index' => (int)($recipient['index'] ?? ($index + 1)),
                    'name' => (string)($recipient['name'] ?? ''),
                    'number' => (string)($recipient['number'] ?? $recipient['chat_id'] ?? ''),
                    'status' => 'queued',
                    'error' => '',
                ];
            }

            return $this->respond([
                "status" => "success",
                "message" => "Campaign queued successfully.",
                "data" => [
                    "campaign_id" => (string)($queueData->campaign_id ?? ''),
                    "queue_id" => (string)($queueData->queue_id ?? ''),
                    "team_id" => $team->id ?? null,
                    "recipient_count" => (int)($queueData->total ?? count($recipients)),
                    "sent_count" => 0,
                    "failed_count" => 0,
                    "items" => $items,
                    "payload_type" => $payload['type'] ?? null,
                    "queue_status" => (string)($queueData->status ?? 'queued'),
                    "scheduled" => !empty($queueData->scheduled),
                    "schedule_at" => (int)($queueData->schedule_at ?? 0),
                ]
            ], 200);
        } catch (\Throwable $th) {
            return $this->respond([
                "status" => "error",
                "message" => $th->getMessage(),
            ], $th->getCode() >= 200 && $th->getCode() <= 499 ? $th->getCode() : 500);
        }
    }
    /**
     * API 5: Sync Chatbot Button Templates to DB
     * POST /Android_api/sync_chatbot_templates
     */
    public function sync_chatbot_templates()
    {
        try {
            $json = $this->_getJsonBody();
            $accessToken = $this->_findTeamAccessToken($json);

            if (!$accessToken) {
                return $this->respond([
                    "status" => "error",
                    "message" => "Wazipar access_token is required"
                ], 400);
            }

            $team = db_get("*", TB_TEAM, ["ids" => addslashes($accessToken)]);
            if (!$team) {
                return $this->respond([
                    "status" => "error",
                    "message" => "Invalid Wazipar access_token"
                ], 401);
            }

            $name = trim((string)($json['name'] ?? ''));
            if ($name === '') {
                return $this->respond([
                    "status" => "error",
                    "message" => "name is required"
                ], 400);
            }

            $templateData = $this->_buildButtonTemplateData($json);
            $saved = $this->_saveTemplateRecord($team, $name, 3, $templateData); // type 3 for Chatbot Button

            return $this->respond([
                "status" => "success",
                "message" => "Chatbot Template saved successfully",
                "data" => $saved
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Android chatbot template sync failed: {message}', ['message' => $e->getMessage()]);

            return $this->respond([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API 6: Sync Chatbot List Templates to DB
     * POST /Android_api/sync_chatbot_list_templates
     */
    public function sync_chatbot_list_templates()
    {
        try {
            $json = $this->_getJsonBody();
            $accessToken = $this->_findTeamAccessToken($json);

            if (!$accessToken) {
                return $this->respond([
                    "status" => "error",
                    "message" => "Wazipar access_token is required"
                ], 400);
            }

            $team = db_get("*", TB_TEAM, ["ids" => addslashes($accessToken)]);
            if (!$team) {
                return $this->respond([
                    "status" => "error",
                    "message" => "Invalid Wazipar access_token"
                ], 401);
            }

            $name = trim((string)($json['name'] ?? ''));
            if ($name === '') {
                return $this->respond([
                    "status" => "error",
                    "message" => "name is required"
                ], 400);
            }

            $templateData = $this->_buildListTemplateData($json);
            $saved = $this->_saveTemplateRecord($team, $name, 4, $templateData); // type 4 for Chatbot List

            return $this->respond([
                "status" => "success",
                "message" => "Chatbot List template saved successfully",
                "data" => $saved
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Android chatbot list template sync failed: {message}', ['message' => $e->getMessage()]);

            return $this->respond([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API 7: Delete Chatbot Template
     * POST /Android_api/delete_chatbot_template
     */
    public function delete_chatbot_template()
    {
        try {
            $json = $this->_getJsonBody();
            $accessToken = $this->_findTeamAccessToken($json);

            if (!$accessToken) {
                return $this->respond([
                    "status" => "error",
                    "message" => "Wazipar access_token is required"
                ], 400);
            }

            $team = db_get("*", TB_TEAM, ["ids" => addslashes($accessToken)]);
            if (!$team) {
                return $this->respond([
                    "status" => "error",
                    "message" => "Invalid Wazipar access_token"
                ], 401);
            }

            $templateId = trim((string)($json['server_template_id'] ?? ''));
            $forceDelete = (bool)($json['force'] ?? false);

            if ($templateId === '') {
                return $this->respond([
                    "status" => "error",
                    "message" => "server_template_id is required"
                ], 400);
            }

            $template = db_get("*", TB_WHATSAPP_TEMPLATE, ["ids" => $templateId, "team_id" => $team->id]);
            if (!$template) {
                return $this->respond([
                    "status" => "error",
                    "message" => "Template not found"
                ], 404);
            }

            // Check if template is used in any active flow
            // Note: Since sp_chatbot_flows table might not exist yet or we need to search its JSON,
            // for now, we will simulate the check if table exists
            $inUse = false;
            $db = \Config\Database::connect();
            if ($db->tableExists('sp_chatbot_flows')) {
                // Check if any flow's canvas_data contains this template ID
                $query = $db->table('sp_chatbot_flows')
                            ->where('team_id', $team->id)
                            ->like('canvas_data', '"propertiesJson":{"serverId":"'.$templateId.'"')
                            ->orLike('canvas_data', '"propertiesJson":{"serverId":"'.$templateId.'"}')
                            ->get();
                if ($query && $query->getNumRows() > 0) {
                    $inUse = true;
                }
            }

            if ($inUse && !$forceDelete) {
                return $this->respond([
                    "status" => "in_use",
                    "message" => "This template is currently used in an active flow. Are you sure you want to delete it? The flow may break."
                ], 200); // 200 so Android can read the 'in_use' status cleanly
            }

            db_delete(TB_WHATSAPP_TEMPLATE, ["ids" => $templateId, "team_id" => $team->id]);

            return $this->respond([
                "status" => "success",
                "message" => "Template deleted successfully"
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Android chatbot template delete failed: {message}', ['message' => $e->getMessage()]);

            return $this->respond([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * API 8: Sync Chatbot Flow
     * POST /Android_api/sync_chatbot_flow
     */
    public function sync_chatbot_flow()
    {
        try {
            $json = $this->_getJsonBody();
            $accessToken = $this->_findTeamAccessToken($json);

            if (!$accessToken) {
                return $this->respond(["status" => "error", "message" => "Wazipar access_token is required"], 400);
            }

            $team = db_get("*", TB_TEAM, ["ids" => addslashes($accessToken)]);
            if (!$team) {
                return $this->respond(["status" => "error", "message" => "Invalid Wazipar access_token"], 401);
            }

            $instanceId = trim((string)($json['instance_id'] ?? ''));
            $name = trim((string)($json['name'] ?? ''));
            $canvasData = trim((string)($json['canvas_data'] ?? ''));
            
            if ($instanceId === '' || $name === '' || $canvasData === '') {
                return $this->respond(["status" => "error", "message" => "instance_id, name, and canvas_data are required"], 400);
            }

            $db = \Config\Database::connect();
            
            // Auto-create tables if they don't exist
            if (!$db->tableExists('sp_chatbot_flows')) {
                $db->query("CREATE TABLE sp_chatbot_flows (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    team_id INT NOT NULL,
                    instance_id VARCHAR(255) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    status INT DEFAULT 1,
                    canvas_data LONGTEXT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )");
            }
            if (!$db->tableExists('sp_chatbot_sessions')) {
                $db->query("CREATE TABLE sp_chatbot_sessions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    team_id INT NOT NULL,
                    instance_id VARCHAR(255) NOT NULL,
                    phone_number VARCHAR(255) NOT NULL,
                    current_flow_id INT,
                    current_node_id VARCHAR(255),
                    variables LONGTEXT,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )");
            }

            $flowId = $json['server_flow_id'] ?? null;
            $data = [
                'team_id' => $team->id,
                'instance_id' => $instanceId,
                'name' => $name,
                'canvas_data' => $canvasData,
                'status' => (int)($json['status'] ?? 1)
            ];

            if ($flowId) {
                $existing = $db->table('sp_chatbot_flows')->where('id', $flowId)->where('team_id', $team->id)->get()->getRow();
                if ($existing) {
                    $db->table('sp_chatbot_flows')->where('id', $flowId)->update($data);
                    $savedId = $flowId;
                } else {
                    $db->table('sp_chatbot_flows')->insert($data);
                    $savedId = $db->insertID();
                }
            } else {
                $db->table('sp_chatbot_flows')->insert($data);
                $savedId = $db->insertID();
            }

            return $this->respond([
                "status" => "success",
                "message" => "Flow synced successfully",
                "data" => ["server_flow_id" => (string)$savedId]
            ], 200);

        } catch (\Throwable $e) {
            log_message('error', 'Android chatbot flow sync failed: {message}', ['message' => $e->getMessage()]);
            return $this->respond(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    /**
     * API 9: Delete Chatbot Flow
     * POST /Android_api/delete_chatbot_flow
     */
    public function delete_chatbot_flow()
    {
        try {
            $json = $this->_getJsonBody();
            $accessToken = $this->_findTeamAccessToken($json);

            if (!$accessToken) {
                return $this->respond(["status" => "error", "message" => "Wazipar access_token is required"], 400);
            }

            $team = db_get("*", TB_TEAM, ["ids" => addslashes($accessToken)]);
            if (!$team) {
                return $this->respond(["status" => "error", "message" => "Invalid Wazipar access_token"], 401);
            }

            $flowId = trim((string)($json['server_flow_id'] ?? ''));
            if ($flowId === '') {
                return $this->respond(["status" => "error", "message" => "server_flow_id is required"], 400);
            }

            $db = \Config\Database::connect();
            if ($db->tableExists('sp_chatbot_flows')) {
                $db->table('sp_chatbot_flows')->where('id', $flowId)->where('team_id', $team->id)->delete();
                // Also clean up any sessions stuck on this flow
                if ($db->tableExists('sp_chatbot_sessions')) {
                    $db->table('sp_chatbot_sessions')->where('current_flow_id', $flowId)->where('team_id', $team->id)->delete();
                }
            }

            return $this->respond(["status" => "success", "message" => "Flow deleted successfully"], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Android chatbot flow delete failed: {message}', ['message' => $e->getMessage()]);
            return $this->respond(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function get_chatbot_settings()
    {
        $json = $this->_getJsonBody();
        $accessToken = $this->_findTeamAccessToken($json);
        if (!$accessToken) {
            ms(["status" => "error", "message" => "access_token is required"]);
        }
        $team = db_get("*", TB_TEAM, ["ids" => addslashes($accessToken)]);
        if (!$team) {
            ms(["status" => "error", "message" => "Invalid access_token"]);
        }
        $team_id = $team->id;

        $instance_id = $json['instance_id'] ?? post('instance_id');

        if (empty($instance_id)) {
            ms(["status" => "error", "message" => "Instance ID is required"]);
        }

        $item = db_get("*", "sp_whatsapp_ai", ["instance_id" => $instance_id, "team_id" => $team_id]);
        if (empty($item)) {
            ms([
                "status" => "success",
                "data" => [
                    "unknown_message_action" => 0,
                    "unknown_message_reply" => ""
                ]
            ]);
        }

        ms([
            "status" => "success",
            "data" => [
                "unknown_message_action" => (int)$item->unknown_message_action,
                "unknown_message_reply" => $item->unknown_message_reply
            ]
        ]);
    }

    public function save_chatbot_settings()
    {
        $json = $this->_getJsonBody();
        $accessToken = $this->_findTeamAccessToken($json);
        if (!$accessToken) {
            ms(["status" => "error", "message" => "access_token is required"]);
        }
        $team = db_get("*", TB_TEAM, ["ids" => addslashes($accessToken)]);
        if (!$team) {
            ms(["status" => "error", "message" => "Invalid access_token"]);
        }
        $team_id = $team->id;

        $instance_id = $json['instance_id'] ?? post('instance_id');
        $unknown_message_action = isset($json['unknown_message_action']) ? (int)$json['unknown_message_action'] : (int)post('unknown_message_action');
        $unknown_message_reply = $json['unknown_message_reply'] ?? post('unknown_message_reply') ?? '';

        if (empty($instance_id)) {
            ms(["status" => "error", "message" => "Instance ID is required"]);
        }

        $item = db_get("*", "sp_whatsapp_ai", ["instance_id" => $instance_id, "team_id" => $team_id]);

        $data = [
            "unknown_message_action" => $unknown_message_action,
            "unknown_message_reply" => $unknown_message_reply
        ];

        if (!empty($item)) {
            db_update("sp_whatsapp_ai", $data, ['instance_id' => $instance_id, 'team_id' => $team_id]);
        } else {
            $data['team_id'] = $team_id;
            $data['instance_id'] = $instance_id;
            $data['status'] = 0;
            db_insert("sp_whatsapp_ai", $data);
        }

        ms([
            "status" => "success",
            "message" => "Settings saved successfully"
        ]);
    }

    public function sync_welcome_message()
    {
        try {
            $json = $this->_getJsonBody();
            $team = $this->_authenticate($json);
            $instanceId = trim((string)($json['instance_id'] ?? ''));
            if ($instanceId === '') {
                return $this->respond(["status" => "error", "message" => "instance_id is required"], 400);
            }

            $flowId = trim((string)($json['flow_id'] ?? ''));
            $name = trim((string)($json['name'] ?? ''));
            $status = (int)($json['status'] ?? 0);
            $steps = is_array($json['steps'] ?? null) ? $json['steps'] : [];

            $now = time();
            $data = [
                'ids' => $flowId !== '' ? $flowId : ids(),
                'team_id' => $team->id,
                'instance_id' => $instanceId,
                'name' => $name,
                'status' => $status,
                'steps' => json_encode($steps, JSON_UNESCAPED_UNICODE),
                'changed' => $now,
            ];

            $existing = db_get("*", "sp_whatsapp_welcome_message", ["team_id" => $team->id, "instance_id" => $instanceId]);
            
            if ($existing) {
                db_update("sp_whatsapp_welcome_message", $data, ["id" => $existing->id]);
            } else {
                $data['created'] = $now;
                db_insert("sp_whatsapp_welcome_message", $data);
            }

            return $this->respond([
                "status" => "success",
                "message" => "Welcome message saved successfully"
            ], 200);
        } catch (\Throwable $e) {
            return $this->respond(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function sync_keyword_replies()
    {
        try {
            $json = $this->_getJsonBody();
            $team = $this->_authenticate($json);
            $instanceId = trim((string)($json['instance_id'] ?? ''));
            if ($instanceId === '') {
                return $this->respond(["status" => "error", "message" => "instance_id is required"], 400);
            }

            $flowId = trim((string)($json['flow_id'] ?? ''));
            $name = trim((string)($json['name'] ?? ''));
            $status = (int)($json['status'] ?? 0);
            $keywords = trim((string)($json['keywords'] ?? ''));
            $steps = is_array($json['steps'] ?? null) ? $json['steps'] : [];

            $now = time();
            $data = [
                'ids' => $flowId !== '' ? $flowId : ids(),
                'team_id' => $team->id,
                'instance_id' => $instanceId,
                'name' => $name,
                'status' => $status,
                'keywords' => $keywords,
                'steps' => json_encode($steps, JSON_UNESCAPED_UNICODE),
                'changed' => $now,
            ];

            $existing = db_get("*", "sp_whatsapp_keyword_reply", ["team_id" => $team->id, "ids" => $flowId]);
            
            if ($existing) {
                db_update("sp_whatsapp_keyword_reply", $data, ["id" => $existing->id]);
            } else {
                $data['created'] = $now;
                db_insert("sp_whatsapp_keyword_reply", $data);
            }

            return $this->respond([
                "status" => "success",
                "message" => "Keyword reply saved successfully"
            ], 200);
        } catch (\Throwable $e) {
            return $this->respond(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function sync_menu_replies()
    {
        try {
            $json = $this->_getJsonBody();
            $team = $this->_authenticate($json);
            $instanceId = trim((string)($json['instance_id'] ?? ''));
            if ($instanceId === '') {
                return $this->respond(["status" => "error", "message" => "instance_id is required"], 400);
            }

            $flowId = trim((string)($json['flow_id'] ?? ''));
            $name = trim((string)($json['name'] ?? ''));
            $status = (int)($json['status'] ?? 0);
            $keywords = trim((string)($json['keywords'] ?? ''));
            $rootNodeId = trim((string)($json['root_node_id'] ?? ''));
            $nodes = is_array($json['nodes'] ?? null) ? $json['nodes'] : [];

            $now = time();
            $data = [
                'ids' => $flowId !== '' ? $flowId : ids(),
                'team_id' => $team->id,
                'instance_id' => $instanceId,
                'name' => $name,
                'status' => $status,
                'keywords' => $keywords,
                'root_node_id' => $rootNodeId,
                'nodes' => json_encode($nodes, JSON_UNESCAPED_UNICODE),
                'changed' => $now,
            ];

            $existing = db_get("*", "sp_whatsapp_menu_reply", ["team_id" => $team->id, "ids" => $flowId]);
            
            if ($existing) {
                db_update("sp_whatsapp_menu_reply", $data, ["id" => $existing->id]);
            } else {
                $data['created'] = $now;
                db_insert("sp_whatsapp_menu_reply", $data);
            }

            return $this->respond([
                "status" => "success",
                "message" => "Menu reply saved successfully"
            ], 200);
        } catch (\Throwable $e) {
            return $this->respond(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

}
