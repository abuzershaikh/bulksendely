import 'package:autoreply/data/models/button_template_model.dart';
import 'package:autoreply/data/models/contact_group_model.dart';
import 'package:autoreply/features/campaigns/models/campaign_draft_model.dart';
import 'package:autoreply/features/campaign_status/models/campaign_status_models.dart';
import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:autoreply/features/sync/services/server_sync_service.dart';
import 'package:autoreply/features/campaigns/models/one_shot_range_model.dart';
import 'package:autoreply/features/campaigns/services/one_shot_storage.dart';

class CampaignSendResult {
  final int total;
  final int sent;
  final List<String> failedNumbers;
  final String instanceId;
  final List<CampaignRecipientStatus> items;
  final bool queued;
  final String campaignId;

  const CampaignSendResult({
    required this.total,
    required this.sent,
    required this.failedNumbers,
    required this.instanceId,
    required this.items,
    this.queued = false,
    this.campaignId = '',
  });
}

class CampaignSendService {
  final ServerSyncService _syncService = ServerSyncService();
  final SubscriptionService _subscriptionService = SubscriptionService.instance;

  Future<CampaignSendResult> sendPlainMessage({
    required String campaignName,
    required ContactGroupModel group,
    required String message,
    required String countryCode,
    required int delaySeconds,
    DateTime? scheduledAt,
  }) async {
    final instanceId = await _syncService.getActiveInstanceId();
    final recipients = _normalizeContacts(group.contacts, countryCode);
    await _subscriptionService.ensureDirectMessageAccess(recipients.length);
    final payload = {'type': 'text', 'message': message};
    try {
      return await _queueCampaign(
        campaignName: campaignName,
        group: group,
        messageLabel: 'Plain Text',
        messageMode: CampaignMessageMode.text.name,
        delaySeconds: delaySeconds,
        instanceId: instanceId,
        recipients: recipients,
        payload: payload,
        scheduledAt: scheduledAt,
      );
    } catch (error) {
      if (scheduledAt != null) {
        throw Exception("Scheduled campaigns require an active server connection.");
      }
      if (!_shouldFallbackToDirectSend(error)) {
        rethrow;
      }
      return _sendPlainDirect(
        instanceId: instanceId,
        recipients: recipients,
        message: message,
      );
    }
  }

  Future<CampaignSendResult> sendTemplateMessage({
    required String campaignName,
    required ContactGroupModel group,
    required ButtonTemplateModel template,
    required String countryCode,
    required int delaySeconds,
    DateTime? scheduledAt,
  }) async {
    final instanceId = await _syncService.getActiveInstanceId();
    final templateId = await _resolveTemplateId(template);
    final recipients = _normalizeContacts(group.contacts, countryCode);
    await _subscriptionService.ensureDirectMessageAccess(recipients.length);
    final payload = {'type': 'template', 'template_id': templateId};
    try {
      return await _queueCampaign(
        campaignName: campaignName,
        group: group,
        messageLabel: 'Template: ${template.name}',
        messageMode: CampaignMessageMode.template.name,
        delaySeconds: delaySeconds,
        instanceId: instanceId,
        recipients: recipients,
        payload: payload,
        scheduledAt: scheduledAt,
      );
    } catch (error) {
      if (scheduledAt != null) {
        throw Exception("Scheduled campaigns require an active server connection.");
      }
      if (!_shouldFallbackToDirectSend(error)) {
        rethrow;
      }
      return _sendTemplateDirect(
        instanceId: instanceId,
        recipients: recipients,
        templateId: templateId,
      );
    }
  }

