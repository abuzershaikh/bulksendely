import 'package:flutter/material.dart';
import '../models/node_model.dart';

class PropertiesPanel extends StatefulWidget {
  final FlowNode? selectedNode;
  final VoidCallback onClose;
  final VoidCallback onNodeUpdated;
  final VoidCallback onDeleteNode;

  const PropertiesPanel({
    super.key,
    required this.selectedNode,
    required this.onClose,
    required this.onNodeUpdated,
    required this.onDeleteNode,
  });

  @override
  State<PropertiesPanel> createState() => _PropertiesPanelState();
}

class _PropertiesPanelState extends State<PropertiesPanel> {
  final List<String> _colors = [
    '#00A884', // Green (Template)
    '#FF9800', // Orange (Condition)
    '#9C27B0', // Purple (API)
    '#2196F3', // Blue (Delay)
    '#009688', // Teal (Go To)
    '#F44336', // Red (End)
    '#E91E63', // Pink
    '#607D8B', // Blue Grey
  ];

  // Controllers for editable fields
  late TextEditingController _keywordsController;
  late TextEditingController _titleController;
  late TextEditingController _captionController;
  late TextEditingController _footerController;
  late TextEditingController _conditionVarController;
  late TextEditingController _conditionValController;
  late TextEditingController _saveVariableController;
  late TextEditingController _goToFlowIdController;
  late TextEditingController _endMessageController;

  String? _lastNodeId;

  @override
  void initState() {
    super.initState();
    _keywordsController = TextEditingController();
    _titleController = TextEditingController();
    _captionController = TextEditingController();
    _footerController = TextEditingController();
    _conditionVarController = TextEditingController();
    _conditionValController = TextEditingController();
    _saveVariableController = TextEditingController();
    _goToFlowIdController = TextEditingController();
    _endMessageController = TextEditingController();
    _syncControllers();
  }

