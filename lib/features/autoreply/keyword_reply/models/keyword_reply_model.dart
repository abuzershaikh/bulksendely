enum KeywordReplyStepType { text, template }

class KeywordReplyStep {
  final String id;
  final KeywordReplyStepType type;
  final String text;
  final String templateId;
  final String templateName;

  const KeywordReplyStep({
    required this.id,
    required this.type,
    this.text = '',
    this.templateId = '',
    this.templateName = '',
  });

  KeywordReplyStep copyWith({
    String? id,
    KeywordReplyStepType? type,
    String? text,
    String? templateId,
    String? templateName,
  }) {
    return KeywordReplyStep(
      id: id ?? this.id,
      type: type ?? this.type,
      text: text ?? this.text,
      templateId: templateId ?? this.templateId,
      templateName: templateName ?? this.templateName,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'type': type.name,
      'text': text,
      'templateId': templateId,
      'templateName': templateName,
    };
  }

  factory KeywordReplyStep.fromJson(Map<String, dynamic> json) {
    final typeName = json['type']?.toString() ?? KeywordReplyStepType.text.name;
    return KeywordReplyStep(
      id: json['id']?.toString() ?? '',
      type: KeywordReplyStepType.values.firstWhere(
        (value) => value.name == typeName,
        orElse: () => KeywordReplyStepType.text,
      ),
      text: json['text']?.toString() ?? '',
      templateId: json['templateId']?.toString() ?? '',
      templateName: json['templateName']?.toString() ?? '',
    );
  }
}

class KeywordReplyFlow {
  final String id;
  final String name;
  final bool isActive;
  final List<String> keywords;
  final List<KeywordReplyStep> steps;
  final int createdAt;
  final String? serverId;

  const KeywordReplyFlow({
    required this.id,
    required this.name,
    required this.isActive,
    required this.keywords,
    required this.steps,
    required this.createdAt,
    this.serverId,
  });

  KeywordReplyFlow copyWith({
    String? id,
    String? name,
    bool? isActive,
    List<String>? keywords,
    List<KeywordReplyStep>? steps,
    int? createdAt,
    String? serverId,
  }) {
    return KeywordReplyFlow(
      id: id ?? this.id,
      name: name ?? this.name,
      isActive: isActive ?? this.isActive,
      keywords: keywords ?? this.keywords,
      steps: steps ?? this.steps,
      createdAt: createdAt ?? this.createdAt,
      serverId: serverId ?? this.serverId,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'isActive': isActive,
      'keywords': keywords,
      'steps': steps.map((step) => step.toJson()).toList(),
      'createdAt': createdAt,
      'serverId': serverId,
    };
  }

  factory KeywordReplyFlow.fromJson(Map<String, dynamic> json) {
    return KeywordReplyFlow(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      isActive: json['isActive'] == true,
      keywords: ((json['keywords'] as List?) ?? [])
          .map((item) => item.toString().trim())
          .where((item) => item.isNotEmpty)
          .toList(),
      steps: ((json['steps'] as List?) ?? [])
          .whereType<Map>()
          .map((step) => KeywordReplyStep.fromJson(Map<String, dynamic>.from(step)))
          .toList(),
      createdAt: (json['createdAt'] as num?)?.toInt() ?? 0,
      serverId: json['serverId']?.toString(),
    );
  }
}
