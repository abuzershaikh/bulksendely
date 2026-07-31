import 'package:flutter/material.dart';
import '../../models/node_model.dart';
import '../../models/connection_model.dart';
import 'node_layout.dart';

class ConnectionPainter extends CustomPainter {
  final List<FlowNode> nodes;
  final List<FlowConnection> connections;

  ConnectionPainter({required this.nodes, required this.connections});

  @override
  void paint(Canvas canvas, Size size) {
    for (var conn in connections) {
      try {
        final sourceNode = nodes.firstWhere((n) => n.uuid == conn.sourceNodeId);
        final destNode = nodes.firstWhere((n) => n.uuid == conn.destNodeId);
        
        final wireColor = _getNodeColor(sourceNode);

        final wirePaint = Paint()
          ..color = wireColor
          ..strokeWidth = 2.5
          ..style = PaintingStyle.stroke;

        final dotPaint = Paint()
          ..color = wireColor
          ..style = PaintingStyle.fill;

        // --- START: exact position of the "+" button on the source node ---
        final btnIndex = int.tryParse(conn.sourcePort.replaceFirst('btn_', '')) ?? 0;
        final hasConnectedFrom = (sourceNode.propertiesJson['connectedFrom'] as String? ?? '').isNotEmpty;

        final portY = NodeLayout.getButtonPortY(btnIndex, hasConnectedFrom: hasConnectedFrom);
        final portX = NodeLayout.getButtonPortX();

        final startOffset = Offset(sourceNode.x + portX, sourceNode.y + portY);

        // --- END: top-center of destination node ---
        final destHasConnectedFrom = (destNode.propertiesJson['connectedFrom'] as String? ?? '').isNotEmpty;
        final inputPort = NodeLayout.getInputPort(hasConnectedFrom: destHasConnectedFrom);
        final endOffset = Offset(destNode.x + inputPort.dx, destNode.y + inputPort.dy);

        // --- Draw bezier curve ---
        final path = Path();
        path.moveTo(startOffset.dx, startOffset.dy);

        // Smooth curve from right side going down to top of next node
        final midY = (startOffset.dy + endOffset.dy) / 2;
        final curveOut = 80.0; // how far the curve goes right before turning down

        path.cubicTo(
          startOffset.dx + curveOut, startOffset.dy,  // control 1: go right
          endOffset.dx, midY,                          // control 2: come from above
          endOffset.dx, endOffset.dy,                  // end point
        );

        canvas.drawPath(path, wirePaint);

        // Draw filled dot at start (on the + button)
        canvas.drawCircle(startOffset, 5, dotPaint);

        // Draw arrow at end (pointing down into the node)
        _drawArrow(canvas, endOffset, wireColor);

      } catch (e) {
        // Node deleted or not found
      }
    }
  }

  Color _getNodeColor(FlowNode node) {
    if (node.colorHex != null && node.colorHex!.isNotEmpty) {
      try {
        final hexCode = node.colorHex!.replaceAll('#', '');
        return Color(int.parse('FF$hexCode', radix: 16));
      } catch (_) {}
    }
    switch (node.type) {
      case 'template': return const Color(0xFF00A884);
      case 'condition': return Colors.orange;
      case 'api': return Colors.purple;
      case 'delay': return Colors.blue;
      case 'go_to': return Colors.teal;
      case 'end': return Colors.red;
      default: return Colors.grey;
    }
  }

  void _drawArrow(Canvas canvas, Offset tip, Color color) {
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.fill;

    final path = Path();
    path.moveTo(tip.dx, tip.dy);
    path.lineTo(tip.dx - 7, tip.dy - 12);
    path.lineTo(tip.dx + 7, tip.dy - 12);
    path.close();
    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant ConnectionPainter oldDelegate) => true;
}