  @override
  void didUpdateWidget(covariant PropertiesPanel oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.selectedNode?.uuid != _lastNodeId) {
      _syncControllers();
    }
  }

  void _syncControllers() {
    final node = widget.selectedNode;
    _lastNodeId = node?.uuid;
    if (node == null) return;

    final props = node.propertiesJson;
    _keywordsController.text = props['keywords'] ?? '';
    _titleController.text = props['title'] ?? '';
    _captionController.text = props['caption'] ?? '';
    _footerController.text = props['footer'] ?? '';
    _conditionVarController.text = props['conditionVar'] ?? '';
    _conditionValController.text = props['conditionVal'] ?? '';
    _saveVariableController.text = props['saveVariable'] ?? '';
    _goToFlowIdController.text = props['goToFlowId'] ?? '';
    _endMessageController.text = props['endMessage'] ?? '';
  }

  void _saveProperty(String key, String value) {
    if (widget.selectedNode == null) return;
    widget.selectedNode!.propertiesJson[key] = value;
    widget.onNodeUpdated();
  }

  @override
  void dispose() {
    _keywordsController.dispose();
    _titleController.dispose();
    _captionController.dispose();
    _footerController.dispose();
    _conditionVarController.dispose();
    _conditionValController.dispose();
    _saveVariableController.dispose();
    _goToFlowIdController.dispose();
    _endMessageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (widget.selectedNode == null) {
      return const SizedBox.shrink(); // Hide if no node is selected
    }

    return Container(
      width: 300, // Fixed width for right panel
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(left: BorderSide(color: Colors.grey.shade300)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(-2, 0),
          )
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            color: Colors.grey.shade50,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Properties',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
                IconButton(
                  icon: const Icon(Icons.close, size: 20),
                  onPressed: widget.onClose,
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(),
                ),
              ],
            ),
          ),
          const Divider(height: 1),

          // Content
          Expanded(
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    onPressed: widget.onDeleteNode,
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.red,
                      side: const BorderSide(color: Colors.red),
                    ),
                    icon: const Icon(Icons.delete_outline, size: 18),
                    label: const Text('Delete Node'),
                  ),
                ),
                const SizedBox(height: 16),
                _buildPropertyItem('Node Type', widget.selectedNode!.type.toUpperCase()),
                _buildPropertyItem('UUID', widget.selectedNode!.uuid),
                const SizedBox(height: 20),
                
                // --- Color Selection ---
                const Text('Node Color', style: TextStyle(fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _colors.map((hex) {
                    final color = Color(int.parse('FF${hex.replaceAll('#', '')}', radix: 16));
                    final isSelected = widget.selectedNode!.colorHex == hex || 
                        (widget.selectedNode!.colorHex == null && _getDefaultColorHex() == hex);
                    
                    return GestureDetector(
                      onTap: () {
                        setState(() {
                          widget.selectedNode!.colorHex = hex;
                        });
                        widget.onNodeUpdated(); // Trigger rebuild
                      },
                      child: Container(
                        width: 32,
                        height: 32,
                        decoration: BoxDecoration(
                          color: color,
                          shape: BoxShape.circle,
                          border: isSelected ? Border.all(color: Colors.black87, width: 3) : null,
                          boxShadow: [
                            if (isSelected) BoxShadow(color: color.withOpacity(0.5), blurRadius: 6)
                          ],
                        ),
                        child: isSelected ? const Icon(Icons.check, size: 16, color: Colors.white) : null,
                      ),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 20),
                
                // ============================================
                // TRIGGER KEYWORDS (for template nodes)
                // ============================================
                if (widget.selectedNode!.type == 'template') ...[
                  // --- Trigger Keywords ---
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFF3E0), // Light orange
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.orange.shade200),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.bolt, color: Colors.orange.shade700, size: 20),
                            const SizedBox(width: 6),
                            Text(
                              'Trigger Keywords',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Colors.orange.shade800,
                                fontSize: 14,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Jab user ye words bhejega toh ye flow start hoga',
                          style: TextStyle(fontSize: 11, color: Colors.orange.shade700),
                        ),
                        const SizedBox(height: 8),
                        TextFormField(
                          controller: _keywordsController,
                          decoration: InputDecoration(
                            border: const OutlineInputBorder(),
                            hintText: 'hi, hello, menu',
                            helperText: 'Comma se alag karein',
                            helperStyle: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                            filled: true,
                            fillColor: Colors.white,
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                          ),
                          onChanged: (val) => _saveProperty('keywords', val),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // --- Title ---
                  const Text('Title', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _titleController,
                    decoration: const InputDecoration(
                      border: OutlineInputBorder(),
                      hintText: 'e.g. Welcome',
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                    onChanged: (val) => _saveProperty('title', val),
                  ),
                  const SizedBox(height: 12),

                  // --- Caption ---
                  const Text('Caption / Message', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _captionController,
                    maxLines: 3,
                    decoration: const InputDecoration(
                      border: OutlineInputBorder(),
                      hintText: 'Hello! How can we help you?',
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                    onChanged: (val) => _saveProperty('caption', val),
                  ),
                  const SizedBox(height: 12),

                  // --- Footer ---
                  const Text('Footer', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _footerController,
                    decoration: const InputDecoration(
                      border: OutlineInputBorder(),
                      hintText: 'Powered by YourBrand',
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                    onChanged: (val) => _saveProperty('footer', val),
                  ),
                  const SizedBox(height: 12),

                  // --- Save Response to Variable ---
                  const Text('Save User Response to Variable', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _saveVariableController,
                    decoration: const InputDecoration(
                      border: OutlineInputBorder(),
                      hintText: 'e.g. name or age',
                      helperText: 'Leave empty if you do not want to save the response',
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                    onChanged: (val) => _saveProperty('saveVariable', val),
                  ),
                  const SizedBox(height: 12),

                  // --- Buttons (read-only display) ---
                  if (widget.selectedNode!.buttons.isNotEmpty) ...[
                    const Text('Buttons', style: TextStyle(fontWeight: FontWeight.bold)),
                    const SizedBox(height: 8),
                    ...widget.selectedNode!.buttons.asMap().entries.map((entry) {
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 4),
                        child: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                              decoration: BoxDecoration(
                                color: Colors.green.shade50,
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                'btn_${entry.key}',
                                style: TextStyle(fontSize: 10, color: Colors.green.shade700, fontFamily: 'monospace'),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Text(entry.value, style: const TextStyle(fontWeight: FontWeight.w500)),
                          ],
                        ),
                      );
                    }),
                  ],
                ]

                // --- Condition Node Fields ---
                else if (widget.selectedNode!.type == 'condition') ...[
                  const Text('If Variable', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _conditionVarController,
                    decoration: const InputDecoration(
                      border: OutlineInputBorder(),
                      hintText: 'Enter variable name...',
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                    onChanged: (val) => _saveProperty('conditionVar', val),
                  ),
                  const SizedBox(height: 12),
                  const Text('Operator', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<String>(
                    decoration: const InputDecoration(
                      border: OutlineInputBorder(),
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                    value: widget.selectedNode!.propertiesJson['conditionOp'] ?? '==',
                    items: const [
                      DropdownMenuItem(value: '==', child: Text('Equals (==)')),
                      DropdownMenuItem(value: '!=', child: Text('Not Equals (!=)')),
                      DropdownMenuItem(value: 'contains', child: Text('Contains')),
                      DropdownMenuItem(value: '>', child: Text('Greater Than (>)' )),
                      DropdownMenuItem(value: '<', child: Text('Less Than (<)' )),
                    ],
                    onChanged: (val) {
                      if (val != null) _saveProperty('conditionOp', val);
                    },
                  ),
                  const SizedBox(height: 12),
                  const Text('Equals To', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _conditionValController,
                    decoration: const InputDecoration(
                      border: OutlineInputBorder(),
                      hintText: 'Enter value to compare...',
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                    onChanged: (val) => _saveProperty('conditionVal', val),
                  ),
                ]

                // --- Go To Flow Node Fields ---
                else if (widget.selectedNode!.type == 'go_to') ...[
                  const Text('Target Flow ID', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _goToFlowIdController,
                    decoration: const InputDecoration(
                      border: OutlineInputBorder(),
                      hintText: 'Enter Flow ID (e.g. 5)',
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                    onChanged: (val) => _saveProperty('goToFlowId', val),
                  ),
                ]
                
                // --- End Node Fields ---
                else if (widget.selectedNode!.type == 'end') ...[
                  const Text('Final Message (Optional)', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _endMessageController,
                    decoration: const InputDecoration(
                      border: OutlineInputBorder(),
                      hintText: 'e.g. Thank you! Conversation ended.',
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                    onChanged: (val) => _saveProperty('endMessage', val),
                  ),
                ],
                
                const SizedBox(height: 30),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: widget.onClose,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.blue,
                      foregroundColor: Colors.white,
                    ),
                    child: const Text('Apply Changes'),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPropertyItem(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
          const SizedBox(height: 4),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }

  String _getDefaultColorHex() {
    switch (widget.selectedNode!.type) {
      case 'template': return '#00A884';
      case 'condition': return '#FF9800';
      case 'api': return '#9C27B0';
      case 'delay': return '#2196F3';
      case 'go_to': return '#009688';
      case 'end': return '#F44336';
      default: return '#9E9E9E';
    }
  }
}
