import 'package:flutter/material.dart';
import 'dart:convert';
import 'dart:async';

import 'package:autoreply/features/sync/services/server_sync_service.dart';
import '../models/flow_model.dart';
import '../models/node_model.dart';
import '../models/connection_model.dart';
import '../widgets/canvas/infinite_canvas.dart';
import '../widgets/properties_panel.dart';
import '../database/chatbot_db_helper.dart';
import '../templates/storage/chatbot_template_storage.dart';

class FlowCanvasScreen extends StatefulWidget {
  final ChatbotFlow flow;

  const FlowCanvasScreen({super.key, required this.flow});

  @override
  State<FlowCanvasScreen> createState() => _FlowCanvasScreenState();
}

class _FlowCanvasScreenState extends State<FlowCanvasScreen> {
  final ServerSyncService _syncService = ServerSyncService();
  bool _isPublishing = false;
  List<FlowNode> nodes = [];
  List<FlowConnection> connections = [];
  String? selectedNodeId;

  // Track which port we are connecting FROM (for button port -> new node)
  String? _pendingSourceNodeId;
  String? _pendingSourcePort;

  // Auto-save debounce timer
  Timer? _saveTimer;
  final ChatbotTemplateStorage _templateStorage = ChatbotTemplateStorage();

  @override
  void initState() {
    super.initState();
    _loadFlow();
    _templateStorage.load().then((_) {
      if (mounted) setState(() {});
    });
  }

  void _loadFlow() {
    if (widget.flow.canvasJson.isNotEmpty && widget.flow.canvasJson != '{}') {
      try {
        final data = jsonDecode(widget.flow.canvasJson);
        nodes = (data['nodes'] as List).map((n) => FlowNode.fromJson(n)).toList();
        connections = (data['connections'] as List).map((c) => FlowConnection.fromJson(c)).toList();
      } catch (e) {
        // Leave canvas empty on error
      }
    }
    // No else block: leave canvas empty for new flows
  }

  void _autoSave() {
    _saveTimer?.cancel();
    _saveTimer = Timer(const Duration(milliseconds: 1000), () async {
      final canvasData = {
        'nodes': nodes.map((n) => n.toJson()).toList(),
        'connections': connections.map((c) => c.toJson()).toList(),
      };
      
      final updatedFlow = ChatbotFlow(
        uuid: widget.flow.uuid,
        name: widget.flow.name,
        version: widget.flow.version,
        status: widget.flow.status,
        canvasJson: jsonEncode(canvasData),
        runtimeJson: widget.flow.runtimeJson, // Keep existing runtime
      );

      await ChatbotDBHelper.instance.updateFlow(updatedFlow);
    });
  }

  Future<void> _deleteNodeWithDialog(String nodeId) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete Node?'),
        content: const Text('Are you sure you want to delete this node and its connections?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Delete', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (confirm == true) {
      setState(() {
        nodes.removeWhere((n) => n.uuid == nodeId);
        connections.removeWhere((c) => c.sourceNodeId == nodeId || c.destNodeId == nodeId);
        if (selectedNodeId == nodeId) {
          selectedNodeId = null;
        }
        _autoSave();
      });
    }
  }

  @override
  void dispose() {
    _saveTimer?.cancel();
    super.dispose();
  }

  void _addNodeFromButtonPort(String sourceNodeId, String sourcePort) {
    _pendingSourceNodeId = sourceNodeId;
    _pendingSourcePort = sourcePort;
    _showTemplateSelectionSheet();
  }

  void _addNodeAndConnect(String type, {List<String> buttons = const [], Map<String, dynamic> properties = const {}}) {
    final newId = DateTime.now().millisecondsSinceEpoch.toString();

    // Calculate position: place below and to the right of the source node
    double newX = 200;
    double newY = 400;
    String connectedFromButtonName = '';

    if (_pendingSourceNodeId != null) {
      try {
        final sourceNode = nodes.firstWhere((n) => n.uuid == _pendingSourceNodeId);
        final btnIndex = int.tryParse((_pendingSourcePort ?? '').replaceFirst('btn_', '')) ?? 0;

        // Get the button name from the source node
        if (btnIndex < sourceNode.buttons.length) {
          connectedFromButtonName = sourceNode.buttons[btnIndex];
        }

        // Position: spread horizontally per button index, place below source
        newX = sourceNode.x + (btnIndex * 350);
        newY = sourceNode.y + 350;
      } catch (_) {}
    }

    // Merge the connectedFrom into properties
    final mergedProps = Map<String, dynamic>.from(properties);
    if (connectedFromButtonName.isNotEmpty) {
      mergedProps['connectedFrom'] = connectedFromButtonName;
    }

    setState(() {
      nodes.add(FlowNode(
        uuid: newId,
        type: type,
        buttons: List<String>.from(buttons),
        propertiesJson: mergedProps,
        x: newX,
        y: newY,
      ));

      // Auto-create connection from button port
      if (_pendingSourceNodeId != null && _pendingSourcePort != null) {
        connections.add(FlowConnection(
          uuid: 'conn_${DateTime.now().millisecondsSinceEpoch}',
          sourceNodeId: _pendingSourceNodeId!,
          sourcePort: _pendingSourcePort!,
          destNodeId: newId,
        ));
      }

      _pendingSourceNodeId = null;
      _pendingSourcePort = null;
      _autoSave(); // Trigger auto-save
    });
  }

