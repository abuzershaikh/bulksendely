/**
 * WazMod - WhatsApp Automation Platform
 * Main application entry point that defines API routes for WhatsApp instance management
 * Compatible with Baileys v6.7.21 (@whiskeysockets/baileys)
 */

// Import required modules and configurations
const config = require("./config.js");           // Application configuration (database, ports, etc.)
const Common = require("./waziper/common.js");   // Common utility functions (database operations, helpers)
const WAZIPER = require("./waziper/waziper.js"); // Core WhatsApp integration module
const express = require('express');              // Express framework for routing
const path = require('path');                    // Node.js path utilities
const fs = require('fs');
const { getContentType } = require('@whiskeysockets/baileys');

async function ensureWelcomeMessageTables() {
    await Common.db_query(`
        CREATE TABLE IF NOT EXISTS sp_whatsapp_welcome_message (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ids VARCHAR(50) NOT NULL,
            team_id INT NOT NULL,
            instance_id VARCHAR(100) NOT NULL,
            name VARCHAR(255) NOT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            steps LONGTEXT NULL,
            changed INT NULL,
            created INT NULL,
            INDEX idx_welcome_team_instance (team_id, instance_id),
            INDEX idx_welcome_ids (ids)
        )
    `);

    await Common.db_query(`
        CREATE TABLE IF NOT EXISTS sp_whatsapp_welcome_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            instance_id VARCHAR(100) NOT NULL,
            whatsapp VARCHAR(50) NOT NULL,
            welcome_ids VARCHAR(50) NULL,
            first_incoming_at DATETIME NULL,
            last_welcome_sent_at DATETIME NULL,
            total_sent INT NOT NULL DEFAULT 0,
            created DATETIME NULL,
            changed DATETIME NULL,
            UNIQUE KEY uniq_welcome_contact (instance_id, whatsapp),
            INDEX idx_welcome_contact (team_id, instance_id, whatsapp)
        )
    `);
}

async function ensureKeywordReplyTables() {
    await Common.db_query(`
        CREATE TABLE IF NOT EXISTS sp_whatsapp_keyword_reply (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ids VARCHAR(50) NOT NULL,
            team_id INT NOT NULL,
            instance_id VARCHAR(100) NOT NULL,
            name VARCHAR(255) NOT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            keywords LONGTEXT NULL,
            steps LONGTEXT NULL,
            changed INT NULL,
            created INT NULL,
            INDEX idx_keyword_team_instance (team_id, instance_id),
            INDEX idx_keyword_ids (ids)
        )
    `);
}

async function ensureMenuReplyTables() {
    await Common.db_query(`
        CREATE TABLE IF NOT EXISTS sp_whatsapp_menu_reply (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ids VARCHAR(50) NOT NULL,
            team_id INT NOT NULL,
            instance_id VARCHAR(100) NOT NULL,
            name VARCHAR(255) NOT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            keywords LONGTEXT NULL,
            root_node_id VARCHAR(100) NULL,
            nodes LONGTEXT NULL,
            changed INT NULL,
            created INT NULL,
            INDEX idx_menu_team_instance (team_id, instance_id),
            INDEX idx_menu_ids (ids)
        )
    `);

    await Common.db_query(`
        CREATE TABLE IF NOT EXISTS sp_whatsapp_menu_session (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            instance_id VARCHAR(100) NOT NULL,
            whatsapp VARCHAR(50) NOT NULL,
            menu_ids VARCHAR(50) NULL,
            current_node_id VARCHAR(100) NULL,
            changed DATETIME NULL,
            created DATETIME NULL,
            UNIQUE KEY uniq_menu_session (instance_id, whatsapp),
            INDEX idx_menu_session_contact (team_id, instance_id, whatsapp)
        )
    `);
}

async function ensureAndroidCampaignTables() {
    await Common.db_query(`
        CREATE TABLE IF NOT EXISTS sp_android_campaign_status (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ids VARCHAR(64) NOT NULL,
            team_id INT NOT NULL DEFAULT 0,
            user_email VARCHAR(190) DEFAULT '',
            campaign_name VARCHAR(255) DEFAULT '',
            target_name VARCHAR(255) DEFAULT '',
            target_count INT NOT NULL DEFAULT 0,
            sent_count INT NOT NULL DEFAULT 0,
            failed_count INT NOT NULL DEFAULT 0,
            message_mode VARCHAR(50) DEFAULT '',
            message_label VARCHAR(255) DEFAULT '',
            delay_seconds INT NOT NULL DEFAULT 0,
            instance_id VARCHAR(190) DEFAULT '',
            status VARCHAR(50) DEFAULT 'queued',
            meta LONGTEXT NULL,
            items LONGTEXT NULL,
            changed INT NOT NULL DEFAULT 0,
            created INT NOT NULL DEFAULT 0,
            UNIQUE KEY uniq_ids (ids),
            KEY idx_team_created (team_id, created)
        )
    `);

    await Common.db_query(`
        CREATE TABLE IF NOT EXISTS sp_android_message_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message_id VARCHAR(190) NOT NULL,
            history_ids VARCHAR(64) NOT NULL,
            team_id INT NOT NULL DEFAULT 0,
            recipient_index INT NOT NULL DEFAULT 0,
            status VARCHAR(50) DEFAULT 'queued',
            created_at INT NOT NULL DEFAULT 0,
            UNIQUE KEY uniq_message_id (message_id),
            KEY idx_history_ids (history_ids)
        )
    `);

    await Common.db_query(`
        CREATE TABLE IF NOT EXISTS sp_android_campaign_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ids VARCHAR(64) NOT NULL,
            history_ids VARCHAR(64) NOT NULL,
            team_id INT NOT NULL DEFAULT 0,
            user_email VARCHAR(190) DEFAULT '',
            campaign_name VARCHAR(255) DEFAULT '',
            target_name VARCHAR(255) DEFAULT '',
            target_count INT NOT NULL DEFAULT 0,
            sent_count INT NOT NULL DEFAULT 0,
            failed_count INT NOT NULL DEFAULT 0,
            message_mode VARCHAR(50) DEFAULT '',
            message_label VARCHAR(255) DEFAULT '',
            delay_seconds INT NOT NULL DEFAULT 0,
            instance_id VARCHAR(190) DEFAULT '',
            current_index INT NOT NULL DEFAULT 0,
            next_run_at INT NOT NULL DEFAULT 0,
            status VARCHAR(50) DEFAULT 'queued',
            payload LONGTEXT NULL,
            recipients LONGTEXT NULL,
            last_error LONGTEXT NULL,
            changed INT NOT NULL DEFAULT 0,
            created INT NOT NULL DEFAULT 0,
            UNIQUE KEY uniq_ids (ids),
            KEY idx_queue_status (status, next_run_at),
            KEY idx_history_ids (history_ids)
        )
    `);
}

function parseJsonArray(value) {
    if (Array.isArray(value)) {
        return value;
    }

    if (!value) {
        return [];
    }

    try {
        const parsed = JSON.parse(value);
        return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        return [];
    }
}

function parseJsonObject(value) {
    if (value && typeof value === 'object' && !Array.isArray(value)) {
        return value;
    }

    if (!value) {
        return {};
    }

    try {
        const parsed = JSON.parse(value);
        return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
    } catch (error) {
        return {};
    }
}

