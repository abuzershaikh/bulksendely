import 'package:autoreply/core/network/api_client.dart';
import 'package:autoreply/features/group_bulk_sender/models/group_batch_model.dart';
import 'package:autoreply/features/group_sender_status/models/group_sender_status_models.dart';
import 'package:autoreply/features/group_sender_status/services/group_sender_status_service.dart';
import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:autoreply/features/sync/services/server_sync_service.dart';
import 'package:autoreply/features/whatsapp/models/whatsapp_group_model.dart';

class GroupBulkSendResult {
  final int total;
  final int sent;
  final List<String> failedGroupIds;
  final String instanceId;
  final List<GroupTargetStatus> items;

  const GroupBulkSendResult({
    required this.total,
    required this.sent,
    required this.failedGroupIds,
    required this.instanceId,
    required this.items,
  });
}

class GroupBulkSenderService {
  final ServerSyncService _syncService = ServerSyncService();
  final SubscriptionService _subscriptionService = SubscriptionService.instance;
  final GroupSenderStatusService _groupSenderStatusService =
      GroupSenderStatusService();

  Future<GroupBulkSendResult> sendText({
    required GroupBatchModel batch,
    required String message,
    required int delaySeconds,
  }) {
    return _send(
      batch: batch,
      delaySeconds: delaySeconds,
      messageLabel: 'Text Message',
      messageMode: GroupBroadcastMessageMode.text.name,
      sender: (group, instanceId) => _syncService.sendMessage(
        instanceId: instanceId,
        number: group.id,
        message: message,
      ),
    );
  }

  Future<GroupBulkSendResult> sendMedia({
    required GroupBatchModel batch,
    required GroupBroadcastMediaType mediaType,
    required String mediaUrl,
    required String caption,
    required String filename,
    required int delaySeconds,
  }) {
    return _send(
      batch: batch,
      delaySeconds: delaySeconds,
      messageLabel:
          'Media: ${mediaType.name[0].toUpperCase()}${mediaType.name.substring(1)}',
      messageMode: GroupBroadcastMessageMode.media.name,
      sender: (group, instanceId) => _syncService.sendMediaMessage(
        instanceId: instanceId,
        number: group.id,
        type: mediaType.name,
        mediaUrl: mediaUrl,
        caption: caption,
        filename: filename,
      ),
    );
  }

  Future<GroupBulkSendResult> _send({
    required GroupBatchModel batch,
    required int delaySeconds,
    required String messageLabel,
    required String messageMode,
    required Future<void> Function(WhatsappGroupModel group, String instanceId) sender,
  }) async {
    await _subscriptionService.ensurePremiumAccess(
      message: 'This is a premium feature. Upgrade to send messages in groups.',
    );
    final instanceId = await _syncService.getActiveInstanceId();
    final failed = <String>[];
    final items = <GroupTargetStatus>[];
    var sent = 0;

    for (var index = 0; index < batch.groups.length; index++) {
      final group = batch.groups[index];
      try {
        await sender(group, instanceId);
        sent++;
        items.add(
          GroupTargetStatus(
            index: index + 1,
            groupId: group.id,
            groupName: group.name,
            status: 'sent',
            error: '',
          ),
        );
      } catch (error) {
        failed.add(group.id);
        items.add(
          GroupTargetStatus(
            index: index + 1,
            groupId: group.id,
            groupName: group.name,
            status: 'failed',
            error: error.toString(),
          ),
        );
      }

      if (index < batch.groups.length - 1 && delaySeconds > 0) {
        await Future<void>.delayed(Duration(seconds: delaySeconds));
      }
    }

    try {
      final accessToken = await ApiClient.requireWaziperAccessToken();
      await _groupSenderStatusService.saveGroupSenderStatus({
        'access_token': accessToken,
        'user_email': _subscriptionService.currentUser?.email ?? '',
        'batch_name': batch.name,
        'target_count': batch.groups.length,
        'sent_count': sent,
        'failed_count': batch.groups.length - sent,
        'message_mode': messageMode,
        'message_label': messageLabel,
        'delay_seconds': delaySeconds,
        'instance_id': instanceId,
        'items': items.map((item) => item.toJson()).toList(),
      });
    } catch (_) {}

    return GroupBulkSendResult(
      total: batch.groups.length,
      sent: sent,
      failedGroupIds: failed,
      instanceId: instanceId,
      items: items,
    );
  }
}
