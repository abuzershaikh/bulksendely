class CampaignStatusSummary {
  final String id;
  final String campaignName;
  final String targetName;
  final int targetCount;
  final int sentCount;
  final int failedCount;
  final String messageMode;
  final String messageLabel;
  final int delaySeconds;
  final String instanceId;
  final String createdAt;
  final int scheduledAt;

  bool get isScheduled => scheduledAt > 0;

  const CampaignStatusSummary({
    required this.id,
    required this.campaignName,
    required this.targetName,
    required this.targetCount,
    required this.sentCount,
    required this.failedCount,
    required this.messageMode,
    required this.messageLabel,
    required this.delaySeconds,
    required this.instanceId,
    required this.createdAt,
    this.scheduledAt = 0,
  });

  factory CampaignStatusSummary.fromJson(Map<String, dynamic> json) {
    final meta = json['meta'] as Map?;
    final int scheduledAt = (meta?['schedule_at'] as num?)?.toInt() ?? 0;
    return CampaignStatusSummary(
      id: json['id']?.toString() ?? '',
      campaignName: json['campaign_name']?.toString() ?? '',
      targetName: json['target_name']?.toString() ?? '',
      targetCount: (json['target_count'] as num?)?.toInt() ?? 0,
      sentCount: (json['sent_count'] as num?)?.toInt() ?? 0,
      failedCount: (json['failed_count'] as num?)?.toInt() ?? 0,
      messageMode: json['message_mode']?.toString() ?? '',
      messageLabel: json['message_label']?.toString() ?? '',
      delaySeconds: (json['delay_seconds'] as num?)?.toInt() ?? 0,
      instanceId: json['instance_id']?.toString() ?? '',
      createdAt: json['created_at']?.toString() ?? '',
      scheduledAt: scheduledAt,
    );
  }
}

class CampaignRecipientStatus {
  final int index;
  final String name;
  final String number;
  final String status;
  final String error;

  const CampaignRecipientStatus({
    required this.index,
    required this.name,
    required this.number,
    required this.status,
    required this.error,
  });

  factory CampaignRecipientStatus.fromJson(Map<String, dynamic> json) {
    return CampaignRecipientStatus(
      index: (json['index'] as num?)?.toInt() ?? 0,
      name: json['name']?.toString() ?? '',
      number: json['number']?.toString() ?? '',
      status: json['status']?.toString() ?? 'failed',
      error: json['error']?.toString() ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'index': index,
      'name': name,
      'number': number,
      'status': status,
      'error': error,
    };
  }
}

class CampaignStatusDetail {
  final CampaignStatusSummary summary;
  final List<CampaignRecipientStatus> items;

  const CampaignStatusDetail({
    required this.summary,
    required this.items,
  });

  factory CampaignStatusDetail.fromJson(Map<String, dynamic> json) {
    return CampaignStatusDetail(
      summary: CampaignStatusSummary.fromJson(
        Map<String, dynamic>.from((json['summary'] as Map?) ?? const {}),
      ),
      items: ((json['items'] as List?) ?? const [])
          .whereType<Map>()
          .map(
            (item) => CampaignRecipientStatus.fromJson(
              Map<String, dynamic>.from(item),
            ),
          )
          .toList(),
    );
  }
}
