class ChatbotFlow {
  final String uuid;
  final String name;
  final int version;
  final String status;
  final String canvasJson;
  final String runtimeJson;
  final DateTime? updatedAt;

  ChatbotFlow({
    required this.uuid,
    required this.name,
    this.version = 1,
    this.status = 'Draft',
    this.canvasJson = '{}',
    this.runtimeJson = '{}',
    this.updatedAt,
  });

  factory ChatbotFlow.fromMap(Map<String, dynamic> map) {
    return ChatbotFlow(
      uuid: map['uuid'] ?? '',
      name: map['name'] ?? '',
      version: map['version'] ?? 1,
      status: map['status'] ?? 'Draft',
      canvasJson: map['canvas_json'] ?? '{}',
      runtimeJson: map['runtime_json'] ?? '{}',
      updatedAt: map['updated_at'] != null ? DateTime.fromMillisecondsSinceEpoch(map['updated_at']) : null,
    );
  }

  Map<String, dynamic> toMap() {
    return {
      'uuid': uuid,
      'name': name,
      'version': version,
      'status': status,
      'canvas_json': canvasJson,
      'runtime_json': runtimeJson,
      'updated_at': updatedAt != null ? updatedAt!.millisecondsSinceEpoch : DateTime.now().millisecondsSinceEpoch,
    };
  }
}

