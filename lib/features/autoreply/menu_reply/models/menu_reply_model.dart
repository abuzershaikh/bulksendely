enum MenuReplyActionType { openNode, sendReply }

enum MenuReplyStepType { text, template, media }

enum MenuReplyMediaType { image, video, audio, document }

class MenuReplyStep {
  final String id;
  final MenuReplyStepType type;
  final String text;
  final String templateId;
  final String templateName;
  final MenuReplyMediaType? mediaType;
  final String mediaUrl;
  final String filename;

  const MenuReplyStep({
    required this.id,
    required this.type,
    this.text = '',
    this.templateId = '',
    this.templateName = '',
    this.mediaType,
    this.mediaUrl = '',
    this.filename = '',
  });

  MenuReplyStep copyWith({
    String? id,
    MenuReplyStepType? type,
    String? text,
    String? templateId,
    String? templateName,
    MenuReplyMediaType? mediaType,
    String? mediaUrl,
    String? filename,
  }) {
    return MenuReplyStep(
      id: id ?? this.id,
      type: type ?? this.type,
      text: text ?? this.text,
      templateId: templateId ?? this.templateId,
      templateName: templateName ?? this.templateName,
      mediaType: mediaType ?? this.mediaType,
      mediaUrl: mediaUrl ?? this.mediaUrl,
      filename: filename ?? this.filename,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'type': type.name,
      'text': text,
      'templateId': templateId,
      'templateName': templateName,
      'mediaType': mediaType?.name,
      'mediaUrl': mediaUrl,
      'filename': filename,
    };
  }

  factory MenuReplyStep.fromJson(Map<String, dynamic> json) {
    final typeName = json['type']?.toString() ?? MenuReplyStepType.text.name;
    final mediaTypeName = json['mediaType']?.toString();

    return MenuReplyStep(
      id: json['id']?.toString() ?? '',
      type: MenuReplyStepType.values.firstWhere(
        (value) => value.name == typeName,
        orElse: () => MenuReplyStepType.text,
      ),
      text: json['text']?.toString() ?? '',
      templateId: json['templateId']?.toString() ?? '',
      templateName: json['templateName']?.toString() ?? '',
      mediaType: mediaTypeName == null
          ? null
          : MenuReplyMediaType.values.firstWhere(
              (value) => value.name == mediaTypeName,
              orElse: () => MenuReplyMediaType.image,
            ),
      mediaUrl: json['mediaUrl']?.toString() ?? '',
      filename: json['filename']?.toString() ?? '',
    );
  }
}

class MenuReplyAction {
  final MenuReplyActionType type;
  final String nextNodeId;
  final List<MenuReplyStep> steps;

  const MenuReplyAction({
    required this.type,
    this.nextNodeId = '',
    this.steps = const [],
  });

  MenuReplyAction copyWith({
    MenuReplyActionType? type,
    String? nextNodeId,
    List<MenuReplyStep>? steps,
  }) {
    return MenuReplyAction(
      type: type ?? this.type,
      nextNodeId: nextNodeId ?? this.nextNodeId,
      steps: steps ?? this.steps,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'type': type.name,
      'nextNodeId': nextNodeId,
      'steps': steps.map((step) => step.toJson()).toList(),
    };
  }

  factory MenuReplyAction.fromJson(Map<String, dynamic> json) {
    final typeName =
        json['type']?.toString() ?? MenuReplyActionType.sendReply.name;
    return MenuReplyAction(
      type: MenuReplyActionType.values.firstWhere(
        (value) => value.name == typeName,
        orElse: () => MenuReplyActionType.sendReply,
      ),
      nextNodeId: json['nextNodeId']?.toString() ?? '',
      steps: ((json['steps'] as List?) ?? [])
          .whereType<Map>()
          .map(
            (step) => MenuReplyStep.fromJson(Map<String, dynamic>.from(step)),
          )
          .toList(),
    );
  }
}

class MenuReplyRow {
  final String id;
  final String title;
  final String description;
  final MenuReplyAction action;

  const MenuReplyRow({
    required this.id,
    required this.title,
    this.description = '',
    required this.action,
  });

