import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:autoreply/features/sync/services/server_sync_service.dart';

class LinkedGroupBroadcastTarget {
  final String instanceId;
  final String linkedNumber;
  final String linkedName;
  final String groupId;
  final String groupName;

  const LinkedGroupBroadcastTarget({
    required this.instanceId,
    required this.linkedNumber,
    required this.linkedName,
    required this.groupId,
    required this.groupName,
  });

  String get key => '$instanceId::$groupId';

  String get numberLabel =>
      linkedNumber.trim().isNotEmpty ? linkedNumber.trim() : instanceId;
}

class LinkedGroupBroadcastResult {
  final int totalGroups;
  final int totalNumbers;
  final int sentGroups;
  final int failedGroups;
  final int queuedGroups;
  final bool usedFallback;
  final List<String> campaignIds;

  const LinkedGroupBroadcastResult({
    required this.totalGroups,
    required this.totalNumbers,
    required this.sentGroups,
    required this.failedGroups,
    required this.queuedGroups,
    required this.usedFallback,
    required this.campaignIds,
  });
}

class LinkedGroupBroadcastService {
  final ServerSyncService _syncService = ServerSyncService();
  final SubscriptionService _subscriptionService = SubscriptionService.instance;

  Future<LinkedGroupBroadcastResult> sendText({
    required String batchName,
    required String message,
    required List<LinkedGroupBroadcastTarget> targets,
    required int delaySeconds,
    int scheduleAt = 0,
  }) {
    return _send(
      batchName: batchName,
      targets: targets,
      delaySeconds: delaySeconds,
      messageMode: 'group_text',
      messageLabel: 'Linked Group Broadcast',
      payloadBuilder: (_) => {'type': 'text', 'message': message},
      scheduleAt: scheduleAt,
    );
  }

  Future<LinkedGroupBroadcastResult> sendMedia({
    required String batchName,
    required String mediaType,
    required String mediaUrl,
    required String caption,
    required String filename,
    String mimeType = '',
    required List<LinkedGroupBroadcastTarget> targets,
    required int delaySeconds,
    int scheduleAt = 0,
  }) {
    final normalizedMediaType = mediaType.trim().toLowerCase();
    return _send(
      batchName: batchName,
      targets: targets,
      delaySeconds: delaySeconds,
      messageMode: 'group_media',
      messageLabel:
          'Linked Group ${normalizedMediaType.isEmpty ? 'Media' : normalizedMediaType[0].toUpperCase()}${normalizedMediaType.length > 1 ? normalizedMediaType.substring(1) : ''}',
      payloadBuilder: (_) => {
        'type': 'media',
        'media_type': normalizedMediaType,
        'mime_type': mimeType.trim(),
        'media_url': mediaUrl,
        'caption': caption,
        'filename': filename,
      },
      scheduleAt: scheduleAt,
    );
  }

  Future<LinkedGroupBroadcastResult> sendForward({
    required String batchName,
    required Map<String, dynamic> sourceMessage,
    required List<LinkedGroupBroadcastTarget> targets,
    required int delaySeconds,
    int scheduleAt = 0,
  }) {
    return _send(
      batchName: batchName,
      targets: targets,
      delaySeconds: delaySeconds,
      messageMode: 'group_forward',
      messageLabel: 'Linked Group Forward',
      payloadBuilder: (_) => {
        'type': 'forward',
        'source_message': sourceMessage,
      },
      scheduleAt: scheduleAt,
    );
  }

  Future<LinkedGroupBroadcastResult> _send({
    required String batchName,
    required List<LinkedGroupBroadcastTarget> targets,
    required int delaySeconds,
    required String messageMode,
    required String messageLabel,
    required Map<String, dynamic> Function(String instanceId) payloadBuilder,
    required int scheduleAt,
  }) async {
    await _subscriptionService.ensurePremiumAccess(
      message:
          'This is a premium feature. Upgrade to send messages in linked groups.',
    );

    final groupedTargets = <String, List<LinkedGroupBroadcastTarget>>{};
    for (final target in targets) {
      if (target.instanceId.trim().isEmpty || target.groupId.trim().isEmpty) {
        continue;
      }
      groupedTargets
          .putIfAbsent(target.instanceId, () => <LinkedGroupBroadcastTarget>[])
          .add(target);
    }

    var sentGroups = 0;
    var failedGroups = 0;
    var queuedGroups = 0;
    final campaignIds = <String>[];

    final launchTasks = groupedTargets.entries.map((entry) async {
      final instanceId = entry.key;
      final instanceTargets = entry.value;
      final campaignName = _buildCampaignName(
        batchName: batchName,
        targetCount: instanceTargets.length,
        numberLabel: instanceTargets.first.numberLabel,
      );

      try {
        final response = await _syncService.launchCampaign(
          instanceId: instanceId,
          campaignName: campaignName,
          targetName: instanceTargets.first.numberLabel,
          delaySeconds: delaySeconds,
          messageMode: messageMode,
          messageLabel: messageLabel,
          userEmail: _subscriptionService.currentUser?.email ?? '',
          recipients: List<Map<String, dynamic>>.generate(
            instanceTargets.length,
            (index) => {
              'index': index + 1,
              'name': instanceTargets[index].groupName,
              'number': instanceTargets[index].groupId,
              'chat_id': instanceTargets[index].groupId,
              'is_group': true,
            },
          ),
          payload: payloadBuilder(instanceId),
          scheduleAt: scheduleAt,
        );

        return _InstanceLaunchResult(
          queuedGroups: instanceTargets.length,
          campaignId: response['data']?['campaign_id']?.toString() ?? '',
        );
      } catch (error) {
        return _InstanceLaunchResult(failedGroups: instanceTargets.length);
      }
    }).toList();

    final launchResults = await Future.wait(launchTasks);
    for (final result in launchResults) {
      failedGroups += result.failedGroups;
      queuedGroups += result.queuedGroups;
      if (result.campaignId.isNotEmpty) {
        campaignIds.add(result.campaignId);
      }
    }

    final successfulCount = queuedGroups + sentGroups;
    if (successfulCount > 0) {
      await _subscriptionService.recordSuccessfulSend(successfulCount);
    }

    return LinkedGroupBroadcastResult(
      totalGroups: groupedTargets.values.fold<int>(
        0,
        (sum, items) => sum + items.length,
      ),
      totalNumbers: groupedTargets.length,
      sentGroups: sentGroups,
      failedGroups: failedGroups,
      queuedGroups: queuedGroups,
      usedFallback: false,
      campaignIds: campaignIds,
    );
  }

  String _buildCampaignName({
    required String batchName,
    required int targetCount,
    required String numberLabel,
  }) {
    final normalizedBatch = batchName.trim();
    final normalizedNumber = numberLabel.trim();
    if (normalizedNumber.isEmpty) {
      return '$normalizedBatch ($targetCount groups)';
    }
    return '$normalizedBatch - $normalizedNumber ($targetCount groups)';
  }
}

class _InstanceLaunchResult {
  final int failedGroups;
  final int queuedGroups;
  final String campaignId;

  const _InstanceLaunchResult({
    this.failedGroups = 0,
    this.queuedGroups = 0,
    this.campaignId = '',
  });
}
