import 'package:autoreply/features/subscription/models/subscription_plan.dart';
import 'package:cloud_firestore/cloud_firestore.dart';

class AppUserSubscription {
  static const int freeMessageLimit = 10;
  static const int freeAutoReplyLimit = 5;

  final String uid;
  final String email;
  final String name;
  final String photoUrl;
  final SubscriptionPlan plan;
  final SubscriptionStatus status;
  final int totalMessagesSent;
  final int freeMessagesUsed;
  final String subscriptionExpiresAt;
  final DateTime? premiumActivatedAt;
  final DateTime? trialStartedAt;
  final DateTime? trialEndsAt;
  final bool trialOver;
  final String trialStatusMessage;
  final String currentSessionId;
  final int sessionCount;
  final String waziperAccessToken;
  final String waziperTeamId;
  final String waziperUserId;
  final String waziperUsername;
  final DateTime? waziperProvisionedAt;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  const AppUserSubscription({
    required this.uid,
    required this.email,
    required this.name,
    required this.photoUrl,
    required this.plan,
    required this.status,
    required this.totalMessagesSent,
    required this.freeMessagesUsed,
    required this.subscriptionExpiresAt,
    required this.premiumActivatedAt,
    required this.trialStartedAt,
    required this.trialEndsAt,
    required this.trialOver,
    required this.trialStatusMessage,
    required this.currentSessionId,
    required this.sessionCount,
    required this.waziperAccessToken,
    required this.waziperTeamId,
    required this.waziperUserId,
    required this.waziperUsername,
    required this.waziperProvisionedAt,
    required this.createdAt,
    required this.updatedAt,
  });

  bool get isPremium =>
      plan != SubscriptionPlan.free && status == SubscriptionStatus.active;

  int get freeMessagesLeft {
    final remaining = freeMessageLimit - freeMessagesUsed;
    return remaining > 0 ? remaining : 0;
  }

  String get planLabel => isPremium ? plan.label : 'Free';

  Map<String, dynamic> toFirestore() {
    return {
      'uid': uid,
      'email': email,
      'name': name,
      'photoUrl': photoUrl,
      'plan': plan.firestoreValue,
      'subscriptionStatus': status.firestoreValue,
      'totalMessagesSent': totalMessagesSent,
      'freeMessagesUsed': freeMessagesUsed,
      'freeMessagesLeft': freeMessagesLeft,
      'subscriptionExpiresAt': subscriptionExpiresAt,
      if (premiumActivatedAt != null)
        'premiumActivatedAt': Timestamp.fromDate(premiumActivatedAt!),
      if (trialStartedAt != null)
        'trialStartedAt': Timestamp.fromDate(trialStartedAt!),
      if (trialEndsAt != null) 'trialEndsAt': Timestamp.fromDate(trialEndsAt!),
      'trialOver': trialOver,
      'trialStatusMessage': trialStatusMessage,
      'currentSessionId': currentSessionId,
      'sessionCount': sessionCount,
      'waziperAccessToken': waziperAccessToken,
      'waziperTeamId': waziperTeamId,
      'waziperUserId': waziperUserId,
      'waziperUsername': waziperUsername,
      if (waziperProvisionedAt != null)
        'waziperProvisionedAt': Timestamp.fromDate(waziperProvisionedAt!),
      'updatedAt': FieldValue.serverTimestamp(),
      if (createdAt == null) 'createdAt': FieldValue.serverTimestamp(),
    };
  }

  AppUserSubscription copyWith({
    String? uid,
    String? email,
    String? name,
    String? photoUrl,
    SubscriptionPlan? plan,
    SubscriptionStatus? status,
    int? totalMessagesSent,
    int? freeMessagesUsed,
    String? subscriptionExpiresAt,
    DateTime? premiumActivatedAt,
    DateTime? trialStartedAt,
    DateTime? trialEndsAt,
    bool? trialOver,
    String? trialStatusMessage,
    String? currentSessionId,
    int? sessionCount,
    String? waziperAccessToken,
    String? waziperTeamId,
    String? waziperUserId,
    String? waziperUsername,
    DateTime? waziperProvisionedAt,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return AppUserSubscription(
      uid: uid ?? this.uid,
      email: email ?? this.email,
      name: name ?? this.name,
      photoUrl: photoUrl ?? this.photoUrl,
      plan: plan ?? this.plan,
      status: status ?? this.status,
      totalMessagesSent: totalMessagesSent ?? this.totalMessagesSent,
      freeMessagesUsed: freeMessagesUsed ?? this.freeMessagesUsed,
      subscriptionExpiresAt:
          subscriptionExpiresAt ?? this.subscriptionExpiresAt,
      premiumActivatedAt: premiumActivatedAt ?? this.premiumActivatedAt,
      trialStartedAt: trialStartedAt ?? this.trialStartedAt,
      trialEndsAt: trialEndsAt ?? this.trialEndsAt,
      trialOver: trialOver ?? this.trialOver,
      trialStatusMessage: trialStatusMessage ?? this.trialStatusMessage,
      currentSessionId: currentSessionId ?? this.currentSessionId,
      sessionCount: sessionCount ?? this.sessionCount,
      waziperAccessToken: waziperAccessToken ?? this.waziperAccessToken,
      waziperTeamId: waziperTeamId ?? this.waziperTeamId,
      waziperUserId: waziperUserId ?? this.waziperUserId,
      waziperUsername: waziperUsername ?? this.waziperUsername,
      waziperProvisionedAt: waziperProvisionedAt ?? this.waziperProvisionedAt,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  factory AppUserSubscription.fromFirestore(Map<String, dynamic> json) {
    DateTime? toDate(dynamic value) {
      if (value is Timestamp) {
        return value.toDate();
      }
      if (value is String && value.isNotEmpty) {
        return DateTime.tryParse(value)?.toLocal();
      }
      return null;
    }

    return AppUserSubscription(
      uid: json['uid']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      photoUrl: json['photoUrl']?.toString() ?? '',
      plan: subscriptionPlanFromString(json['plan']?.toString()),
      status: subscriptionStatusFromString(
        json['subscriptionStatus']?.toString(),
      ),
      totalMessagesSent: (json['totalMessagesSent'] as num?)?.toInt() ?? 0,
      freeMessagesUsed: (json['freeMessagesUsed'] as num?)?.toInt() ?? 0,
      subscriptionExpiresAt: json['subscriptionExpiresAt']?.toString() ?? '',
      premiumActivatedAt: toDate(json['premiumActivatedAt']),
      trialStartedAt: toDate(json['trialStartedAt']),
      trialEndsAt: toDate(json['trialEndsAt']),
      trialOver: json['trialOver'] == true,
      trialStatusMessage: json['trialStatusMessage']?.toString() ?? '',
      currentSessionId: json['currentSessionId']?.toString() ?? '',
      sessionCount: (json['sessionCount'] as num?)?.toInt() ?? 0,
      waziperAccessToken: json['waziperAccessToken']?.toString() ?? '',
      waziperTeamId: json['waziperTeamId']?.toString() ?? '',
      waziperUserId: json['waziperUserId']?.toString() ?? '',
      waziperUsername: json['waziperUsername']?.toString() ?? '',
      waziperProvisionedAt: toDate(json['waziperProvisionedAt']),
      createdAt: toDate(json['createdAt']),
      updatedAt: toDate(json['updatedAt']),
    );
  }
}
