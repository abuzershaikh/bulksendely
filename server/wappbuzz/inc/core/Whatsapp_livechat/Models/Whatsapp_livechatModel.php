<?php
namespace Core\Whatsapp_livechat\Models;
use CodeIgniter\Model;

class Whatsapp_livechatModel extends Model
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function block_plans(){
        return [
            "tab" => 15,
            "position" => 750,
            "label" => __("Whatsapp tool"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => $this->config['name'],
                ],
            ]
        ];
    }

    /**
     * This method is called by WhatsappModel::get_modules() to include this module
     * in the WhatsApp sidebar. Position 3100 places it after Profile (3000).
     */
    public function block_whatsapp(){
        return array(
            "position" => 3100,
            "config" => $this->config,
        );
    }

    /**
     * Get unique chats (contacts) for a given instance from sp_whatsapp_subscriber table
     * This table stores subscriber/contact information with last message data
     */
    public function get_chats($instance_id)
    {
        try {
            $db = \Config\Database::connect();

            // Check if table exists first
            if (!$db->tableExists(TB_WHATSAPP_SUBSCRIBERS)) {
                return [];
            }

            // Query subscribers table which has chatid, lastMessage, lastMessageTime, unreadMessages
            $builder = $db->table(TB_WHATSAPP_SUBSCRIBERS);
            $builder->select("id, chatid, contact_data, lastMessage, lastMessageTime, unreadMessages");
            $builder->where("instance_id", $instance_id);
            $builder->orderBy("lastMessageTime", "DESC");
            $query = $builder->get();

            if ($query) {
                $results = $query->getResult();

                // Process results to extract contact name from contact_data JSON
                foreach ($results as &$row) {
                    $row->remoteJid = $row->chatid;
                    $row->last_message = $row->lastMessage;
                    $row->last_message_time = $row->lastMessageTime;
                    $row->unread_count = (int)($row->unreadMessages ?? 0);

                    // Detect chat type from chatid suffix
                    $row->isGroup = $this->isGroupChat($row->chatid);
                    $row->isNewsletter = strpos($row->chatid, '@newsletter') !== false;
                    $row->chatType = $row->isGroup ? 'group' : ($row->isNewsletter ? 'newsletter' : 'contact');

                    // Try to get contact info from contact_data JSON
                    $row->pushName = '';
                    $row->profilePicUrl = '';
                    if (!empty($row->contact_data)) {
                        $contactData = json_decode($row->contact_data, true);
                        if (is_array($contactData)) {
                            $row->pushName = $contactData['pushName'] ?? $contactData['name'] ?? $contactData['notify'] ?? '';
                            $row->profilePicUrl = $contactData['profilePicUrl'] ?? '';
                        }
                    }

                    // If no name, use phone number or chatid
                    if (empty($row->pushName)) {
                        if ($row->isGroup) {
                            $row->pushName = __('Group');
                        } elseif ($row->isNewsletter) {
                            $row->pushName = __('Newsletter');
                        } else {
                            $row->pushName = $this->extractPhone($row->chatid);
                        }
                    }
                }

                return $results;
            }
        } catch (\Exception $e) {
            log_message('error', 'Whatsapp_livechat get_chats error: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Check if chatid is a group chat
     */
    private function isGroupChat($chatid)
    {
        if (empty($chatid)) return false;
        return strpos($chatid, '@g.us') !== false;
    }

    /**
     * Get messages for a specific chat from sp_whatsapp_messages table
     */
    public function get_messages($instance_id, $remote_jid, $limit = 50)
    {
        try {
            $db = \Config\Database::connect();

            // Check if table exists first
            if (!$db->tableExists(TB_WHATSAPP_MESSAGES)) {
                return [];
            }

            $builder = $db->table(TB_WHATSAPP_MESSAGES);
            $builder->select("id, remoteJid, fromMe, body, mediaUrl, mediaType, createdAt, isDeleted");
            $builder->where("instance_id", $instance_id);
            $builder->where("remoteJid", $remote_jid);
            $builder->where("isDeleted", 0);
            $builder->orderBy("createdAt", "ASC");
            $builder->limit($limit);
            $query = $builder->get();

            if ($query) {
                $results = $query->getResult();

                // Get the WhatsApp server URL for media files
                $wa_server_url = get_option('whatsapp_server_url', '');
                $wa_server_url = rtrim($wa_server_url, '/'); // Remove trailing slash

                // Map to expected format for the view
                foreach ($results as &$row) {
                    $row->text = $row->body;
                    $row->messageTimestamp = $row->createdAt;
                    $row->message_type = $row->fromMe ? 'outgoing' : 'incoming';
                    $row->media = !empty($row->mediaUrl) ? 1 : 0;

                    // Construct full media URL if mediaUrl is just a filename
                    if (!empty($row->mediaUrl)) {
                        // Check if it's already a full URL
                        if (stripos($row->mediaUrl, 'http://') === 0 || stripos($row->mediaUrl, 'https://') === 0) {
                            $row->imagePath = $row->mediaUrl;
                        } else {
                            // Prepend the WhatsApp server URL with /files/ path
                            $row->imagePath = $wa_server_url . '/files/' . $row->mediaUrl;
                        }
                        $row->mediaUrl = $row->imagePath;
                    } else {
                        $row->imagePath = '';
                    }
                }

                return $results;
            }
        } catch (\Exception $e) {
            log_message('error', 'Whatsapp_livechat get_messages error: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Get unread message count for an instance
     */
    public function get_unread_count($instance_id)
    {
        try {
            $db = \Config\Database::connect();

            // Check if table exists first
            if (!$db->tableExists(TB_WHATSAPP_SUBSCRIBERS)) {
                return 0;
            }

            $builder = $db->table(TB_WHATSAPP_SUBSCRIBERS);
            $builder->selectSum("unreadMessages", "count");
            $builder->where("instance_id", $instance_id);
            $query = $builder->get();

            if ($query) {
                $result = $query->getRow();
                return $result ? (int)$result->count : 0;
            }
        } catch (\Exception $e) {
            log_message('error', 'Whatsapp_livechat get_unread_count error: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Mark messages as read for a specific chat
     */
    public function mark_as_read($instance_id, $remote_jid)
    {
        try {
            $db = \Config\Database::connect();

            // Update subscriber record
            $builder = $db->table(TB_WHATSAPP_SUBSCRIBERS);
            $builder->where("instance_id", $instance_id);
            $builder->where("chatid", $remote_jid);
            $builder->update(["unreadMessages" => 0]);

            // Update messages
            if ($db->tableExists(TB_WHATSAPP_MESSAGES)) {
                $builder = $db->table(TB_WHATSAPP_MESSAGES);
                $builder->where("instance_id", $instance_id);
                $builder->where("remoteJid", $remote_jid);
                $builder->where("fromMe", 0);
                $builder->update(["read" => 1]);
            }

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Whatsapp_livechat mark_as_read error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Extract phone number from chatid (e.g., 919537744649@s.whatsapp.net -> 919537744649)
     */
    private function extractPhone($chatid)
    {
        if (empty($chatid)) return '';
        $parts = explode('@', $chatid);
        return $parts[0] ?? $chatid;
    }

    /**
     * Save a sent message to the database
     *
     * @param string $instance_id The WhatsApp instance ID
     * @param string $remote_jid The recipient's JID (e.g., 917688907953@s.whatsapp.net)
     * @param string $message The message body text
     * @param string|null $media_url The media URL if any
     * @param string|null $media_type The media type (imageMessage, videoMessage, documentMessage, audioMessage)
     * @return bool True on success, false on failure
     */
    public function save_sent_message($instance_id, $remote_jid, $message, $media_url = null, $media_type = null)
    {
        try {
            $db = \Config\Database::connect();

            // Check if table exists first
            if (!$db->tableExists(TB_WHATSAPP_MESSAGES)) {
                log_message('error', 'Whatsapp_livechat save_sent_message: Table ' . TB_WHATSAPP_MESSAGES . ' does not exist');
                return false;
            }

            // Generate a unique message ID
            $message_id = 'sent_' . uniqid() . '_' . time();
            $current_time = time();

            // Determine media type from URL if not provided
            if (!empty($media_url) && empty($media_type)) {
                $media_type = $this->detectMediaType($media_url);
            }

            $data = [
                'id' => $message_id,
                'instance_id' => $instance_id,
                'remoteJid' => $remote_jid,
                'contactId' => null,
                'participant' => null,
                'ack' => null,
                'read' => 1, // Sent messages are already read
                'fromMe' => 1, // This is an outgoing message
                'body' => $message ?? '',
                'mediaUrl' => $media_url ?? '',
                'mediaType' => $media_type ?? '',
                'isDeleted' => 0,
                'createdAt' => $current_time,
                'updatedAt' => $current_time,
                'dataJson' => json_encode([
                    'source' => 'livechat',
                    'sent_at' => date('Y-m-d H:i:s', $current_time)
                ])
            ];

            $builder = $db->table(TB_WHATSAPP_MESSAGES);
            $result = $builder->insert($data);

            if ($result) {
                // Also update the subscriber's last message
                $this->updateSubscriberLastMessage($instance_id, $remote_jid, $message ?? '[Media]', $current_time);
                log_message('debug', 'Whatsapp_livechat save_sent_message: Message saved with ID ' . $message_id);
            }

            return $result;
        } catch (\Exception $e) {
            log_message('error', 'Whatsapp_livechat save_sent_message error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Detect media type from URL based on file extension
     */
    private function detectMediaType($url)
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        $video_extensions = ['mp4', 'avi', 'mov', 'wmv', 'webm', 'mkv', '3gp'];
        $audio_extensions = ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'opus'];
        $document_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];

        if (in_array($extension, $image_extensions)) {
            return 'imageMessage';
        } elseif (in_array($extension, $video_extensions)) {
            return 'videoMessage';
        } elseif (in_array($extension, $audio_extensions)) {
            return 'audioMessage';
        } elseif (in_array($extension, $document_extensions)) {
            return 'documentMessage';
        }

        return 'documentMessage'; // Default to document
    }

    /**
     * Update subscriber's last message info after sending a message
     */
    private function updateSubscriberLastMessage($instance_id, $remote_jid, $message, $timestamp)
    {
        try {
            $db = \Config\Database::connect();

            if (!$db->tableExists(TB_WHATSAPP_SUBSCRIBERS)) {
                return false;
            }

            // Check if subscriber exists
            $builder = $db->table(TB_WHATSAPP_SUBSCRIBERS);
            $builder->where("instance_id", $instance_id);
            $builder->where("chatid", $remote_jid);
            $existing = $builder->get()->getRow();

            if ($existing) {
                // Update existing subscriber
                $builder = $db->table(TB_WHATSAPP_SUBSCRIBERS);
                $builder->where("instance_id", $instance_id);
                $builder->where("chatid", $remote_jid);
                $builder->update([
                    "lastMessage" => $message,
                    "lastMessageTime" => $timestamp
                ]);
            } else {
                // Create new subscriber entry
                $phone = $this->extractPhone($remote_jid);
                $builder = $db->table(TB_WHATSAPP_SUBSCRIBERS);
                $builder->insert([
                    "instance_id" => $instance_id,
                    "chatid" => $remote_jid,
                    "phone" => $phone,
                    "contact_data" => json_encode(["pushName" => $phone]),
                    "lastMessage" => $message,
                    "lastMessageTime" => $timestamp,
                    "unreadMessages" => 0,
                    "createdAt" => $timestamp,
                    "updatedAt" => $timestamp
                ]);
            }

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Whatsapp_livechat updateSubscriberLastMessage error: ' . $e->getMessage());
            return false;
        }
    }
}
