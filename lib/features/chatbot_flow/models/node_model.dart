class FlowNode {
  final String uuid;
  final String type; // 'template', 'condition', 'api', 'delay', 'go_to', 'return', 'human', 'ai', 'end'
  Map<String, dynamic> propertiesJson;
  String? colorHex; // Custom color for the node and its wires
  
  // Buttons list for template nodes - each button is an output port
  List<String> buttons;

  // UI Coordinates
  double x;
  double y;

  FlowNode({
    required this.uuid,
    required this.type,
    this.propertiesJson = const {},
    this.buttons = const [],
    this.colorHex,
    this.x = 0,
    this.y = 0,
  });

  factory FlowNode.fromJson(Map<String, dynamic> json) {
    return FlowNode(
      uuid: json['uuid'] ?? '',
      type: json['type'] ?? 'template',
      propertiesJson: Map<String, dynamic>.from(json['propertiesJson'] ?? {}),
      buttons: List<String>.from(json['buttons'] ?? []),
      colorHex: json['colorHex'],
      x: (json['x'] ?? 0).toDouble(),
      y: (json['y'] ?? 0).toDouble(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'uuid': uuid,
      'type': type,
      'propertiesJson': propertiesJson,
      'buttons': buttons,
      'colorHex': colorHex,
      'x': x,
      'y': y,
    };
  }
}
