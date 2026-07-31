"use strict";

$(function () {
    // Helper to get CSRF token from cookie
    function getCsrfToken() {
        var name = 'csrf_cookie';
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        if (match) return match[2];
        return crm_config.csrf_hash;
    }

    // Lightweight time ago helper (no moment.js dependency)
    function timeAgoFromUnix(unix) {
        if (!unix || unix == 0) return 'Unknown';
        var now = Math.floor(Date.now() / 1000);
        var diff = now - unix;
        if (diff < 0) diff = 0;
        if (diff < 60) return diff + 's';
        if (diff < 3600) return Math.floor(diff / 60) + 'm';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd';
        if (diff < 2592000) return Math.floor(diff / 604800) + 'w';
        return Math.floor(diff / 2592000) + 'mo';
    }
    function formatUnixDate(unix) {
        if (!unix || unix == 0) return '';
        var d = new Date(unix * 1000);
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var h = d.getHours(); var ampm = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12;
        return months[d.getMonth()] + ' ' + d.getDate() + ', ' + h + ':' + String(d.getMinutes()).padStart(2,'0') + ' ' + ampm;
    }

    if ($('#kanban-board').length > 0) {
        loadBoard();
    }

    $("#save-item-btn").on("click", function () {
        addItem();
    });

    $(document).on('template-selected', function (e, template) {
        // Set ID
        var tId = template.id || (template.ids ? template.ids : '');
        $('#whatsapp_template_id').val(tId);
        
        // Hide Message Box Container (textarea + label + selector)
        $('#whatsapp_message_text').closest('.mb-3').hide();

        // Show Info
        var infoHtml = `
        <div id="whatsapp_template_preview_info" class="alert alert-success mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-check-circle me-1"></i> Template Selected: <strong>${template.name || 'Template'}</strong>
                </div>
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="resetWhatsAppTemplate()">
                    <i class="fas fa-times"></i> Change
                </button>
            </div>
            <div class="small text-muted mt-1 text-truncate">${template.data?.text || template.data?.body?.text || 'Template content loaded.'}</div>
        </div>`;
        
        // Remove existing info if any
        $('#whatsapp_template_preview_info').remove();
        
        // Insert before the hidden message box
        $('#whatsapp_message_text').closest('.mb-3').after(infoHtml);
    });

    window.resetWhatsAppTemplate = function() {
        $('#whatsapp_template_id').val('');
        $('#whatsapp_template_preview_info').remove();
        $('#whatsapp_message_text').closest('.mb-3').slideDown();
        $('#whatsapp_message_text').val('');
    }

    // Global state
    window.crmBoardData = null;
    window.crmCurrentFilter = 'all';
    window.crmSearchQuery = '';
    window.crmCurrentView = 'kanban';
    window.crmSelectMode = false;

    // Toggle select mode
    window.toggleSelectMode = function() {
        window.crmSelectMode = !window.crmSelectMode;
        var btn = $('#btn-toggle-select');
        if (window.crmSelectMode) {
            btn.addClass('active').html('&#10003; Cancel Select');
        } else {
            btn.removeClass('active').html('&#9744; Select');
            window.selectedLeads.clear();
            updateBulkDeleteButton();
        }
        if (window.crmBoardData) {
            renderBoard(window.crmBoardData);
            renderListView(window.crmBoardData);
        }
    }

    function loadBoard() {
        // Show loading
        $('#kanban-board').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading pipeline...</div></div>');

        $.ajax({
            url: crm_config.base_url + '/get_board',
            type: 'GET',
            dataType: 'json',
            timeout: 60000,
            success: function (data) {
                console.log('CRM Board loaded:', data);
                if (data.error) {
                    $('#kanban-board').html(
                        '<div class="text-center py-5">' +
                        '<div style="font-size:48px;margin-bottom:16px;">&#9888;</div>' +
                        '<h5>Server Error</h5>' +
                        '<p class="text-muted">' + (data.message || 'Unknown error') + '</p>' +
                        '<button class="btn btn-primary btn-sm" onclick="location.reload()">&#8635; Retry</button>' +
                        '</div>'
                    );
                    return;
                }
                window.crmBoardData = data;
                updateStats(data);
                renderBoard(data);
                renderListView(data);
            },
            error: function(xhr, status, error) {
                console.error('CRM Board load error:', status, error);
                console.error('Response:', xhr.responseText ? xhr.responseText.substring(0, 500) : '(empty)');
                var msg = 'Failed to load board.';
                if (status === 'timeout') msg = 'Request timed out (60s). Too many leads may be slowing the server.';
                else if (status === 'parseerror') msg = 'Server returned HTML instead of JSON. You may need to re-login.';
                else if (xhr.status === 500) msg = 'Server error (500). Check PHP error logs.';
                else if (xhr.status === 404) msg = 'Endpoint not found (404). Check routes.';
                else if (xhr.status === 0) msg = 'Network error. Server might be down.';
                
                $('#kanban-board').html(
                    '<div class="text-center py-5">' +
                    '<div style="font-size:48px;margin-bottom:16px;">&#9888;</div>' +
                    '<h5 style="color:var(--crm-text-main)">Could not load board</h5>' +
                    '<p style="color:var(--crm-text-muted);margin-bottom:16px;">' + msg + '</p>' +
                    '<button class="btn btn-primary btn-sm" onclick="location.reload()">&#8635; Retry</button>' +
                    '<br><small class="text-muted mt-2 d-block">Error: ' + (error || status) + ' (HTTP ' + xhr.status + ')</small>' +
                    '</div>'
                );
            }
        });
    }

    // Stats
    function updateStats(data) {
        var items = data.items || [];
        var total = items.length;
        var hot = items.filter(i => i.lead_score >= 70).length;
        var unread = items.filter(i => i.unread_count > 0).length;
        var revenue = 0;
        var won = 0;
        items.forEach(function(i) {
            if (i.deal_amount > 0 && i.deal_status == 'won') { revenue += parseFloat(i.deal_amount); won++; }
        });
        $('#stat-total').text(total);
        $('#stat-revenue').text('$' + revenue.toLocaleString());
        $('#stat-hot').text(hot);
        $('#stat-unread').text(unread);
        $('#stat-won').text(won);
    }

    // Search
    var searchTimer;
    $(document).on('input', '#crm-search-input', function() {
        clearTimeout(searchTimer);
        var q = $(this).val().toLowerCase();
        searchTimer = setTimeout(function() {
            window.crmSearchQuery = q;
            if (window.crmBoardData) {
                renderBoard(window.crmBoardData);
                renderListView(window.crmBoardData);
            }
        }, 250);
    });

    // Filter
    window.filterLeads = function(filter, btn) {
        window.crmCurrentFilter = filter;
        $('.crm-filter-btn').removeClass('active');
        $(btn).addClass('active');
        if (window.crmBoardData) {
            renderBoard(window.crmBoardData);
            renderListView(window.crmBoardData);
        }
    }

    function applyFilters(items) {
        var q = window.crmSearchQuery;
        var f = window.crmCurrentFilter;
        return items.filter(function(i) {
            // Search
            if (q) {
                var match = (i.display_name || i.title || '').toLowerCase().indexOf(q) >= 0 ||
                    (i.title || '').toLowerCase().indexOf(q) >= 0 ||
                    (i.description || '').toLowerCase().indexOf(q) >= 0;
                if (!match && i.labels) {
                    match = i.labels.some(function(l) { return l.name.toLowerCase().indexOf(q) >= 0; });
                }
                if (!match) return false;
            }
            // Filters
            if (f == 'hot') return i.lead_score >= 70;
            if (f == 'unread') return i.unread_count > 0;
            if (f == 'deals') return i.deal_amount > 0;
            if (f == 'ai') return i.lead_score > 0;
            return true;
        });
    }

    // View Toggle
    window.switchView = function(view) {
        window.crmCurrentView = view;
        if (view == 'kanban') {
            $('#kanban-view').show();
            $('#list-view').removeClass('active');
            $('#view-kanban-btn').addClass('active');
            $('#view-list-btn').removeClass('active');
        } else {
            $('#kanban-view').hide();
            $('#list-view').addClass('active');
            $('#view-kanban-btn').removeClass('active');
            $('#view-list-btn').addClass('active');
        }
    }

    // List View
    function renderListView(data) {
        var body = $('#list-view-body');
        body.empty();
        if (!data.items || data.items.length == 0) {
            body.html('<tr><td colspan="8" class="text-center text-muted py-4">No leads found.</td></tr>');
            return;
        }
        var colMap = {};
        (data.columns || []).forEach(function(c) { colMap[c.id] = c; });
        var items = applyFilters(data.items);
        items.forEach(function(item) {
            var col = colMap[item.column_id] || {};
            var scorePercent = Math.min(item.lead_score || 0, 100);
            var scoreColor = scorePercent >= 70 ? '#10b981' : scorePercent >= 40 ? '#f59e0b' : '#64748b';
            var tagsHtml = '';
            if (item.labels && item.labels.length) {
                item.labels.forEach(function(l) {
                    tagsHtml += '<span class="crm-badge" style="background:' + l.color + '20;color:' + l.color + ';border:1px solid ' + l.color + '40;margin-right:3px;">' + l.name + '</span>';
                });
            }
            var timeAgo = timeAgoFromUnix(item.created);
            body.append('<tr onclick="openLeadModal(\'' + item.ids + '\')">' +
                '<td onclick="event.stopPropagation()"><input type="checkbox" class="form-check-input list-checkbox" value="' + item.ids + '" onchange="toggleLeadSelection(\'' + item.ids + '\')"></td>' +
                '<td><div class="lead-cell"><img class="avatar-sm" src="' + (item.avatar || '') + '"><div><div style="font-weight:600;color:var(--crm-text-main)">' + (item.display_name || item.title) + '</div><div style="font-size:11px;color:var(--crm-text-muted)">+' + (item.title || '').split('@')[0] + '</div></div></div></td>' +
                '<td><span class="stage-badge" style="background:' + (col.color || '#6366F1') + '20;color:' + (col.color || '#6366F1') + '">' + (col.name || '-') + '</span></td>' +
                '<td><div class="d-flex align-items-center gap-2"><div class="score-bar"><div class="score-bar-fill" style="width:' + scorePercent + '%;background:' + scoreColor + '"></div></div><span style="font-size:11px;font-weight:600;">' + scorePercent + '</span></div></td>' +
                '<td style="font-weight:700;color:var(--crm-success)">$' + (item.deal_amount || 0) + '</td>' +
                '<td>' + (tagsHtml || '<span style="color:var(--crm-text-subtle);font-size:11px;">—</span>') + '</td>' +
                '<td style="font-size:12px;color:var(--crm-text-muted)">' + timeAgo + '</td>' +
                '<td onclick="event.stopPropagation()"><div class="d-flex gap-1"><button class="icon-btn-sm" onclick="quickWhatsAppReply(\'' + item.ids + '\',\'' + item.title + '\')"><i class="fab fa-whatsapp" style="color:#34D399"></i></button><button class="icon-btn-sm delete" onclick="deleteItemConfirm(\'' + item.ids + '\')"><i class="fas fa-trash-alt"></i></button></div></td>' +
                '</tr>');
        });
    }

    window.toggleAllListCheckboxes = function(el) {
        var checked = el.checked;
        $('.list-checkbox').each(function() {
            this.checked = checked;
            var id = $(this).val();
            if (checked) window.selectedLeads.add(id);
            else window.selectedLeads.delete(id);
        });
        updateBulkDeleteButton();
    }

    window.clearSelection = function() {
        window.selectedLeads.clear();
        $('.lead-checkbox, .list-checkbox').prop('checked', false);
        updateBulkDeleteButton();
    }

    function renderBoard(data) {
        var container = $("#kanban-board");
        container.empty();

        if (!data.columns || data.columns.length == 0) {
            container.html('<div class="crm-empty-state"><div class="empty-icon"><i class="fas fa-columns"></i></div><h4>No Pipeline Stages</h4><p>Create your first pipeline stage to get started.</p></div>');
            return;
        }

        var allFilteredItems = applyFilters(data.items || []);

        data.columns.forEach(function (col) {
            var colItems = allFilteredItems.filter(i => i.column_id == col.id);

            // Calculate column sum
            var colSum = 0;
            colItems.forEach(function(i) { colSum += parseFloat(i.deal_amount || 0); });

            var itemsHtml = '';
            colItems.forEach(function (item) {
                var labelsHtml = '';
                if (item.labels && item.labels.length > 0) {
                    item.labels.forEach(function (label) {
                        labelsHtml += '<span class="crm-badge" style="background-color: ' + label.color + '20; color: ' + label.color + '; border: 1px solid ' + label.color + '40;">' + label.name + '</span>';
                    });
                }

                var aiHtml = '';
                if (item.lead_score > 0) {
                    aiHtml = '<div class="crm-badge badge-ai" title="AI Score: ' + item.lead_score + '"><i class="fas fa-robot"></i> ' + item.lead_score + '</div>';
                }

                var dealHtml = '';
                if (item.deal_amount > 0) {
                    dealHtml = '<div class="crm-badge badge-deal"><i class="fas fa-dollar-sign"></i> $' + item.deal_amount + '</div>';
                }

                var unreadHtml = '';
                if (item.unread_count > 0) {
                    unreadHtml = '<div class="crm-badge badge-unread"><i class="fas fa-envelope"></i> ' + item.unread_count + '</div>';
                }

                var hotHtml = '';
                if (item.lead_score >= 80) {
                    hotHtml = '<div class="crm-badge badge-hot"><i class="fas fa-fire"></i> Hot</div>';
                }

                var desc = item.description || '';
                var timeAgo = timeAgoFromUnix(item.created);
                var fullDate = formatUnixDate(item.created);
                var isSelected = window.selectedLeads && window.selectedLeads.has(item.ids) ? 'checked' : '';

                // Priority class
                var priorityClass = '';
                if (item.lead_score >= 80) priorityClass = 'priority-high';
                else if (item.lead_score >= 40) priorityClass = 'priority-medium';

                // Checkbox only visible in select mode
                var chkHtml = window.crmSelectMode ? '<div class="chk-select me-2"><input type="checkbox" class="form-check-input lead-checkbox" value="' + item.ids + '" onchange="toggleLeadSelection(\'' + item.ids + '\'); event.stopPropagation();" onclick="event.stopPropagation();" ' + isSelected + '></div>' : '';

                itemsHtml += '\n' +
                    '<div class="kanban-item modern-card ' + priorityClass + '" draggable="true" data-id="' + item.ids + '" ondragstart="drag(event)">' +
                    '<div class="card-top">' +
                    chkHtml +
                    '<div class="lead-info">' +
                    '<div class="lead-name" onclick="openLeadModal(\'' + item.ids + '\')" title="' + (item.display_name || item.title) + '">' + (item.display_name || item.title) + '</div>' +
                    '<div class="lead-date" title="' + fullDate + '"><span class="icn">&#xf017;</span> ' + timeAgo + ' ago</div>' +
                    '</div>' +
                    '<div class="card-actions-top">' +
                    '<button class="icon-btn-sm" onclick="openLeadModal(\'' + item.ids + '\', \'tab-calls\'); event.stopPropagation();" title="Log Call">&#9742;</button>' +
                    '<button class="icon-btn-sm delete" onclick="deleteItemConfirm(\'' + item.ids + '\'); event.stopPropagation();" title="Delete Lead">&#128465;</button>' +
                    '</div></div>' +
                    '<div class="card-badges">' + labelsHtml + aiHtml + dealHtml + unreadHtml + hotHtml + '</div>' +
                    '<div class="card-content">' + desc + '</div>' +
                    '<div class="card-footer-actions" onclick="event.stopPropagation()">' +
                    '<button class="action-btn" onclick="quickAddNote(\'' + item.ids + '\')" title="Note">&#128221; Note</button>' +
                    '<button class="action-btn" onclick="quickAddTag(\'' + item.ids + '\')" title="Tag">&#127991; Tag</button>' +
                    '<button class="action-btn" onclick="quickScheduleMessage(\'' + item.ids + '\')" title="Schedule">&#9201; Sched</button>' +
                    '<button class="action-btn whatsapp" onclick="quickWhatsAppReply(\'' + item.ids + '\', \'' + item.title + '\')" title="WhatsApp">&#128172; WA</button>' +
                    '<button class="action-btn view" onclick="openLeadModal(\'' + item.ids + '\')" title="View">&#128065; View</button>' +
                    '</div></div>';
            });

            var sumHtml = colSum > 0 ? '<span class="col-sum">$' + colSum.toLocaleString() + '</span>' : '';

            var html = '<div class="kanban-column" data-id="' + col.id + '" ondrop="drop(event)" ondragover="allowDrop(event)" ondragenter="dragEnter(event)" ondragleave="dragLeave(event)">' +
                '<div class="kanban-header">' +
                '<div class="col-header-left">' +
                '<div class="col-dot" style="background:' + col.color + ';color:' + col.color + ';"></div>' +
                '<span>' + col.name + '</span>' +
                '</div>' +
                '<div class="col-header-right">' +
                sumHtml +
                '<span class="kanban-card-count">' + colItems.length + '</span>' +
                '</div>' +
                '</div>' +
                '<div class="kanban-items">' + itemsHtml + '</div>' +
                '<div class="kanban-add-btn" onclick="openAddModal(\'' + col.id + '\')">' +
                '<i class="fas fa-plus"></i> Add Lead</div>' +
                '</div>';
            container.append(html);
        });
    }

    // Delete Item with Confirmation
    window.deleteItemConfirm = function (itemId) {
        if (confirm('Are you sure you want to delete this lead?')) {
            deleteItem(itemId);
        }
    }

    // Add Item
    window.openAddModal = function (colId) {
        $("#target-column-id").val(colId);
        $("#addItemForm")[0].reset();
        $("#addItemModal").modal('show');
    }

    function addItem() {

        var formData = $("#addItemForm").serializeArray();
        formData.push({ name: crm_config.csrf_token, value: crm_config.csrf_hash });

        $.ajax({
            url: crm_config.base_url + '/add_item',
            type: 'POST',
            data: formData,
            success: function (resp) {
                $("#addItemModal").modal('hide');
                loadBoard();
            }
        });
    }

    function deleteItem(id) {
        $.ajax({
            url: crm_config.base_url + '/delete_item',
            type: 'POST',
            data: {
                item_id: id,
                [crm_config.csrf_token]: crm_config.csrf_hash
            },
            success: function (resp) {
                loadBoard();
            }
        });
    }

    // NEW: Bulk Delete
    window.selectedLeads = new Set();

    window.toggleLeadSelection = function(id) {
        if (window.selectedLeads.has(id)) {
            window.selectedLeads.delete(id);
        } else {
            window.selectedLeads.add(id);
        }
        updateBulkDeleteButton();
    }

    function updateBulkDeleteButton() {
        var count = window.selectedLeads.size;
        $('#selected-count').text(count);
        if (count > 0) {
            $('#crm-bulk-bar').addClass('visible');
        } else {
            $('#crm-bulk-bar').removeClass('visible');
        }
    }

    window.selectAllLeads = function() {
        $('.kanban-item, .crm-list-table tbody tr').each(function() {
            var id = $(this).data('id') || $(this).find('.list-checkbox').val();
            if (id) {
                window.selectedLeads.add(String(id));
                $(this).find('.lead-checkbox, .list-checkbox').prop('checked', true);
            }
        });
        updateBulkDeleteButton();
    }

    window.deleteSelectedLeads = function() {
        var ids = Array.from(window.selectedLeads);
        if (ids.length === 0) return;

        if (!confirm('Are you sure you want to delete ' + ids.length + ' leads? This cannot be undone.')) return;
        
        var btn = $('#btn-bulk-delete');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');

        $.post(crm_config.base_url + '/delete_bulk_items', {
            item_ids: ids,
            [crm_config.csrf_token]: crm_config.csrf_hash
        }, function(res) {
            if (res.status == 'success') {
                window.selectedLeads.clear();
                updateBulkDeleteButton();
                loadBoard();
            } else {
                alert('Failed to delete items: ' + (res.message || 'Unknown error'));
            }
            btn.prop('disabled', false).html('<i class="fas fa-trash"></i> Delete Selected (<span id="selected-count">0</span>)');
        }, 'json');
    }

    // Drag and Drop Global Functions
    window.allowDrop = function (ev) {
        ev.preventDefault();
    }

    window.drag = function (ev) {
        ev.dataTransfer.setData("text", ev.currentTarget.getAttribute("data-id"));
        ev.currentTarget.classList.add('dragging');
    }

    window.drop = function (ev) {
        ev.preventDefault();
        var data = ev.dataTransfer.getData("text");
        var itemEl = document.querySelector(`.kanban-item[data-id='${data}']`);
        itemEl.classList.remove('dragging');

        // Remove drag-over visual from all columns
        document.querySelectorAll('.kanban-column').forEach(function(c) { c.classList.remove('drag-over'); });

        // Find closest column
        var column = ev.target.closest('.kanban-column');
        if (column) {
            column.querySelector('.kanban-items').appendChild(itemEl);

            // Update Status via AJAX
            $.ajax({
                url: crm_config.base_url + '/update_item_status',
                type: 'POST',
                data: {
                    item_id: data,
                    column_id: column.getAttribute('data-id'),
                    [crm_config.csrf_token]: crm_config.csrf_hash
                }
            });
        }
    }

    window.dragEnter = function(ev) {
        ev.preventDefault();
        var col = ev.target.closest('.kanban-column');
        if (col) col.classList.add('drag-over');
    }

    window.dragLeave = function(ev) {
        var col = ev.target.closest('.kanban-column');
        if (col && !col.contains(ev.relatedTarget)) col.classList.remove('drag-over');
    }

    window.syncWhatsAppLeads = function () {
        if (!confirm('Sync all WhatsApp conversations as leads?')) return;

        $.post(crm_config.base_url + '/ajax_sync_whatsapp', {
            [crm_config.csrf_token]: crm_config.csrf_hash
        }, function (res) {
            if (res.status == 'success') {
                alert('Synced ' + res.count + ' new leads from WhatsApp!');
                loadBoard();
            } else {
                alert('Sync failed: ' + res.message);
            }
        }, 'json');
    }

    // Open Lead Detail Modal
    window.openLeadModal = function (leadId, activeTab = 'tab-overview') {
        $('#leadDetailsContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>');
        $('#leadModal').data('lead-id', leadId).modal('show');

        // Reset state or store activeTab to apply after load
        window.currentLeadTab = activeTab;

        var postData = {
            lead_id: leadId,
            [crm_config.csrf_token]: crm_config.csrf_hash
        };

        $.ajax({
            url: crm_config.base_url + '/ajax_get_lead_details',
            type: 'POST',
            data: postData,
            dataType: 'json',
            xhrFields: {
                withCredentials: true
            },
            success: function (res) {
                if (res.status == 'success') {
                    var data = res.data;
                    var lead = data.lead;
                    var notes = data.notes || [];
                    var tags = data.tags || [];
                    var schedules = data.schedules || [];

                    var scoreColor = 'secondary';
                    if (lead.lead_score >= 80) scoreColor = 'success';
                    else if (lead.lead_score >= 50) scoreColor = 'warning';
                    else if (lead.lead_score > 0) scoreColor = 'danger';

                    // Clean Phone
                    var phoneClean = lead.title.split('@')[0];

                    // TIMELINE / NOTES HTML
                    var timelineHtml = '';
                    if (notes.length > 0) {
                        timelineHtml += '<h6 class="text-muted mt-3 mb-2">Notes</h6><div class="list-group list-group-flush">';
                        notes.forEach(function (n) {
                            timelineHtml += `
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <small class="fw-bold">${n.fullname || 'Agent'}</small>
                                    <small class="text-muted">${moment.unix(n.created).fromNow()}</small>
                                </div>
                                <p class="mb-0 text-sm">${n.note}</p>
                            </div>`;
                        });
                        timelineHtml += '</div>';
                    } else {
                        timelineHtml += '<p class="text-muted small mt-3">No notes yet.</p>';
                    }

                    // TAGS HTML
                    var tagsHtml = '';
                    if (tags.length > 0) {
                        tags.forEach(function (t) {
                            tagsHtml += `<span class="badge me-1" style="background-color: ${t.color}">${t.name}</span>`;
                        });
                    } else {
                        tagsHtml = '<span class="text-muted small">No tags</span>';
                    }

                    // SCHEDULES HTML
                    var schedHtml = '';
                    if (schedules.length > 0) {
                        schedHtml += '<h6 class="text-muted mt-3 mb-2">Scheduled Messages</h6>';
                        schedules.forEach(function (s) {
                            var badge = s.status == 1 ? '<span class="badge bg-success">Sent</span>' : '<span class="badge bg-warning">Pending</span>';
                            schedHtml += `
                            <div class="alert alert-light border mb-2 p-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="fw-bold"><i class="far fa-clock"></i> ${moment.unix(s.send_time).format('MMM D, h:mm A')}</small>
                                    ${badge}
                                </div>
                                <div class="small text-truncate">${s.message}</div>
                            </div>`;
                        });
                    }

                    var html = `
                    <div class="row">
                        <div class="col-md-7 border-end">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar me-3" style="width: 50px; height: 50px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">${lead.display_name || phoneClean}</h5>
                                    <div class="text-muted small"><i class="fab fa-whatsapp"></i> +${phoneClean}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                <ul class="nav nav-tabs mb-3" id="leadTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab">Overview</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="calls-tab" data-bs-toggle="tab" data-bs-target="#tab-calls" type="button" role="tab">Calls</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="emails-tab" data-bs-toggle="tab" data-bs-target="#tab-emails" type="button" role="tab">Emails</button>
                    </li>
                </ul>

                        <div class="tab-content" id="leadTabsContent">
                            <!-- TAB 1: OVERVIEW -->
                            <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-7 border-end">
                                        <div class="d-flex align-items-center mb-4">
                                            <div class="symbol symbol-60px me-3">
                                                <div class="symbol-label fs-2 fw-bold bg-primary text-white">
                                                    ${lead.display_name.substring(0, 1).toUpperCase()}
                                                </div>
                                            </div>
                                            <div>
                                                <h3 class="fw-bolder mb-1">${lead.display_name}</h3>
                                                <div class="text-muted">
                                                    <a href="tel:+${phoneClean}" class="text-muted text-decoration-none" title="Click to Call">
                                                        <i class="fas fa-phone-alt text-primary me-1"></i> +${phoneClean}
                                                    </a>
                                                    <span class="mx-2">|</span>
                                                    <i class="fab fa-whatsapp text-success"></i> WhatsApp
                                                </div>
                                                <div class="badge badge-light-${lead.stage_color} fw-bolder mt-1">${lead.stage_name}</div>
                                                <div class="d-flex mt-1">
                                                    ${tagsHtml}
                                                    <a href="javascript:void(0)" class="text-muted ms-2 small" onclick="openTagsModal('${lead.ids}')"><i class="fas fa-plus"></i></a>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- AI Assistant Panel -->
                                        <div class="ai-panel mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="fw-bold text-teal-600"><i class="fas fa-robot"></i> AI Assistant</small>
                                                <div class="ai-actions">
                                                    <button class="btn-ai" onclick="askAi('suggest_reply', '${lead.ids}')"><i class="fas fa-comment-dots"></i> Reply</button>
                                                    <button class="btn-ai" onclick="askAi('summarize', '${lead.ids}')"><i class="fas fa-list-alt"></i> Summary</button>
                                                    <button class="btn-ai" onclick="askAi('next_action', '${lead.ids}')"><i class="fas fa-arrow-right"></i> Next</button>
                                                </div>
                                            </div>
                                            <div id="ai-response-area"></div>
                                        </div>

                                        <div class="d-flex gap-2 mb-3">
                                            <button class="btn btn-outline-primary btn-sm flex-fill" onclick="quickWhatsAppReply('${lead.ids}', '${phoneClean}')"><i class="fab fa-whatsapp"></i> Message</button>
                                            <button class="btn btn-outline-secondary btn-sm flex-fill" onclick="quickAddNote('${lead.ids}')"><i class="fas fa-sticky-note"></i> Note</button>
                                            <div class="dropdown">
                                                <button class="btn btn-outline-dark btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">Export</button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportLead('pdf', '${lead.ids}')">PDF</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportLead('csv', '${lead.ids}')">CSV</a></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="bg-gray-100 rounded p-3 mb-3">
                                            <label class="small text-muted text-uppercase fw-bold">Deal Value</label>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="fs-3 fw-bold text-success">$${lead.deal_amount || 0}</div>
                                                <button class="btn btn-sm btn-outline-secondary" onclick="openDealModal('${lead.ids}')"><i class="fas fa-edit"></i> Edit</button>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="small text-muted text-uppercase fw-bold">Description</label>
                                            <p class="text-gray-800">${lead.description || 'No description'}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <h6 class="fw-bold border-bottom pb-2">Activity</h6>
                                        <div class="timeline-label">
                                            ${timelineHtml}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: CALLS -->
                            <div class="tab-pane fade" id="tab-calls" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="fw-bold">Call History</h5>
                                    <div>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-phone"></i> WhatsApp Call
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="initiateWhatsAppCall('${phoneClean}', 'voice')"><i class="fas fa-phone-alt text-success me-2"></i> Voice Call</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="initiateWhatsAppCall('${phoneClean}', 'video')"><i class="fas fa-video text-primary me-2"></i> Video Call</a></li>
                                            </ul>
                                        </div>
                                        <button class="btn btn-warning btn-sm ms-2" onclick="initiateIvrCall('${phoneClean}')"><i class="fas fa-headset"></i> IVR Call</button>
                                        <button class="btn btn-info btn-sm ms-2" onclick="initiateVoipCall('${phoneClean}', '${lead.display_name}', '${lead.ids}')"><i class="fas fa-phone-volume"></i> VoIP Call</button>
                                        <button class="btn btn-primary btn-sm ms-2" onclick="toggleCallForm()"><i class="fas fa-phone"></i> Log Call</button>
                                    </div>
                                </div>

                                <div id="call-log-form" class="card mb-3 p-3 bg-light" style="display:none;">
                                    <h6>Log New Call</h6>
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <select class="form-select form-select-sm" id="call_outcome">
                                                <option value="answered">Answered</option>
                                                <option value="no_answer">No Answer</option>
                                                <option value="busy">Busy</option>
                                                <option value="voicemail">Voicemail</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <input type="number" class="form-control form-control-sm" id="call_duration" placeholder="Duration (sec)">
                                        </div>
                                        <div class="col-4">
                                            <select class="form-select form-select-sm" id="call_type">
                                                <option value="outgoing">Outgoing</option>
                                                <option value="incoming">Incoming</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <textarea class="form-control form-control-sm" id="call_notes" rows="2" placeholder="Call notes..."></textarea>
                                        </div>
                                        <div class="col-12 text-end">
                                            <button class="btn btn-success btn-sm" onclick="saveCallLog('${lead.ids}')">Save Log</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="call-list">
                                    ${(res.data.calls || []).map(c => `
                                <div class="d-flex align-items-center mb-3">
                                    <div class="symbol symbol-35px me-3">
                                        <span class="symbol-label bg-light-${c.outcome == 'answered' ? 'success' : 'danger'}">
                                            <i class="fas fa-phone-alt ${c.outcome == 'answered' ? 'text-success' : 'text-danger'}"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="text-gray-800 fw-bold fs-6">${c.outcome.replace('_', ' ').toUpperCase()} <span class="text-muted small fw-normal">(${c.call_type})</span></div>
                                        <div class="text-muted small">${c.notes} - by ${c.agent_name || 'Agent'}</div>
                                    </div>
                                    <div class="badge badge-light fs-8">${moment.unix(c.created_at).fromNow()}</div>
                                </div>
                            `).join('') || '<div class="text-center text-muted py-4">No calls logged yet.</div>'}
                                </div>
                            </div>

                            <!-- TAB 3: EMAILS -->
                            <div class="tab-pane fade" id="tab-emails" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="fw-bold">Emails</h5>
                                    <div>
                                        <button class="btn btn-primary btn-sm me-2" onclick="openComposeEmail('${lead.email || ''}')"><i class="fas fa-paper-plane"></i> Send Email</button>
                                        <button class="btn btn-info btn-sm" onclick="openCrmSettings()"><i class="fas fa-cog"></i> Config</button>
                                    </div>
                                </div>
                                <div class="email-list">
                                    ${(res.data.emails || []).map(e => `
                                <div class="card p-2 mb-2 border">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold">${e.subject}</span>
                                        <small class="text-muted">${moment.unix(e.date).format('MMM D')}</small>
                                    </div>
                                    <small class="text-muted">${e.from_email} -> ${e.to_email}</small>
                                    <p class="small mb-0 text-truncate">${e.body}</p>
                                </div>
                            `).join('') || '<div class="text-center text-muted py-4">No emails synced. Configure IMAP to sync.</div>'}
                                </div>
                            </div>
                        </div>
                </div >
                        `;
                    $('#leadDetailsContent').html(html);

                    // Activate requested tab
                    if (window.currentLeadTab) {
                        try {
                            var tabTrigger = document.querySelector('#leadTabs button[data-bs-target="#' + window.currentLeadTab + '"]');
                            var tab = new bootstrap.Tab(tabTrigger);
                            tab.show();
                        } catch (e) { }
                    }
                } else {
                    $('#leadDetailsContent').html('<div class="alert alert-danger">' + (res.message || 'Failed to load lead details') + '</div>');
                }
            },
            error: function (xhr, status, error) {
                $('#leadDetailsContent').html('<div class="alert alert-danger">Error loading details.</div>');
            }
        });
    }

    // Open Deal Modal
    window.openDealModal = function (leadId) {
        // Close lead modal first if it's open (to prevent z-index stacking issues)
        $('#leadModal').modal('hide');

        // Small delay to ensure smooth transition
        setTimeout(function () {
            $('#dealModal').modal('show');
            $('#deal_lead_id').val(leadId);

            // Load existing deal data
            $.post(crm_config.base_url + '/ajax_get_deal', {
                lead_id: leadId,
                [crm_config.csrf_token]: crm_config.csrf_hash
            }, function (res) {
                if (res.status == 'success' && res.data) {
                    $('#deal_amount').val(res.data.amount);
                    $('#deal_date').val(res.data.expected_close_date);
                    $('#deal_status').val(res.data.status).trigger('change');
                    $('#deal_lost_reason').val(res.data.lost_reason);
                } else {
                    $('#dealForm')[0].reset();
                    $('#deal_lead_id').val(leadId);
                }
            }, 'json');
        }, 300);
    }

    // Run AI Qualification
    window.runAiQualification = function (leadId) {
        if (!confirm('Run AI qualification for this lead?')) return;

        var btn = event.target;
        $(btn).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Analyzing...');

        $.post(crm_config.base_url + '/ajax_qualify_lead', {
            lead_id: leadId,
            [crm_config.csrf_token]: crm_config.csrf_hash
        }, function (res) {
            if (res.status == 'success') {
                alert('AI Qualification Complete!\nScore: ' + res.data.lead_score + '\nIntent: ' + res.data.lead_intent);
                $('#leadModal').modal('hide');
                loadBoard();
            } else {
                alert('AI Qualification Failed: ' + res.message);
            }
            $(btn).prop('disabled', false).html('<i class="fas fa-robot"></i> Run AI Qualification');
        }, 'json').fail(function () {
            alert('Error running AI qualification');
            $(btn).prop('disabled', false).html('<i class="fas fa-robot"></i> Run AI Qualification');
        });
    }

    // Quick Add Note
    window.quickAddNote = function (leadId) {
        $('#quick_note_lead_id').val(leadId);
        $('#quick_note_text').val('');
        $('#quickNoteModal').modal('show');
    }

    window.saveQuickNote = function () {
        var leadId = $('#quick_note_lead_id').val();
        var note = $('#quick_note_text').val();

        if (!note) {
            alert('Please enter a note');
            return;
        }

        $.post(crm_config.base_url + '/ajax_notes', {
            action: 'add',
            lead_id: leadId,
            note: note,
            [crm_config.csrf_token]: crm_config.csrf_hash
        }, function (res) {
            if (res.status == 'success') {
                $('#quickNoteModal').modal('hide');
                alert('Note added successfully!');
            } else {
                alert('Failed to add note');
            }
        }, 'json');
    }

    // Quick Add Tag
    window.quickAddTag = function (leadId) {
        $('#quick_tag_lead_id').val(leadId);

        // Load available tags
        $.get(crm_config.base_url + '/get_board', function (data) {
            var options = '<option value="">Select a tag...</option>';
            if (data.labels && data.labels.length > 0) {
                data.labels.forEach(function (label) {
                    options += `<option value="${label.id}">${label.name}</option>`;
                });
            }

            $('#quick_tag_select').html(options);
            $('#quickTagModal').modal('show');
        }, 'json');
    }

    window.saveQuickTag = function () {
        var leadId = $('#quick_tag_lead_id').val();
        var labelId = $('#quick_tag_select').val();

        if (!labelId) {
            alert('Please select a tag');
            return;
        }

        $.post(crm_config.base_url + '/ajax_label_manager', {
            action: 'assign',
            lead_id: leadId,
            label_id: labelId,
            [crm_config.csrf_token]: crm_config.csrf_hash
        }, function (res) {
            if (res.status == 'success') {
                $('#quickTagModal').modal('hide');
                alert('Tag added successfully!');
                loadBoard();
                var activeLeadId = $('#leadModal').data('lead-id');
                if (activeLeadId == leadId) {
                    openLeadModal(leadId);
                }
            } else {
                alert('Failed to add tag');
            }
        }, 'json');
    }

    // Create New Tag
    window.createNewTag = function () {
        var name = $('#new_tag_name').val().trim();
        var color = $('#new_tag_color').val();

        if (!name) {
            alert('Please enter a tag name');
            return;
        }

        $.post(crm_config.base_url + '/ajax_label_manager', {
            action: 'add',
            name: name,
            color: color,
            [crm_config.csrf_token]: crm_config.csrf_hash
        }, function (res) {
            if (res.status == 'success') {
                alert('Tag created successfully!');
                $('#new_tag_name').val('');
                // Reload tag list
                var leadId = $('#quick_tag_lead_id').val();
                quickAddTag(leadId); // Refresh the dropdown
            } else {
                alert('Failed to create tag');
            }
        }, 'json');
    }

    window.openTagsModal = function (leadId) {
        $('#quick_tag_lead_id').val(leadId);
        // Reuse quickAddTag logic but maybe with a different modal if needed
        // For now, let's just trigger quickAddTag
        quickAddTag(leadId);
    }

    // Quick Schedule Message
    // Quick Schedule Message
    window.quickScheduleMessage = function (leadId) {
        $('#quick_schedule_lead_id').val(leadId);
        $('#quick_schedule_message').val('');
        $('#quick_schedule_time').val('');

        var form = $('#quickScheduleForm');
        var loadingHtml = '<div id="schedule_instance_loading" class="text-center py-2"><i class="fas fa-spinner fa-spin"></i> Loading instances...</div>';

        // Remove existing selector/loading if any
        form.find('.instance-selector-container').remove();

        // Inject loading after message textarea
        $('#quick_schedule_message').closest('.mb-3').after('<div class="instance-selector-container">' + loadingHtml + '</div>');

        $('#quickScheduleModal').modal('show');

        // Fetch Instances
        $.get(crm_config.base_url + '/ajax_get_instances', function (res) {
            form.find('.instance-selector-container').empty();

            if (res.status == 'success' && res.data.length > 0) {
                var select = '<div class="mb-3"><label class="form-label">Send from</label><select class="form-select" id="schedule_instance_select">';
                res.data.forEach(function (inst) {
                    select += `<option value="${inst.instance_id}">${inst.name}</option>`;
                });
                select += '</select></div>';
                form.find('.instance-selector-container').html(select);
            } else {
                form.find('.instance-selector-container').html('<div class="alert alert-warning py-1"><small>No active instances found.</small></div>');
            }
        }, 'json');
    }

    window.saveQuickSchedule = function () {
        var leadId = $('#quick_schedule_lead_id').val();
        var message = $('#quick_schedule_message').val();
        var timeInput = $('#quick_schedule_time').val();
        var instanceId = $('#schedule_instance_select').val();

        if (!message || !timeInput) {
            alert('Please fill all fields');
            return;
        }

        // Convert datetime-local to YYYY-MM-DD HH:MM format
        var timeStr = timeInput.replace('T', ' ');

        $.post(crm_config.base_url + '/ajax_schedule', {
            lead_id: leadId,
            message: message,
            time: timeStr,
            instance_id: instanceId,
            [crm_config.csrf_token]: crm_config.csrf_hash
        }, function (res) {
            if (res.status == 'success') {
                $('#quickScheduleModal').modal('hide');
                alert('Message scheduled successfully!');
            } else {
                alert('Failed to schedule: ' + (res.message || 'Unknown error'));
            }
        }, 'json');
    }

    // WhatsApp Quick Reply
    window.quickWhatsAppReply = function (leadId, phoneNumber) {
        $('#whatsapp_lead_id').val(leadId);
        // Clean phone
        var cleanPhone = phoneNumber ? phoneNumber.split('@')[0] : '';
        $('#whatsapp_phone').val(cleanPhone);
        $('#whatsapp_phone_display').text('+' + cleanPhone);
        $('#whatsapp_message_text').val('');
        $('#whatsapp_template_id').val('');

        // Hide Lead Details modal first if it's currently open (prevents z-index collision)
        var leadModalWasOpen = $('#leadModal').hasClass('show');
        if (leadModalWasOpen) {
            $('#leadModal').modal('hide');
        }

        // Show WhatsApp reply modal after a brief delay for smooth transition
        setTimeout(function() {
            $('#whatsAppQuickReplyModal').modal('show');
        }, leadModalWasOpen ? 300 : 0);

        // When WhatsApp modal is closed, re-open Lead Details modal
        $('#whatsAppQuickReplyModal').off('hidden.bs.modal.reopen').on('hidden.bs.modal.reopen', function() {
            if (leadModalWasOpen && leadId) {
                setTimeout(function() {
                    openLeadModal(leadId);
                }, 200);
            }
        });

        // Show loading state if selector doesn't exist or we want to refresh
        var loadingHtml = '<div id="instance_loading" class="text-center py-2"><i class="fas fa-spinner fa-spin"></i> Loading instances...</div>';

        if ($('#whatsapp_instance_select').length == 0) {
            // First time opening - also reset any template indicator in case modal was closed mid-session
            $('#template-selected-indicator').remove();
            $('#whatsapp_message_text').closest('.mb-3').show();
            $('#whatsAppQuickReplyForm .alert').after(loadingHtml);
        } else {
            // If exists, maybe verify it, but for now we'll just leave it or refresh it?
            // Let's replace it with loading to ensure fresh data
            $('#whatsapp_instance_select').closest('.mb-3').remove(); // remove wrapper if properly wrapped, or just the select
            // The previous code injected just the SELECT + LABEL string
            // We need to be careful about removing what we added.
            // The previous code: select calls .after() on .alert.
            // Best to remove any previous select
            $('#whatsapp_instance_select').parent().remove(); // logic depends on how it was added.
            // Simplified: Remove any existing select-container class we add now.
            $('.instance-selector-container').remove();
            
            // Reset UI for Template - remove any template selection indicator
            $('#whatsapp_template_preview_info').remove();
            $('#template-selected-indicator').remove();
            $('#whatsapp_message_text').closest('.mb-3').show();
            $('#whatsAppQuickReplyForm .alert').after('<div class="instance-selector-container">' + loadingHtml + '</div>');
        }

        // Fetch Instances
        $.get(crm_config.base_url + '/ajax_get_instances', function (res) {
            $('.instance-selector-container').remove(); // Remove loading
            $('#instance_loading').remove();

            if (res.status == 'success' && res.data.length > 0) {
                var select = '<div class="mb-3 instance-selector-container"><label class="form-label">Send from Instance</label><select class="form-select" id="whatsapp_instance_select">';
                res.data.forEach(function (inst) {
                    select += `<option value="${inst.instance_id}">${inst.name}</option>`;
                });
                select += '</select></div>';

                $('#whatsAppQuickReplyForm .alert').after(select);
            } else {
                var msg = res.message || 'No active instances found.';
                // If message contains "Error", make it red
                var alertClass = (res.status == 'error' || msg.indexOf('Error') !== -1) ? 'alert-danger' : 'alert-warning';
                $('#whatsAppQuickReplyForm .alert').after(`<div class="alert ${alertClass} py-1 instance-selector-container"><small>${msg}</small></div>`);
            }
        }, 'json').fail(function (xhr, status, error) {
            console.error('Fetch Instances Error:', xhr.responseText);
            $('.instance-selector-container').remove();
            $('#instance_loading').remove();
            $('#whatsAppQuickReplyForm .alert').after(`<div class="alert alert-danger py-1 instance-selector-container"><small>Failed to load instances. ${error || 'Unknown error'}</small></div>`);
        });
    }

    window.sendWhatsAppQuickReply = function () {
        var leadId = $('#whatsapp_lead_id').val();
        var phoneNumber = $('#whatsapp_phone').val();
        var message = $('#whatsapp_message_text').val();
        var instanceId = $('#whatsapp_instance_select').val();
        var templateId = $('#whatsapp_template_id').val();

        if (!message && !templateId) {
            alert('Please enter a message or select a template');
            return;
        }

        // Disable send button
        var btn = $('#whatsapp_send_btn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');

        // Update CSRF
        var csrfName = crm_config.csrf_token;
        var csrfHash = getCsrfToken();

        $.post(crm_config.base_url + '/ajax_send_whatsapp', {
            lead_id: leadId,
            phone: phoneNumber,
            message: message,
            instance_id: instanceId,
            template_id: templateId,
            [csrfName]: csrfHash
        }, function (res) {
            if (res.status == 'success') {
                $('#whatsAppQuickReplyModal').modal('hide');
                alert('Message sent successfully!');
            } else {
                alert('Failed to send message: ' + (res.message || 'Unknown error'));
            }
            btn.prop('disabled', false).html('<i class="fab fa-whatsapp"></i> Send Message');
        }, 'json').fail(function () {
            alert('Error sending message');
            btn.prop('disabled', false).html('<i class="fab fa-whatsapp"></i> Send Message');
        });
    }

    // Keep old function for backwards compatibility (if needed elsewhere)
    window.openWhatsAppChat = function (chatId) {
        // Get the base URL (remove /crm from the path)
        var baseUrl = window.location.origin;
        // Redirect to livechat with this chat_id
        window.location.href = baseUrl + '/livechat?chat=' + encodeURIComponent(chatId);
    }

    if (typeof socket !== 'undefined') {
        socket.on('crm.note', function (data) {
            var activeLeadId = $('#leadModal').data('lead-id');
            if (activeLeadId && activeLeadId == data.lead_id) {
                openLeadModal(data.lead_id);
            }
        });

        socket.on('crm.update_lead', function (data) {
            var activeLeadId = $('#leadModal').data('lead-id');
            if (activeLeadId && activeLeadId == data.lead_id) {
                openLeadModal(data.lead_id);
            }
            if (typeof loadBoard === 'function') loadBoard();
        });

        socket.on('crm.schedule', function (data) {
            var activeLeadId = $('#leadModal').data('lead-id');
            if (activeLeadId && activeLeadId == data.lead_id) {
                openLeadModal(data.lead_id);
            }
        });
    }

    // Export Handler
    window.exportLead = function (type, leadId) {
        // Direct link to Crm controller method
        window.location.href = crm_config.base_url + '/export/' + type + '/' + leadId;
    }

    // AI Functions
    window.askAi = function (action, leadId) {
        var area = $('#ai-response-area');
        area.html('<small class="text-muted"><i class="fas fa-spinner fa-spin"></i> Thinking...</small>');

        $.post(crm_config.base_url + '/ai/' + action, {
            lead_id: leadId,
            [crm_config.csrf_token]: crm_config.csrf_hash
        }, function (res) {
            if (res.status == 'success') {
                var content = res.data;
                // If suggest reply, make it clickable to copy
                if (action == 'suggest_reply') {
                    area.html(`<div class="ai-suggestion-box" onclick="copyToClipboard('${content.replace(/'/g, "\\'")}')" title="Click to copy">
                        <i class="fas fa-copy float-end text-muted"></i>
                        ${content}
                    </div>`);
                } else {
                    area.html(`<div class="ai-suggestion-box">${content}</div>`);
                }
            } else {
                area.html(`<small class="text-danger">AI Error: ${res.message}</small>`);
            }
        }, 'json').fail(function () {
            area.html('<small class="text-danger">Failed to reach AI service.</small>');
        });
    }

    window.copyToClipboard = function (text) {
        navigator.clipboard.writeText(text).then(function () {
            alert('Copied to clipboard!');
            // Ideally open WhatsApp modal and paste it
            var leadId = $('#leadModal').data('lead-id');
            // Check if we can pre-fill
            // quickWhatsAppReply(leadId, ...); // Needs phone
        }, function (err) {
            console.error('Async: Could not copy text: ', err);
        });
    }

    /* ===========================
       NEW FEATURES HANDLERS
       =========================== */

    // 1. CALL LOGGING
    window.toggleCallForm = function () {
        $('#call-log-form').slideToggle();
    }

    window.saveCallLog = function (leadId) {
        var data = {
            lead_id: leadId,
            outcome: $('#call_outcome').val(),
            duration: $('#call_duration').val(),
            type: $('#call_type').val(),
            notes: $('#call_notes').val(),
            [crm_config.csrf_token]: crm_config.csrf_hash
        };

        if (!data.notes) {
            alert('Please add notes'); return;
        }

        $.post(crm_config.base_url + '/ajax_log_call', data, function (res) {
            if (res.status == 'success') {
                // Reload modal or append
                openLeadModal(leadId); // Reload to refresh list
            }
        }, 'json');
    }

    window.initiateWhatsAppCall = function (phone, type) {
        if (!phone) {
            alert("Invalid phone number");
            return;
        }

        var leadId = $('#leadModal').data('lead-id');

        // Log to CRM
        $.post(crm_config.base_url + '/ajax_log_call', {
            lead_id: leadId,
            outcome: 'outgoing',
            duration: 0,
            type: 'whatsapp_' + type,
            notes: 'WhatsApp ' + type + ' call initiated',
            [crm_config.csrf_token]: crm_config.csrf_hash
        });

        // Deep Link
        // whatsapp://call?phone=NUMBER (Official for Desktop)
        // Alternative: whatsapp://send?phone=NUM then manual call
        var url = (type === 'video') ? "whatsapp://call?phone=" + phone + "&video=1" : "whatsapp://call?phone=" + phone;
        window.location.href = url;

        // Refresh call tab after a short delay
        setTimeout(function () {
            openLeadModal(leadId, 'tab-calls');
        }, 3000);
    }

    // 2. EMAIL CONFIG
    window.openEmailConfig = function () {
        // Simple prompt for now, ideally a modal
        var email = prompt("Enter IMAP Email:");
        if (email) {
            var password = prompt("Enter IMAP Password:");
            var host = prompt("Enter IMAP Host (e.g., imap.gmail.com):");
            if (password && host) {
                $.post(crm_config.base_url + '/ajax_save_email_config', {
                    email: email, password: password, host: host, username: email, port: 993, encryption: 'ssl',
                    [crm_config.csrf_token]: crm_config.csrf_hash
                }, function (res) {
                    alert('Configuration saved!');
                }, 'json');
            }
        }
    }

    // 3. FORECAST DASHBOARD
    window.openForecastModal = function () {
        $('#forecastModal').modal('show');
        $('#forecastContent').html('<div class="text-center py-4"><div class="spinner" style="width:40px;height:40px;border:3px solid var(--crm-border,#e2e8f0);border-top-color:#6366F1;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto;"></div></div>');

        $.get(crm_config.base_url + '/ajax_get_forecast', function (res) {
            if (res.status == 'success') {
                var d = res.data;
                var html = '<div class="row g-3 mb-4">' +
                    '<div class="col-md-4"><div class="forecast-card"><div class="forecast-value">$' + parseFloat(d.forecast_revenue).toLocaleString() + '</div><div class="forecast-label">Weighted Revenue</div></div></div>' +
                    '<div class="col-md-4"><div class="forecast-card"><div class="forecast-value" style="background:linear-gradient(135deg,#10B981,#06B6D4);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">$' + parseFloat(d.potential_revenue).toLocaleString() + '</div><div class="forecast-label">Pipeline Value</div></div></div>' +
                    '<div class="col-md-4"><div class="forecast-card"><div class="forecast-value" style="background:linear-gradient(135deg,#F59E0B,#EF4444);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">' + d.deal_count + '</div><div class="forecast-label">Active Deals</div></div></div>' +
                    '</div>' +
                    '<div class="alert alert-info" style="border-radius:12px;"><i class="fas fa-info-circle me-1"></i> <strong>How it works:</strong> Weighted revenue = Deal Amount × Stage Probability (e.g., New Lead=20%, Qualified=60%, Won=100%)</div>';
                $('#forecastContent').html(html);
            }
        }, 'json');
    }
    // ========================================
    // LABEL MANAGEMENT (CRUD)
    // ========================================
    window.openLabelManager = function() {
        if ($('#labelManagerModal').length == 0) {
            var mHtml = '<div class="modal fade" id="labelManagerModal" tabindex="-1">' +
                '<div class="modal-dialog"><div class="modal-content">' +
                '<div class="modal-header"><h5 class="modal-title">&#127991; Manage Labels</h5>' +
                '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
                '<div class="modal-body">' +
                '<div id="label-list-container"><div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span> Loading...</div></div>' +
                '<hr>' +
                '<h6>Create New Label</h6>' +
                '<div class="input-group">' +
                '<input type="text" class="form-control" id="new-label-name" placeholder="Label name...">' +
                '<input type="color" class="form-control form-control-color" id="new-label-color" value="#6366F1">' +
                '<button class="btn btn-primary" onclick="createLabel()">Create</button>' +
                '</div>' +
                '</div></div></div></div>';
            $('body').append(mHtml);
        }
        $('#labelManagerModal').modal('show');
        loadLabels();
    }

    function loadLabels() {
        $.get(crm_config.base_url + '/ajax_get_labels', function(res) {
            var container = $('#label-list-container');
            container.empty();
            if (res.status == 'success' && res.data && res.data.length > 0) {
                res.data.forEach(function(label) {
                    container.append(
                        '<div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded" style="border:1px solid var(--crm-border,#e2e8f0);">' +
                        '<div class="d-flex align-items-center gap-2">' +
                        '<span style="width:16px;height:16px;border-radius:50%;background:' + label.color + ';display:inline-block;"></span>' +
                        '<span style="font-weight:600;">' + label.name + '</span>' +
                        '</div>' +
                        '<div class="d-flex gap-1">' +
                        '<button class="btn btn-sm btn-outline-primary" onclick="editLabel(' + label.id + ',\'' + label.name.replace(/'/g, "\\'") + '\',\'' + label.color + '\')">Edit</button>' +
                        '<button class="btn btn-sm btn-outline-danger" onclick="deleteLabel(' + label.id + ')">Delete</button>' +
                        '</div>' +
                        '</div>'
                    );
                });
            } else {
                container.html('<div class="text-muted text-center py-2">No labels yet. Create one below.</div>');
            }
        }, 'json');
    }

    window.createLabel = function() {
        var name = $('#new-label-name').val().trim();
        var color = $('#new-label-color').val();
        if (!name) { alert('Please enter a label name'); return; }
        $.post(crm_config.base_url + '/ajax_create_label', {
            name: name, color: color,
            [crm_config.csrf_token]: crm_config.csrf_hash
        }, function(res) {
            if (res.status == 'success') {
                $('#new-label-name').val('');
                loadLabels();
                if (typeof loadBoard === 'function') loadBoard();
            } else {
                alert('Error: ' + (res.message || 'Unknown'));
            }
        }, 'json');
    }

    window.editLabel = function(id, oldName, oldColor) {
        var newName = prompt('Edit label name:', oldName);
        if (newName === null) return;
        var newColor = prompt('Edit label color (hex):', oldColor);
        if (newColor === null) newColor = oldColor;
        $.post(crm_config.base_url + '/ajax_update_label', {
            id: id, name: newName, color: newColor,
            [crm_config.csrf_token]: crm_config.csrf_hash
        }, function(res) {
            if (res.status == 'success') {
                loadLabels();
                if (typeof loadBoard === 'function') loadBoard();
            } else {
                alert('Error: ' + (res.message || 'Unknown'));
            }
        }, 'json');
    }

    window.deleteLabel = function(id) {
        if (!confirm('Delete this label? It will be removed from all leads.')) return;
        $.post(crm_config.base_url + '/ajax_delete_label', {
            id: id,
            [crm_config.csrf_token]: crm_config.csrf_hash
        }, function(res) {
            if (res.status == 'success') {
                loadLabels();
                if (typeof loadBoard === 'function') loadBoard();
            } else {
                alert('Error: ' + (res.message || 'Unknown'));
            }
        }, 'json');
    }

});

/* ===========================
    CRM SETTINGS (SMTP & IVR)
    =========================== */
window.openCrmSettings = function () {
    if ($('#crmSettingsModal').length == 0) {
        var modalHtml = `
        <div class="modal fade" id="crmSettingsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-cog text-muted"></i> CRM Settings</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs mb-3" id="settingsTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" id="smtp-tab" data-bs-toggle="tab" data-bs-target="#tab-smtp" type="button" role="tab">Email (SMTP)</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="ivr-tab" data-bs-toggle="tab" data-bs-target="#tab-ivr" type="button" role="tab">IVR / Voice</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="settingsTabsContent">
                            <!-- SMTP TAB -->
                            <div class="tab-pane fade show active" id="tab-smtp" role="tabpanel">
                                <form id="smtpForm">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label">SMTP Host</label>
                                            <input type="text" class="form-control" name="host" placeholder="smtp.gmail.com">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Port</label>
                                            <input type="number" class="form-control" name="port" placeholder="587">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" name="username">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Password</label>
                                            <input type="password" class="form-control" name="password">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Encryption</label>
                                            <select class="form-select" name="encryption">
                                                <option value="tls">TLS</option>
                                                <option value="ssl">SSL</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">From Email</label>
                                            <input type="email" class="form-control" name="from_email">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">From Name</label>
                                            <input type="text" class="form-control" name="from_name">
                                        </div>
                                    </div>
                                    <div class="mt-4 text-end">
                                        <button type="button" class="btn btn-primary" onclick="saveSmtpSettings()">Save SMTP Settings</button>
                                    </div>
                                </form>
                            </div>
                            <!-- IVR TAB -->
                            <div class="tab-pane fade" id="tab-ivr" role="tabpanel">
                                <div class="alert alert-info py-2"><small>Powered by Twilio</small></div>
                                <form id="ivrForm">
                                    <div class="mb-3">
                                        <label class="form-label">Account SID</label>
                                        <input type="text" class="form-control" name="account_sid">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Auth Token</label>
                                        <input type="password" class="form-control" name="auth_token">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Twilio Phone Number</label>
                                        <input type="text" class="form-control" name="phone_number" placeholder="+1234567890">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">IVR Message (Text-to-Speech)</label>
                                        <textarea class="form-control" name="ivr_message" rows="2" placeholder="Hello, connecting you to an agent..."></textarea>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="record_calls" id="record_calls">
                                        <label class="form-check-label" for="record_calls">Record all calls</label>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-primary" onclick="saveIvrSettings()">Save IVR Settings</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
        $('body').append(modalHtml);
    }

    $('#crmSettingsModal').modal('show');
    loadCrmSettings();
}

window.loadCrmSettings = function () {
    const $modal = $('#crmSettingsModal');
    console.log("Loading CRM Settings from: " + crm_config.base_url);

    // Load SMTP
    $.get(crm_config.base_url + '/ajax_get_smtp_settings', function (res) {
        console.log("SMTP Load Response:", res);
        if (res.status == 'success' && res.data) {
            var d = res.data;
            $modal.find('[name="host"]').val(d.host);
            $modal.find('[name="port"]').val(d.port);
            $modal.find('[name="username"]').val(d.username);
            $modal.find('[name="encryption"]').val(d.encryption);
            $modal.find('[name="from_email"]').val(d.from_email);
            $modal.find('[name="from_name"]').val(d.from_name);
            if (d.has_password) {
                $modal.find('[name="password"]').val('●●●●●●●●●●');
            } else {
                $modal.find('[name="password"]').val('');
            }
        }
    }, 'json').fail(function (err) {
        console.error("Failed to load SMTP settings:", err);
    });

    // Load IVR
    $.get(crm_config.base_url + '/ajax_get_ivr_settings', function (res) {
        console.log("IVR Load Response:", res);
        if (res.status == 'success' && res.data) {
            var d = res.data;
            $modal.find('[name="account_sid"]').val(d.account_sid);
            if (d.has_token) {
                $modal.find('[name="auth_token"]').val('●●●●●●●●●●');
            } else {
                $modal.find('[name="auth_token"]').val('');
            }
            $modal.find('[name="phone_number"]').val(d.phone_number);
            $modal.find('[name="ivr_message"]').val(d.ivr_message);
            $modal.find('#record_calls').prop('checked', d.record_calls == 1);
        }
    }, 'json').fail(function (err) {
        console.error("Failed to load IVR settings:", err);
    });
}

window.saveSmtpSettings = function () {
    var $btn = $('#tab-smtp button');
    var originalText = $btn.text();
    $btn.prop('disabled', true).text('Saving...');

    var data = $('#smtpForm').serializeArray();
    data.push({ name: crm_config.csrf_token, value: crm_config.csrf_hash });

    $.post(crm_config.base_url + '/ajax_save_smtp_settings', data, function (res) {
        $btn.prop('disabled', false).text(originalText);
        if (res.status == 'success') {
            alert('SMTP Settings Saved Successfully!');
            loadCrmSettings(); // Reload to refresh masked passwords
        } else {
            alert('Error: ' + res.message);
        }
    }, 'json').fail(function () {
        $btn.prop('disabled', false).text(originalText);
        alert('Network error while saving SMTP settings');
    });
}

window.saveIvrSettings = function () {
    var $btn = $('#tab-ivr button');
    var originalText = $btn.text();
    $btn.prop('disabled', true).text('Saving...');

    var data = $('#ivrForm').serializeArray();
    data.push({ name: crm_config.csrf_token, value: crm_config.csrf_hash });

    $.post(crm_config.base_url + '/ajax_save_ivr_settings', data, function (res) {
        $btn.prop('disabled', false).text(originalText);
        if (res.status == 'success') {
            alert('IVR Settings Saved Successfully!');
            loadCrmSettings();
        } else {
            alert('Error: ' + res.message);
        }
    }, 'json').fail(function () {
        $btn.prop('disabled', false).text(originalText);
        alert('Network error while saving IVR settings');
    });
}

/* ===========================
    EMAIL & IVR ACTIONS
    =========================== */

// Open Compose Email Modal
window.openComposeEmail = function (email) {
    // Simple prompt or custom modal? Let's do custom modal on fly
    if ($('#composeEmailModal').length == 0) {
        var modalHtml = `
        <div class="modal fade" id="composeEmailModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Send Email</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="composeEmailForm">
                            <input type="hidden" id="email_lead_id">
                            <div class="mb-3">
                                <label class="form-label">To</label>
                                <input type="email" class="form-control" id="email_to" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" class="form-control" id="email_subject">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" id="email_message" rows="5"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="sendEmail()">Send</button>
                    </div>
                </div>
            </div>
        </div>`;
        $('body').append(modalHtml);
    }

    var leadId = $('#leadModal').data('lead-id');
    $('#email_lead_id').val(leadId);
    $('#email_to').val(email || '');
    $('#composeEmailModal').modal('show');
}

window.sendEmail = function () {
    var btn = event.target;
    $(btn).prop('disabled', true).text('Sending...');

    var data = {
        lead_id: $('#email_lead_id').val(),
        to_email: $('#email_to').val(),
        subject: $('#email_subject').val(),
        message: $('#email_message').val(),
        [crm_config.csrf_token]: crm_config.csrf_hash
    };

    $.post(crm_config.base_url + '/ajax_send_email', data, function (res) {
        $(btn).prop('disabled', false).text('Send');
        if (res.status == 'success') {
            $('#composeEmailModal').modal('hide');
            alert('Email Sent!');
            openLeadModal(data.lead_id, 'tab-emails'); // Refresh
        } else {
            alert('Failed: ' + res.message);
        }
    }, 'json');
}

// Initiate IVR Call
// DIALER UI & LIVE CALL LOGIC
window.currentCallSid = null;
window.callTimerInterval = null;
window.callSeconds = 0;

window.initiateIvrCall = function (phone) {
    if (!phone) {
        alert("Invalid phone number");
        return;
    }

    // Inject Dialer Modal if not exists
    if ($('#dialerModal').length === 0) {
        var dialerHtml = `
        <div class="modal fade" id="dialerModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">
                    <div class="modal-body p-0 text-center text-white" style="background: linear-gradient(135deg, #2c3e50, #4ca1af); min-height: 400px; display: flex; flex-direction: column; justify-content: space-between;">
                        
                        <!-- Top: Status & Timer -->
                        <div class="pt-5">
                            <div class="mb-2">
                                <div class="avatar shadow" style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 30px;">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <h5 class="mb-1" id="dialer_phone_display"></h5>
                            <div class="small opacity-75" id="dialer_status">Calling...</div>
                            <div class="h2 font-weight-bold mt-3" id="dialer_timer">00:00</div>
                        </div>

                        <!-- Mid: Wave Animation (Visual) -->
                        <div class="flex-grow-1 d-flex align-items-center justify-content-center">
                            <div class="wave-anim">
                                <span></span><span></span><span></span>
                            </div>
                        </div>

                        <!-- Bottom: Controls -->
                        <div class="pb-5 px-4 d-flex justify-content-around align-items-end">
                            <button class="btn btn-icon btn-outline-light rounded-circle shadow-sm" style="width: 50px; height: 50px; border:none; background:rgba(255,255,255,0.2);" title="Mute (Visual Only)">
                                <i class="fas fa-microphone-slash"></i>
                            </button>
                            
                            <button class="btn btn-danger btn-icon rounded-circle shadow-lg" style="width: 65px; height: 65px; font-size: 24px;" onclick="endLiveCall()">
                                <i class="fas fa-phone-slash"></i>
                            </button>
                            
                            <button class="btn btn-icon btn-outline-light rounded-circle shadow-sm" style="width: 50px; height: 50px; border:none; background:rgba(255,255,255,0.2);" title="Speaker (Visual Only)">
                                <i class="fas fa-volume-up"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .wave-anim span {
                display: inline-block;
                width: 5px;
                height: 20px;
                margin: 0 3px;
                background: rgba(255,255,255,0.5);
                border-radius: 3px;
                animation: wave 1s infinite ease-in-out;
            }
            .wave-anim span:nth-child(2) { animation-delay: 0.1s; }
            .wave-anim span:nth-child(3) { animation-delay: 0.2s; }
            @keyframes wave {
                0%, 100% { height: 10px; opacity: 0.5; }
                50% { height: 30px; opacity: 1; }
            }
        </style>
        `;
        $('body').append(dialerHtml);
    }

    // Reset State
    $('#dialer_phone_display').text(phone);
    $('#dialer_status').text('Dialing Agent...');
    $('#dialer_timer').text('00:00');
    window.callSeconds = 0;
    if (window.callTimerInterval) clearInterval(window.callTimerInterval);

    // Show Modal
    $('#dialerModal').modal('show');

    // Initiate Call
    var leadId = $('#leadModal').data('lead-id');

    $.post(crm_config.base_url + '/ajax_initiate_ivr_call', {
        lead_id: leadId,
        phone: phone,
        [crm_config.csrf_token]: crm_config.csrf_hash
    }, function (res) {
        if (res.status == 'success') {
            window.currentCallSid = res.sid;
            $('#dialer_status').text('Ringing Agent...');

            // Start Timer (Simulated start on initiate for immediate feedback, 
            // ideally we wait for answer event via socket)
            startDialerTimer();

        } else {
            alert('Call Failed: ' + res.message);
            $('#dialerModal').modal('hide');
        }
    }, 'json');
}

window.startDialerTimer = function () {
    if (window.callTimerInterval) clearInterval(window.callTimerInterval);
    window.callTimerInterval = setInterval(function () {
        window.callSeconds++;
        var m = Math.floor(window.callSeconds / 60);
        var s = window.callSeconds % 60;
        $('#dialer_timer').text((m < 10 ? '0' + m : m) + ':' + (s < 10 ? '0' + s : s));
    }, 1000);
}

window.endLiveCall = function () {
    if (!window.currentCallSid) {
        $('#dialerModal').modal('hide');
        return;
    }

    $('#dialer_status').text('Ending Call...');

    $.post(crm_config.base_url + '/ajax_end_call', {
        call_sid: window.currentCallSid,
        [crm_config.csrf_token]: crm_config.csrf_hash
    }, function (res) {
        // Close modal regardless of success to ensure UI doesn't stick
        $('#dialerModal').modal('hide');
        if (window.callTimerInterval) clearInterval(window.callTimerInterval);

        // Refresh logs
        var leadId = $('#leadModal').data('lead-id');
        if (leadId) openLeadModal(leadId, 'tab-calls');
    }, 'json');
}

// ============================================================
// WAVOIP INTEGRATION
// ============================================================

// Initiate IVR Call from CRM
window.initiateIvrCall = function (phone) {
    if (!phone) {
        alert("Invalid phone number");
        return;
    }
    // Open WaVoip dialer with the phone pre-filled
    var baseUrl = window.location.origin;
    window.open(baseUrl + '/wavoip/dialer?phone=' + encodeURIComponent(phone), '_blank');
}

// Initiate VoIP Call from CRM (opens WaVoip Dialer with SDK)
window.initiateVoipCall = function (phone, contactName, contactId) {
    if (!phone) {
        alert("Invalid phone number");
        return;
    }

    // Log the call intent in the CRM
    var leadId = contactId || ($('#leadModal').data('lead-id') || '');

    $.post(window.location.origin + '/wavoip/call_from_crm', {
        phone: phone,
        contact_name: contactName || '',
        contact_id: leadId,
        [crm_config.csrf_token]: crm_config.csrf_hash
    }, function (res) {
        // Open WaVoip dialer with the real SDK - phone is pre-filled and call starts
        var baseUrl = window.location.origin;
        window.open(baseUrl + '/wavoip/dialer?phone=' + encodeURIComponent(phone), '_blank');

        // Refresh call tab
        if (leadId) {
            setTimeout(function () {
                openLeadModal(leadId, 'tab-calls');
            }, 2000);
        }
    }, 'json').fail(function () {
        // Even if CRM log fails, still open dialer
        var baseUrl = window.location.origin;
        window.open(baseUrl + '/wavoip/dialer?phone=' + encodeURIComponent(phone), '_blank');
    });
}

// Socket Listener for Status Updates (Optional Integration)
// If you have a global socket object, you can listen for 'call.status_update' here
// and update #dialer_status text (e.g. Ringing -> In Progress)
try {
    if (typeof socket !== 'undefined') {
        socket.on('call.status_update', function (data) {
            if (data.call_sid == window.currentCallSid) {
                $('#dialer_status').text(data.status.replace('_', ' ').toUpperCase());
                if (data.status == 'completed' || data.status == 'busy' || data.status == 'no-answer') {
                    setTimeout(function () {
                        $('#dialerModal').modal('hide');
                        if (window.callTimerInterval) clearInterval(window.callTimerInterval);
                    }, 2000);
                }
            }
        });
    }
} catch (e) { }
