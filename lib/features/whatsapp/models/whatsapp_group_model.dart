class WhatsappGroupModel {
  final String id;
  final String name;
  final String description;
  final int participantCount;
  final bool announce;
  final bool userIsAdmin;
  final bool isCommunity;
  final bool isCommunityAnnounce;
  final String? linkedParent;

  const WhatsappGroupModel({
    required this.id,
    required this.name,
    this.description = '',
    this.participantCount = 0,
    this.announce = false,
    this.userIsAdmin = false,
    this.isCommunity = false,
    this.isCommunityAnnounce = false,
    this.linkedParent,
  });

  bool get isSelectable => !announce || userIsAdmin;

  factory WhatsappGroupModel.fromJson(Map<String, dynamic> json) {
    final participants = json['participants'];
    final sizeValue = json['size'] ?? json['participantCount'];

    return WhatsappGroupModel(
      id: json['id']?.toString() ?? '',
      name:
          json['name']?.toString() ??
          json['subject']?.toString() ??
          'Unnamed Group',
      description:
          json['description']?.toString() ?? json['desc']?.toString() ?? '',
      participantCount: sizeValue is num
          ? sizeValue.toInt()
          : participants is List
          ? participants.length
          : 0,
      announce: json['announce'] == true,
      userIsAdmin: json['userIsAdmin'] == true,
      isCommunity: json['isCommunity'] == true,
      isCommunityAnnounce: json['isCommunityAnnounce'] == true,
      linkedParent: json['linkedParent']?.toString(),
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'description': description,
    'size': participantCount,
    'announce': announce,
    'userIsAdmin': userIsAdmin,
    'isCommunity': isCommunity,
    'isCommunityAnnounce': isCommunityAnnounce,
    'linkedParent': linkedParent,
  };
}