  void _showTemplateSelectionSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return DraggableScrollableSheet(
          initialChildSize: 0.6,
          minChildSize: 0.3,
          maxChildSize: 0.9,
          expand: false,
          builder: (context, scrollController) {
            return Padding(
              padding: const EdgeInsets.all(16),
              child: ListView(
                controller: scrollController,
                children: [
                  Center(
                    child: Container(
                      width: 40, height: 4,
                      decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
                    ),
                  ),
                  const SizedBox(height: 16),
                  const Text('Add Node', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 4),
                  Text('Select a node type to add to the flow', style: TextStyle(color: Colors.grey.shade600)),
                  const SizedBox(height: 20),

                  // --- Custom Templates ---
                  if (_templateStorage.templates.isNotEmpty) ...[
                    const Text('⭐ My Templates', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                    const SizedBox(height: 8),
                    ..._templateStorage.templates.map((t) => _buildNodeOption(
                      icon: Icons.mark_chat_read_rounded,
                      color: const Color(0xFF00A884),
                      title: t.name,
                      subtitle: t.caption,
                      onTap: () {
                        Navigator.pop(context);
                        _addNodeAndConnect(
                          'template', 
                          buttons: t.buttons.map<String>((b) => b.displayText).toList(), 
                          properties: {
                            'title': t.title,
                            'caption': t.caption,
                            'footer': t.footer ?? '',
                          }
                        );
                      },
                    )),
                    const SizedBox(height: 16),
                  ],

                  // --- Default Templates ---
                  const Text('📨 Default Templates', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                  const SizedBox(height: 8),
                  _buildNodeOption(
                    icon: Icons.message_rounded,
                    color: Colors.blueGrey,
                    title: 'Welcome Template',
                    subtitle: 'Greeting message with buttons',
                    onTap: () {
                      Navigator.pop(context);
                      _addNodeAndConnect('template', buttons: ['Buy', 'Support', 'Price'], properties: {
                        'title': 'Welcome',
                        'caption': 'Hello! How can we help?',
                      });
                    },
                  ),
                  _buildNodeOption(
                    icon: Icons.shopping_cart_rounded,
                    color: Colors.blueGrey,
                    title: 'Buy Template',
                    subtitle: 'Product purchase flow',
                    onTap: () {
                      Navigator.pop(context);
                      _addNodeAndConnect('template', buttons: ['Confirm', 'Cancel'], properties: {
                        'title': 'Buy Product',
                        'caption': 'Please confirm your order details.',
                      });
                    },
                  ),
                  _buildNodeOption(
                    icon: Icons.attach_money_rounded,
                    color: Colors.blueGrey,
                    title: 'Price Template',
                    subtitle: 'Show pricing info',
                    onTap: () {
                      Navigator.pop(context);
                      _addNodeAndConnect('template', buttons: ['Order Now', 'Back'], properties: {
                        'title': 'Price List',
                        'caption': 'Here are our current prices.',
                      });
                    },
                  ),
                  _buildNodeOption(
                    icon: Icons.support_agent_rounded,
                    color: Colors.blueGrey,
                    title: 'Support Template',
                    subtitle: 'Connect to support agent',
                    onTap: () {
                      Navigator.pop(context);
                      _addNodeAndConnect('template', properties: {
                        'title': 'Support',
                        'caption': 'An agent will be with you shortly.',
                      });
                    },
                  ),

                  const SizedBox(height: 20),
                  const Text('⚙️ Logic Nodes', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                  const SizedBox(height: 8),
                  _buildNodeOption(
                    icon: Icons.alt_route_rounded,
                    color: Colors.orange,
                    title: 'Condition',
                    subtitle: 'If/Else branching logic',
                    onTap: () {
                      Navigator.pop(context);
                      _addNodeAndConnect('condition', buttons: ['Yes', 'No']);
                    },
                  ),

                  _buildNodeOption(
                    icon: Icons.shortcut_rounded,
                    color: Colors.teal,
                    title: 'Go To Flow',
                    subtitle: 'Jump to another flow',
                    onTap: () {
                      Navigator.pop(context);
                      _addNodeAndConnect('go_to');
                    },
                  ),
                  _buildNodeOption(
                    icon: Icons.stop_circle_rounded,
                    color: Colors.red,
                    title: 'End Flow',
                    subtitle: 'Terminate the conversation',
                    onTap: () {
                      Navigator.pop(context);
                      _addNodeAndConnect('end');
                    },
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildNodeOption({
    required IconData icon,
    required Color color,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: color.withOpacity(0.1),
          child: Icon(icon, color: color, size: 22),
        ),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Text(subtitle, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
        trailing: const Icon(Icons.add_circle_outline, color: Colors.grey),
        onTap: onTap,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.flow.name),
        actions: [
          TextButton.icon(
            icon: const Icon(Icons.save, size: 18),
            label: const Text('Save'),
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Flow saved as Draft!')),
              );
            },
          ),
          TextButton.icon(
            icon: _isPublishing 
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                : const Icon(Icons.publish, size: 18),
            label: const Text('Publish'),
            style: TextButton.styleFrom(foregroundColor: Colors.green),
            onPressed: _isPublishing ? null : () async {
              setState(() => _isPublishing = true);
              try {
                final canvasData = {
                  'nodes': nodes.map((n) => n.toJson()).toList(),
                  'connections': connections.map((c) => c.toJson()).toList(),
                };
                
                final instanceId = await _syncService.getActiveInstanceId();
                if (instanceId.isEmpty) {
                  throw Exception('No active WhatsApp linked. Please link a device first.');
                }
                
                final serverId = await _syncService.syncChatbotFlow(
                  name: widget.flow.name,
                  canvasData: jsonEncode(canvasData),
                  instanceId: instanceId,
                  // serverFlowId: widget.flow.serverFlowId, // We will need to store this eventually if we want to update it
                );
                
                if (mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Flow Published successfully! ✅'), backgroundColor: Colors.green),
                  );
                }
              } catch (e) {
                if (mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text('Failed to publish: $e'), backgroundColor: Colors.red),
                  );
                }
              } finally {
                if (mounted) setState(() => _isPublishing = false);
              }
            },
          ),
        ],
      ),
      body: SafeArea(
        child: Stack(
          children: [
            InfiniteCanvas(
              nodes: nodes,
              connections: connections,
              selectedNodeId: selectedNodeId,
              onNodeMoved: (nodeId, newPosition) {
                setState(() {
                  final node = nodes.firstWhere((n) => n.uuid == nodeId);
                  node.x = newPosition.dx;
                  node.y = newPosition.dy;
                  _autoSave(); // Trigger auto-save
                });
              },
              onNodeTapped: (nodeId) {
                setState(() {
                  selectedNodeId = nodeId;
                });
              },
              onNodeDeleted: _deleteNodeWithDialog,
              onCanvasTapped: () {
                setState(() {
                  selectedNodeId = null;
                });
              },
              onButtonPortTap: _addNodeFromButtonPort,
            ),

          // Properties Panel on the right side
          if (selectedNodeId != null)
            Positioned(
              right: 0,
              top: 0,
              bottom: 0,
              child: SafeArea(
                child: PropertiesPanel(
                  selectedNode: nodes.firstWhere(
                    (n) => n.uuid == selectedNodeId,
                    orElse: () => nodes.first,
                  ),
                  onClose: () {
                    setState(() {
                      selectedNodeId = null;
                    });
                  },
                  onNodeUpdated: () {
                    setState(() {}); // Rebuild canvas to reflect color changes
                    _autoSave(); // Trigger auto-save
                  },
                  onDeleteNode: () => _deleteNodeWithDialog(selectedNodeId!),
                ),
              ),
            ),
        ],
      ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          _pendingSourceNodeId = null;
          _pendingSourcePort = null;
          _showTemplateSelectionSheet();
        },
        icon: const Icon(Icons.add),
        label: const Text('Add Node'),
      ),
    );
  }
}
