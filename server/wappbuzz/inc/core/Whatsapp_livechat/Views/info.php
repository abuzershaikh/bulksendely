<?php if ($status == "success"): ?>
<?php $livechat_enabled = get_option('whatsapp_livechat_status', 0) == 1; ?>
<!-- Include Socket.io for real-time messaging (always load so toggle can enable it) -->
<?php if (!empty($whatsapp_server_url)): ?>
<script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
<?php endif; ?>

<div class="card b-r-6 h-100 <?php echo !$livechat_enabled ? 'livechat-disabled' : ''; ?>" id="livechat_main_card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fad fa-comments <?php echo $livechat_enabled ? 'text-success' : 'text-muted'; ?> me-2"></i>
            <?php _e("Live Chat")?> - <?php _ec(get_data($account, "name"))?>
        </h3>
        <div class="card-toolbar d-flex align-items-center gap-2">
            <!-- Live Chat Enable/Disable Toggle -->
            <div class="form-check form-switch me-2" title="<?php _e("Enable/Disable Live Chat System")?>">
                <input class="form-check-input" type="checkbox" id="livechat_toggle" <?php echo $livechat_enabled ? 'checked' : ''; ?>>
                <label class="form-check-label small" for="livechat_toggle"><?php echo $livechat_enabled ? __('Enabled') : __('Disabled'); ?></label>
            </div>
            <button type="button" class="btn btn-sm btn-light-primary btn-icon" id="livechat_refresh_all_btn" title="<?php _e("Refresh all contacts and messages")?>" <?php echo !$livechat_enabled ? 'disabled' : ''; ?>>
                <i class="fad fa-sync-alt"></i>
            </button>
            <span class="badge <?php echo $livechat_enabled ? 'badge-light-success' : 'badge-light-secondary'; ?>" id="livechat_status_badge"><?php echo $livechat_enabled ? __('Connected') : __('Disabled'); ?></span>
        </div>
    </div>
    <div class="card-body p-0">
        <input type="hidden" id="livechat_instance_id" value="<?php _ec(get_data($account, "token"))?>">
        <input type="hidden" id="livechat_access_token" value="<?php _ec($access_token)?>">
        <input type="hidden" id="livechat_server_url" value="<?php _ec($whatsapp_server_url)?>">
        <input type="hidden" id="livechat_enabled" value="<?php echo $livechat_enabled ? '1' : '0'; ?>">

        <div class="d-flex livechat-container">
            <!-- Chat List Sidebar -->
            <div class="border-end livechat-sidebar d-flex flex-column">
                <div class="p-3 border-bottom">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fal fa-search"></i></span>
                        <input type="text" class="form-control form-control-solid" id="livechat_search" placeholder="<?php _e("Search contacts...")?>">
                    </div>
                </div>
                <div class="flex-grow-1 overflow-auto" id="livechat_contacts">
                    <?php if (!empty($chats) && is_array($chats) && count($chats) > 0): ?>
                        <?php foreach ($chats as $chat):
                            $displayName = $chat->pushName ?? '';
                            $phoneNumber = '';
                            $secondaryText = '';
                            $isGroup = !empty($chat->isGroup);
                            $isNewsletter = !empty($chat->isNewsletter);

                            // Extract phone number from remoteJid
                            if (!empty($chat->remoteJid)) {
                                $jidParts = explode('@', $chat->remoteJid);
                                $phoneNumber = $jidParts[0];
                            }

                            // Determine display format based on type
                            if ($isGroup) {
                                // For groups: show group name and group ID
                                if (empty($displayName)) {
                                    $displayName = $phoneNumber; // Use group ID if no name
                                }
                                $secondaryText = $chat->remoteJid ?? '';
                            } elseif ($isNewsletter) {
                                // For newsletters: show name and ID
                                if (empty($displayName)) {
                                    $displayName = 'Newsletter';
                                }
                                $secondaryText = $chat->remoteJid ?? '';
                            } else {
                                // For personal contacts
                                if (!empty($displayName)) {
                                    // Has name - show name on first line, phone on second
                                    $secondaryText = '+' . $phoneNumber;
                                } else {
                                    // No name - show phone number as display name
                                    $displayName = '+' . $phoneNumber;
                                    $secondaryText = '';
                                }
                            }

                            $unreadCount = isset($chat->unread_count) ? (int)$chat->unread_count : 0;
                            $profilePic = $chat->profilePicUrl ?? '';
                            $lastTime = '';
                            if (!empty($chat->last_message_time)) {
                                $timestamp = (int)$chat->last_message_time;
                                $today = strtotime('today');
                                if ($timestamp >= $today) {
                                    $lastTime = date('H:i', $timestamp);
                                } else {
                                    $lastTime = date('d/m', $timestamp);
                                }
                            }
                            // Determine avatar color based on type
                            $avatarBg = $isGroup ? 'bg-light-info text-info' : ($isNewsletter ? 'bg-light-warning text-warning' : 'bg-light-success text-success');
                            $avatarIcon = $isGroup ? 'users' : ($isNewsletter ? 'newspaper' : '');
                        ?>
                        <div class="livechat-contact p-3 border-bottom cursor-pointer hover-bg-light <?php echo $unreadCount > 0 ? 'has-unread' : ''?>"
                             data-jid="<?php _ec($chat->remoteJid ?? '')?>"
                             data-name="<?php _ec($displayName)?>"
                             data-phone="<?php _ec($phoneNumber)?>"
                             data-is-group="<?php echo $isGroup ? '1' : '0'?>">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-40px symbol-circle me-3 position-relative">
                                    <?php if (!empty($profilePic)): ?>
                                    <img src="<?php _ec($profilePic)?>" alt="" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">
                                    <?php else: ?>
                                    <span class="symbol-label <?php echo $avatarBg?> fs-6 fw-bolder">
                                        <?php if ($avatarIcon): ?>
                                        <i class="fad fa-<?php echo $avatarIcon?> fs-6"></i>
                                        <?php else: ?>
                                        <?php _ec(strtoupper(substr($displayName ?: 'U', 0, 1)))?>
                                        <?php endif; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($isGroup): ?>
                                    <span class="position-absolute bottom-0 end-0 bg-info rounded-circle" style="width:12px;height:12px;border:2px solid #fff;"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="fw-bold text-gray-800 text-truncate <?php echo $unreadCount > 0 ? 'fw-bolder' : ''?>">
                                                <?php if ($isGroup): ?><i class="fad fa-users text-info me-1 fs-8"></i><?php endif; ?>
                                                <?php _ec($displayName)?>
                                            </div>
                                            <?php if (!empty($secondaryText)): ?>
                                            <div class="text-gray-500 fs-8 text-truncate"><?php _ec($secondaryText)?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end ms-2 flex-shrink-0">
                                            <div class="text-gray-500 fs-8 text-nowrap <?php echo $unreadCount > 0 ? 'text-primary' : ''?>">
                                                <?php _ec($lastTime)?>
                                            </div>
                                            <?php if ($unreadCount > 0): ?>
                                            <span class="badge bg-success text-white rounded-pill mt-1" style="min-width:20px;font-size:11px;"><?php echo $unreadCount > 99 ? '99+' : $unreadCount?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-gray-500 fs-7 text-truncate mt-1" style="max-width: 100%;">
                                        <?php _ec($chat->last_message ?? '')?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-10 text-gray-500" id="livechat_no_contacts">
                            <i class="fad fa-inbox fs-2x mb-3 d-block"></i>
                            <div class="mb-3"><?php _e("No conversations yet")?></div>
                            <div class="fs-7 text-muted px-3"><?php _e("Start a new chat by entering a phone number below")?></div>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- New Chat Input -->
                <div class="p-3 border-top bg-light">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control form-control-solid" id="livechat_new_phone" placeholder="<?php _e("Phone number (with country code)")?>">
                        <button type="button" class="btn btn-success btn-sm" id="livechat_start_chat">
                            <i class="fal fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="flex-grow-1 d-flex flex-column livechat-main">
                <div class="p-3 border-bottom bg-light" id="livechat_header" style="display: none;">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-40px symbol-circle me-3">
                            <span class="symbol-label bg-light-primary text-primary fs-6 fw-bolder" id="livechat_contact_avatar">?</span>
                        </div>
                        <div>
                            <div class="fw-bold text-gray-800" id="livechat_contact_name"></div>
                            <div class="text-gray-500 fs-7" id="livechat_contact_phone"></div>
                        </div>
                    </div>
                </div>

                <div class="flex-grow-1 overflow-auto p-4" id="livechat_messages">
                    <div class="text-center py-10 text-gray-500" id="livechat_empty">
                        <i class="fad fa-comments fs-2x mb-3 d-block"></i>
                        <div><?php _e("Select a contact or enter a phone number to start chatting")?></div>
                    </div>
                </div>

                <div class="p-3 border-top" id="livechat_input" style="display: none;">
                    <!-- Media Upload Area -->
                    <?php if (permission("whatsapp_send_media")): ?>
                    <div class="mb-2" id="livechat_media_area">
                        <div class="fm-selected-media fm-selected-mini" id="livechat_medias" data-loading="false" data-result="html" data-select-multi="0">
                            <div class="fm-progress-bar bg-primary"></div>
                            <div class="items clearfix"></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2 align-items-center">
                        <?php if (permission("whatsapp_send_media")): ?>
                        <div class="dropdown">
                            <button type="button" class="btn btn-light btn-icon" data-bs-toggle="dropdown" aria-expanded="false" title="<?php _e("Attach media")?>">
                                <i class="fal fa-paperclip"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item btnOpenFileManager" href="javascript:void(0);" data-select-multi="0" data-type="image,video,doc,pdf,audio,other" data-id="0" data-name="livechat_medias">
                                        <i class="fad fa-folder-open me-2"></i> <?php _e("File Manager")?>
                                    </a>
                                </li>
                                <li>
                                    <label class="dropdown-item cursor-pointer">
                                        <i class="fad fa-upload me-2"></i> <?php _e("Upload File")?>
                                        <input id="upload_custom_livechat_medias" type="file" name="files[]" class="d-none">
                                    </label>
                                </li>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <input type="text" class="form-control form-control-solid flex-grow-1" id="livechat_message_input" placeholder="<?php _e("Type a message...")?>">
                        <button type="button" class="btn btn-success btn-icon" id="livechat_send_btn" title="<?php _e("Send message")?>">
                            <i class="fal fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function(){
    var currentJid = null;
    var instanceId = $('#livechat_instance_id').val();
    var accessToken = $('#livechat_access_token').val();
    var serverUrl = $('#livechat_server_url').val();
    var livechatSocket = null;
    var refreshInterval = null;
    var teamId = '<?php echo get_team("id")?>';
    var livechatEnabled = $('#livechat_enabled').val() == '1';

    console.log('Livechat: Page initialized');
    console.log('Livechat: instanceId:', instanceId);
    console.log('Livechat: accessToken:', accessToken ? 'set' : 'not set');
    console.log('Livechat: serverUrl:', serverUrl);
    console.log('Livechat: teamId:', teamId);
    console.log('Livechat: csrf:', typeof csrf !== 'undefined' ? csrf : 'undefined');

    // Helper function to get cookie value by name
    function getCookie(name) {
        var value = "; " + document.cookie;
        var parts = value.split("; " + name + "=");
        if (parts.length == 2) return parts.pop().split(";").shift();
        return null;
    }

    // Update global csrf variable from cookie after each AJAX request
    // This is needed because CSRF token regenerates on every request
    $(document).ajaxComplete(function(event, xhr, settings) {
        var newCsrf = getCookie('csrf_cookie_name');
        if(newCsrf && typeof csrf !== 'undefined') {
            csrf = newCsrf;
        }
    });

    // Initialize file manager upload
    <?php if (permission("whatsapp_send_media")): ?>
    if(typeof File_manager !== 'undefined') {
        File_manager.upload("#upload_custom_livechat_medias");
    }
    <?php endif; ?>

    // Initialize Socket.io for real-time updates
    function initSocket() {
        console.log('Livechat: Initializing socket, serverUrl:', serverUrl, 'io defined:', typeof io !== 'undefined');

        if(serverUrl && serverUrl != '' && typeof io !== 'undefined') {
            try {
                livechatSocket = io(serverUrl, {
                    transports: ['websocket', 'polling'],
                    reconnection: true,
                    reconnectionAttempts: 10,
                    reconnectionDelay: 2000
                });

                livechatSocket.on('connect', function() {
                    console.log('Livechat: Socket connected, id:', livechatSocket.id);
                });

                // Listen for the exact event name emitted by the backend: instance-{instance_id}-appMessage-create
                // This is emitted by CreateMessageService in extend.js when a new message is received
                var primaryEventName = 'instance-' + instanceId + '-appMessage-create';
                livechatSocket.on(primaryEventName, function(data) {
                    console.log('Livechat: Received primary event:', primaryEventName, data);
                    // The backend sends: { message: messageData, subscriber: contact }
                    handleNewMessage(data.message || data);
                });

                // Also listen for fallback event names (for backward compatibility)
                var eventNames = [
                    'new_message_' + teamId,
                    'incoming_message_' + teamId,
                    'message_received_' + teamId,
                    'chat_message_' + teamId,
                    'new_message_' + instanceId,
                    'message_' + instanceId
                ];

                eventNames.forEach(function(eventName) {
                    livechatSocket.on(eventName, function(data) {
                        console.log('Livechat: Received fallback event:', eventName, data);
                        handleNewMessage(data);
                    });
                });

                livechatSocket.on('connect_error', function(err) {
                    console.log('Livechat: Socket connection error:', err.message);
                });

                livechatSocket.on('disconnect', function(reason) {
                    console.log('Livechat: Socket disconnected:', reason);
                });

            } catch(e) {
                console.log('Livechat: Socket.io init error:', e);
            }
        } else {
            console.log('Livechat: Socket.io not initialized - serverUrl:', serverUrl, 'io:', typeof io);
        }

        // Always start polling as fallback/supplement
        startPolling();
    }

    function startPolling() {
        if(refreshInterval) clearInterval(refreshInterval);
        console.log('Livechat: Starting polling interval (10 seconds)');
        refreshInterval = setInterval(function() {
            // Always refresh contact list to update unread badges
            refreshContactList();

            if(currentJid) {
                // Also refresh messages for current chat
                refreshCurrentChat();
            }
        }, 10000); // Poll every 10 seconds
    }

    function refreshContactList() {
        if(!instanceId) return;

        $.ajax({
            url: '<?php _ec(get_module_url("get_contacts"))?>',
            type: 'POST',
            dataType: 'json',
            data: {
                instance_id: instanceId,
                csrf: csrf
            },
            success: function(response) {
                if(response && response.status == 'success' && response.contacts) {
                    // Update unread badges without re-rendering the entire list
                    updateContactBadges(response.contacts);
                }
            },
            error: function(xhr, status, error) {
                console.log('Livechat: Contact refresh error:', status, error);
            }
        });
    }

    function updateContactBadges(contacts) {
        contacts.forEach(function(contact) {
            var remoteJid = contact.remoteJid || contact.chatid || '';
            var unreadCount = parseInt(contact.unread_count) || 0;
            var $contact = $('.livechat-contact[data-jid="' + remoteJid + '"]');

            if($contact.length && !$contact.hasClass('active')) {
                var $badgeContainer = $contact.find('.text-end.ms-2.flex-shrink-0');
                if(!$badgeContainer.length) {
                    $badgeContainer = $contact.find('.text-end.flex-shrink-0');
                }
                var $badge = $contact.find('.badge.bg-success');

                if(unreadCount > 0) {
                    var displayCount = unreadCount > 99 ? '99+' : unreadCount;

                    if($badge.length) {
                        // Update existing badge
                        $badge.text(displayCount);
                    } else {
                        // Add new badge
                        if($badgeContainer.length) {
                            $badgeContainer.append('<span class="badge bg-success text-white rounded-pill mt-1" style="min-width:20px;font-size:11px;">' + displayCount + '</span>');
                        }
                    }

                    // Add styling for unread contacts
                    $contact.addClass('has-unread');
                    $contact.find('.fw-bold').addClass('fw-bolder');
                    $contact.find('.text-gray-500.fs-8.text-nowrap').addClass('text-primary');
                } else {
                    // Remove unread styling if count is 0
                    if($badge.length) {
                        $badge.remove();
                    }
                    $contact.removeClass('has-unread');
                    $contact.find('.fw-bolder').removeClass('fw-bolder');
                    $contact.find('.text-primary.text-gray-500.fs-8.text-nowrap').removeClass('text-primary');
                }

                // Update last message if provided
                if(contact.last_message) {
                    var $lastMsgDiv = $contact.find('.text-gray-500.fs-7.text-truncate');
                    if($lastMsgDiv.length) {
                        $lastMsgDiv.text(contact.last_message);
                    }
                }
            }
        });
    }

    function refreshCurrentChat() {
        if(!currentJid || !instanceId) return;

        $.ajax({
            url: '<?php _ec(get_module_url("get_messages"))?>',
            type: 'POST',
            dataType: 'json',
            data: {
                instance_id: instanceId,
                remote_jid: currentJid,
                csrf: csrf
            },
            success: function(response) {
                if(response && response.status == 'success') {
                    var currentCount = $('#livechat_messages .d-flex.mb-3').length;
                    var newCount = response.messages ? response.messages.length : 0;
                    // Only re-render if message count changed (new messages received)
                    if(newCount > currentCount) {
                        console.log('Livechat: New messages detected, refreshing (' + currentCount + ' -> ' + newCount + ')');
                        renderMessages(response.messages);
                        playNotificationSound();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('Livechat: Polling error:', status, error);
            }
        });
    }

    function handleNewMessage(data) {
        if(!data) return;

        var remoteJid = data.remoteJid || data.chatid || data.chat_id;
        console.log('Livechat: handleNewMessage called with remoteJid:', remoteJid, 'data:', data);

        // If message is for current chat, reload messages
        if(currentJid && remoteJid == currentJid) {
            loadMessages(currentJid);
        } else if(remoteJid) {
            // Update unread badge for contact in sidebar
            var $contact = $('.livechat-contact[data-jid="' + remoteJid + '"]');
            console.log('Livechat: Found contact element:', $contact.length);

            if($contact.length) {
                // Find the badge container - matches PHP structure: .text-end.ms-2.flex-shrink-0
                var $badgeContainer = $contact.find('.text-end.ms-2.flex-shrink-0');
                if(!$badgeContainer.length) {
                    // Fallback to other possible selectors
                    $badgeContainer = $contact.find('.text-end.flex-shrink-0');
                }
                var $badge = $contact.find('.badge.bg-success');

                console.log('Livechat: Badge container found:', $badgeContainer.length, 'Existing badge:', $badge.length);

                if($badge.length) {
                    // Update existing badge count
                    var count = parseInt($badge.text()) || 0;
                    var newCount = count + 1;
                    $badge.text(newCount > 99 ? '99+' : newCount);
                } else {
                    // Add new badge - append to the time container div
                    if($badgeContainer.length) {
                        $badgeContainer.append('<span class="badge bg-success text-white rounded-pill mt-1" style="min-width:20px;font-size:11px;">1</span>');
                    } else {
                        // Fallback: try to find the time div and add after it
                        var $timeDiv = $contact.find('.text-gray-500.fs-8.text-nowrap');
                        if($timeDiv.length) {
                            $timeDiv.addClass('text-primary').after('<span class="badge bg-success text-white rounded-pill mt-1" style="min-width:20px;font-size:11px;">1</span>');
                        }
                    }
                }

                $contact.addClass('has-unread');

                // Make the name bolder
                $contact.find('.fw-bold').addClass('fw-bolder');

                // Update time color to primary
                $contact.find('.text-gray-500.fs-8.text-nowrap').addClass('text-primary');

                // Update last message text if available
                var messageBody = data.body || data.message || '';
                if(messageBody) {
                    var $lastMsgDiv = $contact.find('.text-gray-500.fs-7.text-truncate');
                    if($lastMsgDiv.length) {
                        $lastMsgDiv.text(messageBody);
                    }
                }
            }

            // Show notification
            var messageText = data.body || data.message || '';
            if(messageText) {
                Core.notify('<?php _e("New message from")?> ' + (remoteJid.split('@')[0]), 'info');
            }
        }

        playNotificationSound();
    }

    function playNotificationSound() {
        try {
            var audio = new Audio('<?php echo get_theme_url()?>Assets/sounds/notification.mp3');
            audio.volume = 0.3;
            audio.play().catch(function(e){});
        } catch(e) {}
    }

    // Initialize socket connection only if livechat is enabled
    if(livechatEnabled) {
        initSocket();
    } else {
        console.log('Livechat: System is disabled, skipping socket/polling initialization');
    }

    // Live Chat Toggle Handler
    $('#livechat_toggle').on('change', function(){
        var $toggle = $(this);
        var $card = $('#livechat_main_card');
        var $badge = $('#livechat_status_badge');
        var $refreshBtn = $('#livechat_refresh_all_btn');
        var $label = $toggle.next('label');
        var newStatus = $toggle.is(':checked') ? 1 : 0;

        // Add loading state
        $card.addClass('livechat-toggle-loading');
        $toggle.prop('disabled', true);

        $.ajax({
            url: '<?php _ec(get_module_url("toggle_status"))?>',
            type: 'POST',
            dataType: 'json',
            data: {
                status: newStatus,
                csrf: csrf
            },
            success: function(response) {
                if(response && response.status == 'success') {
                    livechatEnabled = newStatus == 1;
                    $('#livechat_enabled').val(newStatus);

                    if(livechatEnabled) {
                        // Enable state
                        $card.removeClass('livechat-disabled');
                        $badge.removeClass('badge-light-secondary').addClass('badge-light-success').text('<?php _e("Connected")?>');
                        $label.text('<?php _e("Enabled")?>');
                        $refreshBtn.prop('disabled', false);
                        $card.find('.card-title i').removeClass('text-muted').addClass('text-success');
                        // Initialize socket and polling
                        initSocket();
                        Core.notify('<?php _e("Live Chat enabled successfully")?>', 'success');
                    } else {
                        // Disable state
                        $card.addClass('livechat-disabled');
                        $badge.removeClass('badge-light-success').addClass('badge-light-secondary').text('<?php _e("Disabled")?>');
                        $label.text('<?php _e("Disabled")?>');
                        $refreshBtn.prop('disabled', true);
                        $card.find('.card-title i').removeClass('text-success').addClass('text-muted');
                        // Stop socket and polling
                        if(livechatSocket) {
                            livechatSocket.disconnect();
                            livechatSocket = null;
                        }
                        if(refreshInterval) {
                            clearInterval(refreshInterval);
                            refreshInterval = null;
                        }
                        Core.notify('<?php _e("Live Chat disabled successfully")?>', 'success');
                    }
                } else {
                    // Revert toggle on error
                    $toggle.prop('checked', !$toggle.is(':checked'));
                    Core.notify(response.message || '<?php _e("Failed to update status")?>', 'error');
                }
            },
            error: function() {
                // Revert toggle on error
                $toggle.prop('checked', !$toggle.is(':checked'));
                Core.notify('<?php _e("Failed to update status")?>', 'error');
            },
            complete: function() {
                $card.removeClass('livechat-toggle-loading');
                $toggle.prop('disabled', false);
            }
        });
    });

    // Contact click handler
    $(document).on('click', '.livechat-contact', function(){
        var $contact = $(this);
        var jid = $contact.data('jid');
        var name = $contact.data('name') || $contact.find('.fw-bold').first().text().trim();
        var phone = $contact.data('phone') || (jid ? jid.split('@')[0] : '');
        var isGroup = $contact.data('is-group') == '1';
        currentJid = jid;

        console.log('Livechat: Contact clicked, jid:', jid, 'name:', name, 'phone:', phone, 'isGroup:', isGroup);

        // Update UI
        $('.livechat-contact').removeClass('active has-unread');
        $contact.addClass('active');

        // Remove unread badge
        $contact.find('.badge').remove();

        $('#livechat_header').show();
        $('#livechat_input').show();
        $('#livechat_empty').hide();

        // Update header with name and phone
        $('#livechat_contact_name').text(name);
        if (isGroup) {
            $('#livechat_contact_phone').text(jid || '');
        } else {
            $('#livechat_contact_phone').text(phone ? '+' + phone : '');
        }
        $('#livechat_contact_avatar').text(name ? name.charAt(0).toUpperCase() : '?');

        // Load messages
        loadMessages(jid);
    });

    // Start new chat with phone number
    $('#livechat_start_chat').on('click', function(){
        var phone = $('#livechat_new_phone').val().trim().replace(/[^0-9]/g, '');
        if(!phone) {
            Core.notify('<?php _e("Please enter a phone number")?>', 'warning');
            return;
        }

        currentJid = phone + '@s.whatsapp.net';

        // Update UI
        $('.livechat-contact').removeClass('bg-light-primary');
        $('#livechat_header').show();
        $('#livechat_input').show();
        $('#livechat_empty').hide();

        $('#livechat_contact_name').text(phone);
        $('#livechat_contact_phone').text(phone);
        $('#livechat_contact_avatar').text(phone.charAt(0));
        $('#livechat_messages').html('<div class="text-center py-5 text-gray-500"><?php _e("Start the conversation by sending a message")?></div>');

        $('#livechat_new_phone').val('');
        $('#livechat_message_input').focus();
    });

    // Enter key for new phone number
    $('#livechat_new_phone').on('keypress', function(e){
        if(e.which == 13){
            $('#livechat_start_chat').click();
        }
    });

    // Send message
    $('#livechat_send_btn').on('click', function(){
        sendMessage();
    });

    $('#livechat_message_input').on('keypress', function(e){
        if(e.which == 13){
            sendMessage();
        }
    });

    // Refresh all contacts and messages button
    $('#livechat_refresh_all_btn').on('click', function(){
        var $btn = $(this);
        $btn.prop('disabled', true).find('i').addClass('fa-spin');

        $.ajax({
            url: '<?php _ec(get_module_url("get_contacts"))?>',
            type: 'POST',
            dataType: 'json',
            data: {
                instance_id: instanceId,
                csrf: csrf
            },
            success: function(response) {
                if(response && response.status == 'success' && response.contacts) {
                    // Refresh the contact list
                    renderContactList(response.contacts);
                    Core.notify('<?php _e("Contacts and messages refreshed")?>', 'success');

                    // If we have a current chat selected, refresh its messages too
                    if(currentJid) {
                        loadMessages(currentJid);
                    }
                } else {
                    Core.notify(response.message || '<?php _e("Failed to refresh contacts")?>', 'error');
                }
            },
            error: function() {
                Core.notify('<?php _e("Failed to refresh contacts")?>', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).find('i').removeClass('fa-spin');
            }
        });
    });

    function loadMessages(jid){
        if(!jid) {
            console.log('Livechat: loadMessages called without jid');
            return;
        }

        console.log('Livechat: loadMessages called with jid:', jid, 'instanceId:', instanceId);

        $('#livechat_messages').html('<div class="text-center py-5"><i class="fad fa-spinner-third fa-spin fs-2x text-primary"></i></div>');

        var ajaxUrl = '<?php _ec(get_module_url("get_messages"))?>';
        console.log('Livechat: AJAX URL:', ajaxUrl, 'csrf:', csrf);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                instance_id: instanceId,
                remote_jid: jid,
                csrf: csrf
            },
            success: function(response, textStatus, xhr){
                console.log('Livechat: loadMessages response status:', xhr.status);
                console.log('Livechat: loadMessages response:', response);
                console.log('Livechat: response type:', typeof response);
                if(response && response.status == 'success'){
                    console.log('Livechat: Rendering', response.messages ? response.messages.length : 0, 'messages');
                    if(response.messages && response.messages.length > 0) {
                        console.log('Livechat: First message:', JSON.stringify(response.messages[0]));
                    }
                    renderMessages(response.messages);
                } else {
                    console.log('Livechat: No messages or error in response, status:', response ? response.status : 'undefined');
                    $('#livechat_messages').html('<div class="text-center py-5 text-gray-500"><?php _e("No messages yet")?></div>');
                }
            },
            error: function(xhr, status, error){
                console.log('Livechat: loadMessages AJAX error');
                console.log('Livechat: HTTP status:', xhr.status);
                console.log('Livechat: Error status:', status);
                console.log('Livechat: Error message:', error);
                console.log('Livechat: Response text:', xhr.responseText);
                console.log('Livechat: Response headers:', xhr.getAllResponseHeaders());
                $('#livechat_messages').html('<div class="text-center py-5 text-danger"><?php _e("Failed to load messages")?> (' + xhr.status + ')</div>');
            }
        });
    }

    function renderMessages(messages){
        console.log('Livechat: renderMessages called with:', messages);
        var html = '';
        try {
            if(messages && messages.length > 0){
                messages.forEach(function(msg){
                    var isOutgoing = msg.message_type == 'outgoing' || msg.fromMe == 1;
                    var align = isOutgoing ? 'justify-content-end' : 'justify-content-start';
                    var bgClass = isOutgoing ? 'bg-primary text-white' : 'bg-light-success';
                    var textClass = isOutgoing ? 'text-white-50' : 'text-gray-500';

                    html += '<div class="d-flex mb-3 ' + align + '">';
                    html += '<div class="livechat-bubble p-3 rounded ' + bgClass + '">';

                    // Render media based on type
                    if(msg.mediaUrl && msg.mediaUrl != ''){
                        html += renderMedia(msg.mediaUrl, msg.mediaType, isOutgoing);
                    } else if(msg.media == 1 && msg.imagePath){
                        html += renderMedia(msg.imagePath, 'imageMessage', isOutgoing);
                    }

                    // Render text/caption
                    if(msg.text || msg.body) {
                        html += '<div class="livechat-text">' + escapeHtml(msg.text || msg.body) + '</div>';
                    }
                    html += '<div class="fs-8 ' + textClass + ' mt-1">' + formatTime(msg.messageTimestamp || msg.createdAt) + '</div>';
                    html += '</div></div>';
                });
            } else {
                html = '<div class="text-center py-5 text-gray-500"><?php _e("No messages yet")?></div>';
            }
            console.log('Livechat: renderMessages HTML length:', html.length);
            $('#livechat_messages').html(html);
            scrollToBottom();
        } catch(e) {
            console.error('Livechat: renderMessages error:', e);
            $('#livechat_messages').html('<div class="text-center py-5 text-danger"><?php _e("Error rendering messages")?></div>');
        }
    }

    function renderMedia(url, mediaType, isOutgoing){
        var html = '';
        if(!url) return html;

        var type = (mediaType || '').toLowerCase();
        var linkClass = isOutgoing ? 'text-white' : 'text-gray-800';
        var fileName = url.split('/').pop() || 'File';

        // Image types
        if(type.indexOf('image') > -1 || type == 'stickermessage') {
            html += '<a href="' + url + '" target="_blank">';
            html += '<img src="' + url + '" class="livechat-media-preview mb-2" alt="Image" onerror="this.onerror=null; this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
            html += '<div class="livechat-media-placeholder mb-2" style="display:none;"><i class="fad fa-image fs-24 me-2"></i><span>' + escapeHtml(fileName) + '</span></div>';
            html += '</a>';
        }
        // Video types
        else if(type.indexOf('video') > -1) {
            html += '<video src="' + url + '" controls class="livechat-video-preview mb-2" onerror="this.onerror=null; this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';"></video>';
            html += '<div class="livechat-media-placeholder mb-2" style="display:none;"><i class="fad fa-video fs-24 me-2"></i><span>' + escapeHtml(fileName) + '</span></div>';
        }
        // Audio types
        else if(type.indexOf('audio') > -1 || type.indexOf('ptt') > -1) {
            html += '<audio src="' + url + '" controls class="livechat-audio-preview mb-2" onerror="this.onerror=null; this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';"></audio>';
            html += '<div class="livechat-media-placeholder mb-2" style="display:none;"><i class="fad fa-microphone fs-24 me-2"></i><span>' + escapeHtml(fileName) + '</span></div>';
        }
        // Document/file types
        else if(type.indexOf('document') > -1 || type.indexOf('file') > -1) {
            html += '<a href="' + url + '" target="_blank" class="livechat-document ' + linkClass + ' mb-2">';
            html += '<i class="fad fa-file-alt"></i>';
            html += '<span class="text-truncate">' + escapeHtml(fileName) + '</span>';
            html += '</a>';
        }
        // Generic/unknown - try to display as link
        else if(url) {
            html += '<a href="' + url + '" target="_blank" class="' + linkClass + ' mb-2 d-block"><i class="fad fa-external-link me-1"></i><?php _e("View attachment")?></a>';
        }

        return html;
    }

    function renderContactList(contacts){
        var html = '';
        if(contacts && contacts.length > 0){
            contacts.forEach(function(chat){
                var displayName = chat.pushName || '';
                var phoneNumber = '';
                var secondaryText = '';
                var isGroup = chat.isGroup || false;
                var isNewsletter = chat.isNewsletter || false;
                var remoteJid = chat.remoteJid || chat.chatid || '';
                var profilePic = chat.profilePicUrl || '';

                // Extract phone number from remoteJid
                if(remoteJid) {
                    var jidParts = remoteJid.split('@');
                    phoneNumber = jidParts[0];
                }

                // Determine display format based on type
                if(isGroup) {
                    if(!displayName) displayName = '<?php _e("Group")?>';
                    secondaryText = remoteJid;
                } else if(isNewsletter) {
                    if(!displayName) displayName = '<?php _e("Newsletter")?>';
                    secondaryText = remoteJid;
                } else {
                    if(displayName) {
                        secondaryText = '+' + phoneNumber;
                    } else {
                        displayName = '+' + phoneNumber;
                        secondaryText = '';
                    }
                }

                var unreadCount = parseInt(chat.unread_count) || 0;
                var lastTime = '';
                if(chat.last_message_time) {
                    var timestamp = parseInt(chat.last_message_time);
                    var today = new Date();
                    today.setHours(0,0,0,0);
                    if(timestamp >= today.getTime()/1000) {
                        lastTime = formatTime(timestamp);
                    } else {
                        var d = new Date(timestamp * 1000);
                        lastTime = (d.getDate() < 10 ? '0' : '') + d.getDate() + '/' + ((d.getMonth()+1) < 10 ? '0' : '') + (d.getMonth()+1);
                    }
                }

                // Match PHP avatar styling - green for contacts, blue for groups, orange for newsletters
                var avatarBg = isGroup ? 'bg-light-info text-info' : (isNewsletter ? 'bg-light-warning text-warning' : 'bg-light-success text-success');
                var avatarIcon = isGroup ? 'users' : (isNewsletter ? 'newspaper' : '');
                var firstLetter = displayName ? displayName.charAt(0).toUpperCase() : 'U';

                html += '<div class="livechat-contact p-3 border-bottom cursor-pointer hover-bg-light' + (unreadCount > 0 ? ' has-unread' : '') + '"';
                html += ' data-jid="' + escapeHtml(remoteJid) + '"';
                html += ' data-name="' + escapeHtml(displayName) + '"';
                html += ' data-phone="' + escapeHtml(phoneNumber) + '"';
                html += ' data-is-group="' + (isGroup ? '1' : '0') + '">';

                html += '<div class="d-flex align-items-center">';
                html += '<div class="symbol symbol-40px symbol-circle me-3 position-relative">';

                // Avatar - profile picture or icon/letter
                if(profilePic) {
                    html += '<img src="' + escapeHtml(profilePic) + '" alt="" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">';
                } else {
                    html += '<span class="symbol-label ' + avatarBg + ' fs-6 fw-bolder">';
                    if(avatarIcon) {
                        html += '<i class="fad fa-' + avatarIcon + ' fs-6"></i>';
                    } else {
                        html += firstLetter;
                    }
                    html += '</span>';
                }

                // Group indicator dot
                if(isGroup) {
                    html += '<span class="position-absolute bottom-0 end-0 bg-info rounded-circle" style="width:12px;height:12px;border:2px solid #fff;"></span>';
                }

                html += '</div>'; // end symbol

                html += '<div class="flex-grow-1 overflow-hidden">';
                html += '<div class="d-flex justify-content-between align-items-start">';
                html += '<div class="flex-grow-1 overflow-hidden">';
                html += '<div class="fw-bold text-gray-800 text-truncate' + (unreadCount > 0 ? ' fw-bolder' : '') + '">';
                if(isGroup) {
                    html += '<i class="fad fa-users text-info me-1 fs-8"></i>';
                }
                html += escapeHtml(displayName);
                html += '</div>';

                if(secondaryText) {
                    html += '<div class="text-gray-500 fs-8 text-truncate">' + escapeHtml(secondaryText) + '</div>';
                }
                html += '</div>'; // end name section

                html += '<div class="text-end ms-2 flex-shrink-0">';
                html += '<div class="text-gray-500 fs-8 text-nowrap' + (unreadCount > 0 ? ' text-primary' : '') + '">' + lastTime + '</div>';
                if(unreadCount > 0) {
                    var displayCount = unreadCount > 99 ? '99+' : unreadCount;
                    html += '<span class="badge bg-success text-white rounded-pill mt-1" style="min-width:20px;font-size:11px;">' + displayCount + '</span>';
                }
                html += '</div>'; // end time/badge section

                html += '</div>'; // end top row
                html += '<div class="text-gray-500 fs-7 text-truncate mt-1" style="max-width: 100%;">' + escapeHtml(chat.last_message || '') + '</div>';
                html += '</div>'; // end flex-grow-1 overflow-hidden

                html += '</div>'; // end d-flex align-items-center
                html += '</div>'; // end livechat-contact
            });
        } else {
            html = '<div class="text-center py-10 text-gray-500">';
            html += '<i class="fad fa-inbox fs-2x mb-3 d-block"></i>';
            html += '<div class="mb-3"><?php _e("No conversations yet")?></div>';
            html += '<div class="fs-7 text-muted px-3"><?php _e("Start a new chat by entering a phone number below")?></div>';
            html += '</div>';
        }
        $('#livechat_contacts').html(html);
    }

    function sendMessage(){
        var message = $('#livechat_message_input').val().trim();
        var medias = [];

        // Get selected media files from File Manager (class is fm-list-item, not fm-mini-item)
        $('#livechat_medias .items .fm-list-item').each(function(){
            var file = $(this).data('file');
            if(file) medias.push(file);
        });

        if(!message && medias.length == 0) {
            Core.notify('<?php _e("Please enter a message or select a media file")?>', 'warning');
            return;
        }
        if(!currentJid) {
            Core.notify('<?php _e("Please select a contact first")?>', 'warning');
            return;
        }

        $('#livechat_send_btn').prop('disabled', true).html('<i class="fad fa-spinner-third fa-spin"></i>');

        $.ajax({
            url: '<?php _ec(get_module_url("send"))?>',
            type: 'POST',
            dataType: 'json',
            data: {
                instance_id: instanceId,
                remote_jid: currentJid,
                message: message,
                medias: medias,
                csrf: csrf
            },
            success: function(response){
                if(response && response.status == 'success'){
                    // Store sent media URLs before clearing (class is fm-list-item, not fm-mini-item)
                    var sentMediaUrls = [];
                    $('#livechat_medias .items .fm-list-item').each(function(){
                        var $img = $(this).find('img.lazy, img');
                        if($img.length) {
                            // Try data-src first (lazy loaded), then src
                            var src = $img.attr('data-src') || $img.attr('src');
                            if(src) sentMediaUrls.push(src);
                        }
                    });

                    $('#livechat_message_input').val('');
                    // Clear media
                    $('#livechat_medias .items').html('');

                    // Add message to UI with proper media preview
                    var html = '<div class="d-flex mb-3 justify-content-end">';
                    html += '<div class="livechat-bubble p-3 rounded bg-primary text-white">';

                    // Show sent media thumbnails
                    if(sentMediaUrls.length > 0) {
                        sentMediaUrls.forEach(function(mediaUrl){
                            html += '<a href="' + mediaUrl + '" target="_blank"><img src="' + mediaUrl + '" class="livechat-media-preview mb-2" alt="Sent media"></a>';
                        });
                    } else if(medias.length > 0) {
                        html += '<div class="mb-2"><i class="fad fa-paperclip me-1"></i> <?php _e("Media attached")?></div>';
                    }

                    if(message) {
                        html += '<div class="livechat-text">' + escapeHtml(message) + '</div>';
                    }
                    html += '<div class="fs-8 text-white-50 mt-1"><?php _e("Just now")?></div>';
                    html += '</div></div>';

                    var $empty = $('#livechat_messages').find('.text-gray-500.text-center');
                    if($empty.length) $empty.remove();

                    $('#livechat_messages').append(html);
                    scrollToBottom();

                    Core.notify('<?php _e("Message sent")?>', 'success');
                } else {
                    Core.notify(response.message || '<?php _e("Failed to send message")?>', 'error');
                }
            },
            error: function(){
                Core.notify('<?php _e("Failed to send message")?>', 'error');
            },
            complete: function(){
                $('#livechat_send_btn').prop('disabled', false).html('<i class="fal fa-paper-plane"></i>');
            }
        });
    }

    function scrollToBottom(){
        var $messages = $('#livechat_messages');
        if($messages.length) {
            $messages.scrollTop($messages[0].scrollHeight);
        }
    }

    function formatTime(timestamp){
        if(!timestamp) return '';
        var ts = parseInt(timestamp);
        if(isNaN(ts)) return '';
        var date = new Date(ts * 1000);
        return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }

    function escapeHtml(text) {
        if(!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Search contacts
    $('#livechat_search').on('keyup', function(){
        var search = $(this).val().toLowerCase();
        $('.livechat-contact').each(function(){
            var name = $(this).find('.fw-bold').text().toLowerCase();
            var visible = name.indexOf(search) > -1;
            $(this).toggle(visible);
        });
    });
});
</script>

