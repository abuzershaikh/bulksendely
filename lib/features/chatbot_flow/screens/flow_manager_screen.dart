import 'package:flutter/material.dart';
import '../models/flow_model.dart';
import 'flow_canvas_screen.dart';
import '../database/chatbot_db_helper.dart';
import '../templates/screens/chatbot_template_library_screen.dart';
import 'chatbot_settings_screen.dart';
import 'package:autoreply/features/sync/services/server_sync_service.dart';

class FlowManagerScreen extends StatefulWidget {
  const FlowManagerScreen({super.key});

  @override
  State<FlowManagerScreen> createState() => _FlowManagerScreenState();
}

class _FlowManagerScreenState extends State<FlowManagerScreen> {
  List<ChatbotFlow> _flows = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _refreshFlows();
  }

  Future _refreshFlows() async {
    setState(() => _isLoading = true);
    _flows = await ChatbotDBHelper.instance.readAllFlows();
    setState(() => _isLoading = false);
  }

  void _createNewFlow() {
    showDialog(
      context: context,
      builder: (context) {
        String flowName = '';
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Text('Create New Flow', style: TextStyle(fontWeight: FontWeight.bold)),
          content: TextField(
            decoration: InputDecoration(
              hintText: 'Flow Name',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              filled: true,
              fillColor: Colors.grey.shade50,
            ),
            onChanged: (val) => flowName = val,
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              onPressed: () async {
                if (flowName.isNotEmpty) {
                  final newFlow = ChatbotFlow(
                    uuid: DateTime.now().millisecondsSinceEpoch.toString(),
                    name: flowName,
                  );
                  await ChatbotDBHelper.instance.createFlow(newFlow);
                  _refreshFlows();
                  if (mounted) Navigator.pop(context);
                }
              },
              child: const Text('Create'),
            ),
          ],
        );
      },
    );
  }

  Future<void> _deleteFlow(ChatbotFlow flow) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Delete Flow'),
        content: Text('Are you sure you want to delete "${flow.name}"?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red.shade50,
              foregroundColor: Colors.red,
              elevation: 0,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
    if (confirm == true) {
      await ChatbotDBHelper.instance.deleteFlow(flow.uuid);
      _refreshFlows();
    }
  }

  Future<void> _toggleFlowStatus(ChatbotFlow flow, bool isActive) async {
    final newStatus = isActive ? 'Published' : 'Inactive';
    final updatedFlow = ChatbotFlow(
      uuid: flow.uuid,
      name: flow.name,
      version: flow.version,
      status: newStatus,
      canvasJson: flow.canvasJson,
      runtimeJson: flow.runtimeJson,
      updatedAt: DateTime.now(),
    );
    await ChatbotDBHelper.instance.updateFlow(updatedFlow);
    _refreshFlows();
  }

  void _openFlowCanvas(ChatbotFlow flow) async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => FlowCanvasScreen(flow: flow),
      ),
    );
    // Refresh when returning from canvas
    _refreshFlows();
  }

  void _openSettings() async {
    try {
      final syncService = ServerSyncService();
      final instanceId = await syncService.getActiveInstanceId();
      if (!mounted) return;
      
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => ChatbotSettingsScreen(instanceId: instanceId),
        ),
      );
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Could not get active instance: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade100,
      appBar: AppBar(
        title: const Text('Chatbot Flows', style: TextStyle(fontWeight: FontWeight.w600)),
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        actions: [
          IconButton(
            icon: const Icon(Icons.style_rounded, color: Colors.blue),
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => const ChatbotTemplateLibraryScreen(),
                ),
              );
            },
            tooltip: 'Manage Templates',
          ),
          IconButton(
            icon: const Icon(Icons.settings, color: Colors.grey),
            onPressed: _openSettings,
            tooltip: 'Chatbot Settings',
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _createNewFlow,
        icon: const Icon(Icons.add),
        label: const Text('New Flow'),
      ),
      body: _isLoading 
          ? const Center(child: CircularProgressIndicator())
          : _flows.isEmpty 
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.smart_toy_rounded, size: 80, color: Colors.grey.shade300),
                      const SizedBox(height: 16),
                      Text(
                        'No flows yet.',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600, color: Colors.grey.shade600),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Create a new chatbot flow to get started!',
                        style: TextStyle(color: Colors.grey.shade500),
                      ),
                    ],
                  ),
                )
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _flows.length,
                  itemBuilder: (context, index) {
                    final flow = _flows[index];
                    final isPublished = flow.status == 'Published';
                    
                    return Container(
                      margin: const EdgeInsets.only(bottom: 16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.04),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        flow.name,
                                        style: const TextStyle(
                                          fontSize: 18,
                                          fontWeight: FontWeight.bold,
                                        ),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        'Version ${flow.version} • ${flow.status}',
                                        style: TextStyle(
                                          color: Colors.grey.shade600,
                                          fontSize: 13,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                Switch(
                                  value: isPublished,
                                  activeColor: Colors.green,
                                  onChanged: (val) => _toggleFlowStatus(flow, val),
                                ),
                              ],
                            ),
                            const SizedBox(height: 16),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                  decoration: BoxDecoration(
                                    color: isPublished ? Colors.green.shade50 : Colors.grey.shade100,
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: Row(
                                    children: [
                                      Icon(
                                        isPublished ? Icons.check_circle_rounded : Icons.pause_circle_rounded,
                                        size: 16,
                                        color: isPublished ? Colors.green : Colors.grey,
                                      ),
                                      const SizedBox(width: 6),
                                      Text(
                                        isPublished ? 'Active' : 'Inactive',
                                        style: TextStyle(
                                          color: isPublished ? Colors.green : Colors.grey,
                                          fontSize: 12,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                Row(
                                  children: [
                                    IconButton(
                                      icon: const Icon(Icons.delete_outline, color: Colors.redAccent),
                                      onPressed: () => _deleteFlow(flow),
                                      tooltip: 'Delete Flow',
                                    ),
                                    ElevatedButton.icon(
                                      onPressed: () => _openFlowCanvas(flow),
                                      icon: const Icon(Icons.edit, size: 16),
                                      label: const Text('Edit'),
                                      style: ElevatedButton.styleFrom(
                                        elevation: 0,
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(12),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
    );
  }
}
