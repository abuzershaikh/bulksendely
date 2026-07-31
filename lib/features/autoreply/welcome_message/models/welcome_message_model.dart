enum WelcomeMessageStepType { text, media, template }

enum WelcomeMessageMediaType { image, video, audio, document }

class WelcomeMessageStep {
  final String id;
  final WelcomeMessageStepType type;
  final String text;
  final WelcomeMessageMediaType? mediaType;
  final String mediaUrl;
  final String filename;
  final String templateId;
  final String templateName;

  const WelcomeMessageStep({
    required this.id,
    required this.type,
    this.text = '',
    this.mediaType,
    this.mediaUrl = '',
    this.filename = '',
    this.templateId = '',
    this.templateName = '',
  });

  WelcomeMessageStep copyWith({
    String? id,
    WelcomeMessageStepType? type,
    String? text,
    WelcomeMessageMediaType? mediaType,
    String? mediaUrl,
    String? filename,
    String? templateId,
    String? templateName,
  }) {
    return WelcomeMessageStep(
      id: id ?? this.id,
      type: type ?? this.type,
      text: text ?? this.text,
      mediaType: mediaType ?? this.mediaType,
      mediaUrl: mediaUrl ?? this.mediaUrl,
      filename: filename ?? this.filename,
      templateId: templateId ?? this.templateId,
      templateName: templateName ?? this.templateName,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'type': type.name,
      'text': text,
      'mediaType': mediaType?.name,
      'mediaUrl': mediaUrl,
      'filename': filename,
      'templateId': templateId,
      'templateName': templateName,
    };
  }

  factory WelcomeMessageStep.fromJson(Map<String, dynamic> json) {
    final typeName = json['type']?.toString() ?? WelcomeMessageStepType.text.name;
    final mediaTypeName = json['mediaType']?.toString();

    return WelcomeMessageStep(
      id: json['id']?.toString() ?? '',
      type: WelcomeMessageStepType.values.firstWhere(
        (value) => value.name == typeName,
        orElse: () => WelcomeMessageStepType.text,
      ),
      text: json['text']?.toString() ?? '',
      mediaType: mediaTypeName == null
          ? null
          : WelcomeMessageMediaType.values.firstWhere(
              (value) => value.name == mediaTypeName,
              orElse: () => WelcomeMessageMediaType.image,
            ),
      mediaUrl: json['mediaUrl']?.toString() ?? '',
      filename: json['filename']?.toString() ?? '',
      templateId: json['templateId']?.toString() ?? '',
      templateName: json['templateName']?.toString() ?? '',
    );
  }
}

class WelcomeMessageFlow {
  final String id;
  final String name;
  final bool isActive;
  final List<WelcomeMessageStep> steps;
  final int createdAt;
  final String? serverId;

  const WelcomeMessageFlow({
    required this.id,
    required this.name,
    required this.isActive,
    required this.steps,
    required this.createdAt,
    this.serverId,
  });

  WelcomeMessageFlow copyWith({
    String? id,
    String? name,
    bool? isActive,
    List<WelcomeMessageStep>? steps,
    int? createdAt,
    String? serverId,
  }) {
    return WelcomeMessageFlow(
      id: id ?? this.id,
      name: name ?? this.name,
      isActive: isActive ?? this.isActive,
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
      'steps': steps.map((step) => step.toJson()).toList(),
      'createdAt': createdAt,
      'serverId': serverId,
    };
  }

  factory WelcomeMessageFlow.fromJson(Map<String, dynamic> json) {
    return WelcomeMessageFlow(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      isActive: json['isActive'] == true,
      steps: ((json['steps'] as List?) ?? [])
          .whereType<Map>()
          .map((step) => WelcomeMessageStep.fromJson(Map<String, dynamic>.from(step)))
          .toList(),
      createdAt: (json['createdAt'] as num?)?.toInt() ?? 0,
      serverId: json['serverId']?.toString(),
    );
  }
}
