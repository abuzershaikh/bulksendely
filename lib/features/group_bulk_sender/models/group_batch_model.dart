import 'package:autoreply/features/whatsapp/models/whatsapp_group_model.dart';

enum GroupBroadcastMessageMode { text, media }

enum GroupBroadcastMediaType { image, video, audio, document }

class GroupBatchModel {
  final String id;
  final String name;
  final List<WhatsappGroupModel> groups;
  final int createdAt;

  const GroupBatchModel({
    required this.id,
    required this.name,
    required this.groups,
    required this.createdAt,
  });

  GroupBatchModel copyWith({
    String? id,
    String? name,
    List<WhatsappGroupModel>? groups,
    int? createdAt,
  }) {
    return GroupBatchModel(
      id: id ?? this.id,
      name: name ?? this.name,
      groups: groups ?? this.groups,
      createdAt: createdAt ?? this.createdAt,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'groups': groups
          .map(
            (group) => {
              'id': group.id,
              'name': group.name,
              'description': group.description,
              'participantCount': group.participantCount,
              'announce': group.announce,
              'userIsAdmin': group.userIsAdmin,
              'isCommunity': group.isCommunity,
              'isCommunityAnnounce': group.isCommunityAnnounce,
              'linkedParent': group.linkedParent,
            },
          )
          .toList(),
      'createdAt': createdAt,
    };
  }

  factory GroupBatchModel.fromJson(Map<String, dynamic> json) {
    return GroupBatchModel(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      groups: ((json['groups'] as List?) ?? [])
          .whereType<Map>()
          .map((group) => WhatsappGroupModel.fromJson(Map<String, dynamic>.from(group)))
          .toList(),
      createdAt: (json['createdAt'] as num?)?.toInt() ?? 0,
    );
  }
}
