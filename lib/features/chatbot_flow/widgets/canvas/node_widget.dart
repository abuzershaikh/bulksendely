import 'package:flutter/material.dart';
import '../../models/node_model.dart';
import 'node_layout.dart';

class NodeWidget extends StatelessWidget {
  final FlowNode node;
  final Function(String nodeId, String buttonId)? onButtonPortTap;
  final VoidCallback? onSettingsTap;
  final VoidCallback? onDeleteTap;

  const NodeWidget({
    super.key,
    required this.node,
    this.onButtonPortTap,
    this.onSettingsTap,
    this.onDeleteTap,
  });

  @override
  Widget build(BuildContext context) {
    final connectedFrom = node.propertiesJson['connectedFrom'] as String? ?? '';

    return Container(
      width: NodeLayout.nodeWidth,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.12),
            blurRadius: 12,
            offset: const Offset(0, 4),
          )
        ],
        border: Border.all(color: Colors.grey.shade300),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // ── Header ── (FIXED: NodeLayout.headerHeight)
          SizedBox(
            height: NodeLayout.headerHeight,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              decoration: BoxDecoration(
                color: _getNodeColor().withOpacity(0.1),
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(12),
                  topRight: Radius.circular(12),
                ),
              ),
              child: Row(
                children: [
                  Icon(_getNodeIcon(), size: 14, color: _getNodeColor()),
                  const SizedBox(width: 6),
                  Text(
                    _getNodeLabel(),
                    style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: _getNodeColor()),
                  ),
                  const Spacer(),
                  InkWell(
                    onTap: onSettingsTap,
                    borderRadius: BorderRadius.circular(4),
                    child: Padding(
                      padding: const EdgeInsets.all(6.0),
                      child: Icon(Icons.settings_outlined, size: 16, color: _getNodeColor()),
                    ),
                  ),
                  const SizedBox(width: 4),
                  InkWell(
                    onTap: onDeleteTap,
                    borderRadius: BorderRadius.circular(4),
                    child: const Padding(
                      padding: EdgeInsets.all(6.0),
                      child: Icon(Icons.delete_outline, size: 16, color: Colors.red),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // ── Connected From Badge ── (FIXED: NodeLayout.connectedFromBadgeHeight or 0)
          if (connectedFrom.isNotEmpty)
            SizedBox(
              height: NodeLayout.connectedFromBadgeHeight,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                color: const Color(0xFF2196F3).withOpacity(0.08),
                child: Row(
                  children: [
                    const Icon(Icons.link_rounded, size: 14, color: Color(0xFF2196F3)),
                    const SizedBox(width: 6),
                    Text(
                      'From: $connectedFrom',
                      style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF2196F3)),
                    ),
                  ],
                ),
              ),
            ),

          // ── Content Area ── (FIXED: NodeLayout.contentHeight)
          SizedBox(
            height: NodeLayout.contentHeight,
            child: _buildNodeContent(),
          ),

          // ── Button Ports ── (FIXED: each NodeLayout.buttonRowHeight)
          if (node.buttons.isNotEmpty)
            ...node.buttons.asMap().entries.map((entry) {
              final idx = entry.key;
              final btnText = entry.value;
              return _buildButtonPort(btnText, 'btn_$idx');
            }),
        ],
      ),
    );
  }

  Widget _buildButtonPort(String buttonText, String portId) {
    final Color portColor = _getNodeColor(); // Match the node's color for ports and wire
    return SizedBox(
      height: NodeLayout.buttonRowHeight,
      child: InkWell(
        onTap: () {
          onButtonPortTap?.call(node.uuid, portId);
        },
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12),
          decoration: BoxDecoration(
            border: Border(top: BorderSide(color: Colors.grey.shade200)),
          ),
          child: Row(
            children: [
              Icon(Icons.reply_rounded, size: 14, color: portColor),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  buttonText,
                  style: TextStyle(color: portColor, fontWeight: FontWeight.w600, fontSize: 13),
                ),
              ),
              // The "+" connection port circle
              Container(
                width: NodeLayout.portCircleSize,
                height: NodeLayout.portCircleSize,
                decoration: BoxDecoration(
                  color: portColor.withOpacity(0.1),
                  shape: BoxShape.circle,
                  border: Border.all(color: portColor, width: 2),
                ),
                child: Icon(Icons.add, size: 14, color: portColor),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildNodeContent() {
    switch (node.type) {
      case 'template':
        return _buildTemplateContent();
      case 'condition':
        return _buildConditionContent();
      case 'api':
        return _buildApiContent();
      case 'delay':
        return _buildDelayContent();
      case 'go_to':
        return _buildGoToContent();
      case 'end':
        return _buildEndContent();
      default:
        return Center(child: Text(node.type.toUpperCase()));
    }
  }

  Widget _buildTemplateContent() {
    final title = node.propertiesJson['title'] as String? ?? 'Message';
    final caption = node.propertiesJson['caption'] as String? ?? '';
    final footer = node.propertiesJson['footer'] as String? ?? '';

    return Padding(
      padding: const EdgeInsets.all(10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          if (title.isNotEmpty)
            Text(title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14), maxLines: 1, overflow: TextOverflow.ellipsis),
          const SizedBox(height: 4),
          Flexible(
            child: Text(caption, style: const TextStyle(fontSize: 12, color: Colors.black87, height: 1.3), maxLines: 2, overflow: TextOverflow.ellipsis),
          ),
          if (footer.isNotEmpty)
            Text(footer, style: TextStyle(color: Colors.grey.shade500, fontSize: 11), maxLines: 1, overflow: TextOverflow.ellipsis),
        ],
      ),
    );
  }

  Widget _buildConditionContent() {
    return Padding(
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text('If {{${node.propertiesJson['conditionVar'] ?? 'city'}}}', style: const TextStyle(fontWeight: FontWeight.w600)),
          const SizedBox(height: 4),
          Text('${node.propertiesJson['conditionOp'] ?? '=='} "${node.propertiesJson['conditionVal'] ?? 'Mumbai'}"', style: TextStyle(color: Colors.grey.shade700)),
        ],
      ),
    );
  }

  Widget _buildApiContent() {
    return Padding(
      padding: const EdgeInsets.all(12),
      child: Row(children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
          decoration: BoxDecoration(color: Colors.purple.shade100, borderRadius: BorderRadius.circular(4)),
          child: Text(node.propertiesJson['method'] ?? 'GET', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.purple)),
        ),
        const SizedBox(width: 6),
        Expanded(child: Text(node.propertiesJson['url'] ?? '/api/data', style: const TextStyle(fontSize: 12), overflow: TextOverflow.ellipsis)),
      ]),
    );
  }

  Widget _buildDelayContent() {
    return Padding(
      padding: const EdgeInsets.all(12),
      child: Row(children: [
        const Icon(Icons.timer, size: 18, color: Colors.blue),
        const SizedBox(width: 8),
        Text('Wait ${node.propertiesJson['seconds'] ?? 5} sec', style: const TextStyle(fontWeight: FontWeight.w500)),
      ]),
    );
  }

  Widget _buildGoToContent() {
    return Padding(
      padding: const EdgeInsets.all(12),
      child: Row(children: [
        const Icon(Icons.shortcut_rounded, size: 18, color: Colors.teal),
        const SizedBox(width: 8),
        Expanded(child: Text('Go To: ${node.propertiesJson['flow_name'] ?? 'Select Flow'}', style: const TextStyle(fontWeight: FontWeight.w500), overflow: TextOverflow.ellipsis)),
      ]),
    );
  }

  Widget _buildEndContent() {
    return const Padding(
      padding: EdgeInsets.all(12),
      child: Row(children: [
        Icon(Icons.stop_circle_rounded, size: 18, color: Colors.red),
        SizedBox(width: 8),
        Text('End Flow', style: TextStyle(fontWeight: FontWeight.w500, color: Colors.red)),
      ]),
    );
  }

  Color _getNodeColor() {
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

  IconData _getNodeIcon() {
    switch (node.type) {
      case 'template': return Icons.message_rounded;
      case 'condition': return Icons.alt_route_rounded;
      case 'api': return Icons.api_rounded;
      case 'delay': return Icons.timer_rounded;
      case 'go_to': return Icons.shortcut_rounded;
      case 'end': return Icons.stop_circle_rounded;
      default: return Icons.widgets_rounded;
    }
  }

  String _getNodeLabel() {
    switch (node.type) {
      case 'template': return 'Template Message';
      case 'condition': return 'Condition';
      case 'api': return 'API Request';
      case 'delay': return 'Delay';
      case 'go_to': return 'Go To Flow';
      case 'end': return 'End Flow';
      default: return node.type.toUpperCase();
    }
  }
}