function normalizeMediaUrl(rawUrl) {
    const value = `${rawUrl || ''}`.trim();
    if (!value) return '';
    if (/^https?:\/\//i.test(value)) return value;
    if (value.startsWith('/files/')) {
        return `${config.frontend}${value}`;
    }
    const filename = value.replace(/^\/+/, '');
    return `${config.frontend}/files/${filename}`;
}

function sanitizeUploadFilename(rawName) {
    const safe = `${rawName || ''}`.trim().replace(/[^\w.\-]/g, '_');
    if (!safe) return `media_${Date.now()}.bin`;
    return safe;
}

function extensionFromMime(mimeType) {
    const mime = `${mimeType || ''}`.toLowerCase();
    if (mime === 'video/mp4') return '.mp4';
    if (mime === 'video/quicktime') return '.mov';
    if (mime === 'video/x-matroska') return '.mkv';
    if (mime === 'video/webm') return '.webm';
    if (mime === 'image/jpeg') return '.jpg';
    if (mime === 'image/png') return '.png';
    if (mime === 'image/webp') return '.webp';
    if (mime === 'audio/mpeg') return '.mp3';
    if (mime === 'audio/wav') return '.wav';
    if (mime === 'application/pdf') return '.pdf';
    return '.bin';
}

function sanitizeUploadFolder(rawFolder) {
    const normalized = `${rawFolder || ''}`
        .replace(/\\/g, '/')
        .split('/')
        .map((part) => part.trim().replace(/[^\w\-]/g, ''))
        .filter(Boolean)
        .slice(0, 4);
    return normalized.length ? normalized.join('/') : 'android_uploads';
}

const MAX_WHATSAPP_INSTANCES_PER_TEAM = 10;

async function listTeamWhatsappInstances(teamId) {
    const accountRows = await Common.db_query(`
        SELECT
            token AS instance_id,
            name,
            pid,
            status,
            login_type,
            created,
            changed
        FROM sp_accounts
        WHERE team_id = '${teamId}'
          AND token IS NOT NULL
          AND token != ''
          AND social_network = 'whatsapp'
        ORDER BY created DESC, changed DESC
    `, false);

    const sessionRows = await Common.db_query(`
        SELECT
            instance_id,
            status,
            data,
            id
        FROM sp_whatsapp_sessions
        WHERE team_id = '${teamId}'
        ORDER BY id DESC
    `, false);

    const merged = new Map();
    const ensure = (instanceId) => {
        if (!instanceId) return null;
        if (!merged.has(instanceId)) {
            merged.set(instanceId, {
                instance_id: instanceId,
                linkedNumber: '',
                linkedName: '',
                account_status: 0,
                login_type: 0,
                connected: false,
                wsState: 'NOT_LOADED',
                healthy: false,
                source: 'session',
                sortStamp: 0,
            });
        }
        return merged.get(instanceId);
    };

    for (const row of Array.isArray(sessionRows) ? sessionRows : []) {
        const entry = ensure(row.instance_id);
        if (!entry) continue;

        entry.session_status = row.status;
        entry.sortStamp = Math.max(entry.sortStamp || 0, Number(row.id || 0));

        try {
            const decoded = row.data ? JSON.parse(row.data) : null;
            if (decoded && typeof decoded === 'object') {
                entry.linkedNumber =
                    `${decoded.id || decoded.wid || entry.linkedNumber || ''}`.trim();
                entry.linkedName =
                    `${decoded.name || entry.linkedName || ''}`.trim();
            }
        } catch (error) {
            // Ignore malformed session payloads and continue with best-effort data.
        }
    }

    for (const row of Array.isArray(accountRows) ? accountRows : []) {
        const entry = ensure(row.instance_id);
        if (!entry) continue;

        entry.source = 'account';
        entry.account_status = Number(row.status || 0);
        entry.login_type = Number(row.login_type || 0);
        entry.linkedName = `${row.name || entry.linkedName || ''}`.trim();
        entry.linkedNumber = `${row.pid || entry.linkedNumber || ''}`.trim();
        entry.sortStamp = Math.max(
            entry.sortStamp || 0,
            Number(row.changed || row.created || 0)
        );
    }

    const instances = Array.from(merged.values()).sort(
        (left, right) => (right.sortStamp || 0) - (left.sortStamp || 0)
    );

    for (const entry of instances) {
        const health = await WAZIPER.check_session_health(entry.instance_id);
        entry.healthy = health.healthy === true;
        entry.connected = health.healthy === true;
        entry.wsState = health.wsState || entry.wsState;
        if (health.user) {
            entry.linkedNumber = `${health.user}`.trim() || entry.linkedNumber;
        }
        if (health.name) {
            entry.linkedName = `${health.name}`.trim() || entry.linkedName;
        }
    }

    return instances.slice(0, MAX_WHATSAPP_INSTANCES_PER_TEAM);
}

async function updateQueuedCampaignHistory(queueRow, overrides = {}) {
    const items = overrides.items ?? parseJsonArray(queueRow.recipients);
    const counts = summarizeCampaignRecipients(items);

    await Common.db_update('sp_android_campaign_status', [
        {
            sent_count: overrides.sent_count ?? counts.sentCount,
            failed_count: overrides.failed_count ?? counts.failedCount,
            status: overrides.status ?? queueRow.status ?? 'queued',
            items: JSON.stringify(items),
            changed: Common.time(),
        },
        {
            ids: queueRow.history_ids,
        }
    ]);
}

function summarizeCampaignRecipients(recipients = []) {
    let sentCount = 0;
    let failedCount = 0;

    for (const recipient of Array.isArray(recipients) ? recipients : []) {
        const status = `${recipient?.status || ''}`.toLowerCase();
        if (status === 'sent') {
            sentCount += 1;
        } else if (status === 'failed') {
            failedCount += 1;
        }
    }

    return {
        sentCount,
        failedCount,
    };
}

async function sendQueuedCampaignRecipient(queueRow) {
    const payload = parseJsonObject(queueRow.payload);
    const recipients = parseJsonArray(queueRow.recipients);
    const recipient = recipients[queueRow.current_index];

    if (!recipient) {
        throw new Error('Campaign recipient not found');
    }

    let number = `${recipient.number || ''}`.trim();
    let chatId = `${recipient.chat_id || ''}`.trim();
    const isGroup =
        recipient.is_group === true ||
        chatId.includes('g.us') ||
        number.includes('g.us');

    if (isGroup) {
        chatId = chatId || number;
        if (!chatId) {
            throw new Error('Recipient chat_id is invalid');
        }
        if (chatId.includes('g.us') && !chatId.includes('@')) {
            chatId = chatId.replace('g.us', '@g.us');
        }
        number = chatId;
    } else {
        number = number.replace(/\D/g, '');
        if (!number) {
            throw new Error('Recipient number is invalid');
        }
        chatId = `${number}@s.whatsapp.net`;
    }

    const item = {
        team_id: queueRow.team_id,
        caption: '',
        media: '',
        filename: '',
        template: 0,
        type: 1,
        media_type: null,
        mime_type: '',
    };
    let forwardSourceMessage = null;
    let forwardSourceMessageType = '';

    switch (`${payload.type || ''}`.trim()) {
        case 'template':
            item.type = Number(payload.template_item_type || 0);
            item.template = `${payload.template_id || ''}`.trim();
            if (!item.type || !item.template) {
                throw new Error('Template payload is invalid');
            }
            break;

        case 'media':
            item.caption = `${payload.caption || ''}`;
            item.media = normalizeMediaUrl(payload.media_url);
            item.filename = `${payload.filename || ''}`.trim();
            item.media_type = normalizeCampaignMediaType(payload.media_type) || null;
            item.mime_type = `${payload.mime_type || ''}`.trim().toLowerCase();
            if (!item.media) {
                throw new Error('Media payload is missing media_url');
            }
            break;

        case 'forward': {
            const source = payload.source_message;
            if (!source || typeof source !== 'object' || Array.isArray(source)) {
                throw new Error('Forward payload is missing source_message');
            }
            const sourceMessage = source.message && typeof source.message === 'object'
                ? source.message
                : null;
            if (!sourceMessage) {
                throw new Error('Forward payload source_message.message is invalid');
            }
            const sourceKey = source.key && typeof source.key === 'object'
                ? source.key
                : {};
            const normalizedRemoteJid = String(sourceKey.remoteJid || sourceKey.remoteJidAlt || '').trim();
            const normalizedParticipant = String(sourceKey.participant || sourceKey.participantAlt || '').trim();
            if (!normalizedRemoteJid || !sourceKey.id) {
                throw new Error('Forward source key must have remoteJid and id');
            }

            const rawForwardContent = (
                sourceMessage.deviceSentMessage &&
                typeof sourceMessage.deviceSentMessage === 'object' &&
                sourceMessage.deviceSentMessage.message &&
                typeof sourceMessage.deviceSentMessage.message === 'object'
            )
                ? sourceMessage.deviceSentMessage.message
                : sourceMessage;
            const actualMessageType =
                getContentType(rawForwardContent) ||
                Object.keys(rawForwardContent).find((key) =>
                    key !== 'senderKeyDistributionMessage' &&
                    key !== 'messageContextInfo'
                ) ||
                '';

            if (!actualMessageType || typeof rawForwardContent[actualMessageType] === 'undefined') {
                throw new Error('Forward source message type is unsupported');
            }

            console.log('[FORWARD] Source message validated:', {
                remoteJid: normalizedRemoteJid,
                id: sourceKey.id,
                fromMe: sourceKey.fromMe,
                participant: normalizedParticipant,
                originalKeys: Object.keys(rawForwardContent),
                messageType: actualMessageType
            });

            forwardSourceMessage = {
                key: {
                    ...sourceKey,
                    remoteJid: normalizedRemoteJid,
                    ...(normalizedParticipant ? { participant: normalizedParticipant } : {}),
                },
                message: {
                    [actualMessageType]: rawForwardContent[actualMessageType],
                },
            };
            forwardSourceMessageType = actualMessageType;
            break;
        }

        case 'text':
        default:
            item.caption = `${payload.message || ''}`;
            if (!item.caption.trim()) {
                throw new Error('Text payload is missing message');
            }
            break;
    }

    if (forwardSourceMessage) {
        console.log('ðŸ”„ [FORWARD] Attempting to forward message:', {
            chatId: chatId,
            isGroup: chatId.includes('@g.us'),
            sourceKey: forwardSourceMessage.key,
            messageType: forwardSourceMessageType || Object.keys(forwardSourceMessage.message || {})[0]
        });
        
        const readiness = await WAZIPER.waitForSessionReady(queueRow.instance_id, {
            attempts: 6,
            delayMs: 700,
            recreateAfterMs: 6000,
        });
        const client = readiness.client;
        if (!client || !readiness.ready) {
            const errorMsg = `Forward failed: session not ready (${readiness.wsState || 'UNKNOWN'})`;
            console.error('âŒ [FORWARD]', errorMsg);
            throw new Error(errorMsg);
        }
        
        try {
            // Send with force: true to always show forward tag
            const result = await client.sendMessage(chatId, { 
                forward: forwardSourceMessage, 
                force: true 
            });
            
            console.log('âœ… [FORWARD] Success:', {
                chatId: chatId,
                messageId: result?.key?.id,
                timestamp: result?.messageTimestamp
            });
            
            return { 
                status: 1, 
                type: 'api', 
                phone_number: chatId, 
                stats: true,
                messageId: result?.key?.id
            };
        } catch (sendError) {
            console.error('âŒ [FORWARD] Send failed:', {
                error: sendError.message,
                chatId: chatId,
                sourceKey: forwardSourceMessage.key
            });
            throw new Error(`Forward send failed: ${sendError.message}`);
        }
    }

    return await new Promise(async (resolve, reject) => {
        try {
            await WAZIPER.auto_send(
                queueRow.instance_id,
                chatId,
                chatId,
                'api',
                item,
                false,
                false,
                false,
                function (result) {
                    if (result && result.status !== 0) {
                        resolve(result);
                    } else {
                        reject(new Error(result?.message || 'Failed to send message'));
                    }
                }
            );
        } catch (error) {
            reject(error);
        }
    });
}

const activeCampaignWorkers = new Set();
const activeCampaignInstances = new Set();
let campaignWorkerTickInProgress = false;
const CAMPAIGN_RETRY_LIMIT = 8;
const CAMPAIGN_ADAPTIVE_STATE = new Map();
const CAMPAIGN_BURST_SEND_LIMIT = Math.max(
    1,
    Number(process.env.CAMPAIGN_BURST_SEND_LIMIT || 30)
);
const CAMPAIGN_WORKER_TICK_MS = Math.max(
    150,
    Number(process.env.CAMPAIGN_WORKER_TICK_MS || 200)
);
const CAMPAIGN_RETRY_BASE_DELAY_SEC = Math.max(
    1,
    Number(process.env.CAMPAIGN_RETRY_BASE_DELAY_SEC || 3)
);
const CAMPAIGN_GROUP_FAST_DELAY_SEC = Math.max(
    0,
    Number(process.env.CAMPAIGN_GROUP_FAST_DELAY_SEC || 0)
);
const CAMPAIGN_WS_RETRY_SEC = Math.max(
    1,
    Number(process.env.CAMPAIGN_WS_RETRY_SEC || 5)
);

function getCampaignAdaptiveState(instanceId) {
    if (!instanceId) {
        return {
            extraDelaySec: 0,
            cooldownUntil: 0,
            recentTimeouts: [],
            recentSuccesses: 0,
            lastUpdatedAt: 0,
        };
    }

    if (!CAMPAIGN_ADAPTIVE_STATE.has(instanceId)) {
        CAMPAIGN_ADAPTIVE_STATE.set(instanceId, {
            extraDelaySec: 0,
            cooldownUntil: 0,
            recentTimeouts: [],
            recentSuccesses: 0,
            lastUpdatedAt: 0,
        });
    }

    return CAMPAIGN_ADAPTIVE_STATE.get(instanceId);
}

function compactAdaptiveState(state, now) {
    // Keep only recent timeout signals from the last 5 minutes.
    state.recentTimeouts = (state.recentTimeouts || []).filter((ts) => now - ts <= 300);
}

function markAdaptiveFailure(instanceId, error) {
    const now = Common.time();
    const state = getCampaignAdaptiveState(instanceId);
    compactAdaptiveState(state, now);

    const text = `${error?.message || error || ''}`.toLowerCase();
    const timeoutLike =
        text.includes('timed out') ||
        text.includes('request time-out') ||
        text.includes('timeout') ||
        text.includes('socket') ||
        text.includes('stream') ||
        text.includes('connection');

    if (timeoutLike) {
        state.recentTimeouts.push(now);
    }

    // Step-up adaptive delay on transient failures, capped at 45s extra.
    state.extraDelaySec = Math.min(45, Number(state.extraDelaySec || 0) + (timeoutLike ? 5 : 2));
    state.recentSuccesses = 0;
    state.lastUpdatedAt = now;

    // If timeout spikes, enter cooldown to avoid hammering WhatsApp.
    if (state.recentTimeouts.length >= 4) {
        state.cooldownUntil = now + 90;
    } else if (state.recentTimeouts.length >= 2) {
        state.cooldownUntil = Math.max(Number(state.cooldownUntil || 0), now + 30);
    }
}

function markAdaptiveSuccess(instanceId) {
    const now = Common.time();
    const state = getCampaignAdaptiveState(instanceId);
    compactAdaptiveState(state, now);

    state.recentSuccesses = Number(state.recentSuccesses || 0) + 1;
    state.lastUpdatedAt = now;

    // Gradually relax throttle every 3 successes.
    if (state.recentSuccesses >= 3) {
        state.extraDelaySec = Math.max(0, Number(state.extraDelaySec || 0) - 2);
        state.recentSuccesses = 0;
    }

    if (state.recentTimeouts.length === 0 && state.extraDelaySec > 0) {
        state.extraDelaySec = Math.max(0, state.extraDelaySec - 1);
    }
}

function getAdaptiveNextDelaySec(instanceId, baseDelaySec) {
    const now = Common.time();
    const state = getCampaignAdaptiveState(instanceId);
    compactAdaptiveState(state, now);
    const base = Math.max(Number(baseDelaySec || 0), 0);
    return base + Math.max(Number(state.extraDelaySec || 0), 0);
}

function normalizeCampaignMediaType(value) {
    const raw = `${value || ''}`.trim().toLowerCase();
    if (!raw) return '';
    if (raw.startsWith('video/')) return 'video';
    if (raw.startsWith('image/')) return 'image';
    if (raw.startsWith('audio/')) return 'audio';
    if (raw.startsWith('application/')) return 'document';
    if (raw === 'video' || raw === 'image' || raw === 'audio' || raw === 'document') {
        return raw;
    }
    return '';
}

function isGroupCampaignRecipient(recipient) {
    const number = `${recipient?.number || ''}`.toLowerCase();
    const chatId = `${recipient?.chat_id || ''}`.toLowerCase();
    return recipient?.is_group === true || number.includes('g.us') || chatId.includes('g.us');
}

function normalizeGroupChatId(groupId) {
    let normalized = `${groupId || ''}`.trim();
    if (!normalized) return '';
    if (normalized.includes('g.us') && !normalized.includes('@')) {
        normalized = normalized.replace('g.us', '@g.us');
    }
    return normalized;
}

async function findGroupScheduleLock({ teamId, instanceId, groupId }) {
    const normalizedGroupId = normalizeGroupChatId(groupId);
    if (!teamId || !instanceId || !normalizedGroupId) {
        return null;
    }

    const now = Common.time();
    const rows = await Common.db_query(`
        SELECT id, history_ids, campaign_name, next_run_at, status, recipients
        FROM sp_android_campaign_queue
        WHERE team_id = '${teamId}'
          AND instance_id = '${instanceId}'
          AND status IN ('queued', 'processing')
          AND next_run_at >= ${now}
        ORDER BY next_run_at ASC
        LIMIT 100
    `, false);

    if (!Array.isArray(rows) || !rows.length) {
        return null;
    }

    for (const row of rows) {
        const recipients = parseJsonArray(row.recipients);
        const hasPendingGroup = recipients.some((recipient) => {
            const recipientGroup = normalizeGroupChatId(recipient?.chat_id || recipient?.number || '');
            if (!recipientGroup || recipientGroup !== normalizedGroupId) {
                return false;
            }
            const status = `${recipient?.status || ''}`.toLowerCase();
            return status === 'queued' || status === 'failed' || status === '';
        });

        if (hasPendingGroup) {
            return {
                campaign_id: row.history_ids || '',
                campaign_name: row.campaign_name || '',
                queue_status: row.status || 'queued',
                next_run_at: Number(row.next_run_at || 0),
                next_run_in_sec: Math.max(0, Number(row.next_run_at || 0) - now),
            };
        }
    }

    return null;
}

function isTransientCampaignSendError(error) {
    const message = `${error?.message || error || ''}`.toLowerCase();
    return (
        message.includes('timed out') ||
        message.includes('request time-out') ||
        message.includes('timeout') ||
        message.includes('connection') ||
        message.includes('socket') ||
        message.includes('stream') ||
        message.includes('closed') ||
        message.includes('not loaded') ||
        message.includes('session') ||
        message.includes('retry')
    );
}

async function processQueuedCampaignRow(queueRow) {
    if (!queueRow?.ids || activeCampaignWorkers.has(queueRow.ids)) {
        return;
    }

    const instanceId = `${queueRow.instance_id || ''}`.trim();
    if (!instanceId) {
        return;
    }

    if (activeCampaignInstances.has(instanceId)) {
        return;
    }

    activeCampaignWorkers.add(queueRow.ids);
    activeCampaignInstances.add(instanceId);

    try {
        const recipients = parseJsonArray(queueRow.recipients);
        const now = Common.time();
        const adaptiveState = getCampaignAdaptiveState(instanceId);
        const manualDelaySec = Math.max(Number(queueRow.delay_seconds || 0), 0);
        const hasOnlyGroupRecipients =
            recipients.length > 0 && recipients.every((recipient) => isGroupCampaignRecipient(recipient));
        const effectiveManualDelaySec = hasOnlyGroupRecipients
            ? Math.min(manualDelaySec, CAMPAIGN_GROUP_FAST_DELAY_SEC)
            : manualDelaySec;

        if (Number(adaptiveState.cooldownUntil || 0) > now) {
            await Common.db_update('sp_android_campaign_queue', [
                {
                    status: 'queued',
                    next_run_at: Number(adaptiveState.cooldownUntil),
                    last_error: 'Adaptive cooldown active due to timeout spike',
                    changed: now,
                },
                { ids: queueRow.ids }
            ]);
            return;
        }

        if (!recipients.length) {
            await Common.db_update('sp_android_campaign_queue', [
                {
                    status: 'failed',
                    last_error: 'No recipients found',
                    changed: now,
                },
                { ids: queueRow.ids }
            ]);
            await updateQueuedCampaignHistory(queueRow, {
                status: 'failed',
                items: [],
            });
            return;
        }

        if (queueRow.current_index >= recipients.length) {
            const unretriedFailedIndex = recipients.findIndex(r => r && r.status === 'failed' && !r.end_auto_retried);
            if (unretriedFailedIndex !== -1) {
                console.log(`🔄 [AutoRetry] Campaign "${queueRow.campaign_name}" resumed at end. Auto-retrying failed recipient index ${unretriedFailedIndex}...`);
                recipients[unretriedFailedIndex].status = 'queued';
                recipients[unretriedFailedIndex].end_auto_retried = true;
                recipients[unretriedFailedIndex].error = 'Auto-retrying failed delivery...';
                
                const currentCounts = summarizeCampaignRecipients(recipients);
                await Common.db_update('sp_android_campaign_queue', [
                    {
                        current_index: unretriedFailedIndex,
                        sent_count: currentCounts.sentCount,
                        failed_count: currentCounts.failedCount,
                        recipients: JSON.stringify(recipients),
                        status: 'queued',
                        next_run_at: Common.time() + 3,
                        changed: now,
                    },
                    { ids: queueRow.ids }
                ]);
                await updateQueuedCampaignHistory(
                    { ...queueRow, current_index: unretriedFailedIndex, sent_count: currentCounts.sentCount, failed_count: currentCounts.failedCount, recipients: JSON.stringify(recipients), status: 'queued' },
                    { status: 'queued', items: recipients, sent_count: currentCounts.sentCount, failed_count: currentCounts.failedCount }
                );
                return;
            }

            await Common.db_update('sp_android_campaign_queue', [
                {
                    status: 'completed',
                    changed: now,
                },
                { ids: queueRow.ids }
            ]);
            await updateQueuedCampaignHistory(queueRow, {
                status: 'completed',
                items: recipients,
                sent_count: queueRow.sent_count,
                failed_count: queueRow.failed_count,
            });
            return;
        }

        await WAZIPER.session(queueRow.instance_id, false);
        const health = await WAZIPER.check_session_health(queueRow.instance_id);

        if (!health.healthy) {
            await Common.db_update('sp_android_campaign_queue', [
                {
                    status: 'queued',
                    next_run_at: now + CAMPAIGN_WS_RETRY_SEC,
                    last_error: health.reason,
                    changed: now,
                },
                { ids: queueRow.ids }
            ]);
            return;
        }

        await Common.db_update('sp_android_campaign_queue', [
            {
                status: 'processing',
                last_error: null,
                changed: now,
            },
            { ids: queueRow.ids }
        ]);

        const queueCounts = summarizeCampaignRecipients(recipients);
        let sentCount = queueCounts.sentCount;
        let failedCount = queueCounts.failedCount;
        let currentIndex = Number(queueRow.current_index || 0);
        let burstSentCount = 0;

        while (currentIndex < recipients.length) {
            // Stop/start actions can change queue status while this worker is mid-loop.
            // Re-check the latest persisted status before sending the next recipient.
            const latestQueueRow = await Common.db_get('sp_android_campaign_queue', [
                { ids: queueRow.ids }
            ]);
            if (!latestQueueRow) {
                return;
            }
            const latestStatus = `${latestQueueRow.status || ''}`.toLowerCase();
            if (latestStatus === 'paused') {
                await updateQueuedCampaignHistory(
                    {
                        ...queueRow,
                        status: 'paused',
                        recipients: latestQueueRow.recipients,
                    },
                    {
                        status: 'paused',
                        items: parseJsonArray(latestQueueRow.recipients),
                    }
                );
                return;
            }

            const currentRecipient = recipients[currentIndex] || {};
            const recipientStatus = `${currentRecipient?.status || ''}`.toLowerCase();
            if (recipientStatus === 'sent' || recipientStatus === 'timeout_pending') {
                currentIndex += 1;
                continue;
            }
            let sentSuccessfully = false;

            try {
                const result = await sendQueuedCampaignRecipient({
                    ...queueRow,
                    current_index: currentIndex,
                    recipients: JSON.stringify(recipients),
                });
                
                const msgId = result?.messageId || result?.message?.key?.id;
                if (msgId) {
                    await Common.db_insert('sp_android_message_tracking', {
                        message_id: msgId,
                        history_ids: queueRow.history_ids,
                        team_id: queueRow.team_id,
                        recipient_index: currentIndex,
                        status: 'sent',
                        created_at: Common.time()
                    });
                }

                sentCount += 1;
                sentSuccessfully = true;
                recipients[currentIndex] = {
                    ...currentRecipient,
                    status: 'sent',
                    message_id: msgId || '',
                    error: '',
                };
                markAdaptiveSuccess(queueRow.instance_id);
            } catch (error) {
                // If it timed out, we optimisticially mark it as timeout_pending
                if (error.isTimeout && error.messageId) {
                    await Common.db_insert('sp_android_message_tracking', {
                        message_id: error.messageId,
                        history_ids: queueRow.history_ids,
                        team_id: queueRow.team_id,
                        recipient_index: currentIndex,
                        status: 'timeout_pending',
                        created_at: Common.time()
                    });
                    
                    sentCount += 1;
                    sentSuccessfully = true;
                    recipients[currentIndex] = {
                        ...currentRecipient,
                        status: 'timeout_pending',
                        message_id: error.messageId,
                        error: 'Awaiting delivery receipt (Timeout)',
                    };
                    markAdaptiveFailure(queueRow.instance_id, error);
                    continue; // move to next recipient!
                }

                const retryCount = Number(currentRecipient.retry_count || 0);
                const transientFailure = isTransientCampaignSendError(error);
                const canRetry = transientFailure && retryCount < CAMPAIGN_RETRY_LIMIT;

                if (canRetry) {
                    recipients[currentIndex] = {
                        ...currentRecipient,
                        status: 'queued',
                        retry_count: retryCount + 1,
                        error: error.message || 'Send failed',
                    };

                    const pausedQueueRow = await Common.db_get('sp_android_campaign_queue', [
                        { ids: queueRow.ids }
                    ]);
                    const pausedStatus = `${pausedQueueRow?.status || ''}`.toLowerCase();
                    if (pausedStatus === 'paused') {
                        await updateQueuedCampaignHistory(
                            {
                                ...queueRow,
                                status: 'paused',
                                recipients: JSON.stringify(recipients),
                            },
                            {
                                status: 'paused',
                                items: recipients,
                                sent_count: sentCount,
                                failed_count: failedCount,
                            }
                        );
                        return;
                    }

                    await Common.db_update('sp_android_campaign_queue', [
                        {
                            sent_count: sentCount,
                            failed_count: failedCount,
                            current_index: currentIndex,
                            recipients: JSON.stringify(recipients),
                            status: 'queued',
                            next_run_at: Common.time() + getAdaptiveNextDelaySec(queueRow.instance_id, CAMPAIGN_RETRY_BASE_DELAY_SEC),
                            last_error: error.message || 'Transient send failure',
                            changed: Common.time(),
                        },
                        { ids: queueRow.ids }
                    ]);

                    markAdaptiveFailure(queueRow.instance_id, error);

                    await updateQueuedCampaignHistory(
                        {
                            ...queueRow,
                            current_index: currentIndex,
                            sent_count: sentCount,
                            failed_count: failedCount,
                            recipients: JSON.stringify(recipients),
                            status: 'queued',
                        },
                        {
                            status: 'queued',
                            items: recipients,
                            sent_count: sentCount,
                            failed_count: failedCount,
                        }
                    );
                    return;
                }

                failedCount += 1;
                recipients[currentIndex] = {
                    ...currentRecipient,
                    status: 'failed',
                    retry_count: retryCount,
                    error: error.message || 'Send failed',
                };
                markAdaptiveFailure(queueRow.instance_id, error);
            }

            const pausedQueueRow = await Common.db_get('sp_android_campaign_queue', [
                { ids: queueRow.ids }
            ]);
            const pausedStatus = `${pausedQueueRow?.status || ''}`.toLowerCase();
            if (pausedStatus === 'paused') {
                await updateQueuedCampaignHistory(
                    {
                        ...queueRow,
                        status: 'paused',
                        recipients: JSON.stringify(recipients),
                    },
                    {
                        status: 'paused',
                        items: recipients,
                        sent_count: sentCount,
                        failed_count: failedCount,
                    }
                );
                return;
            }

            let nextIndex = currentIndex + 1;
            let isCompleted = nextIndex >= recipients.length;

            if (isCompleted) {
                const unretriedFailedIndex = recipients.findIndex(r => r && r.status === 'failed' && !r.end_auto_retried);
                if (unretriedFailedIndex !== -1) {
                    console.log(`🔄 [AutoRetry] Campaign "${queueRow.campaign_name}" finished pass. Auto-retrying failed recipient #${unretriedFailedIndex + 1} (${recipients[unretriedFailedIndex].number || recipients[unretriedFailedIndex].name})...`);
                    recipients[unretriedFailedIndex].status = 'queued';
                    recipients[unretriedFailedIndex].end_auto_retried = true;
                    recipients[unretriedFailedIndex].error = 'Auto-retrying failed delivery...';
                    
                    nextIndex = unretriedFailedIndex;
                    isCompleted = false;
                    
                    const recalc = summarizeCampaignRecipients(recipients);
                    sentCount = recalc.sentCount;
                    failedCount = recalc.failedCount;
                }
            }

            const adaptiveDelaySec = isCompleted
                ? 0
                : getAdaptiveNextDelaySec(queueRow.instance_id, effectiveManualDelaySec);
            const canContinueBurst = (
                !isCompleted &&
                effectiveManualDelaySec <= 1 &&
                adaptiveDelaySec <= 1 &&
                sentSuccessfully &&
                burstSentCount < CAMPAIGN_BURST_SEND_LIMIT
            );
            const nextStatus = isCompleted ? 'completed' : 'queued';
            const nextRunAt = isCompleted
                ? 0
                : Common.time() + (canContinueBurst ? 0 : adaptiveDelaySec);
            const updatedQueueRow = {
                ...queueRow,
                current_index: nextIndex,
                sent_count: sentCount,
                failed_count: failedCount,
                status: nextStatus,
                recipients: JSON.stringify(recipients),
            };

            await Common.db_update('sp_android_campaign_queue', [
                {
                    sent_count: sentCount,
                    failed_count: failedCount,
                    current_index: nextIndex,
                    recipients: JSON.stringify(recipients),
                    status: nextStatus,
                    next_run_at: nextRunAt,
                    changed: Common.time(),
                },
                { ids: queueRow.ids }
            ]);

            await updateQueuedCampaignHistory(updatedQueueRow, {
                status: nextStatus,
                items: recipients,
                sent_count: sentCount,
                failed_count: failedCount,
            });

            if (isCompleted || !canContinueBurst) {
                return;
            }

            burstSentCount += 1;
            currentIndex = nextIndex;
            queueRow = updatedQueueRow;
        }
    } catch (error) {
        markAdaptiveFailure(queueRow.instance_id, error);
        await Common.db_update('sp_android_campaign_queue', [
            {
                status: 'queued',
                next_run_at: Common.time() + getAdaptiveNextDelaySec(queueRow.instance_id, CAMPAIGN_RETRY_BASE_DELAY_SEC),
                last_error: error.message || 'Campaign processor failed',
                changed: Common.time(),
            },
            { ids: queueRow.ids }
        ]);
    } finally {
        activeCampaignWorkers.delete(queueRow.ids);
        activeCampaignInstances.delete(instanceId);
    }
}

async function processQueuedCampaigns() {
    if (campaignWorkerTickInProgress) {
        return;
    }

    campaignWorkerTickInProgress = true;

    try {
        await ensureAndroidCampaignTables();
        const now = Common.time();
        const rows = await Common.db_query(`
            SELECT *
            FROM sp_android_campaign_queue
            WHERE status IN ('queued', 'processing')
              AND next_run_at <= ${now}
            ORDER BY next_run_at ASC, created ASC
            LIMIT 40
        `, false);

        if (Array.isArray(rows) && rows.length) {
            const tasks = rows.map((row) =>
                processQueuedCampaignRow(row).catch((error) => {
                    console.error(
                        `Queued campaign row failed (${row.ids || row.id || 'unknown'}):`,
                        error.message
                    );
                })
            );
            await Promise.all(tasks);
        }
    } catch (error) {
        console.error('Error in queued campaign worker:', error.message);
    } finally {
        campaignWorkerTickInProgress = false;
    }
}

/**
 * Static file serving configuration
 * Serves uploaded files (images, videos, documents) from the 'files' directory
 * This allows media files to be accessed via HTTP for WhatsApp message sending
 */
WAZIPER.app.use('/files', express.static(path.join(__dirname, 'files')));

/**
 * GET /instance
 * Retrieves information about a specific WhatsApp instance
 *
 * Query Parameters:
 * @param {string} access_token - Team authentication token for authorization
 * @param {string} instance_id - Unique identifier for the WhatsApp instance
 *
 * Returns: Instance information including connection status, user details, and avatar
 *
 * This endpoint is used to check if an instance is connected and retrieve its current state
 */
WAZIPER.app.get('/instance', WAZIPER.cors, async (req, res) => {
    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;

    // Validate access and retrieve instance information
    await WAZIPER.instance(access_token, instance_id,  res, async (client) => {
        await WAZIPER.get_info(instance_id, res);
    });
});

/**
 * GET /session/health
 * Check session health status
 *
 * Query Parameters:
 * @param {string} access_token - Team authentication token (optional for basic check)
 * @param {string} instance_id - WhatsApp instance identifier
 *
 * Returns: Session health status including:
 * - healthy: boolean indicating if session is ready
 * - reason: explanation of health status
 * - user: WhatsApp user name (if authenticated)
 * - wsState: WebSocket connection state (if connected)
 *
 * This endpoint allows external processes to check session health
 * without needing direct access to the sessions object in memory
 */
WAZIPER.app.get('/session/health', WAZIPER.cors, async (req, res) => {
    const { access_token, instance_id } = req.query;

    // Validate instance_id is provided
    if (!instance_id) {
        return res.json({
            status: 'error',
            message: 'instance_id parameter is required'
        });
    }

    // Optional: Validate access token if provided
    if (access_token) {
        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({
                status: 'error',
                message: 'Invalid access token'
            });
        }

        // Verify instance belongs to team
        const session = await Common.db_get("sp_whatsapp_sessions", [
            { instance_id: instance_id },
            { team_id: team.id }
        ]);
        if (!session) {
            return res.json({
                status: 'error',
                message: 'Instance not found or does not belong to this team'
            });
        }
    }

    // Check session health
    const health = await WAZIPER.check_session_health(instance_id);

    return res.json({
        status: 'success',
        instance_id: instance_id,
        health: health
    });
});

