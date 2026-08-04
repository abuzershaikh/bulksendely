enum OneShotRangeStatus {
  pending,
  partial,
  sent,
}

class OneShotRange {
  final int startIndex; // 1-based index (e.g. 1)
  final int endIndex; // 1-based index (e.g. 50)
  final int totalCount;
  final OneShotRangeStatus status;
  final int sentCount;
  final String? lastSentAt;

  const OneShotRange({
    required this.startIndex,
    required this.endIndex,
    required this.totalCount,
    this.status = OneShotRangeStatus.pending,
    this.sentCount = 0,
    this.lastSentAt,
  });

  String get label {
    final statusSuffix = status == OneShotRangeStatus.sent
        ? ' (Sent ✅)'
        : status == OneShotRangeStatus.partial
            ? ' (Partial $sentCount/$totalCount ⏳)'
            : '';
    return '$startIndex - $endIndex$statusSuffix';
  }

  OneShotRange copyWith({
    int? startIndex,
    int? endIndex,
    int? totalCount,
    OneShotRangeStatus? status,
    int? sentCount,
    String? lastSentAt,
  }) {
    return OneShotRange(
      startIndex: startIndex ?? this.startIndex,
      endIndex: endIndex ?? this.endIndex,
      totalCount: totalCount ?? this.totalCount,
      status: status ?? this.status,
      sentCount: sentCount ?? this.sentCount,
      lastSentAt: lastSentAt ?? this.lastSentAt,
    );
  }
}

class OneShotSettings {
  final int rangeSize; // e.g. 10, 20, 50, 100, 200
  final bool autoMarkSent;

  const OneShotSettings({
    this.rangeSize = 50,
    this.autoMarkSent = true,
  });

  Map<String, dynamic> toJson() => {
        'rangeSize': rangeSize,
        'autoMarkSent': autoMarkSent,
      };

  factory OneShotSettings.fromJson(Map<String, dynamic> json) =>
      OneShotSettings(
        rangeSize: (json['rangeSize'] as num?)?.toInt() ?? 50,
        autoMarkSent: json['autoMarkSent'] as bool? ?? true,
      );
}

class OneShotHistoryLog {
  final String id;
  final String groupId;
  final String groupName;
  final String rangeLabel;
  final int sentCount;
  final int totalCount;
  final String timestamp;

  const OneShotHistoryLog({
    required this.id,
    required this.groupId,
    required this.groupName,
    required this.rangeLabel,
    required this.sentCount,
    required this.totalCount,
    required this.timestamp,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'groupId': groupId,
        'groupName': groupName,
        'rangeLabel': rangeLabel,
        'sentCount': sentCount,
        'totalCount': totalCount,
        'timestamp': timestamp,
      };

  factory OneShotHistoryLog.fromJson(Map<String, dynamic> json) =>
      OneShotHistoryLog(
        id: json['id']?.toString() ?? '',
        groupId: json['groupId']?.toString() ?? '',
        groupName: json['groupName']?.toString() ?? '',
        rangeLabel: json['rangeLabel']?.toString() ?? '',
        sentCount: (json['sentCount'] as num?)?.toInt() ?? 0,
        totalCount: (json['totalCount'] as num?)?.toInt() ?? 0,
        timestamp: json['timestamp']?.toString() ?? '',
      );
}
