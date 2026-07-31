import 'package:flutter/material.dart';
import '../../models/node_model.dart';
import '../../models/connection_model.dart';
import 'connection_painter.dart';
import 'node_widget.dart';

class InfiniteCanvas extends StatelessWidget {
  final List<FlowNode> nodes;
  final List<FlowConnection> connections;
  final String? selectedNodeId;
  final Function(String nodeId, Offset newPosition)? onNodeMoved;
  final Function(String nodeId)? onNodeTapped;
  final Function(String nodeId)? onNodeDeleted;
  final VoidCallback? onCanvasTapped;
  final Function(String sourceNodeId, String sourcePort)? onButtonPortTap;

  const InfiniteCanvas({
    super.key,
    required this.nodes,
    required this.connections,
    this.selectedNodeId,
    this.onNodeMoved,
    this.onNodeTapped,
    this.onNodeDeleted,
    this.onCanvasTapped,
    this.onButtonPortTap,
  });

  @override
  Widget build(BuildContext context) {
    return InteractiveViewer(
      boundaryMargin: const EdgeInsets.all(double.infinity),
      minScale: 0.1,
      maxScale: 2.0,
      constrained: false,
      child: GestureDetector(
        onTap: onCanvasTapped,
        child: SizedBox(
          width: 10000,
          height: 10000,
          child: Stack(
            children: [
              // Draw Background
              Positioned.fill(
                child: Container(
                  color: const Color(0xFF1E112A), // Dark Violet
                ),
              ),

              // Draw Wires (behind nodes)
              CustomPaint(
                size: const Size(10000, 10000),
                painter: ConnectionPainter(nodes: nodes, connections: connections),
              ),

              // Draw Nodes
              ...nodes.map((node) {
                return Positioned(
                  left: node.x,
                  top: node.y,
                  child: GestureDetector(
                    onPanUpdate: (details) {
                      final newPos = Offset(node.x + details.delta.dx, node.y + details.delta.dy);
                      onNodeMoved?.call(node.uuid, newPos);
                    },
                    child: Container(
                      decoration: BoxDecoration(
                        border: selectedNodeId == node.uuid
                            ? Border.all(color: Colors.blue, width: 3)
                            : Border.all(color: Colors.transparent, width: 3),
                        borderRadius: BorderRadius.circular(15),
                      ),
                      child: NodeWidget(
                        node: node,
                        onButtonPortTap: onButtonPortTap,
                        onSettingsTap: () => onNodeTapped?.call(node.uuid),
                        onDeleteTap: () => onNodeDeleted?.call(node.uuid),
                      ),
                    ),
                  ),
                );
              }),
            ],
          ),
        ),
      ),
    );
  }
}