  Future<CampaignSendResult> sendMediaMessage({
    required String campaignName,
    required ContactGroupModel group,
    required CampaignMediaType mediaType,
    required String mediaUrl,
    required String caption,
    required String filename,
    required String countryCode,
    required int delaySeconds,
    DateTime? scheduledAt,
  }) async {
    final instanceId = await _syncService.getActiveInstanceId();
    final recipients = _normalizeContacts(group.contacts, countryCode);
    await _subscriptionService.ensureDirectMessageAccess(recipients.length);
    final payload = {
      'type': 'media',
      'media_type': mediaType.name,
      'media_url': mediaUrl,
      'caption': caption,
      'filename': filename,
    };
    try {
      return await _queueCampaign(
        campaignName: campaignName,
        group: group,
        messageLabel:
            'Media: ${mediaType.name[0].toUpperCase()}${mediaType.name.substring(1)}',
        messageMode: CampaignMessageMode.media.name,
        delaySeconds: delaySeconds,
        instanceId: instanceId,
        recipients: recipients,
        payload: payload,
        scheduledAt: scheduledAt,
      );
    } catch (error) {
      if (scheduledAt != null) {
        throw Exception("Scheduled campaigns require an active server connection.");
      }
      if (!_shouldFallbackToDirectSend(error)) {
        rethrow;
      }
      return _sendMediaDirect(
        instanceId: instanceId,
        recipients: recipients,
        mediaType: mediaType,
        mediaUrl: mediaUrl,
        caption: caption,
        filename: filename,
      );
    }
  }

  List<_NormalizedRecipient> _normalizeContacts(
    List<ContactModel> contacts,
    String countryCode,
  ) {
    final seen = <String>{};
    final numbers = <_NormalizedRecipient>[];
    final cleanedCountryCode = countryCode.replaceAll(RegExp(r'[^0-9]'), '');

    for (final contact in contacts) {
      var cleaned = contact.number.replaceAll(RegExp(r'[^0-9]'), '');
      if (cleaned.isEmpty) {
        continue;
      }

      if (cleanedCountryCode.isNotEmpty) {
        if (cleaned.startsWith('00$cleanedCountryCode')) {
          // Normalize international prefix style (e.g. 0091...) to 91...
          cleaned = cleaned.substring(2);
        } else if (!cleaned.startsWith(cleanedCountryCode)) {
          cleaned = '$cleanedCountryCode$cleaned';
        }
      }

      if (cleaned.isEmpty || seen.contains(cleaned)) {
        continue;
      }

      seen.add(cleaned);
      numbers.add(_NormalizedRecipient(name: contact.name, number: cleaned));
    }

    return numbers;
  }

  Future<CampaignSendResult> _queueCampaign({
    required String campaignName,
    required ContactGroupModel group,
    required String messageLabel,
    required String messageMode,
    required int delaySeconds,
    required String instanceId,
    required List<_NormalizedRecipient> recipients,
    required Map<String, dynamic> payload,
    DateTime? scheduledAt,
  }) async {
    final queuedItems = List<CampaignRecipientStatus>.generate(
      recipients.length,
      (index) => CampaignRecipientStatus(
        index: index + 1,
        name: recipients[index].name,
        number: recipients[index].number,
        status: 'queued',
        error: '',
      ),
    );

    final response = await _syncService.launchCampaign(
      instanceId: instanceId,
      campaignName: campaignName,
      targetName: group.name,
      delaySeconds: delaySeconds,
      messageMode: messageMode,
      messageLabel: messageLabel,
      userEmail: _subscriptionService.currentUser?.email ?? '',
      recipients: queuedItems.map((item) => item.toJson()).toList(),
      payload: payload,
      scheduleAt: scheduledAt != null ? (scheduledAt.millisecondsSinceEpoch ~/ 1000) : 0,
    );

    final responseData = Map<String, dynamic>.from(
      (response['data'] as Map?) ?? const {},
    );
    final sentCount = (responseData['sent_count'] as num?)?.toInt() ?? 0;
    final failedCount = (responseData['failed_count'] as num?)?.toInt() ?? 0;
    final responseItems = ((responseData['items'] as List?) ?? const [])
        .whereType<Map>()
        .map(
          (item) => CampaignRecipientStatus.fromJson(
            Map<String, dynamic>.from(item),
          ),
        )
        .toList();

    await _subscriptionService.recordSuccessfulSend(sentCount);

    try {
      final numbers = recipients.map((r) => r.number).toList();
      await OneShotStorage.instance.markNumbersSent(group.id, numbers);
      final nowStr = DateTime.now().toLocal().toString().split('.')[0];
      await OneShotStorage.instance.addHistoryLog(
        OneShotHistoryLog(
          id: DateTime.now().millisecondsSinceEpoch.toString(),
          groupId: group.id,
          groupName: group.name,
          rangeLabel: '${recipients.length} contacts (${group.name})',
          sentCount: recipients.length,
          totalCount: recipients.length,
          timestamp: nowStr,
        ),
      );
    } catch (_) {}

    return CampaignSendResult(
      total: recipients.length,
      sent: sentCount,
      failedNumbers: responseItems
          .where((item) => item.status.toLowerCase() == 'failed')
          .map((item) => item.number)
          .where((number) => number.isNotEmpty)
          .toList(growable: false),
      instanceId: instanceId,
      items: responseItems.isNotEmpty ? responseItems : queuedItems,
      queued: sentCount == 0 && failedCount == 0,
      campaignId: response['data']?['campaign_id']?.toString() ?? '',
    );
  }

