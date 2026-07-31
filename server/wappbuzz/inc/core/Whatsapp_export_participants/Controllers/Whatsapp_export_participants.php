<?php
namespace Core\Whatsapp_export_participants\Controllers;

class Whatsapp_export_participants extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Whatsapp_export_participants\Models\Whatsapp_export_participantsModel();
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

        $data['content'] = view('Core\Whatsapp_export_participants\Views\content', $data_content );

        return view('Core\Whatsapp\Views\index', $data);
    }

    public function groups() {
        $team_id = get_team("id");
        $access_token = get_team("ids");
        $ids = post("account");
        $account = db_get("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "login_type" => 2, "ids" => $ids, "team_id" => $team_id]);

        if(!empty($account)){
            $result = wa_get_curl("get_groups", [ "instance_id" => $account->token, "access_token" => $access_token ]);

            // Check if result is null or empty
            if(empty($result)){
                $data = [
                    "status" => "error",
                    "message" => "Unable to connect to WhatsApp server. Please try again later."
                ];
                return view('Core\Whatsapp_export_participants\Views\groups', $data);
            }

            // Check for error status with proper null safety
            if(isset($result->status) && $result->status == "error"){
                $data = [
                    "status" => "error",
                    "message" => $result->message ?? "An error occurred while fetching groups"
                ];
                return view('Core\Whatsapp_export_participants\Views\groups', $data);
            }

            $data = [
                "status" => "success",
                "result" => $result,
                "account" => $account,
                "access_token" => $access_token,
            ];

        }else{
            $data = [
                "status" => "error",
                "message" => "WhatsApp account does not exist. Please try again or re-login your WhatsApp account"
            ];

        }

        return view('Core\Whatsapp_export_participants\Views\groups', $data);
    }

    public function export_group($account_id = false, $group_id = false){
        $team_id = get_team("id");
        $access_token = get_team("ids");
        $account = db_get("*", TB_ACCOUNTS, ["social_network" => "whatsapp", "login_type" => 2, "ids" => $account_id, "team_id" => $team_id]);

        if(!empty($account)){
            $result = wa_get_curl("get_groups", [ "instance_id" => $account->token, "access_token" => $access_token ]);

            if(empty($result)){
                redirect_to( get_module_url() );
                return;
            }

            if(isset($result->status) && $result->status == "error"){
                redirect_to( get_module_url() );
                return;
            }

            if(!empty( $result->data )){

                foreach ($result->data as $key => $value) {
                    if($value->id == $group_id){
                        $participants = $value->participants ?? [];

                        // Try to get subscriber names from database (lightweight indexed query)
                        $subscriber_names = [];
                        if (!empty($participants)) {
                            try {
                                $db = \Config\Database::connect();
                                if ($db->tableExists(TB_WHATSAPP_SUBSCRIBERS)) {
                                    $builder = $db->table(TB_WHATSAPP_SUBSCRIBERS);
                                    $builder->select('chatid, contact_data');
                                    $builder->where('instance_id', $account->token);
                                    $subscribers = $builder->get()->getResult();

                                    foreach ($subscribers as $sub) {
                                        $name = '';
                                        if (!empty($sub->contact_data)) {
                                            $contactData = json_decode($sub->contact_data, true);
                                            if (is_array($contactData)) {
                                                $name = $contactData['pushName'] ?? $contactData['name'] ?? $contactData['notify'] ?? '';
                                            }
                                        }
                                        // Map by phone number (without @s.whatsapp.net suffix)
                                        $phone = explode('@', $sub->chatid)[0] ?? '';
                                        if ($phone && $name) {
                                            $subscriber_names[$phone] = $name;
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                // Silently continue without names if query fails
                                log_message('error', 'Export participants: Failed to get subscriber names - ' . $e->getMessage());
                            }
                        }

                        // Log first participant structure for debugging (only once)
                        if (!empty($participants) && count($participants) > 0) {
                            $first_participant = $participants[0];
                            log_message('debug', 'Export participants: First participant structure: ' . json_encode($first_participant));
                        }

                        $data = [];
                        foreach ($participants as $participant) {
                            $participant_id = $participant->id ?? '';

                            // WhatsApp may return LID (Linked Device ID) format like "280246767083586@lid"
                            // or traditional format like "917357935653@s.whatsapp.net"
                            // Note: LIDs cannot be converted to phone numbers on the backend without WhatsApp API support
                            // Check for multiple possible phone number sources in the participant object
                            $phone_number = '';

                            // Priority 1: Check for 'number' property (direct phone number)
                            if (isset($participant->number) && !empty($participant->number)) {
                                $phone_number = $participant->number;
                                // Remove + prefix if present
                                $phone_number = ltrim($phone_number, '+');
                            }
                            // Priority 2: Check for 'phone' property
                            elseif (isset($participant->phone) && !empty($participant->phone)) {
                                $phone_number = $participant->phone;
                                $phone_number = ltrim($phone_number, '+');
                            }
                            // Priority 3: Check for traditional JID format (ends with @s.whatsapp.net)
                            elseif (strpos($participant_id, '@s.whatsapp.net') !== false) {
                                $phone_number = explode('@', $participant_id)[0] ?? '';
                            }
                            // Priority 4: Check for 'jid' property that might contain phone format
                            elseif (isset($participant->jid) && !empty($participant->jid)) {
                                if (strpos($participant->jid, '@s.whatsapp.net') !== false) {
                                    $phone_number = explode('@', $participant->jid)[0] ?? '';
                                }
                            }
                            // Priority 5: If LID format (@lid suffix), phone number not directly available
                            // Note: LID is WhatsApp's Linked Device ID and cannot be converted to phone without API
                            elseif (strpos($participant_id, '@lid') !== false) {
                                $phone_number = '';
                            }
                            // Priority 6: Check if the ID is purely numeric and looks like a phone number
                            // Real phone numbers are typically 10-13 digits (including country code)
                            // LIDs (Linked Device IDs) are typically 14-18 digits
                            // WhatsApp phone JIDs should end with @s.whatsapp.net
                            else {
                                $potential_number = explode('@', $participant_id)[0] ?? '';
                                // Only treat as phone if:
                                // 1. Purely numeric
                                // 2. 10-13 digits (valid phone number length with country code)
                                // Numbers longer than 13 digits are likely LIDs, not phone numbers
                                if (is_numeric($potential_number) && strlen($potential_number) >= 10 && strlen($potential_number) <= 13) {
                                    $phone_number = $potential_number;
                                } else {
                                    // LID or other format - cannot extract phone number from backend
                                    // This occurs when WhatsApp returns Linked Device IDs instead of phone JIDs
                                    $phone_number = '';
                                }
                            }

                            // Try to get name from: 1) participant object, 2) subscriber table
                            $user_name = '';
                            if (isset($participant->name) && !empty($participant->name)) {
                                $user_name = $participant->name;
                            } elseif (isset($participant->pushName) && !empty($participant->pushName)) {
                                $user_name = $participant->pushName;
                            } elseif (isset($participant->notify) && !empty($participant->notify)) {
                                $user_name = $participant->notify;
                            } elseif (!empty($phone_number) && isset($subscriber_names[$phone_number])) {
                                $user_name = $subscriber_names[$phone_number];
                            }

                            $data[] = [
                                'Mobile number' => $phone_number,
                                'User' => $user_name,
                                'ID' => $participant_id
                            ];
                        }

                        // Use 'name' property instead of 'subject' - the API returns 'name' for group name
                        $group_name = $value->name ?? 'Group';
                        // Sanitize filename to remove invalid characters
                        $safe_name = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $group_name);
                        $filename = trim($safe_name) . " participants " . date("Y-m-d") . ".csv";

                        download_send_headers($filename);
                        echo array2csv($data);
                        exit; // Exit after sending the file to prevent further output
                    }
                }

                // Group not found in the list
                redirect_to( get_module_url() );
                return;
            }else{
                redirect_to( get_module_url() );
                return;
            }
        }else{
            redirect_to( get_module_url() );
            return;
        }
    }
}