/**
 * GET /get_qrcode
 * Generates and returns a QR code for WhatsApp authentication
 *
 * Query Parameters:
 * @param {string} access_token - Team authentication token
 * @param {string} instance_id - WhatsApp instance identifier
 * @param {string} reset - Optional: Set to 'true' or '1' to force new session creation
 *
 * Returns: Base64 encoded QR code image that can be scanned with WhatsApp mobile app
 *
 * This is the primary method for connecting a WhatsApp account to the platform.
 * The QR code must be scanned within the timeout period (default: 40 seconds)
 * Compatible with Baileys 6.7.21 QR code generation
 *
 * FIX: Added reset parameter to allow forcing new session creation when instance
 * already exists but needs to reconnect (e.g., after logout or connection loss)
 */
WAZIPER.app.get('/get_qrcode', WAZIPER.cors, async (req, res) => {
    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;
    var reset = req.query.reset === 'true' || req.query.reset === '1';

    await WAZIPER.instance(access_token, instance_id, res, async (client) => {
        await WAZIPER.get_qrcode(instance_id, res);
    }, reset);
});

/**
 * GET /get_paircode
 * Generates a pairing code for WhatsApp authentication (alternative to QR code)
 *
 * Query Parameters:
 * @param {string} access_token - Team authentication token
 * @param {string} instance_id - WhatsApp instance identifier
 * @param {string} phone - Phone number to pair (without + or special characters)
 *
 * Returns: 8-digit pairing code formatted as XXXX-XXXX
 *
 * This method allows connecting without scanning a QR code by entering a pairing code
 * in the WhatsApp mobile app. Requires Baileys 6.7.21+ with requestPairingCode support
 */
WAZIPER.app.get('/get_paircode', WAZIPER.cors, async (req, res) => {
    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;

    await WAZIPER.instance(access_token, instance_id, res, async (client) => {
        await WAZIPER.get_pairing(instance_id, req, res);
    });
});

/**
 * GET /get_groups
 * Retrieves all WhatsApp groups that the instance is a member of
 *
 * Query Parameters:
 * @param {string} access_token - Team authentication token
 * @param {string} instance_id - WhatsApp instance identifier
 *
 * Returns: Array of group objects with id, name, size, description, and participants
 *
 * Groups are cached in memory and updated when new messages are received from groups
 */
WAZIPER.app.get('/get_groups', WAZIPER.cors, async (req, res) => {
    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;

    await WAZIPER.instance(access_token, instance_id,  res, async (client) => {
        await WAZIPER.get_groups(instance_id, res);
    });
});

/**
 * GET /logout
 * Logs out a WhatsApp instance and removes all session data
 *
 * Query Parameters:
 * @param {string} access_token - Team authentication token (not currently validated)
 * @param {string} instance_id - WhatsApp instance identifier to logout
 *
 * This will:
 * - Close the WebSocket connection
 * - Delete session files from disk
 * - Remove instance from memory
 * - Update database status to disconnected
 *
 * The instance will need to be re-authenticated with QR code or pairing code
 */
WAZIPER.app.get('/logout', WAZIPER.cors, async (req, res) => {
    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;
    WAZIPER.logout(instance_id, res);
});

/**
 * POST /send_message
 * Sends a WhatsApp message through the bulk messaging queue system
 *
 * Query Parameters:
 * @param {string} access_token - Team authentication token
 * @param {string} instance_id - WhatsApp instance identifier
 * @param {string} type - Message type (1=text, 2=image, 3=video, 4=audio, 5=document)
 *
 * Body Parameters:
 * @param {string} chat_id - Recipient WhatsApp ID (phone@s.whatsapp.net or groupid@g.us)
 * @param {string} caption - Message text or media caption
 * @param {string} media_url - URL of media file (for non-text messages)
 * @param {string} filename - Filename for document messages
 * @param {number} template - Template type for buttons/lists (0=none, 1=buttons, 2=list)
 *
 * This endpoint queues messages for sending and handles retries on failure
 * Compatible with Baileys 6.7.21 message sending methods
 */
WAZIPER.app.post('/send_message', WAZIPER.cors, async (req, res) => {
    console.log(`ðŸ“¨ POST /send_message - access_token: ${req.query.access_token?.substring(0, 10)}..., instance_id: ${req.query.instance_id}`);

    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;

    await WAZIPER.instance(access_token, instance_id, res, async (client) => {
        WAZIPER.send_message(instance_id, access_token, req, res);
    });
});

/**
 * POST /direct_send_message
 * Sends a WhatsApp message directly without queue processing
 *
 * Query Parameters:
 * @param {string} access_token - Team authentication token
 * @param {string} instance_id - WhatsApp instance identifier
 * @param {string} type - Message type (1=text, 2=image, 3=video, 4=audio, 5=document)
 *
 * Body Parameters:
 * @param {string} chat_id - Recipient WhatsApp ID
 * @param {string} caption - Message text or media caption
 * @param {string} media_url - URL of media file
 * @param {string} filename - Filename for documents
 * @param {number} template - Template type
 *
 * This bypasses the queue system for immediate message delivery
 * Use for time-sensitive messages or single sends
 */
