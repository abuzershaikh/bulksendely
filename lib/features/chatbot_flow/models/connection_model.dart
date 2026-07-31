class FlowConnection {
  final String uuid;
  final String sourceNodeId;
  final String sourcePort; // e.g., 'button_1', 'success', 'fail'
  final String destNodeId;
  final String destPort; // usually 'input'

  FlowConnection({
    required this.uuid,
    required this.sourceNodeId,
    required this.sourcePort,
    required this.destNodeId,
    this.destPort = 'input',
  });

  factory FlowConnection.fromJson(Map<String, dynamic> json) {
    return FlowConnection(
      uuid: json['uuid'] ?? '',
      sourceNodeId: json['sourceNodeId'] ?? '',
      sourcePort: json['sourcePort'] ?? '',
      destNodeId: json['destNodeId'] ?? '',
      destPort: json['destPort'] ?? 'input',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'uuid': uuid,
      'sourceNodeId': sourceNodeId,
      'sourcePort': sourcePort,
      'destNodeId': destNodeId,
      'destPort': destPort,
    };
  }
}