  MenuReplyRow copyWith({
    String? id,
    String? title,
    String? description,
    MenuReplyAction? action,
  }) {
    return MenuReplyRow(
      id: id ?? this.id,
      title: title ?? this.title,
      description: description ?? this.description,
      action: action ?? this.action,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'description': description,
      'action': action.toJson(),
    };
  }

  factory MenuReplyRow.fromJson(Map<String, dynamic> json) {
    return MenuReplyRow(
      id: json['id']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      action: MenuReplyAction.fromJson(
        Map<String, dynamic>.from((json['action'] as Map?) ?? const {}),
      ),
    );
  }
}

class MenuReplyNode {
  final String id;
  final String name;
  final String title;
  final String body;
  final String footer;
  final String buttonText;
  final List<MenuReplyRow> rows;

  const MenuReplyNode({
    required this.id,
    required this.name,
    this.title = '',
    this.body = '',
    this.footer = '',
    this.buttonText = '',
    this.rows = const [],
  });

  MenuReplyNode copyWith({
    String? id,
    String? name,
    String? title,
    String? body,
    String? footer,
    String? buttonText,
    List<MenuReplyRow>? rows,
  }) {
    return MenuReplyNode(
      id: id ?? this.id,
      name: name ?? this.name,
      title: title ?? this.title,
      body: body ?? this.body,
      footer: footer ?? this.footer,
      buttonText: buttonText ?? this.buttonText,
      rows: rows ?? this.rows,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'title': title,
      'body': body,
      'footer': footer,
      'buttonText': buttonText,
      'rows': rows.map((row) => row.toJson()).toList(),
    };
  }

  factory MenuReplyNode.fromJson(Map<String, dynamic> json) {
    return MenuReplyNode(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      body: json['body']?.toString() ?? '',
      footer: json['footer']?.toString() ?? '',
      buttonText: json['buttonText']?.toString() ?? '',
      rows: ((json['rows'] as List?) ?? [])
          .whereType<Map>()
          .map((row) => MenuReplyRow.fromJson(Map<String, dynamic>.from(row)))
          .toList(),
    );
  }
}

class MenuReplyFlow {
  final String id;
  final String name;
  final bool isActive;
  final List<String> keywords;
  final String rootNodeId;
  final List<MenuReplyNode> nodes;
  final int createdAt;
  final String? serverId;

  const MenuReplyFlow({
    required this.id,
    required this.name,
    required this.isActive,
    required this.keywords,
    required this.rootNodeId,
    required this.nodes,
    required this.createdAt,
    this.serverId,
  });

  MenuReplyFlow copyWith({
    String? id,
    String? name,
    bool? isActive,
    List<String>? keywords,
    String? rootNodeId,
    List<MenuReplyNode>? nodes,
    int? createdAt,
    String? serverId,
  }) {
    return MenuReplyFlow(
      id: id ?? this.id,
      name: name ?? this.name,
      isActive: isActive ?? this.isActive,
      keywords: keywords ?? this.keywords,
      rootNodeId: rootNodeId ?? this.rootNodeId,
      nodes: nodes ?? this.nodes,
      createdAt: createdAt ?? this.createdAt,
      serverId: serverId ?? this.serverId,
    );
  }

  MenuReplyNode? get rootNode {
    for (final node in nodes) {
      if (node.id == rootNodeId) return node;
    }
    return nodes.isEmpty ? null : nodes.first;
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'isActive': isActive,
      'keywords': keywords,
      'rootNodeId': rootNodeId,
      'nodes': nodes.map((node) => node.toJson()).toList(),
      'createdAt': createdAt,
      'serverId': serverId,
    };
  }

  factory MenuReplyFlow.fromJson(Map<String, dynamic> json) {
    return MenuReplyFlow(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      isActive: json['isActive'] == true,
      keywords: ((json['keywords'] as List?) ?? [])
          .map((item) => item.toString().trim())
          .where((item) => item.isNotEmpty)
          .toList(),
      rootNodeId: json['rootNodeId']?.toString() ?? '',
      nodes: ((json['nodes'] as List?) ?? [])
          .whereType<Map>()
          .map(
            (node) => MenuReplyNode.fromJson(Map<String, dynamic>.from(node)),
          )
          .toList(),
      createdAt: (json['createdAt'] as num?)?.toInt() ?? 0,
      serverId: json['serverId']?.toString(),
    );
  }
}