WAZIPER.app.post('/direct_send_message', WAZIPER.cors, async (req, res) => {
    console.log(`ðŸ“¨ POST /direct_send_message - access_token: ${req.query.access_token?.substring(0, 10)}..., instance_id: ${req.query.instance_id}`);

    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;

    await WAZIPER.instance(access_token, instance_id, res, async (client) => {
        WAZIPER.single_send_message(instance_id, access_token, req, res);
    });
});

/**
 * POST /send_template
 * Sends a WhatsApp Business API template message (for official API accounts only)
 *
 * Query Parameters:
 * @param {string} access_token - Team authentication token
 * @param {string} instance_id - WhatsApp Business API instance identifier
 *
 * Body Parameters:
 * @param {string} chat_id - Recipient phone number
 * @param {string} template_name - Approved template name from Meta Business Manager
 * @param {string} language_code - Template language code (e.g., 'en', 'pt_BR')
 * @param {array} components - Template components (header, body, buttons with parameters)
 *
 * This endpoint is only for WhatsApp Business API (Cloud API) accounts
 * Templates must be pre-approved by Meta before use
 * Not applicable for regular WhatsApp Web connections via Baileys
 */
WAZIPER.app.post('/send_template', WAZIPER.cors, async (req, res) => {
    console.log(`ðŸ“¨ POST /send_template - access_token: ${req.query.access_token?.substring(0, 10)}..., instance_id: ${req.query.instance_id}`);

    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;

    await WAZIPER.instance(access_token, instance_id,  res, async (client) => {
        WAZIPER.send_cloud_template(instance_id, access_token, req, res);
    });
});

/**
 * GET /reset
 * Administrative endpoint to restart the entire application server
 *
 * Query Parameters:
 * @param {string} api_key - Admin API key for authorization (stored in sp_options table)
 *
 * Security: Requires valid admin API key to prevent unauthorized restarts
 *
 * This will terminate the Node.js process, which should be automatically
 * restarted by a process manager (PM2, systemd, etc.)
 * Use with caution as it will disconnect all active WhatsApp sessions temporarily
 */
WAZIPER.app.get('/reset', WAZIPER.cors, async function (req, res) {
    var api_key = await Common.db_query(`select value from sp_options where name = 'admin_api_key'`, true);
    if (api_key) {
        if (req.query.api_key == api_key.value) {
            res.json({
                status: 'success',
                message: 'Server restart initiated'
            });

            // Log the restart for audit purposes
            console.log(`[SECURITY] Server restart requested at ${new Date().toISOString()}`);

            process.exit();
        } else {
            // âœ… SECURITY FIX: Don't expose the actual API key in error response
            // This prevents attackers from discovering the admin API key
            res.json({
                status: 'error',
                message: 'Authentication failed. Invalid API key.'
            });

            // Log failed attempt for security monitoring
            console.warn(`[SECURITY] Failed reset attempt from IP: ${req.ip} at ${new Date().toISOString()}`);
        }
    } else {
        res.json({
            status: 'error',
            message: 'Admin API key not configured'
        });
    }

});

/**
 * GET /clear_cache_ai
 * Clears the OpenAI conversation history cache for a specific instance
 *
 * Query Parameters:
 * @param {string} access_token - Team authentication token (not currently validated)
 * @param {string} instance_id - WhatsApp instance identifier
 *
 * This resets the AI chatbot's conversation memory, forcing it to start fresh
 * Useful when the AI context becomes too long or needs to be reset
 * Affects all chats for the specified instance
 */
WAZIPER.app.get('/clear_cache_ai', WAZIPER.cors, async function (req, res) {

    var access_token = req.query.access_token;
    var instance_id = req.query.instance_id;
    WAZIPER.resetAi(instance_id, res);
});

/**
 * POST /webhook/:accountId
 * Receives incoming webhook events from WhatsApp Business API (Cloud API)
 *
 * URL Parameters:
 * @param {string} accountId - WhatsApp Business API account identifier
 *
 * Body: WhatsApp webhook payload containing messages, status updates, etc.
 *
 * This endpoint processes incoming messages from official WhatsApp Business API accounts
 * and triggers chatbot/autoresponder logic. Not used for Baileys-based connections.
 */
WAZIPER.app.post('/webhook/:accountId', async function (req, res) {
    WAZIPER.webhook_handler(req.params.accountId, req, res);
})

/**
 * GET /webhook/:accountId
 * Webhook verification endpoint for WhatsApp Business API setup
 *
 * URL Parameters:
 * @param {string} accountId - WhatsApp Business API account identifier
 *
 * Query Parameters:
 * @param {string} hub.mode - Should be 'subscribe' for verification
 * @param {string} hub.verify_token - Verification token configured in Meta Business Manager
 * @param {string} hub.challenge - Challenge string to echo back for verification
 *
 * This endpoint is called by Meta when setting up webhook URLs
 * It verifies that the webhook URL is valid and owned by the account holder
 * Returns the challenge string if verification token matches
 */
WAZIPER.app.get('/webhook/:accountId', async function (req, res) {
    // Retrieve verification token from database
    let VERIFY_TOKEN = await Common.db_get('sp_options', [{ name: 'wa_verify_token' }]);
    VERIFY_TOKEN = VERIFY_TOKEN.value;
    const accountId = req.params.accountId;

    // Extract webhook verification parameters from Meta
    let mode = req.query['hub.mode'];
    let token = req.query['hub.verify_token'];
    let challenge = req.query['hub.challenge'];

    // Verify token matches and mode is subscribe
    if (mode === 'subscribe' && token === VERIFY_TOKEN) {
        res.send(challenge); // Echo back the challenge for Meta verification
    } else {
        res.sendStatus(403); // Reject if tokens don't match
    }
})

WAZIPER.app.all('/api/media/upload_base64', WAZIPER.cors, async (req, res) => {
    try {
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = `${params.access_token || ''}`.trim();
        const data_base64 = `${params.data_base64 || ''}`.trim();
        const mime_type = `${params.mime_type || 'application/octet-stream'}`.trim().toLowerCase();
        const folder = sanitizeUploadFolder(params.folder || 'android_uploads');
        let filename = sanitizeUploadFilename(params.filename || '');

        if (!access_token || !data_base64) {
            return res.json({
                status: 'error',
                message: 'access_token and data_base64 are required'
            });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({
                status: 'error',
                message: 'Authentication failed. Invalid access_token.'
            });
        }

        const commaAt = data_base64.indexOf(',');
        const pureBase64 = commaAt >= 0 ? data_base64.substring(commaAt + 1) : data_base64;
        const fileBuffer = Buffer.from(pureBase64, 'base64');
        if (!fileBuffer.length) {
            return res.json({ status: 'error', message: 'Uploaded file data is empty' });
        }

        const maxBytes = 100 * 1024 * 1024;
        if (fileBuffer.length > maxBytes) {
            return res.json({
                status: 'error',
                message: 'File too large. Max 100MB allowed for direct upload.'
            });
        }

        if (!filename.includes('.')) {
            filename = `${filename}${extensionFromMime(mime_type)}`;
        }

        const datedFolder = `${new Date().toISOString().slice(0, 10).replace(/-/g, '')}`;
        const finalName = `${Date.now()}_${filename}`;
        const relativeDir = path.join('files', folder, datedFolder);
        const absoluteDir = path.join(__dirname, relativeDir);
        fs.mkdirSync(absoluteDir, { recursive: true });
        const absolutePath = path.join(absoluteDir, finalName);
        fs.writeFileSync(absolutePath, fileBuffer);

        const publicPath = `/${path.join(relativeDir, finalName).replace(/\\/g, '/')}`;
        const url = normalizeMediaUrl(publicPath.replace('/files/', '/'));

        return res.json({
            status: 'success',
            message: 'Media uploaded successfully',
            data: {
                url,
                filename: finalName,
                size: fileBuffer.length,
                mime_type,
                path: publicPath,
            }
        });
    } catch (error) {
        console.error('Error in /api/media/upload_base64:', error);
        return res.json({
            status: 'error',
            message: error.message || 'Failed to upload media',
        });
    }
});

/**
 * POST/GET /api/send
 * Unified API endpoint for sending WhatsApp messages
 * Supports: text, media, button templates, and list templates
 *
 * Query Parameters (GET) or Body Parameters (POST):
 * @param {string} number - Phone number (without @s.whatsapp.net)
 * @param {string} type - Message type: 'text', 'media', 'button', 'list'
 * @param {string} message - Message text/caption
 * @param {string} media_url - Media URL (for type='media')
 * @param {string} filename - Filename (for type='media' documents)
 * @param {number} template_id - Template ID from database (for type='button' or 'list')
 * @param {string} instance_id - WhatsApp instance identifier
 * @param {string} access_token - Team authentication token
 *
 * Examples:
 * GET: /api/send?number=84933313xxx&type=text&message=Hello&instance_id=XXX&access_token=XXX
 * POST: /api/send with JSON body
 */
