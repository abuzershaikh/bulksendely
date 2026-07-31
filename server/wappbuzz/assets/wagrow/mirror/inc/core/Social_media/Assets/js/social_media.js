/**
 * Social Media Module - JavaScript
 * Premium interactions, real-time preview, AI features
 */
(function() {
    'use strict';

    // ============================================================
    // Platform icon mapping
    // ============================================================
    const PLATFORM_ICONS = {
        facebook: 'fab fa-facebook-f',
        instagram: 'fab fa-instagram',
        linkedin: 'fab fa-linkedin-in',
        twitter: 'fab fa-twitter',
        telegram: 'fab fa-telegram-plane',
        youtube: 'fab fa-youtube'
    };

    const PLATFORM_NAMES = {
        facebook: 'Facebook',
        instagram: 'Instagram',
        linkedin: 'LinkedIn',
        twitter: 'Twitter / X',
        telegram: 'Telegram',
        youtube: 'YouTube'
    };

    const CHAR_LIMITS = {
        facebook: 63206,
        instagram: 2200,
        linkedin: 3000,
        twitter: 280,
        telegram: 4096,
        youtube: 500
    };

    // ============================================================
    // Post Composer - Live Preview
    // ============================================================
    window.smUpdatePreview = function() {
        const content = document.getElementById('sm-post-content');
        const preview = document.getElementById('sm-preview-text');
        const charCount = document.getElementById('sm-char-count');

        if (content && preview) {
            const text = content.value;
            preview.textContent = text || 'Your post preview will appear here...';

            if (charCount) {
                const len = text.length;
                charCount.textContent = len + ' characters';
                charCount.className = 'sm-char-count';
                if (len > 2000) charCount.className += ' warning';
                if (len > 2200) charCount.className += ' danger';
            }
        }
    };

    // ============================================================
    // Platform Selection
    // ============================================================
    window.smTogglePlatform = function(el) {
        const card = el.closest('.sm-checkbox-card');
        const checkbox = card.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        card.classList.toggle('selected', checkbox.checked);
    };

    // ============================================================
    // AI Caption Generator
    // ============================================================
    window.smGenerateCaption = function() {
        const topic = document.getElementById('sm-ai-topic');
        const platform = document.getElementById('sm-ai-platform');
        const btn = document.getElementById('sm-ai-generate-btn');
        
        if (!topic || !topic.value.trim()) {
            if (typeof iziToast !== 'undefined') {
                iziToast.warning({ title: 'Note', message: 'Please enter a topic for AI generation' });
            }
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

        $.ajax({
            url: PATH + 'social_media/ai_generate_caption',
            method: 'POST',
            data: {
                topic: topic.value,
                platform: platform ? platform.value : 'instagram',
                csrf: AJAX_HASH
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success' && res.caption) {
                    const contentField = document.getElementById('sm-post-content');
                    if (contentField) {
                        contentField.value = res.caption;
                        smUpdatePreview();
                    }
                    if (typeof iziToast !== 'undefined') {
                        iziToast.success({ title: 'AI', message: 'Caption generated!' });
                    }
                } else {
                    if (typeof iziToast !== 'undefined') {
                        iziToast.error({ title: 'Error', message: res.message || 'Failed to generate caption' });
                    }
                }
            },
            error: function() {
                if (typeof iziToast !== 'undefined') {
                    iziToast.error({ title: 'Error', message: 'AI service unavailable' });
                }
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sparkles"></i> Generate';
            }
        });
    };

    // ============================================================
    // AI Hashtag Generator
    // ============================================================
    window.smGenerateHashtags = function() {
        const topic = document.getElementById('sm-hashtag-topic');
        
        if (!topic || !topic.value.trim()) {
            if (typeof iziToast !== 'undefined') {
                iziToast.warning({ title: 'Note', message: 'Please enter a topic' });
            }
            return;
        }

        $.ajax({
            url: PATH + 'social_media/ai_generate_hashtags',
            method: 'POST',
            data: {
                topic: topic.value,
                csrf: AJAX_HASH
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    const hashtagField = document.getElementById('sm-hashtags');
                    if (hashtagField) {
                        hashtagField.value = res.hashtags;
                    }
                }
            }
        });
    };

    // ============================================================
    // AI Reply Generator
    // ============================================================
    window.smGenerateReply = function(commentText, replyInputId) {
        $.ajax({
            url: PATH + 'social_media/ai_generate_reply',
            method: 'POST',
            data: {
                comment: commentText,
                csrf: AJAX_HASH
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    const field = document.getElementById(replyInputId);
                    if (field) field.value = res.reply;
                }
            }
        });
    };

    // ============================================================
    // Inbox - Load Messages
    // ============================================================
    window.smLoadConversation = function(conversationId, el) {
        // Highlight selected item
        document.querySelectorAll('.sm-inbox-item').forEach(i => i.classList.remove('active'));
        if (el) el.classList.add('active');

        $.ajax({
            url: PATH + 'social_media/get_messages',
            method: 'POST',
            data: {
                conversation_id: conversationId,
                csrf: AJAX_HASH
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    const container = document.getElementById('sm-inbox-messages');
                    if (!container) return;

                    let html = '';
                    res.messages.forEach(function(msg) {
                        const dir = msg.direction === 'outbound' ? 'outbound' : 'inbound';
                        const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        html += '<div class="sm-message-bubble ' + dir + '">' +
                                    msg.message +
                                    '<div class="sm-message-time">' + time + '</div>' +
                                '</div>';
                    });
                    container.innerHTML = html;
                    container.scrollTop = container.scrollHeight;
                }
            }
        });
    };

    // ============================================================
    // Send Reply
    // ============================================================
    window.smSendReply = function(type, entityId) {
        const input = document.getElementById('sm-reply-input');
        if (!input || !input.value.trim()) return;

        $.ajax({
            url: PATH + 'social_media/send_reply',
            method: 'POST',
            data: {
                type: type,
                entity_id: entityId,
                reply: input.value,
                csrf: AJAX_HASH
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    // Add message to chat
                    const container = document.getElementById('sm-inbox-messages');
                    if (container) {
                        const bubble = document.createElement('div');
                        bubble.className = 'sm-message-bubble outbound';
                        bubble.innerHTML = input.value + '<div class="sm-message-time">Just now</div>';
                        container.appendChild(bubble);
                        container.scrollTop = container.scrollHeight;
                    }
                    input.value = '';
                    if (typeof iziToast !== 'undefined') {
                        iziToast.success({ title: 'Sent', message: 'Reply sent successfully!' });
                    }
                }
            }
        });
    };

    // ============================================================
    // Toggle Automation
    // ============================================================
    window.smToggleAutomation = function(id) {
        $.ajax({
            url: PATH + 'social_media/toggle_automation',
            method: 'POST',
            data: { id: id, csrf: AJAX_HASH },
            dataType: 'json',
            success: function(res) {
                if (typeof iziToast !== 'undefined') {
                    iziToast.success({ title: 'Updated', message: res.message });
                }
            }
        });
    };

    // ============================================================
    // Delete Handlers
    // ============================================================
    window.smDeletePost = function(id) {
        if (!confirm('Delete this post?')) return;
        $.ajax({
            url: PATH + 'social_media/delete_post',
            method: 'POST',
            data: { id: id, csrf: AJAX_HASH },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') location.reload();
            }
        });
    };

    window.smDeleteAutomation = function(id) {
        if (!confirm('Delete this automation rule?')) return;
        $.ajax({
            url: PATH + 'social_media/delete_automation',
            method: 'POST',
            data: { id: id, csrf: AJAX_HASH },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') location.reload();
            }
        });
    };

    window.smDeleteMedia = function(id) {
        if (!confirm('Delete this media file?')) return;
        $.ajax({
            url: PATH + 'social_media/delete_media',
            method: 'POST',
            data: { id: id, csrf: AJAX_HASH },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') location.reload();
            }
        });
    };

    window.smDisconnectAccount = function(id) {
        if (!confirm('Disconnect this account?')) return;
        $.ajax({
            url: PATH + 'social_media/disconnect_account',
            method: 'POST',
            data: { id: id, csrf: AJAX_HASH },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') location.reload();
            }
        });
    };

    // ============================================================
    // Calendar - Simple Monthly View
    // ============================================================
    window.smCalendar = {
        currentDate: new Date(),

        init: function() {
            this.render();
        },

        render: function() {
            const container = document.getElementById('sm-calendar-container');
            if (!container) return;

            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDay = firstDay.getDay();
            const today = new Date();

            // Header
            document.getElementById('sm-calendar-title').textContent = 
                firstDay.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

            let html = '';
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            days.forEach(d => {
                html += '<div class="sm-calendar-header-cell">' + d + '</div>';
            });

            // Previous month padding
            const prevMonth = new Date(year, month, 0);
            for (let i = startDay - 1; i >= 0; i--) {
                html += '<div class="sm-calendar-cell other-month"><div class="sm-calendar-date">' + (prevMonth.getDate() - i) + '</div></div>';
            }

            // Current month days
            for (let day = 1; day <= lastDay.getDate(); day++) {
                const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
                html += '<div class="sm-calendar-cell' + (isToday ? ' today' : '') + '" data-date="' + year + '-' + String(month+1).padStart(2,'0') + '-' + String(day).padStart(2,'0') + '">';
                html += '<div class="sm-calendar-date">' + day + '</div>';
                html += '<div class="sm-calendar-events" id="cal-events-' + day + '"></div>';
                html += '</div>';
            }

            // Next month padding
            const remaining = 42 - (startDay + lastDay.getDate());
            for (let i = 1; i <= remaining; i++) {
                html += '<div class="sm-calendar-cell other-month"><div class="sm-calendar-date">' + i + '</div></div>';
            }

            container.innerHTML = html;

            // Load events
            this.loadEvents(year + '-' + String(month+1).padStart(2,'0') + '-01', 
                           year + '-' + String(month+1).padStart(2,'0') + '-' + String(lastDay.getDate()).padStart(2,'0'));
        },

        loadEvents: function(start, end) {
            $.ajax({
                url: PATH + 'social_media/get_calendar_events',
                method: 'POST',
                data: { start: start, end: end, csrf: AJAX_HASH },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success' && res.events) {
                        res.events.forEach(function(event) {
                            const date = new Date(event.start);
                            const day = date.getDate();
                            const container = document.getElementById('cal-events-' + day);
                            if (container) {
                                const el = document.createElement('div');
                                el.className = 'sm-calendar-event ' + event.status;
                                el.textContent = event.title;
                                el.title = event.title;
                                container.appendChild(el);
                            }
                        });
                    }
                }
            });
        },

        prev: function() {
            this.currentDate.setMonth(this.currentDate.getMonth() - 1);
            this.render();
        },

        next: function() {
            this.currentDate.setMonth(this.currentDate.getMonth() + 1);
            this.render();
        }
    };

    // ============================================================
    // Analytics Charts (using Highcharts already loaded)
    // ============================================================
    window.smInitAnalytics = function() {
        if (typeof Highcharts === 'undefined') return;

        // Engagement chart
        const engagementEl = document.getElementById('sm-engagement-chart');
        if (engagementEl) {
            Highcharts.chart('sm-engagement-chart', {
                chart: { type: 'areaspline', backgroundColor: 'transparent', height: 300 },
                title: { text: null },
                credits: { enabled: false },
                xAxis: {
                    categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    labels: { style: { color: '#636E72' } }
                },
                yAxis: { title: { text: null }, gridLineColor: 'rgba(0,0,0,0.05)' },
                plotOptions: {
                    areaspline: {
                        fillOpacity: 0.1,
                        marker: { radius: 4 }
                    }
                },
                legend: { itemStyle: { color: '#636E72' } },
                series: [{
                    name: 'Likes',
                    data: [45, 67, 89, 56, 78, 92, 110],
                    color: '#6C5CE7'
                }, {
                    name: 'Comments',
                    data: [12, 23, 34, 18, 29, 45, 38],
                    color: '#00CEC9'
                }, {
                    name: 'Shares',
                    data: [5, 12, 8, 15, 10, 22, 19],
                    color: '#FD79A8'
                }]
            });
        }

        // Platform distribution chart
        const platformEl = document.getElementById('sm-platform-chart');
        if (platformEl) {
            Highcharts.chart('sm-platform-chart', {
                chart: { type: 'pie', backgroundColor: 'transparent', height: 300 },
                title: { text: null },
                credits: { enabled: false },
                plotOptions: {
                    pie: {
                        innerSize: '60%',
                        dataLabels: {
                            format: '<b>{point.name}</b>: {point.percentage:.1f}%',
                            style: { color: '#636E72' }
                        }
                    }
                },
                series: [{
                    name: 'Posts',
                    data: [
                        { name: 'Facebook', y: 35, color: '#1877F2' },
                        { name: 'Instagram', y: 30, color: '#E4405F' },
                        { name: 'LinkedIn', y: 15, color: '#0A66C2' },
                        { name: 'Twitter', y: 12, color: '#1DA1F2' },
                        { name: 'Telegram', y: 5, color: '#0088CC' },
                        { name: 'YouTube', y: 3, color: '#FF0000' }
                    ]
                }]
            });
        }
    };

    // ============================================================
    // PHASE 2: Notification Polling
    // ============================================================
    let smNotifInterval = null;

    window.smStartNotificationPolling = function() {
        // Poll every 30 seconds
        smNotifInterval = setInterval(function() {
            $.ajax({
                url: PATH + 'social_media/get_notifications',
                method: 'POST',
                data: { since: new Date(Date.now() - 60000).toISOString(), csrf: AJAX_HASH },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        // Update badge
                        const badge = document.getElementById('sm-notif-badge');
                        if (badge) {
                            badge.textContent = res.unread_count || '';
                            badge.style.display = res.unread_count > 0 ? 'flex' : 'none';
                        }

                        // Show toast for new notifications
                        if (res.notifications && res.notifications.length > 0 && typeof iziToast !== 'undefined') {
                            const latest = res.notifications[0];
                            if (latest && !latest.is_read) {
                                iziToast.info({
                                    title: latest.title || 'Notification',
                                    message: latest.message || '',
                                    timeout: 5000,
                                    position: 'topRight'
                                });
                            }
                        }
                    }
                }
            });
        }, 30000);
    };

    window.smMarkNotificationRead = function(id, el) {
        $.post(PATH + 'social_media/mark_notification_read', { id: id, csrf: AJAX_HASH }, function() {
            if (el) el.closest('.sm-notif-item')?.classList.add('read');
        }, 'json');
    };

    window.smMarkAllNotificationsRead = function() {
        $.post(PATH + 'social_media/mark_all_read', { csrf: AJAX_HASH }, function(res) {
            if (res.status === 'success') {
                document.querySelectorAll('.sm-notif-item').forEach(el => el.classList.add('read'));
                const badge = document.getElementById('sm-notif-badge');
                if (badge) badge.style.display = 'none';
                if (typeof iziToast !== 'undefined') {
                    iziToast.success({ title: 'Done', message: 'All marked as read' });
                }
            }
        }, 'json');
    };

    // ============================================================
    // PHASE 2: Publish Now
    // ============================================================
    window.smPublishNow = function(id) {
        if (!confirm('Publish this post now to all selected platforms?')) return;

        const btn = event.target.closest('button');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...'; }

        $.ajax({
            url: PATH + 'social_media/publish_now',
            method: 'POST',
            data: { id: id, csrf: AJAX_HASH },
            dataType: 'json',
            success: function(res) {
                if (typeof iziToast !== 'undefined') {
                    if (res.status === 'success') {
                        iziToast.success({ title: 'Published!', message: res.message });
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        iziToast.error({ title: 'Failed', message: res.message });
                    }
                }
            },
            error: function() {
                if (typeof iziToast !== 'undefined') {
                    iziToast.error({ title: 'Error', message: 'Failed to publish. Check your connection.' });
                }
            },
            complete: function() {
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Publish Now'; }
            }
        });
    };

    // ============================================================
    // PHASE 2: Bulk Post Selection
    // ============================================================
    window.smSelectAllPosts = function(checkbox) {
        document.querySelectorAll('.sm-post-checkbox').forEach(cb => {
            cb.checked = checkbox.checked;
        });
        smUpdateBulkBar();
    };

    window.smUpdateBulkBar = function() {
        const checked = document.querySelectorAll('.sm-post-checkbox:checked');
        const bar = document.getElementById('sm-bulk-bar');
        const count = document.getElementById('sm-bulk-count');
        if (bar) {
            bar.style.display = checked.length > 0 ? 'flex' : 'none';
        }
        if (count) {
            count.textContent = checked.length;
        }
    };

    window.smBulkDeletePosts = function() {
        const ids = Array.from(document.querySelectorAll('.sm-post-checkbox:checked')).map(cb => cb.value);
        if (ids.length === 0) return;
        if (!confirm('Delete ' + ids.length + ' selected post(s)?')) return;

        $.ajax({
            url: PATH + 'social_media/bulk_delete_posts',
            method: 'POST',
            data: { ids: ids, csrf: AJAX_HASH },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    if (typeof iziToast !== 'undefined') iziToast.success({ title: 'Deleted', message: res.message });
                    setTimeout(function() { location.reload(); }, 1000);
                }
            }
        });
    };

    // ============================================================
    // PHASE 2: Edit Post Preview
    // ============================================================
    window.smUpdateEditPreview = function() {
        const content = document.getElementById('postContent');
        const preview = document.getElementById('previewContent');
        const charCount = document.getElementById('charCount');
        if (content && preview) {
            preview.innerHTML = content.value ? content.value.replace(/\n/g, '<br>') : 'Your post preview will appear here...';
        }
        if (content && charCount) {
            charCount.textContent = content.value.length;
        }
    };

    // ============================================================
    // Init on DOM Ready
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        // Init calendar if on calendar page
        if (document.getElementById('sm-calendar-container')) {
            smCalendar.init();
        }

        // Init analytics charts if on analytics page
        if (document.getElementById('sm-engagement-chart')) {
            setTimeout(smInitAnalytics, 300);
        }

        // Attach live preview to composer
        const composer = document.getElementById('sm-post-content');
        if (composer) {
            composer.addEventListener('input', smUpdatePreview);
        }

        // Edit post preview
        const editComposer = document.getElementById('postContent');
        if (editComposer) {
            editComposer.addEventListener('input', smUpdateEditPreview);
        }

        // Start notification polling on social media pages
        if (window.location.href.indexOf('social_media') !== -1) {
            smStartNotificationPolling();
        }

        // Bulk checkbox listeners
        document.querySelectorAll('.sm-post-checkbox').forEach(cb => {
            cb.addEventListener('change', smUpdateBulkBar);
        });
    });

})();
