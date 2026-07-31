enum ButtonTemplateType { text, link, call, copy }

enum TemplateLibraryType { button, list }

class TemplateButton {
  final ButtonTemplateType type;
  final String displayText;
  final String? url;
  final String? phoneNumber;
  final String? copyText;

  TemplateButton({
    required this.type,
    required this.displayText,
    this.url,
    this.phoneNumber,
    this.copyText,
  });

  Map<String, dynamic> toJson() {
    return {
      'type': type.name,
      'displayText': displayText,
      'url': url,
      'phoneNumber': phoneNumber,
      'copyText': copyText,
    };
  }

  factory TemplateButton.fromJson(Map<String, dynamic> json) {
    final typeName = json['type']?.toString() ?? ButtonTemplateType.text.name;
    final type = ButtonTemplateType.values.firstWhere(
      (value) => value.name == typeName,
      orElse: () => ButtonTemplateType.text,
    );

    return TemplateButton(
      type: type,
      displayText: json['displayText']?.toString() ?? '',
      url: json['url']?.toString(),
      phoneNumber: json['phoneNumber']?.toString(),
      copyText: json['copyText']?.toString(),
    );
  }
}

class ListTemplateRow {
  final String id;
  final String title;
  final String description;

  const ListTemplateRow({
    required this.id,
    required this.title,
    this.description = '',
  });

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'description': description,
    };
  }

  factory ListTemplateRow.fromJson(Map<String, dynamic> json) {
    return ListTemplateRow(
      id: json['id']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
    );
  }
}

class ListTemplateSection {
  final String title;
  final List<ListTemplateRow> rows;

  const ListTemplateSection({
    required this.title,
    required this.rows,
  });

  Map<String, dynamic> toJson() {
    return {
      'title': title,
      'rows': rows.map((row) => row.toJson()).toList(),
    };
  }

  factory ListTemplateSection.fromJson(Map<String, dynamic> json) {
    return ListTemplateSection(
      title: json['title']?.toString() ?? '',
      rows: ((json['rows'] as List?) ?? [])
          .whereType<Map>()
          .map((row) => ListTemplateRow.fromJson(Map<String, dynamic>.from(row)))
          .toList(),
    );
  }
}

class ButtonTemplateModel {
  final String id;
  final String? serverId;
  final TemplateLibraryType templateType;
  final String name;
  final String title; // Bold top text
  final String caption; // Main text
  final String footer; // Small bottom text
  final String? imageUrl; // Header image URL
  final List<TemplateButton> buttons; // Up to 3
  final String listButtonText;
  final List<ListTemplateSection> sections;

  ButtonTemplateModel({
    required this.id,
    this.serverId,
    this.templateType = TemplateLibraryType.button,
    required this.name,
    required this.title,
    required this.caption,
    required this.footer,
    this.imageUrl,
    required this.buttons,
    this.listButtonText = '',
    this.sections = const [],
  });

  ButtonTemplateModel copyWith({
    String? id,
    String? serverId,
    TemplateLibraryType? templateType,
    String? name,
    String? title,
    String? caption,
    String? footer,
    String? imageUrl,
    List<TemplateButton>? buttons,
    String? listButtonText,
    List<ListTemplateSection>? sections,
  }) {
    return ButtonTemplateModel(
      id: id ?? this.id,
      serverId: serverId ?? this.serverId,
      templateType: templateType ?? this.templateType,
      name: name ?? this.name,
      title: title ?? this.title,
      caption: caption ?? this.caption,
      footer: footer ?? this.footer,
      imageUrl: imageUrl ?? this.imageUrl,
      buttons: buttons ?? this.buttons,
      listButtonText: listButtonText ?? this.listButtonText,
      sections: sections ?? this.sections,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'serverId': serverId,
      'templateType': templateType.name,
      'name': name,
      'title': title,
      'caption': caption,
      'footer': footer,
      'imageUrl': imageUrl,
      'buttons': buttons.map((button) => button.toJson()).toList(),
      'listButtonText': listButtonText,
      'sections': sections.map((section) => section.toJson()).toList(),
    };
  }

  factory ButtonTemplateModel.fromJson(Map<String, dynamic> json) {
    final templateTypeName =
        json['templateType']?.toString() ?? TemplateLibraryType.button.name;
    final templateType = TemplateLibraryType.values.firstWhere(
      (value) => value.name == templateTypeName,
      orElse: () => TemplateLibraryType.button,
    );

    return ButtonTemplateModel(
      id: json['id']?.toString() ?? '',
      serverId: json['serverId']?.toString(),
      templateType: templateType,
      name: json['name']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      caption: json['caption']?.toString() ?? '',
      footer: json['footer']?.toString() ?? '',
      imageUrl: json['imageUrl']?.toString(),
      buttons: ((json['buttons'] as List?) ?? [])
          .whereType<Map>()
          .map((button) => TemplateButton.fromJson(Map<String, dynamic>.from(button)))
          .toList(),
      listButtonText: json['listButtonText']?.toString() ?? '',
      sections: ((json['sections'] as List?) ?? [])
          .whereType<Map>()
          .map((section) => ListTemplateSection.fromJson(Map<String, dynamic>.from(section)))
          .toList(),
    );
  }
}