WAZIPER.app.all('/api/send', WAZIPER.cors, async (req, res) => {
    try {
        // Debug logging (disabled in production)
        // Set config.debug = true to enable detailed request logging

        // Support both GET (query params) and POST (body params)
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };

        const number = params.number;
        const type = params.type;
        const message = params.message || '';
        const media_url = params.media_url || '';
        const normalizedMediaUrl = normalizeMediaUrl(media_url);
        const filename = params.filename || '';
        const template_id = params.template_id || 0;
        const instance_id = params.instance_id;
        const access_token = params.access_token;

        // Validate required parameters
        if (!number || !type || !instance_id || !access_token) {
            // Enhanced error message for debugging
            const debugInfo = config.debug ? {
                received_params: {
                    number: number || 'MISSING',
                    type: type || 'MISSING',
                    instance_id: instance_id || 'MISSING',
                    access_token: access_token ? 'PROVIDED' : 'MISSING'
                },
                method: req.method,
                content_type: req.headers['content-type'],
                body_keys: Object.keys(req.body || {}),
                query_keys: Object.keys(req.query || {})
            } : undefined;

            return res.json({
                status: 'error',
                message: 'Missing required parameters: number, type, instance_id, access_token',
                debug: debugInfo
            });
        }

        // Authenticate team
        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({
                status: 'error',
                message: 'Authentication failed. Invalid access_token.'
            });
        }

        const normalizedNumber = `${number || ''}`.trim();
        const looksLikeGroup =
            normalizedNumber.includes('g.us') ||
            normalizedNumber.includes('@g.us');
        if (looksLikeGroup) {
            const lock = await findGroupScheduleLock({
                teamId: team.id,
                instanceId: instance_id,
                groupId: normalizedNumber,
            });
            if (lock) {
                return res.json({
                    status: 'error',
                    message: 'This group already has a scheduled server send. Manual send is locked until it runs.',
                    data: lock,
                });
            }
        }

        // Validate instance
        await WAZIPER.instance(access_token, instance_id, res, async (client) => {
            // Format phone number
            // âœ… UPDATED: Support both @s.whatsapp.net (old) and @lid (new) formats for individual chats
            let chat_id = number;
            if (!chat_id.includes('@')) {
                // Default to @s.whatsapp.net for backward compatibility
                // WhatsApp will automatically handle @lid format when needed
                chat_id = `${number}@s.whatsapp.net`;
            }

            // Prepare message item based on type
            let item = {
                team_id: team.id,
                caption: message,
                media: normalizedMediaUrl,
                filename: filename,
                template: 0,
                type: 1, // Default to text
                media_type: null // âœ… NEW: Explicit media type for URLs without extensions
            };

            // Map type string to numeric type and set template
            // IMPORTANT: Type mapping for auto_send function:
            // - Type 1: Text messages (default)
            // - Type 2: Button templates (with template ID) - AUTO-DETECTED from DB
            // - Type 3: List templates (with template ID) - AUTO-DETECTED from DB
            // - Type 4: Poll templates (with template ID) - AUTO-DETECTED from DB
            // - Type 5+: Media messages (image, video, audio, document)
            //
            // Template types are now DYNAMICALLY DETECTED by querying sp_whatsapp_template table
            // Database template.type -> Message item.type mapping:
            //   DB type 1 (List) -> item.type 3
            //   DB type 2 (Button) -> item.type 2
            //   DB type 3 (Poll) -> item.type 4
            switch (type.toLowerCase()) {
                case 'text':
                    item.type = 1;
                    item.template = 0;
                    break;

                case 'media':
                case 'image':
                    item.type = 1; // Media uses type 1 with media_url
                    item.template = 0;
                    item.media_type = 'image'; // âœ… FIX: Explicit media type
                    if (!media_url) {
                        return res.json({
                            status: 'error',
                            message: 'media_url is required for media type'
                        });
                    }
                    break;

                case 'video':
                    item.type = 1; // Video uses type 1 with media_url
                    item.template = 0;
                    item.media_type = 'video'; // âœ… FIX: Explicit media type
                    if (!media_url) {
                        return res.json({
                            status: 'error',
                            message: 'media_url is required for video type'
                        });
                    }
                    break;

                case 'audio':
                    item.type = 1; // Audio uses type 1 with media_url
                    item.template = 0;
                    item.media_type = 'audio'; // âœ… FIX: Explicit media type
                    if (!media_url) {
                        return res.json({
                            status: 'error',
                            message: 'media_url is required for audio type'
                        });
                    }
                    break;

                case 'document':
                    item.type = 1; // Document uses type 1 with media_url
                    item.template = 0;
                    item.media_type = 'document'; // âœ… FIX: Explicit media type
                    if (!media_url) {
                        return res.json({
                            status: 'error',
                            message: 'media_url is required for document type'
                        });
                    }
                    break;

                case 'button':
                case 'list':
                case 'poll':
                case 'template':
                    // âœ… DYNAMIC TYPE DETECTION: Query database to determine correct message type
                    // This replaces hardcoded type values with automatic detection based on template
                    if (!template_id || template_id == 0) {
                        return res.json({
                            status: 'error',
                            message: `template_id is required for ${type} type`
                        });
                    }

                    item.template = template_id;

                    // Query database to get template and its type
                    // Using 'ids' column (UUID format like "694e5133c38f8") instead of 'id' (auto-increment integer)
                    let template = await Common.db_get("sp_whatsapp_template", [
                        { ids: template_id },
                        { team_id: team.id }
                    ]);

                    if (!template && `${template_id}`.match(/^\d+$/)) {
                        template = await Common.db_get("sp_whatsapp_template", [
                            { id: Number(template_id) },
                            { team_id: team.id }
                        ]);
                    }

                    if (!template) {
                        return res.json({
                            status: 'error',
                            message: `Template with ID ${template_id} not found for your team`
                        });
                    }

                    let templateData = {};
                    try {
                        templateData = template.data ? JSON.parse(template.data) : {};
                    } catch (e) {
                        templateData = {};
                    }

                    if ((!templateData.templateButtons || !templateData.templateButtons.length) && Array.isArray(templateData.buttons)) {
                        templateData.templateButtons = templateData.buttons.map((button, index) => {
                            const displayText = button?.buttonText?.displayText || button?.displayText || '';
                            if (!displayText) {
                                return null;
                            }

                            return {
                                index,
                                quickReplyButton: {
                                    displayText: displayText,
                                    id: button?.buttonId || `btn_${index}`
                                }
                            };
                        }).filter(Boolean);

                        template.data = JSON.stringify(templateData);
                    }

                    // Map database template type to message type for auto_send function
                    // Database type -> Message type mapping:
                    // - DB type 1 (List template) -> Message type 3
                    // - DB type 2 (Button template) -> Message type 2
                    // - DB type 3 (Poll template) -> Message type 4
                    const templateTypeMap = {
                        1: 3,  // List template
                        2: 2,  // Button template
                        3: 4   // Poll template
                    };

                    item.type = templateTypeMap[template.type];

                    if (!item.type) {
                        return res.json({
                            status: 'error',
                            message: `Invalid template type ${template.type} for template ID ${template_id}`
                        });
                    }

                    // Validate type matches if user specified button/list/poll explicitly
                    const typeValidation = {
                        'button': 2,
                        'list': 1,
                        'poll': 3
                    };

                    if (type !== 'template' && typeValidation[type] && template.type !== typeValidation[type]) {
                        const expectedTypes = {
                            'button': 'button template (type 2)',
                            'list': 'list template (type 1)',
                            'poll': 'poll template (type 3)'
                        };
                        return res.json({
                            status: 'error',
                            message: `Template ID ${template_id} is not a ${expectedTypes[type]}. Found type ${template.type} instead.`
                        });
                    }
                    break;

                default:
                    return res.json({
                        status: 'error',
                        message: `Invalid type: ${type}. Supported types: text, media, image, video, audio, document, button, list, poll, template`
                    });
            }

            // Send message using auto_send function
            await WAZIPER.auto_send(instance_id, chat_id, chat_id, "api", item, false, false, false, function (result) {
                if (result && result.status !== 0) {
                    return res.json({
                        status: 'success',
                        message: 'Message sent successfully',
                        data: {
                            type: type,
                            number: number,
                            chat_id: chat_id,
                            template_id: template_id > 0 ? template_id : undefined,
                            result: result.message || result
                        }
                    });
                } else {
                    return res.json({
                        status: 'error',
                        message: result?.message || 'Failed to send message',
                        details: result
                    });
                }
            });
        }, false); // Don't reset instance

    } catch (error) {
        console.error('Error in /api/send:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

WAZIPER.app.all('/api/group_schedule_lock_status', WAZIPER.cors, async (req, res) => {
    try {
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = `${params.access_token || ''}`.trim();
        const instance_id = `${params.instance_id || ''}`.trim();
        const group_id = `${params.group_id || ''}`.trim();

        if (!access_token || !instance_id || !group_id) {
            return res.json({
                status: 'error',
                message: 'access_token, instance_id and group_id are required'
            });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({ status: 'error', message: 'Authentication failed. Invalid access_token.' });
        }

        const lock = await findGroupScheduleLock({
            teamId: team.id,
            instanceId: instance_id,
            groupId: group_id,
        });

        return res.json({
            status: 'success',
            data: {
                locked: !!lock,
                lock,
            },
        });
    } catch (error) {
        console.error('Error in /api/group_schedule_lock_status:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message,
        });
    }
});

/**
 * POST/GET /api/create_instance
 * Creates a new WhatsApp instance for the authenticated team
 *
 * Query Parameters (GET) or Body Parameters (POST):
 * @param {string} access_token - Team authentication token
 *
 * Returns: Instance ID and QR code for authentication
 */
WAZIPER.app.all('/api/create_instance', WAZIPER.cors, async (req, res) => {
    try {
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = params.access_token;

        if (!access_token) {
            return res.json({
                status: 'error',
                message: 'Missing required parameter: access_token'
            });
        }

        // Authenticate team
        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({
                status: 'error',
                message: 'Authentication failed. Invalid access_token.'
            });
        }

        const existingInstances = await listTeamWhatsappInstances(team.id);
        if (existingInstances.length >= MAX_WHATSAPP_INSTANCES_PER_TEAM) {
            return res.json({
                status: 'error',
                message: `You can link up to ${MAX_WHATSAPP_INSTANCES_PER_TEAM} WhatsApp numbers per user.`,
                data: {
                    max_instances: MAX_WHATSAPP_INSTANCES_PER_TEAM,
                    current_instances: existingInstances.length,
                }
            });
        }

        // Generate unique instance ID
        const instance_id = Common.makeid(13);

        // Create instance record in sp_accounts
        const instanceData = {
            team_id: team.id,
            token: instance_id,
            name: `Instance ${instance_id}`,
            status: 0,
            login_type: 0,
            created: Math.floor(Date.now() / 1000)
        };

        await Common.db_insert('sp_accounts', instanceData);

        // âœ… FIX: Also create session record in sp_whatsapp_sessions
        // This is required for /get_qrcode to work properly
        const sessionData = {
            instance_id: instance_id,
            team_id: team.id,
            status: 1,
            data: JSON.stringify({})
        };

        await Common.db_insert('sp_whatsapp_sessions', sessionData);

        return res.json({
            status: 'success',
            message: 'Instance created successfully',
            data: {
                instance_id: instance_id,
                qr_code_url: `${config.frontend}/get_qrcode?access_token=${access_token}&instance_id=${instance_id}`,
                next_step: 'Scan the QR code with your WhatsApp mobile app to connect'
            }
        });

    } catch (error) {
        console.error('Error in /api/create_instance:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

WAZIPER.app.all('/api/sync_contacts', WAZIPER.cors, async (req, res) => {
    try {
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = params.access_token;
        const group_name = (params.group_name || '').trim();
        const contacts = Array.isArray(params.contacts) ? params.contacts : [];

        if (!access_token) {
            return res.json({ status: 'error', message: 'Missing required parameter: access_token' });
        }

        if (!group_name) {
            return res.json({ status: 'error', message: 'group_name is required' });
        }

        if (!contacts.length) {
            return res.json({ status: 'error', message: 'At least one contact is required' });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({ status: 'error', message: 'Authentication failed. Invalid access_token.' });
        }

        const now = Common.time();
        const contactGroup = {
            ids: Common.makeid(13),
            team_id: team.id,
            name: group_name,
            status: 1,
            changed: now,
            created: now
        };

        const insertGroup = await Common.db_insert('sp_whatsapp_contacts', contactGroup);
        const contactId = insertGroup?.insertId;

        if (!contactId) {
            return res.json({ status: 'error', message: 'Unable to create contact group' });
        }

        let savedCount = 0;

        for (const contact of contacts) {
            const rawPhone = `${contact?.number || ''}`.trim();
            const rawName = `${contact?.name || ''}`.trim();
            const phone = rawPhone.replace(/\D/g, '');

            if (!phone) {
                continue;
            }

            await Common.db_insert('sp_whatsapp_phone_numbers', {
                ids: Common.makeid(13),
                team_id: team.id,
                pid: contactId,
                phone: phone,
                params: JSON.stringify({
                    name: rawName,
                    source: params.source || 'Android App'
                }),
                is_valid: 1
            });

            savedCount++;
        }

        return res.json({
            status: 'success',
            message: 'Contacts synced successfully',
            data: {
                server_group_id: contactId,
                saved_contacts: savedCount
            }
        });
    } catch (error) {
        console.error('Error in /api/sync_contacts:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

WAZIPER.app.all('/api/sync_templates', WAZIPER.cors, async (req, res) => {
    try {
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = params.access_token;
        const name = `${params.name || ''}`.trim();
        const title = `${params.title || ''}`.trim();
        const caption = `${params.caption || ''}`.trim();
        const footer = `${params.footer || ''}`.trim();
        const imageUrl = `${params.image_url || ''}`.trim();
        const buttons = Array.isArray(params.buttons) ? params.buttons : [];

        if (!access_token) {
            return res.json({ status: 'error', message: 'Missing required parameter: access_token' });
        }

        if (!name || !caption) {
            return res.json({ status: 'error', message: 'name and caption are required' });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({ status: 'error', message: 'Authentication failed. Invalid access_token.' });
        }

        if (!buttons.length) {
            return res.json({ status: 'error', message: 'Add at least one button item' });
        }

        if (buttons.length > 3) {
            return res.json({ status: 'error', message: 'Only up to 3 button items allowed' });
        }

        const templateButtons = [];

        for (let index = 0; index < buttons.length; index++) {
            const button = buttons[index] || {};
            const type = `${button?.type || 'text'}`.trim();
            const displayText = `${button?.displayText || ''}`.trim();
            const url = `${button?.url || ''}`.trim();
            const phoneNumber = `${button?.phoneNumber || ''}`.trim();
            const copyText = `${button?.copyText || ''}`.trim();
            const buttonIndex = index + 1;

            if (!displayText) {
                return res.json({
                    status: 'error',
                    message: `Button ${buttonIndex}: Please enter display text`
                });
            }

            switch (type) {
                case 'text':
                    templateButtons.push({
                        index: buttonIndex,
                        quickReplyButton: {
                            displayText,
                            id: Common.makeid(13)
                        }
                    });
                    break;

                case 'link':
                    try {
                        const parsedUrl = new URL(url);
                        if (!['http:', 'https:'].includes(parsedUrl.protocol)) {
                            throw new Error('Invalid protocol');
                        }
                    } catch (error) {
                        return res.json({
                            status: 'error',
                            message: `Button ${buttonIndex}: Invalid URL`
                        });
                    }

                    templateButtons.push({
                        index: buttonIndex,
                        urlButton: {
                            displayText,
                            url
                        }
                    });
                    break;

                case 'call':
                    if (!phoneNumber) {
                        return res.json({
                            status: 'error',
                            message: `Button ${buttonIndex}: Phone number is required`
                        });
                    }

                    templateButtons.push({
                        index: buttonIndex,
                        callButton: {
                            displayText,
                            phoneNumber
                        }
                    });
                    break;

                case 'copy':
                    if (!copyText) {
                        return res.json({
                            status: 'error',
                            message: `Button ${buttonIndex}: Please enter copy code`
                        });
                    }

                    templateButtons.push({
                        index: buttonIndex,
                        urlButton: {
                            displayText,
                            url: `https://www.whatsapp.com/otp/code/?otp_type=COPY_CODE&code=${encodeURIComponent(copyText)}`,
                            disabled: false
                        }
                    });
                    break;

                default:
                    return res.json({
                        status: 'error',
                        message: 'The type button item incorrect'
                    });
            }
        }

        const now = Common.time();
        const templateData = {
            templateButtons
        };

        if (footer) {
            templateData.footer = footer;
        }

        if (title) {
            templateData.title = title;
        }

        if (imageUrl) {
            templateData.media_url = imageUrl;
            templateData.caption = caption;
            templateData.has_media = true;
        } else {
            templateData.text = caption;
        }

        const templateIds = Common.makeid(13);
        const insertTemplate = await Common.db_insert('sp_whatsapp_template', {
            ids: templateIds,
            team_id: team.id,
            type: 2,
            name,
            data: JSON.stringify(templateData),
            changed: now,
            created: now
        });

        return res.json({
            status: 'success',
            message: 'Template saved successfully',
            data: {
                server_template_id: templateIds,
                template_row_id: insertTemplate?.insertId || null
            }
        });
    } catch (error) {
        console.error('Error in /api/sync_templates:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

WAZIPER.app.all('/api/sync_list_templates', WAZIPER.cors, async (req, res) => {
    try {
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = params.access_token;
        const name = `${params.name || ''}`.trim();
        const title = `${params.title || ''}`.trim();
        const caption = `${params.caption || ''}`.trim();
        const footer = `${params.footer || ''}`.trim();
        const buttonText = `${params.button_text || ''}`.trim();
        const sections = Array.isArray(params.sections) ? params.sections : [];

        if (!access_token) {
            return res.json({ status: 'error', message: 'Missing required parameter: access_token' });
        }

        if (!name || !caption || !buttonText) {
            return res.json({ status: 'error', message: 'name, caption and button_text are required' });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({ status: 'error', message: 'Authentication failed. Invalid access_token.' });
        }

        if (!sections.length) {
            return res.json({ status: 'error', message: 'Add at least one section' });
        }

        const cleanSections = [];
        for (let index = 0; index < sections.length; index++) {
            const section = sections[index] || {};
            const sectionTitle = `${section.title || ''}`.trim();
            const rows = Array.isArray(section.rows) ? section.rows : [];

            if (!sectionTitle) {
                return res.json({ status: 'error', message: `Section ${index + 1}: title is required` });
            }

            if (!rows.length) {
                return res.json({ status: 'error', message: `Section ${index + 1}: add at least one row` });
            }

            cleanSections.push({
                title: sectionTitle,
                rows: rows.map((row, rowIndex) => {
                    const rowId = `${row?.id || Common.makeid(13)}`.trim();
                    const rowTitle = `${row?.title || ''}`.trim();
                    const rowDescription = `${row?.description || ''}`.trim();

                    if (!rowTitle) {
                        throw new Error(`Section ${index + 1}, row ${rowIndex + 1}: title is required`);
                    }

                    return {
                        title: rowTitle,
                        rowId,
                        description: rowDescription,
                    };
                }),
            });
        }

        const now = Common.time();
        const templateData = {
            text: caption,
            buttonText,
            sections: cleanSections,
        };

        if (footer) {
            templateData.footer = footer;
        }

        if (title) {
            templateData.title = title;
        }

        const templateIds = Common.makeid(13);
        const insertTemplate = await Common.db_insert('sp_whatsapp_template', {
            ids: templateIds,
            team_id: team.id,
            type: 1,
            name,
            data: JSON.stringify(templateData),
            changed: now,
            created: now
        });

        return res.json({
            status: 'success',
            message: 'List template saved successfully',
            data: {
                server_template_id: templateIds,
                template_row_id: insertTemplate?.insertId || null
            }
        });
    } catch (error) {
        console.error('Error in /api/sync_list_templates:', error);
        return res.json({
            status: 'error',
            message: error.message || 'Internal server error',
            error: error.message
        });
    }
});

WAZIPER.app.all('/api/campaigns/launch', WAZIPER.cors, async (req, res) => {
    try {
        await ensureAndroidCampaignTables();

        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = `${params.access_token || ''}`.trim();
        const instance_id = `${params.instance_id || ''}`.trim();
        const campaign_name = `${params.campaign_name || ''}`.trim();
        const target_name = `${params.target_name || ''}`.trim();
        const message_mode = `${params.message_mode || ''}`.trim();
        const message_label = `${params.message_label || ''}`.trim();
        const user_email = `${params.user_email || ''}`.trim();
        const requestedDelaySeconds = Math.max(Number(params.delay_seconds || 0), 0);
        const schedule_at = Math.max(Number(params.schedule_at || 0), 0);
        const recipients = Array.isArray(params.recipients) ? params.recipients : [];
        const payload = params.payload && typeof params.payload === 'object' ? params.payload : {};

        if (!access_token || !instance_id || !campaign_name) {
            return res.json({
                status: 'error',
                message: 'access_token, instance_id and campaign_name are required'
            });
        }

        if (!recipients.length) {
            return res.json({
                status: 'error',
                message: 'At least one campaign recipient is required'
            });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({ status: 'error', message: 'Authentication failed. Invalid access_token.' });
        }

        const session = await Common.db_get("sp_whatsapp_sessions", [
            { instance_id: instance_id },
            { team_id: team.id }
        ]);

        if (!session) {
            return res.json({
                status: 'error',
                message: 'WhatsApp instance not found for this workspace'
            });
        }

        const normalizedRecipients = recipients
            .map((recipient, index) => {
                let number = `${recipient?.number || ''}`.trim();
                let chatId = `${recipient?.chat_id || ''}`.trim();
                const isGroup =
                    recipient?.is_group === true ||
                    chatId.includes('g.us') ||
                    number.includes('g.us');

                if (isGroup) {
                    chatId = chatId || number;
                    if (!chatId) {
                        return null;
                    }
                    if (chatId.includes('g.us') && !chatId.includes('@')) {
                        chatId = chatId.replace('g.us', '@g.us');
                    }
                    number = chatId;
                } else {
                    number = number.replace(/\D/g, '');
                    if (!number) {
                        return null;
                    }
                }

                return {
                    index: Number(recipient?.index || index + 1),
                    name: `${recipient?.name || ''}`.trim(),
                    number: number,
                    chat_id: chatId,
                    is_group: isGroup,
                    status: 'queued',
                    error: '',
                };
            })
            .filter(Boolean);

        if (!normalizedRecipients.length) {
            return res.json({
                status: 'error',
                message: 'No valid recipients found in campaign payload'
            });
        }

        const hasOnlyGroupRecipients = normalizedRecipients.every((recipient) => isGroupCampaignRecipient(recipient));
        const delay_seconds = hasOnlyGroupRecipients
            ? Math.min(requestedDelaySeconds, CAMPAIGN_GROUP_FAST_DELAY_SEC)
            : requestedDelaySeconds;

        const cleanPayload = {
            type: `${payload.type || ''}`.trim(),
        };

        switch (cleanPayload.type) {
            case 'template': {
                const templateId = `${payload.template_id || ''}`.trim();
                if (!templateId) {
                    return res.json({
                        status: 'error',
                        message: 'template_id is required for template campaigns'
                    });
                }

                let template = await Common.db_get("sp_whatsapp_template", [
                    { ids: templateId },
                    { team_id: team.id }
                ]);

                if (!template && templateId.match(/^\d+$/)) {
                    template = await Common.db_get("sp_whatsapp_template", [
                        { id: Number(templateId) },
                        { team_id: team.id }
                    ]);
                }

                if (!template) {
                    return res.json({
                        status: 'error',
                        message: 'Template not found on server'
                    });
                }

                const templateTypeMap = {
                    1: 3,
                    2: 2,
                    3: 4,
                };

                cleanPayload.template_id = templateId;
                cleanPayload.template_item_type = templateTypeMap[Number(template.type || 0)] || 0;

                if (!cleanPayload.template_item_type) {
                    return res.json({
                        status: 'error',
                        message: 'Template type is not supported for campaign sending'
                    });
                }
                break;
            }

            case 'media':
                cleanPayload.media_type = normalizeCampaignMediaType(payload.media_type);
                cleanPayload.mime_type = `${payload.mime_type || ''}`.trim().toLowerCase();
                cleanPayload.media_url = normalizeMediaUrl(payload.media_url);
                cleanPayload.caption = `${payload.caption || ''}`;
                cleanPayload.filename = `${payload.filename || ''}`.trim();
                if (!cleanPayload.media_url) {
                    return res.json({
                        status: 'error',
                        message: 'media_url is required for media campaigns'
                    });
                }
                break;

            case 'forward': {
                const source = payload.source_message;
                if (!source || typeof source !== 'object' || Array.isArray(source)) {
                    return res.json({
                        status: 'error',
                        message: 'source_message is required for forward campaigns'
                    });
                }
                const sourceMessage = source.message && typeof source.message === 'object'
                    ? source.message
                    : null;
                if (!sourceMessage) {
                    return res.json({
                        status: 'error',
                        message: 'source_message.message is required for forward campaigns'
                    });
                }
                cleanPayload.source_message = {
                    key: source.key && typeof source.key === 'object' ? source.key : {},
                    message: sourceMessage,
                };
                break;
            }

            case 'text':
            default:
                cleanPayload.type = 'text';
                cleanPayload.message = `${payload.message || ''}`;
                if (!cleanPayload.message.trim()) {
                    return res.json({
                        status: 'error',
                        message: 'message is required for text campaigns'
                    });
                }
                break;
        }

        const now = Common.time();
        const scheduledAt = schedule_at > now ? schedule_at : 0;
        const queueStartAt = scheduledAt > 0 ? scheduledAt : now;
        const historyIds = Common.makeid(24);
        const queueIds = Common.makeid(24);

        await Common.db_insert('sp_android_campaign_status', {
            ids: historyIds,
            team_id: team.id,
            user_email,
            campaign_name,
            target_name,
            target_count: normalizedRecipients.length,
            sent_count: 0,
            failed_count: 0,
            message_mode,
            message_label,
            delay_seconds,
            instance_id,
            status: 'queued',
            meta: JSON.stringify({
                payload: cleanPayload,
                queued_via: 'android_api',
                schedule_at: scheduledAt,
            }),
            items: JSON.stringify(normalizedRecipients),
            changed: now,
            created: now,
        });

        await Common.db_insert('sp_android_campaign_queue', {
            ids: queueIds,
            history_ids: historyIds,
            team_id: team.id,
            user_email,
            campaign_name,
            target_name,
            target_count: normalizedRecipients.length,
            sent_count: 0,
            failed_count: 0,
            message_mode,
            message_label,
            delay_seconds,
            instance_id,
            current_index: 0,
            next_run_at: queueStartAt,
            status: 'queued',
            payload: JSON.stringify(cleanPayload),
            recipients: JSON.stringify(normalizedRecipients),
            changed: now,
            created: now,
        });

        processQueuedCampaigns().catch(() => {});

        return res.json({
            status: 'success',
            message: 'Campaign queued successfully',
            data: {
                campaign_id: historyIds,
                queue_id: queueIds,
                total: normalizedRecipients.length,
                status: 'queued',
                scheduled: scheduledAt > 0,
                schedule_at: scheduledAt,
            }
        });
    } catch (error) {
        console.error('Error in /api/campaigns/launch:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

WAZIPER.app.all('/api/welcome_messages/save', WAZIPER.cors, async (req, res) => {
    try {
        await ensureWelcomeMessageTables();

        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = params.access_token;
        const instance_id = `${params.instance_id || ''}`.trim();
        const flow_id = `${params.flow_id || ''}`.trim();
        const name = `${params.name || ''}`.trim();
        const status = Number(params.status) === 0 ? 0 : 1;
        const steps = Array.isArray(params.steps) ? params.steps : [];

        if (!access_token || !instance_id || !name) {
            return res.json({
                status: 'error',
                message: 'access_token, instance_id and name are required'
            });
        }

        if (!steps.length) {
            return res.json({
                status: 'error',
                message: 'At least one welcome message step is required'
            });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({ status: 'error', message: 'Authentication failed. Invalid access_token.' });
        }

        const now = Common.time();
        const ids = flow_id || Common.makeid(13);
        const existing = await Common.db_get('sp_whatsapp_welcome_message', [
            { ids: ids },
            { team_id: team.id },
            { instance_id: instance_id }
        ]);

        const payload = {
            ids,
            team_id: team.id,
            instance_id,
            name,
            status,
            steps: JSON.stringify(steps),
            changed: now,
        };

        if (existing) {
            await Common.db_update('sp_whatsapp_welcome_message', [payload, { id: existing.id }]);
        } else {
            payload.created = now;
            await Common.db_insert('sp_whatsapp_welcome_message', payload);
        }

        return res.json({
            status: 'success',
            message: 'Welcome message flow saved successfully',
            data: {
                flow_id: ids
            }
        });
    } catch (error) {
        console.error('Error in /api/welcome_messages/save:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

WAZIPER.app.all('/api/keyword_replies/save', WAZIPER.cors, async (req, res) => {
    try {
        await ensureKeywordReplyTables();

        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = params.access_token;
        const instance_id = `${params.instance_id || ''}`.trim();
        const flow_id = `${params.flow_id || ''}`.trim();
        const name = `${params.name || ''}`.trim();
        const status = Number(params.status) === 0 ? 0 : 1;
        const keywords = Array.isArray(params.keywords) ? params.keywords : [];
        const steps = Array.isArray(params.steps) ? params.steps : [];

        if (!access_token || !instance_id || !name) {
            return res.json({
                status: 'error',
                message: 'access_token, instance_id and name are required'
            });
        }

        if (!keywords.length || !steps.length) {
            return res.json({
                status: 'error',
                message: 'keywords and steps are required'
            });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({ status: 'error', message: 'Authentication failed. Invalid access_token.' });
        }

        const now = Common.time();
        const ids = flow_id || Common.makeid(13);
        const existing = await Common.db_get('sp_whatsapp_keyword_reply', [
            { ids: ids },
            { team_id: team.id },
            { instance_id: instance_id }
        ]);

        const payload = {
            ids,
            team_id: team.id,
            instance_id,
            name,
            status,
            keywords: JSON.stringify(keywords),
            steps: JSON.stringify(steps),
            changed: now,
        };

        if (existing) {
            await Common.db_update('sp_whatsapp_keyword_reply', [payload, { id: existing.id }]);
        } else {
            payload.created = now;
            await Common.db_insert('sp_whatsapp_keyword_reply', payload);
        }

        return res.json({
            status: 'success',
            message: 'Keyword reply saved successfully',
            data: {
                flow_id: ids
            }
        });
    } catch (error) {
        console.error('Error in /api/keyword_replies/save:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

WAZIPER.app.all('/api/menu_replies/save', WAZIPER.cors, async (req, res) => {
    try {
        await ensureMenuReplyTables();

        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = params.access_token;
        const instance_id = `${params.instance_id || ''}`.trim();
        const flow_id = `${params.flow_id || ''}`.trim();
        const name = `${params.name || ''}`.trim();
        const status = Number(params.status) === 0 ? 0 : 1;
        const keywords = Array.isArray(params.keywords) ? params.keywords : [];
        const root_node_id = `${params.root_node_id || ''}`.trim();
        const nodes = Array.isArray(params.nodes) ? params.nodes : [];

        if (!access_token || !instance_id || !name) {
            return res.json({
                status: 'error',
                message: 'access_token, instance_id and name are required'
            });
        }

        if (!keywords.length || !root_node_id || !nodes.length) {
            return res.json({
                status: 'error',
                message: 'keywords, root_node_id and nodes are required'
            });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({ status: 'error', message: 'Authentication failed. Invalid access_token.' });
        }

        const now = Common.time();
        const ids = flow_id || Common.makeid(13);
        const existing = await Common.db_get('sp_whatsapp_menu_reply', [
            { ids: ids },
            { team_id: team.id },
            { instance_id: instance_id }
        ]);

        const payload = {
            ids,
            team_id: team.id,
            instance_id,
            name,
            status,
            keywords: JSON.stringify(keywords),
            root_node_id,
            nodes: JSON.stringify(nodes),
            changed: now,
        };

        if (existing) {
            await Common.db_update('sp_whatsapp_menu_reply', [payload, { id: existing.id }]);
        } else {
            payload.created = now;
            await Common.db_insert('sp_whatsapp_menu_reply', payload);
        }

        return res.json({
            status: 'success',
            message: 'Menu reply saved successfully',
            data: {
                flow_id: ids
            }
        });
    } catch (error) {
        console.error('Error in /api/menu_replies/save:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

WAZIPER.app.all('/api/active_instance', WAZIPER.cors, async (req, res) => {
    try {
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = params.access_token;

        if (!access_token) {
            return res.json({ status: 'error', message: 'Missing required parameter: access_token' });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({ status: 'error', message: 'Authentication failed. Invalid access_token.' });
        }

        const instances = await listTeamWhatsappInstances(team.id);
        if (!instances.length) {
            return res.json({ status: 'error', message: 'No WhatsApp instance found' });
        }

        for (const instance of instances) {
            if (instance.connected) {
                return res.json({
                    status: 'success',
                    data: {
                        instance_id: instance.instance_id,
                        user: instance.linkedNumber || null,
                        wsState: instance.wsState,
                        wid: instance.linkedNumber || null,
                        name: instance.linkedName || null
                    }
                });
            }
        }

        return res.json({ status: 'error', message: 'No active connected WhatsApp instance found' });
    } catch (error) {
        console.error('Error in /api/active_instance:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

WAZIPER.app.all('/api/instances', WAZIPER.cors, async (req, res) => {
    try {
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = params.access_token;

        if (!access_token) {
            return res.json({ status: 'error', message: 'Missing required parameter: access_token' });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({ status: 'error', message: 'Authentication failed. Invalid access_token.' });
        }

        const instances = await listTeamWhatsappInstances(team.id);

        return res.json({
            status: 'success',
            data: {
                max_instances: MAX_WHATSAPP_INSTANCES_PER_TEAM,
                current_instances: instances.length,
                instances: instances.map((instance) => ({
                    instance_id: instance.instance_id,
                    linkedNumber: instance.linkedNumber || null,
                    linkedName: instance.linkedName || null,
                    connected: instance.connected === true,
                    healthy: instance.healthy === true,
                    wsState: instance.wsState || 'NOT_LOADED',
                    account_status: instance.account_status || 0,
                    login_type: instance.login_type || 0,
                })),
            }
        });
    } catch (error) {
        console.error('Error in /api/instances:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

/**
 * POST/GET /api/set_webhook
 * Configure webhook URL for an instance to receive event notifications
 *
 * Query Parameters (GET) or Body Parameters (POST):
 * @param {string} access_token - Team authentication token (required)
 * @param {string} instance_id - WhatsApp instance identifier (required)
 * @param {string} webhook_url - URL to receive webhook events (required)
 * @param {boolean} enable - Enable (true/1) or disable (false/0) webhook (optional, default: true)
 * @param {string} allowed_events - Comma-separated list of events to trigger (optional, default: all events)
 *
 * Available Events:
 * - messages.upsert (new messages)
 * - messages.update (message status updates)
 * - call (incoming/outgoing calls)
 * - contacts.upsert (new contacts)
 * - received_message (processed messages)
 * - new subscriber (new chatbot subscriber)
 * - capturer (chatbot data capture)
 *
 * Returns: Success/error status with webhook configuration details
 *
 * Example:
 * POST /api/set_webhook?access_token=xxx&instance_id=yyy&webhook_url=https://example.com/webhook&enable=true
 */
WAZIPER.app.all('/api/set_webhook', WAZIPER.cors, async (req, res) => {
    try {
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = params.access_token;
        const instance_id = params.instance_id;
        const webhook_url = params.webhook_url;
        const enable = params.enable === 'true' || params.enable === '1' || params.enable === true ? 1 : 0;
        const allowed_events = params.allowed_events || '';

        // Validate required parameters
        if (!access_token) {
            return res.json({
                status: 'error',
                message: 'Missing required parameter: access_token'
            });
        }

        if (!instance_id) {
            return res.json({
                status: 'error',
                message: 'Missing required parameter: instance_id'
            });
        }

        if (!webhook_url) {
            return res.json({
                status: 'error',
                message: 'Missing required parameter: webhook_url'
            });
        }

        // Validate webhook URL format
        try {
            new URL(webhook_url);
        } catch (e) {
            return res.json({
                status: 'error',
                message: 'Invalid webhook_url format. Must be a valid URL (e.g., https://example.com/webhook)'
            });
        }

        // Authenticate team
        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({
                status: 'error',
                message: 'Authentication failed. Invalid access_token.'
            });
        }

        // Verify instance exists and belongs to team
        const instance = await Common.db_get("sp_accounts", [{ token: instance_id }, { team_id: team.id }]);
        const sessionInstance = instance
            ? null
            : await Common.db_get("sp_whatsapp_sessions", [{ instance_id: instance_id }, { team_id: team.id }]);
        if (!instance && !sessionInstance) {
            return res.json({
                status: 'error',
                message: 'Instance not found or does not belong to your team.'
            });
        }

        // Check if webhook configuration already exists
        const existingWebhook = await Common.db_get("sp_whatsapp_webhook", [{ instance_id: instance_id }]);

        if (existingWebhook) {
            // Update existing webhook configuration
            await Common.db_update("sp_whatsapp_webhook", [
                {
                    webhook_url: webhook_url,
                    status: enable,
                    allowed_events: allowed_events,
                    team_id: team.id
                },
                { instance_id: instance_id }
            ]);

            return res.json({
                status: 'success',
                message: 'Webhook configuration updated successfully',
                data: {
                    instance_id: instance_id,
                    webhook_url: webhook_url,
                    enabled: enable === 1,
                    allowed_events: allowed_events || 'all events',
                    action: 'updated'
                }
            });
        } else {
            // Create new webhook configuration
            const webhookData = {
                ids: Common.makeid(13),
                team_id: team.id,
                instance_id: instance_id,
                webhook_url: webhook_url,
                allowed_events: allowed_events,
                status: enable
            };

            await Common.db_insert('sp_whatsapp_webhook', webhookData);

            return res.json({
                status: 'success',
                message: 'Webhook configuration created successfully',
                data: {
                    instance_id: instance_id,
                    webhook_url: webhook_url,
                    enabled: enable === 1,
                    allowed_events: allowed_events || 'all events',
                    action: 'created'
                }
            });
        }

    } catch (error) {
        console.error('Error in /api/set_webhook:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

/**
 * POST/GET /api/reboot
 * Logout from WhatsApp Web and force a fresh QR code scan (session reboot)
 *
 * Query Parameters (GET) or Body Parameters (POST):
 * @param {string} access_token - Team authentication token (required)
 * @param {string} instance_id - WhatsApp instance identifier (required)
 *
 * This endpoint:
 * - Logs out from WhatsApp Web (removes device from "Linked Devices")
 * - Deletes all session files and credentials
 * - Keeps the same instance_id
 * - Forces a fresh QR code scan for re-authentication
 *
 * Use this when:
 * - Session is stuck or not responding
 * - Need to re-authenticate the same instance
 * - Want to clear session data but keep instance configuration
 *
 * Returns: Success status with message to scan new QR code
 *
 * Example:
 * POST /api/reboot?access_token=xxx&instance_id=yyy
 */
WAZIPER.app.all('/api/reboot', WAZIPER.cors, async (req, res) => {
    try {
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = params.access_token;
        const instance_id = params.instance_id;

        // Validate required parameters
        if (!access_token) {
            return res.json({
                status: 'error',
                message: 'Missing required parameter: access_token'
            });
        }

        if (!instance_id) {
            return res.json({
                status: 'error',
                message: 'Missing required parameter: instance_id'
            });
        }

        // Authenticate team
        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({
                status: 'error',
                message: 'Authentication failed. Invalid access_token.'
            });
        }

        // Verify instance exists and belongs to team
        const instance = await Common.db_get("sp_accounts", [{ token: instance_id }, { team_id: team.id }]);
        if (!instance) {
            return res.json({
                status: 'error',
                message: 'Instance not found or does not belong to your team.'
            });
        }

        // Perform logout (this will delete session files and clear memory)
        await WAZIPER.logout(instance_id);

        return res.json({
            status: 'success',
            message: 'Instance rebooted successfully. Please scan QR code to re-authenticate.',
            data: {
                instance_id: instance_id,
                action: 'rebooted',
                next_step: 'Use /get_qrcode to generate new QR code for authentication'
            }
        });

    } catch (error) {
        console.error('Error in /api/reboot:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

/**
 * POST/GET /api/reset_instance
 * Complete instance reset - logout, generate new instance ID, and delete all data
 *
 * Query Parameters (GET) or Body Parameters (POST):
 * @param {string} access_token - Team authentication token (required)
 * @param {string} instance_id - WhatsApp instance identifier to reset (required)
 *
 * This endpoint:
 * - Logs out from WhatsApp Web
 * - Generates a NEW instance_id (different from current one)
 * - Deletes all old session files and credentials
 * - Updates database with new instance_id
 * - Migrates chatbot, autoresponder, and webhook configurations to new instance
 *
 * âš ï¸ WARNING: This is a destructive operation!
 * - Old instance_id will no longer work
 * - All API calls must use the new instance_id
 * - Session must be re-authenticated with QR code
 *
 * Use this when:
 * - Instance is permanently corrupted
 * - Need to completely reset and start fresh
 * - Security: Invalidate old instance_id
 *
 * Returns: New instance_id and instructions for re-authentication
 *
 * Example:
 * POST /api/reset_instance?access_token=xxx&instance_id=yyy
 */
WAZIPER.app.all('/api/reset_instance', WAZIPER.cors, async (req, res) => {
    try {
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = params.access_token;
        const old_instance_id = params.instance_id;

        // Validate required parameters
        if (!access_token) {
            return res.json({
                status: 'error',
                message: 'Missing required parameter: access_token'
            });
        }

        if (!old_instance_id) {
            return res.json({
                status: 'error',
                message: 'Missing required parameter: instance_id'
            });
        }

        // Authenticate team
        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({
                status: 'error',
                message: 'Authentication failed. Invalid access_token.'
            });
        }

        // Verify instance exists and belongs to team
        const instance = await Common.db_get("sp_accounts", [{ token: old_instance_id }, { team_id: team.id }]);
        if (!instance) {
            return res.json({
                status: 'error',
                message: 'Instance not found or does not belong to your team.'
            });
        }

        // Generate new instance ID
        const new_instance_id = Common.makeid(13);

        // Logout old instance (deletes session files and clears memory)
        await WAZIPER.logout(old_instance_id);

        // Update instance record with new instance_id
        await Common.db_update("sp_accounts", [
            { token: new_instance_id, status: 0 },
            { token: old_instance_id }
        ]);

        // Migrate related configurations to new instance_id
        await Common.db_update("sp_whatsapp_sessions", [
            { instance_id: new_instance_id },
            { instance_id: old_instance_id }
        ]);

        await Common.db_update("sp_whatsapp_autoresponder", [
            { instance_id: new_instance_id },
            { instance_id: old_instance_id }
        ]);

        await Common.db_update("sp_whatsapp_chatbot", [
            { instance_id: new_instance_id },
            { instance_id: old_instance_id }
        ]);

        await Common.db_update("sp_whatsapp_webhook", [
            { instance_id: new_instance_id },
            { instance_id: old_instance_id }
        ]);

        return res.json({
            status: 'success',
            message: 'Instance reset successfully with new instance ID',
            data: {
                old_instance_id: old_instance_id,
                new_instance_id: new_instance_id,
                action: 'reset',
                next_step: 'Use /get_qrcode with new instance_id to generate QR code',
                warning: 'Old instance_id is no longer valid. Use new_instance_id for all future API calls.'
            }
        });

    } catch (error) {
        console.error('Error in /api/reset_instance:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

/**
 * POST/GET /api/cleanup_instances
 * Deletes every WhatsApp instance owned by the authenticated team.
 *
 * Query Parameters (GET) or Body Parameters (POST):
 * @param {string} access_token - Team authentication token (required)
 */
WAZIPER.app.all('/api/cleanup_instances', WAZIPER.cors, async (req, res) => {
    try {
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = params.access_token;

        if (!access_token) {
            return res.json({
                status: 'error',
                message: 'Missing required parameter: access_token'
            });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({
                status: 'error',
                message: 'Authentication failed. Invalid access_token.'
            });
        }

        const accounts = await Common.db_fetch("sp_accounts", [{ team_id: team.id }]);
        const sessions = await Common.db_fetch("sp_whatsapp_sessions", [{ team_id: team.id }]);
        const instanceIds = new Set();

        if (Array.isArray(accounts)) {
            for (const account of accounts) {
                if (account && account.token) {
                    instanceIds.add(account.token);
                }
            }
        }

        if (Array.isArray(sessions)) {
            for (const session of sessions) {
                if (session && session.instance_id) {
                    instanceIds.add(session.instance_id);
                }
            }
        }

        const cleanedInstanceIds = [];
        const cleanupErrors = [];

        for (const instanceId of instanceIds) {
            try {
                await WAZIPER.logout(instanceId);
                cleanedInstanceIds.push(instanceId);
            } catch (cleanupError) {
                console.error(`Error cleaning instance ${instanceId}:`, cleanupError);
                cleanupErrors.push({
                    instance_id: instanceId,
                    error: cleanupError.message
                });
            }
        }

        const teamScopedTables = [
            "sp_whatsapp_ai",
            "sp_whatsapp_autoresponder",
            "sp_whatsapp_callresponder",
            "sp_whatsapp_chatbot",
            "sp_whatsapp_contacts",
            "sp_whatsapp_funnels",
            "sp_whatsapp_history",
            "sp_whatsapp_keyword_reply",
            "sp_whatsapp_menu_reply",
            "sp_whatsapp_menu_session",
            "sp_whatsapp_phone_numbers",
            "sp_whatsapp_schedules",
            "sp_whatsapp_sessions",
            "sp_whatsapp_sessions_evo",
            "sp_whatsapp_stats",
            "sp_whatsapp_subscriber",
            "sp_whatsapp_template",
            "sp_whatsapp_virtual_number",
            "sp_whatsapp_webhook",
            "sp_whatsapp_welcome_log",
            "sp_whatsapp_welcome_message",
            "sp_accounts"
        ];

        const instanceScopedTables = [
            "sp_whatsapp_ai",
            "sp_whatsapp_ar_responses",
            "sp_whatsapp_autoresponder",
            "sp_whatsapp_callresponder",
            "sp_whatsapp_chatbot",
            "sp_whatsapp_funnels",
            "sp_whatsapp_history",
            "sp_whatsapp_keyword_reply",
            "sp_whatsapp_livechat",
            "sp_whatsapp_menu_reply",
            "sp_whatsapp_menu_session",
            "sp_whatsapp_messages",
            "sp_whatsapp_sessions",
            "sp_whatsapp_sessions_evo",
            "sp_whatsapp_subscriber",
            "sp_whatsapp_webhook",
            "sp_whatsapp_welcome_log",
            "sp_whatsapp_welcome_message"
        ];

        for (const tableName of teamScopedTables) {
            try {
                await Common.db_delete(tableName, [{ team_id: team.id }]);
            } catch (tableCleanupError) {
                console.error(`Error cleaning team rows from ${tableName}:`, tableCleanupError);
            }
        }

        for (const instanceId of instanceIds) {
            for (const tableName of instanceScopedTables) {
                try {
                    await Common.db_delete(tableName, [{ instance_id: instanceId }]);
                } catch (tableCleanupError) {
                    console.error(`Error cleaning instance ${instanceId} from ${tableName}:`, tableCleanupError);
                }
            }
        }

        return res.json({
            status: 'success',
            message: cleanedInstanceIds.length
                ? 'WhatsApp instances cleaned successfully'
                : 'No WhatsApp instances found to clean',
            data: {
                cleaned_count: cleanedInstanceIds.length,
                cleaned_instance_ids: cleanedInstanceIds,
                error_count: cleanupErrors.length,
                errors: cleanupErrors
            }
        });
    } catch (error) {
        console.error('Error in /api/cleanup_instances:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

/**
 * POST/GET /api/reconnect
 * Re-establish connection to WhatsApp Web without requiring QR code scan
 *
 * Query Parameters (GET) or Body Parameters (POST):
 * @param {string} access_token - Team authentication token (required)
 * @param {string} instance_id - WhatsApp instance identifier (required)
 *
 * This endpoint:
 * - Attempts to reconnect using existing session credentials
 * - Does NOT delete session files
 * - Does NOT require QR code scan (if session is still valid)
 * - Useful for recovering from temporary connection loss
 *
 * Use this when:
 * - Connection was lost due to network issues
 * - Instance shows as disconnected but session is still valid
 * - Want to reconnect without re-scanning QR code
 *
 * Note: If session credentials are invalid or expired, this will fail.
 * In that case, use /api/reboot to force re-authentication with QR code.
 *
 * Returns: Success status with connection state
 *
 * Example:
 * POST /api/reconnect?access_token=xxx&instance_id=yyy
 */
WAZIPER.app.all('/api/reconnect', WAZIPER.cors, async (req, res) => {
    try {
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = params.access_token;
        const instance_id = params.instance_id;

        // Validate required parameters
        if (!access_token) {
            return res.json({
                status: 'error',
                message: 'Missing required parameter: access_token'
            });
        }

        if (!instance_id) {
            return res.json({
                status: 'error',
                message: 'Missing required parameter: instance_id'
            });
        }

        // Authenticate team
        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({
                status: 'error',
                message: 'Authentication failed. Invalid access_token.'
            });
        }

        // Verify instance exists and belongs to team
        const instance = await Common.db_get("sp_accounts", [{ token: instance_id }, { team_id: team.id }]);
        if (!instance) {
            return res.json({
                status: 'error',
                message: 'Instance not found or does not belong to your team.'
            });
        }

        // Check if session credentials exist
        const fs = require('fs');
        const session_dir = './sessions/';
        const SESSION_PATH = session_dir + instance_id;

        if (!fs.existsSync(SESSION_PATH)) {
            return res.json({
                status: 'error',
                message: 'No session credentials found. Please use /api/reboot to re-authenticate with QR code.',
                data: {
                    instance_id: instance_id,
                    has_credentials: false,
                    action_required: 'reboot'
                }
            });
        }

        // Attempt reconnection using existing credentials
        try {
            // If session already exists in memory, close it first
            if (WAZIPER.sessions && WAZIPER.sessions[instance_id]) {
                const existingSession = WAZIPER.sessions[instance_id];
                if (existingSession.ws && existingSession.ws.close) {
                    existingSession.ws.close();
                }
                delete WAZIPER.sessions[instance_id];
            }

            // Create new socket connection using existing credentials (reset=false)
            await WAZIPER.makeWASocket(instance_id);

            return res.json({
                status: 'success',
                message: 'Reconnection initiated successfully',
                data: {
                    instance_id: instance_id,
                    action: 'reconnected',
                    has_credentials: true,
                    note: 'Connection is being re-established. Check /instance endpoint for status.'
                }
            });

        } catch (reconnectError) {
            console.error('Reconnection error:', reconnectError);
            return res.json({
                status: 'error',
                message: 'Failed to reconnect. Session credentials may be invalid.',
                error: reconnectError.message,
                data: {
                    instance_id: instance_id,
                    action_required: 'Use /api/reboot to re-authenticate with QR code'
                }
            });
        }

    } catch (error) {
        console.error('Error in /api/reconnect:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

WAZIPER.app.all('/api/campaigns/retry_failed', WAZIPER.cors, async (req, res) => {
    try {
        await ensureAndroidCampaignTables();

        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = `${params.access_token || ''}`.trim();
        const campaign_id = `${params.campaign_id || ''}`.trim();
        const group_id = `${params.group_id || ''}`.trim();

        if (!access_token || !campaign_id) {
            return res.json({
                status: 'error',
                message: 'access_token and campaign_id are required'
            });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({ status: 'error', message: 'Authentication failed. Invalid access_token.' });
        }

        const queueRow = await Common.db_get('sp_android_campaign_queue', [
            { history_ids: campaign_id },
            { team_id: team.id }
        ]);

        if (!queueRow) {
            return res.json({
                status: 'error',
                message: 'Campaign queue not found for this workspace'
            });
        }

        const recipients = parseJsonArray(queueRow.recipients);
        if (!recipients.length) {
            return res.json({
                status: 'error',
                message: 'No recipients found in campaign queue'
            });
        }

        let retriedCount = 0;
        const normalizedGroupId = group_id.replace('g.us', '@g.us');
        const updatedRecipients = recipients.map((recipient) => {
            const status = `${recipient?.status || ''}`.toLowerCase();
            const recipientGroup = `${recipient?.chat_id || recipient?.number || ''}`.trim();
            const matchGroup = !normalizedGroupId || recipientGroup === normalizedGroupId;
            if (status === 'failed' && matchGroup) {
                retriedCount += 1;
                return {
                    ...recipient,
                    status: 'queued',
                    error: '',
                    retry_count: Number(recipient?.retry_count || 0),
                };
            }
            return recipient;
        });

        if (retriedCount == 0) {
            return res.json({
                status: 'error',
                message: normalizedGroupId
                    ? 'No failed entry found for this group'
                    : 'No failed groups found to retry'
            });
        }

        const firstPendingIndex = updatedRecipients.findIndex((recipient) => {
            const status = `${recipient?.status || ''}`.toLowerCase();
            return status === 'queued' || status === 'failed';
        });

        const nextIndex = firstPendingIndex >= 0 ? firstPendingIndex : Number(queueRow.current_index || 0);
        const queueCounts = summarizeCampaignRecipients(updatedRecipients);
        const now = Common.time();

        await Common.db_update('sp_android_campaign_queue', [
            {
                sent_count: queueCounts.sentCount,
                failed_count: queueCounts.failedCount,
                recipients: JSON.stringify(updatedRecipients),
                current_index: nextIndex,
                status: 'queued',
                next_run_at: now,
                last_error: null,
                changed: now,
            },
            { id: queueRow.id }
        ]);

        await updateQueuedCampaignHistory(
            {
                ...queueRow,
                sent_count: queueCounts.sentCount,
                failed_count: queueCounts.failedCount,
                recipients: JSON.stringify(updatedRecipients),
                current_index: nextIndex,
                status: 'queued',
            },
            {
                status: 'queued',
                items: updatedRecipients,
                sent_count: queueCounts.sentCount,
                failed_count: queueCounts.failedCount,
            }
        );

        processQueuedCampaigns().catch(() => {});

        return res.json({
            status: 'success',
            message: `Retry queued for ${retriedCount} failed group(s)`,
            data: {
                campaign_id,
                retried_count: retriedCount,
                next_index: nextIndex,
                status: 'queued',
            }
        });
    } catch (error) {
        console.error('Error in /api/campaigns/retry_failed:', error);
        return res.json({
            status: 'error',
            message: 'Internal server error',
            error: error.message
        });
    }
});

WAZIPER.app.all('/api/campaigns/stop', WAZIPER.cors, async (req, res) => {
    try {
        await ensureAndroidCampaignTables();
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = `${params.access_token || ''}`.trim();
        const campaign_id = `${params.campaign_id || ''}`.trim();

        if (!access_token || !campaign_id) {
            return res.json({ status: 'error', message: 'access_token and campaign_id are required' });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({ status: 'error', message: 'Authentication failed. Invalid access_token.' });
        }

        const queueRow = await Common.db_get('sp_android_campaign_queue', [
            { history_ids: campaign_id },
            { team_id: team.id }
        ]);

        if (!queueRow) {
            return res.json({ status: 'error', message: 'Campaign queue not found for this workspace' });
        }

        const now = Common.time();
        await Common.db_update('sp_android_campaign_queue', [
            { status: 'paused', changed: now },
            { id: queueRow.id }
        ]);

        await updateQueuedCampaignHistory(
            { ...queueRow, status: 'paused' },
            { status: 'paused' }
        );

        return res.json({
            status: 'success',
            message: 'Campaign stopped',
            data: { campaign_id, queue_status: 'paused' }
        });
    } catch (error) {
        console.error('Error in /api/campaigns/stop:', error);
        return res.json({ status: 'error', message: 'Internal server error', error: error.message });
    }
});

WAZIPER.app.all('/api/campaigns/start', WAZIPER.cors, async (req, res) => {
    try {
        await ensureAndroidCampaignTables();
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = `${params.access_token || ''}`.trim();
        const campaign_id = `${params.campaign_id || ''}`.trim();

        if (!access_token || !campaign_id) {
            return res.json({ status: 'error', message: 'access_token and campaign_id are required' });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({ status: 'error', message: 'Authentication failed. Invalid access_token.' });
        }

        const queueRow = await Common.db_get('sp_android_campaign_queue', [
            { history_ids: campaign_id },
            { team_id: team.id }
        ]);

        if (!queueRow) {
            return res.json({ status: 'error', message: 'Campaign queue not found for this workspace' });
        }

        const now = Common.time();
        await Common.db_update('sp_android_campaign_queue', [
            {
                status: 'queued',
                next_run_at: now,
                last_error: null,
                changed: now,
            },
            { id: queueRow.id }
        ]);

        await updateQueuedCampaignHistory(
            { ...queueRow, status: 'queued' },
            { status: 'queued' }
        );
        processQueuedCampaigns().catch(() => { });

        return res.json({
            status: 'success',
            message: 'Campaign started',
            data: { campaign_id, queue_status: 'queued' }
        });
    } catch (error) {
        console.error('Error in /api/campaigns/start:', error);
        return res.json({ status: 'error', message: 'Internal server error', error: error.message });
    }
});

WAZIPER.app.all('/api/campaigns/delete', WAZIPER.cors, async (req, res) => {
    try {
        await ensureAndroidCampaignTables();
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const access_token = `${params.access_token || ''}`.trim();
        const campaign_id = `${params.campaign_id || ''}`.trim();

        if (!access_token || !campaign_id) {
            return res.json({ status: 'error', message: 'access_token and campaign_id are required' });
        }

        const team = await Common.db_get("sp_team", [{ ids: access_token }]);
        if (!team) {
            return res.json({ status: 'error', message: 'Authentication failed. Invalid access_token.' });
        }

        await Common.db_delete('sp_android_campaign_queue', [
            { history_ids: campaign_id },
            { team_id: team.id }
        ]);
        await Common.db_delete('sp_android_campaign_status', [
            { ids: campaign_id },
            { team_id: team.id }
        ]);

        return res.json({
            status: 'success',
            message: 'Campaign deleted',
            data: { campaign_id }
        });
    } catch (error) {
        console.error('Error in /api/campaigns/delete:', error);
        return res.json({ status: 'error', message: 'Internal server error', error: error.message });
    }
});

/**
 * GET /
 * Root endpoint - health check and welcome message
 *
 * Returns: Success status with welcome message
 *
 * Used to verify the API server is running and accessible
 */
WAZIPER.app.get('/', WAZIPER.cors, async (req, res) => {
    return res.json({ status: 'success', message: `WELCOME TO MQ TECH GURU!!!` });
});

/**
 * Catch-all 404 handler
 * This must be the last route defined
 * Returns a JSON error for any undefined routes
 */
WAZIPER.app.use((req, res) => {
    console.log(`âŒ 404 Not Found: ${req.method} ${req.path}`);
    res.status(404).json({
        status: 'error',
        message: 'Endpoint not found',
        path: req.path,
        method: req.method,
        available_endpoints: [
            'GET /',
            'POST /api/create_instance',
            'GET /api/instances',
            'POST /api/set_webhook',
            'POST /api/reboot',
            'POST /api/reset_instance',
            'POST /api/cleanup_instances',
            'POST /api/reconnect',
            'GET /instance',
            'GET /session/health',
            'GET /get_qrcode',
            'GET /get_paircode',
            'GET /logout',
            'POST /api/send',
            'POST /api/campaigns/launch',
            'POST /api/campaigns/start',
            'POST /api/campaigns/stop',
            'POST /api/campaigns/delete',
            'POST /api/campaigns/retry_failed',
            'POST /send_message',
            'POST /direct_send_message',
            'POST /send_template',
            'GET /get_groups',
            'GET /clear_cache_ai',
            'GET /reset',
            'GET /webhook/:accountId',
            'POST /webhook/:accountId'
        ]
    });
});

/**
 * Start the HTTP server
 *
 * Listens on the port specified in config.js (default: 7708)
 * The server handles both REST API requests and Socket.IO connections
 * for real-time updates to the frontend
 */
WAZIPER.server.listen(config.port, async () => {
    console.log(`âœ… Server running on port ${config.port}`);
    console.log(`ðŸ“¡ API Base URL: http://localhost:${config.port}`);
    console.log(`ðŸŒ Frontend URL: ${config.frontend}`);
    console.log(`\nðŸ“‹ Available Endpoints:`);
    console.log(`   GET  /                      - Health check`);
    console.log(`   POST /api/create_instance   - Create new instance`);
    console.log(`   GET  /api/instances        - List up to 10 linked instances`);
    console.log(`   POST /api/set_webhook       - Configure webhook URL`);
    console.log(`   POST /api/reboot            - Reboot instance (logout + new QR)`);
    console.log(`   POST /api/reset_instance    - Reset with new instance ID`);
    console.log(`   POST /api/cleanup_instances - Delete every team instance`);
    console.log(`   POST /api/reconnect         - Reconnect without QR code`);
    console.log(`   GET  /instance              - Get instance info`);
    console.log(`   GET  /session/health        - Check session health status`);
    console.log(`   GET  /get_qrcode            - Generate QR code`);
    console.log(`   GET  /get_paircode          - Generate pairing code`);
    console.log(`   GET  /logout                - Logout instance`);
    console.log(`   POST /api/send              - Send message (unified)`);
    console.log(`   POST /api/campaigns/launch  - Queue campaign in background`);
    console.log(`   POST /send_message          - Send message (queued)`);
    console.log(`   POST /direct_send_message   - Send message (direct)`);
    console.log(`   POST /send_template         - Send Business API template`);
    console.log(`   GET  /get_groups            - Get all groups`);
    console.log(`   GET  /clear_cache_ai        - Clear AI cache`);
    console.log(`   GET  /reset                 - Restart server (admin)`);
    console.log(`\nðŸ”§ Media Download: DISABLED (Fix #1 applied)`);
    console.log(`âœ… All endpoints registered successfully\n`);

    await ensureAndroidCampaignTables();
    setInterval(() => {
        processQueuedCampaigns().catch((error) => {
            console.error('Queued campaign worker tick failed:', error.message);
        });
    }, CAMPAIGN_WORKER_TICK_MS);

    /**
     * AUTO-LOAD ACTIVE SESSIONS ON STARTUP
     *
     * This ensures that all active WhatsApp sessions are loaded into memory
     * when the server starts, so that event handlers (messages.upsert, call, etc.)
     * are properly registered and can process incoming messages/calls.
     *
     * Without this, sessions are only loaded on-demand when API requests are made,
     * which means chatbot, autoresponder, and other event-driven features won't work
     * until an API call is made to load the session.
     *
     * This fix ensures:
     * - Chatbot can respond to messages immediately after startup
     * - Autoresponder can process keywords immediately
     * - Call responder can handle calls immediately
     * - All event handlers are active and listening
     */
    console.log(`\nðŸ”„ Loading active sessions...`);

    try {
        // Get all active Baileys sessions (login_type = 2, status = 1)
        const active_accounts = await Common.db_query(`
            SELECT a.token as instance_id, a.name, a.id
            FROM sp_accounts as a
            WHERE a.social_network = 'whatsapp'
            AND a.login_type = '2'
            AND a.status = 1
        `, false);

        if (!active_accounts || active_accounts.length === 0) {
            console.log(`â„¹ï¸  No active sessions to load`);
            return;
        }

        console.log(`ðŸ“± Found ${active_accounts.length} active session(s) to load`);

        let loadedCount = 0;
        let failedCount = 0;

        // Load each session sequentially to avoid overwhelming the system
        for (const account of active_accounts) {
            const instance_id = account.instance_id;
            const accountName = account.name || 'Unknown';

            try {
                console.log(`   Loading session: ${instance_id} (${accountName})...`);

                // Load the session (reset=false to use existing credentials)
                await WAZIPER.session(instance_id, false);

                loadedCount++;
                console.log(`   âœ… Loaded: ${instance_id} (${accountName})`);

                // Small delay between sessions to avoid rate limiting
                await new Promise(resolve => setTimeout(resolve, 1000));

            } catch (error) {
                failedCount++;
                console.error(`   âŒ Failed to load ${instance_id} (${accountName}):`, error.message);
            }
        }

        console.log(`\nðŸ“Š Session Loading Summary:`);
        console.log(`   âœ… Loaded: ${loadedCount}`);
        console.log(`   â Œ Failed: ${failedCount}`);
        console.log(`   ðŸ“± Total: ${active_accounts.length}`);
        console.log(`\nðŸŽ‰ Server initialization complete!\n`);

    } catch (error) {
        console.error(`❌ Error loading sessions on startup:`, error.message);
        console.error(error.stack);
    }
});

global.AndroidCampaignTracker = {
    handleMessageUpdate: async (instance_id, messages) => {
        try {
            for (const msg of messages) {
                if (msg.update && msg.update.status) {
                    const status = msg.update.status;
                    const messageId = msg.key?.id;
                    if (!messageId) continue;
                    
                    // Look up the message_id in the tracking table
                    const trackingRow = await Common.db_query(`SELECT * FROM sp_android_message_tracking WHERE message_id = '${messageId}' LIMIT 1`);
                    if (trackingRow) {
                        // 1: Server, 2: Delivered, 3: Read, 4: Failed
                        let newStatus = '';
                        if (status >= 1 && status <= 3) {
                            newStatus = 'sent';
                        } else if (status === 4) {
                            newStatus = 'failed';
                        }
                        
                        if (newStatus && trackingRow.status !== newStatus) {
                            // Update tracking table
                            await Common.db_query(`UPDATE sp_android_message_tracking SET status = '${newStatus}' WHERE message_id = '${messageId}'`);
                            
                            // Update queue and status tables
                            const history_ids = trackingRow.history_ids;
                            const queueRow = await Common.db_get('sp_android_campaign_queue', [{ history_ids: history_ids }]);
                            if (queueRow && queueRow.recipients) {
                                let recipients = JSON.parse(queueRow.recipients);
                                if (recipients[trackingRow.recipient_index]) {
                                    recipients[trackingRow.recipient_index].status = newStatus;
                                    recipients[trackingRow.recipient_index].error = newStatus === 'failed' ? 'Delivery Failed (WhatsApp Server)' : '';
                                    
                                    await Common.db_update('sp_android_campaign_queue', [
                                        { recipients: JSON.stringify(recipients) },
                                        { history_ids: history_ids }
                                    ]);
                                    
                                    // Make sure updateQueuedCampaignHistory is available globally or we can just require it if needed
                                    // Actually updateQueuedCampaignHistory is defined in app.js scope, so it's accessible.
                                    await updateQueuedCampaignHistory(
                                        { history_ids: history_ids, recipients: JSON.stringify(recipients) },
                                        { items: recipients }
                                    );
                                    
                                    console.log(`✅ [AndroidCampaignTracker] Updated message ${messageId} to ${newStatus} via delivery receipt`);
                                }
                            }
                        }
                    }
                }
            }
        } catch (error) {
            console.error('❌ [AndroidCampaignTracker] Error handling message update:', error.message);
        }
    }
};


// ============================================================================
// CLOUD BACKUP & RESTORE ENDPOINTS
// ============================================================================
WAZIPER.app.all('/api/user/backup', WAZIPER.cors, async (req, res) => {
    try {
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const user_email = `${params.user_email || params.email || ''}`.trim().toLowerCase();
        let backup_data = params.backup_data;

        if (!user_email) {
            return res.json({ status: 'error', message: 'user_email is required' });
        }

        if (typeof backup_data === 'object') {
            backup_data = JSON.stringify(backup_data);
        } else if (typeof backup_data === 'string') {
            backup_data = backup_data.trim();
        }

        if (!backup_data) {
            return res.json({ status: 'error', message: 'backup_data is required' });
        }

        const now = Common.time();
        const existing = await Common.db_get('sp_user_backups', [{ user_email }]);

        if (existing) {
            await Common.db_update('sp_user_backups', [
                {
                    backup_data,
                    version: (existing.version || 1) + 1,
                    updated_at: now,
                },
                { id: existing.id }
            ]);
        } else {
            await Common.db_insert('sp_user_backups', {
                user_email,
                backup_data,
                version: 1,
                created_at: now,
                updated_at: now,
            });
        }

        return res.json({
            status: 'success',
            message: 'Backup saved successfully',
            data: { user_email, updated_at: now }
        });
    } catch (error) {
        console.error('Error in /api/user/backup:', error);
        return res.json({ status: 'error', message: error.message });
    }
});

WAZIPER.app.all('/api/user/restore', WAZIPER.cors, async (req, res) => {
    try {
        const params = req.method === 'GET' ? req.query : { ...req.query, ...req.body };
        const user_email = `${params.user_email || params.email || ''}`.trim().toLowerCase();

        if (!user_email) {
            return res.json({ status: 'error', message: 'user_email is required' });
        }

        const row = await Common.db_get('sp_user_backups', [{ user_email }]);
        if (!row || !row.backup_data) {
            return res.json({ status: 'error', message: 'No backup found for this email' });
        }

        let parsedData;
        try {
            parsedData = JSON.parse(row.backup_data);
        } catch (_) {
            parsedData = row.backup_data;
        }

        return res.json({
            status: 'success',
            message: 'Backup retrieved successfully',
            data: {
                user_email: row.user_email,
                backup_data: parsedData,
                version: row.version,
                updated_at: row.updated_at,
            }
        });
    } catch (error) {
        console.error('Error in /api/user/restore:', error);
        return res.json({ status: 'error', message: error.message });
    }
});
