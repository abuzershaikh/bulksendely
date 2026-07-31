class ContactModel {
  final String name;
  final String number; // Must be normalized WhatsApp format if possible

  ContactModel({
    required this.name,
    required this.number,
  });

  Map<String, dynamic> toJson() {
    return {
      'name': name,
      'number': number,
    };
  }

  factory ContactModel.fromJson(Map<String, dynamic> json) {
    return ContactModel(
      name: json['name']?.toString() ?? '',
      number: json['number']?.toString() ?? '',
    );
  }
}

class ContactGroupModel {
  final String id;
  final String? serverId;
  final String name;
  final List<ContactModel> contacts;
  final DateTime createdAt;
  final String source; // e.g. "Phone", "CSV", "Text"

  ContactGroupModel({
    required this.id,
    this.serverId,
    required this.name,
    required this.contacts,
    required this.createdAt,
    required this.source,
  });

  ContactGroupModel copyWith({
    String? id,
    String? serverId,
    String? name,
    List<ContactModel>? contacts,
    DateTime? createdAt,
    String? source,
  }) {
    return ContactGroupModel(
      id: id ?? this.id,
      serverId: serverId ?? this.serverId,
      name: name ?? this.name,
      contacts: contacts ?? this.contacts,
      createdAt: createdAt ?? this.createdAt,
      source: source ?? this.source,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'serverId': serverId,
      'name': name,
      'contacts': contacts.map((contact) => contact.toJson()).toList(),
      'createdAt': createdAt.toIso8601String(),
      'source': source,
    };
  }

  factory ContactGroupModel.fromJson(Map<String, dynamic> json) {
    return ContactGroupModel(
      id: json['id']?.toString() ?? '',
      serverId: json['serverId']?.toString(),
      name: json['name']?.toString() ?? '',
      contacts: ((json['contacts'] as List?) ?? [])
          .whereType<Map>()
          .map((contact) => ContactModel.fromJson(Map<String, dynamic>.from(contact)))
          .toList(),
      createdAt: DateTime.tryParse(json['createdAt']?.toString() ?? '') ?? DateTime.now(),
      source: json['source']?.toString() ?? '',
    );
  }
}
