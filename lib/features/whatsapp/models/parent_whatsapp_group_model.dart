
class ParentWhatsappGroupModel {
  final String id;
  final String name;
  final List<LinkedWhatsappGroupItem> linkedGroups;

  const ParentWhatsappGroupModel({
    required this.id,
    required this.name,
    required this.linkedGroups,
  });

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'linkedGroups': linkedGroups.map((g) => g.toJson()).toList(),
    };
  }

  factory ParentWhatsappGroupModel.fromJson(Map<String, dynamic> json) {
    return ParentWhatsappGroupModel(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      linkedGroups: (json['linkedGroups'] as List?)
              ?.map((g) => LinkedWhatsappGroupItem.fromJson(Map<String, dynamic>.from(g)))
              .toList() ??
          [],
    );
  }
}

class LinkedWhatsappGroupItem {
  final String instanceId;
  final String instanceName;
  final String instanceNumber;
  final String groupId;
  final String groupName;

  const LinkedWhatsappGroupItem({
    required this.instanceId,
    required this.instanceName,
    required this.instanceNumber,
    required this.groupId,
    required this.groupName,
  });

  Map<String, dynamic> toJson() {
    return {
      'instanceId': instanceId,
      'instanceName': instanceName,
      'instanceNumber': instanceNumber,
      'groupId': groupId,
      'groupName': groupName,
    };
  }

  factory LinkedWhatsappGroupItem.fromJson(Map<String, dynamic> json) {
    return LinkedWhatsappGroupItem(
      instanceId: json['instanceId']?.toString() ?? '',
      instanceName: json['instanceName']?.toString() ?? '',
      instanceNumber: json['instanceNumber']?.toString() ?? '',
      groupId: json['groupId']?.toString() ?? '',
      groupName: json['groupName']?.toString() ?? '',
    );
  }
}
