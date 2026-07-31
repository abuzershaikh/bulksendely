class GroupSenderStatusSummary {
  final String id;
  final String batchName;
  final int targetCount;
  final int sentCount;
  final int failedCount;
  final String messageMode;
  final String messageLabel;
  final int delaySeconds;
  final String instanceId;
  final String queueStatus;
  final int nextRunInSec;
  final bool cooldownActive;
  final String lastError;
  final String createdAt;

  const GroupSenderStatusSummary({
    required this.id,
    required this.batchName,
    required this.targetCount,
    required this.sentCount,
    required this.failedCount,
    required this.messageMode,
    required this.messageLabel,
    required this.delaySeconds,
    required this.instanceId,
    required this.queueStatus,
    required this.nextRunInSec,
    required this.cooldownActive,
    required this.lastError,
    required this.createdAt,
  });

  int get pendingCount {
    final pending = targetCount - sentCount - failedCount;
    return pending < 0 ? 0 : pending;
  }

  factory GroupSenderStatusSummary.fromJson(Map<String, dynamic> json) {
    return GroupSenderStatusSummary(
      id: json['id']?.toString() ?? '',
      batchName: json['batch_name']?.toString() ?? '',
      targetCount: (json['target_count'] as num?)?.toInt() ?? 0,
      sentCount: (json['sent_count'] as num?)?.toInt() ?? 0,
      failedCount: (json['failed_count'] as num?)?.toInt() ?? 0,
      messageMode: json['message_mode']?.toString() ?? '',
      messageLabel: json['message_label']?.toString() ?? '',
      delaySeconds: (json['delay_seconds'] as num?)?.toInt() ?? 0,
      instanceId: json['instance_id']?.toString() ?? '',
      queueStatus: json['queue_status']?.toString() ?? '',
      nextRunInSec: (json['next_run_in_sec'] as num?)?.toInt() ?? 0,
      cooldownActive: json['cooldown_active'] == true,
      lastError: json['last_error']?.toString() ?? '',
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class GroupTargetStatus {
  final int index;
  final String groupId;
  final String groupName;
  final String status;
  final String error;

  const GroupTargetStatus({
    required this.index,
    required this.groupId,
    required this.groupName,
    required this.status,
    required this.error,
  });

  factory GroupTargetStatus.fromJson(Map<String, dynamic> json) {
    return GroupTargetStatus(
      index: (json['index'] as num?)?.toInt() ?? 0,
      groupId: json['group_id']?.toString() ?? '',
      groupName: json['group_name']?.toString() ?? '',
      status: json['status']?.toString() ?? 'failed',
      error: json['error']?.toString() ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'index': index,
      'group_id': groupId,
      'group_name': groupName,
      'status': status,
      'error': error,
    };
  }
}

class GroupSenderStatusDetail {
  final GroupSenderStatusSummary summary;
  final List<GroupTargetStatus> items;

  const GroupSenderStatusDetail({
    required this.summary,
    required this.items,
  });

  factory GroupSenderStatusDetail.fromJson(Map<String, dynamic> json) {
    return GroupSenderStatusDetail(
      summary: GroupSenderStatusSummary.fromJson(
        Map<String, dynamic>.from((json['summary'] as Map?) ?? const {}),
      ),
      items: ((json['items'] as List?) ?? const [])
          .whereType<Map>()
          .map(
            (item) => GroupTargetStatus.fromJson(
              Map<String, dynamic>.from(item),
            ),
          )
          .toList(),
    );
  }

  int get sentCount =>
      items.where((item) => item.status.toLowerCase() == 'sent').length;

  int get failedCount =>
      items.where((item) => item.status.toLowerCase() == 'failed').length;

  int get pendingCount =>
      items.where((item) => item.status.toLowerCase() == 'queued').length;
}