  bool _shouldFallbackToDirectSend(Object error) {
    final text = error.toString().toLowerCase();
    return text.contains('404') ||
        text.contains('not found') ||
        text.contains('campaigns/launch');
  }

  Future<CampaignSendResult> _sendPlainDirect({
    required String instanceId,
    required List<_NormalizedRecipient> recipients,
    required String message,
  }) {
    return _sendDirect(
      instanceId: instanceId,
      recipients: recipients,
      sender: (recipient) => _syncService.sendMessage(
        instanceId: instanceId,
        number: recipient.number,
        message: message,
      ),
    );
  }

  Future<CampaignSendResult> _sendTemplateDirect({
    required String instanceId,
    required List<_NormalizedRecipient> recipients,
    required String templateId,
  }) {
    return _sendDirect(
      instanceId: instanceId,
      recipients: recipients,
      sender: (recipient) => _syncService.sendTemplate(
        instanceId: instanceId,
        number: recipient.number,
        templateId: templateId,
      ),
    );
  }

  Future<CampaignSendResult> _sendMediaDirect({
    required String instanceId,
    required List<_NormalizedRecipient> recipients,
    required CampaignMediaType mediaType,
    required String mediaUrl,
    required String caption,
    required String filename,
  }) {
    return _sendDirect(
      instanceId: instanceId,
      recipients: recipients,
      sender: (recipient) => _syncService.sendMediaMessage(
        instanceId: instanceId,
        number: recipient.number,
        type: mediaType.name,
        mediaUrl: mediaUrl,
        caption: caption,
        filename: filename,
      ),
    );
  }

  Future<CampaignSendResult> _sendDirect({
    required String instanceId,
    required List<_NormalizedRecipient> recipients,
    required Future<Map<String, dynamic>> Function(
      _NormalizedRecipient recipient,
    )
    sender,
  }) async {
    final items = <CampaignRecipientStatus>[];
    final failedNumbers = <String>[];
    var sent = 0;

    for (var index = 0; index < recipients.length; index++) {
      final recipient = recipients[index];
      try {
        await sender(recipient);
        sent += 1;
        items.add(
          CampaignRecipientStatus(
            index: index + 1,
            name: recipient.name,
            number: recipient.number,
            status: 'sent',
            error: '',
          ),
        );
      } catch (error) {
        failedNumbers.add(recipient.number);
        items.add(
          CampaignRecipientStatus(
            index: index + 1,
            name: recipient.name,
            number: recipient.number,
            status: 'failed',
            error: error.toString(),
          ),
        );
      }
    }
    await _subscriptionService.recordSuccessfulSend(sent);

    return CampaignSendResult(
      total: recipients.length,
      sent: sent,
      failedNumbers: failedNumbers,
      instanceId: instanceId,
      items: items,
    );
  }

  Future<String> _resolveTemplateId(ButtonTemplateModel template) async {
    final existingServerId = template.serverId ?? '';
    if (existingServerId.isNotEmpty &&
        !RegExp(r'^\d+$').hasMatch(existingServerId)) {
      return existingServerId;
    }

    if (existingServerId.isNotEmpty &&
        RegExp(r'^\d+$').hasMatch(existingServerId)) {
      // Older app builds stored numeric row IDs. Re-sync once to get the stable template UUID.
      final migratedId = await _syncService.syncTemplate(template);
      if (migratedId != null && migratedId.isNotEmpty) {
        return migratedId;
      }
      return template.serverId!;
    }

    final serverId = await _syncService.syncTemplate(template);
    if (serverId == null || serverId.isEmpty) {
      throw Exception('Template is not synced yet');
    }

    return serverId;
  }
}

class _NormalizedRecipient {
  final String name;
  final String number;

  const _NormalizedRecipient({required this.name, required this.number});
}