<style>
.livechat-container { height: 550px; min-height: 400px; }
.livechat-sidebar { width: 320px; min-width: 280px; max-width: 350px; }
.livechat-main { min-width: 300px; }
.livechat-bubble { max-width: 75%; word-wrap: break-word; }
.livechat-text { white-space: pre-wrap; }
.hover-bg-light:hover { background-color: #f5f8fa; }
.cursor-pointer { cursor: pointer; }
.livechat-contact.active { background-color: #e8f4f8 !important; }
.livechat-contact.has-unread { background-color: #f0f9ff; }
.livechat-contact.has-unread:hover { background-color: #e5f5ff; }
.symbol-label { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; }
#livechat_media_area .fm-selected-mini { min-height: auto; padding: 5px; }
#livechat_media_area .fm-selected-mini .drophere { display: none; }
#livechat_media_area .fm-selected-mini .items:empty + .drophere { display: none; }
.livechat-media-preview { max-width: 200px; max-height: 200px; border-radius: 8px; cursor: pointer; }
.livechat-media-preview:hover { opacity: 0.9; }
.livechat-video-preview { max-width: 200px; border-radius: 8px; }
.livechat-audio-preview { max-width: 250px; }
.livechat-document { display: flex; align-items: center; padding: 8px; background: rgba(0,0,0,0.05); border-radius: 8px; }
.livechat-document i { font-size: 24px; margin-right: 10px; }
.livechat-media-placeholder { display: flex; align-items: center; padding: 12px 16px; background: rgba(0,0,0,0.1); border-radius: 8px; color: inherit; font-size: 12px; }
.livechat-media-placeholder i { opacity: 0.7; }
.livechat-media-placeholder span { max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
@media (max-width: 768px) {
    .livechat-container { flex-direction: column; height: auto; }
    .livechat-sidebar { width: 100%; max-width: 100%; height: 300px; }
    .livechat-main { height: 400px; }
}
/* Disabled state styles */
.livechat-disabled { background-color: #f5f5f5 !important; }
.livechat-disabled .card-body { opacity: 0.6; pointer-events: none; }
.livechat-disabled .card-header { background-color: #e9ecef !important; }
.livechat-disabled .livechat-container { filter: grayscale(50%); }
.livechat-disabled #livechat_send_btn,
.livechat-disabled #livechat_message_input,
.livechat-disabled #livechat_search { pointer-events: none; opacity: 0.5; }
.livechat-toggle-loading { opacity: 0.5; pointer-events: none; }
</style>

<?php else: ?>
    
<div class="text-center py-5">
    <div class="fs-70 text-danger">
        <i class="fad fa-exclamation-triangle"></i>
    </div>
    <h3><?php _e("An Unexpected Error Occurred")?></h3>
    <div class="text-gray-700"><?php _e($message)?></div>
</div>

<?php endif ?>

