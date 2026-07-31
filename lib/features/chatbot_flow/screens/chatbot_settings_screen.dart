import 'package:flutter/material.dart';
import 'package:autoreply/features/sync/services/server_sync_service.dart';

class ChatbotSettingsScreen extends StatefulWidget {
  final String instanceId;
  const ChatbotSettingsScreen({super.key, required this.instanceId});

  @override
  State<ChatbotSettingsScreen> createState() => _ChatbotSettingsScreenState();
}

class _ChatbotSettingsScreenState extends State<ChatbotSettingsScreen> {
  final ServerSyncService _syncService = ServerSyncService();
  final TextEditingController _replyController = TextEditingController();
  
  bool _isLoading = true;
  bool _isSaving = false;
  int _unknownAction = 0; // 0 = Resend Menu, 1 = Custom Reply, 2 = End Session

  @override
  void initState() {
    super.initState();
    _loadSettings();
  }

  Future<void> _loadSettings() async {
    try {
      final settings = await _syncService.getChatbotSettings(widget.instanceId);
      setState(() {
        _unknownAction = int.tryParse(settings['unknown_message_action']?.toString() ?? '0') ?? 0;
        if (_unknownAction != 0 && _unknownAction != 1 && _unknownAction != 2) _unknownAction = 0;
        _replyController.text = settings['unknown_message_reply']?.toString() ?? '';
        _isLoading = false;
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading settings: $e')),
        );
      }
      setState(() => _isLoading = false);
    }
  }

  Future<void> _saveSettings() async {
    if (_unknownAction == 1 && _replyController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter a fallback reply message.')),
      );
      return;
    }

    setState(() => _isSaving = true);
    try {
      await _syncService.saveChatbotSettings(
        instanceId: widget.instanceId,
        unknownMessageAction: _unknownAction,
        unknownMessageReply: _replyController.text.trim(),
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Settings saved successfully!')),
        );
        Navigator.pop(context);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error saving settings: $e')),
        );
      }
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Chatbot Settings'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Unknown Message Behavior',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'What should the chatbot do if it receives a message that does not match any keyword while in an active session?',
                    style: TextStyle(color: Colors.grey),
                  ),
                  const SizedBox(height: 16),
                  Card(
                    elevation: 2,
                    child: Padding(
                      padding: const EdgeInsets.all(16.0),
                      child: DropdownButtonFormField<int>(
                        decoration: const InputDecoration(
                          labelText: 'Action',
                          border: OutlineInputBorder(),
                        ),
                        value: _unknownAction,
                        items: const [
                          DropdownMenuItem(
                            value: 0,
                            child: Text('Resend Current Menu'),
                          ),
                          DropdownMenuItem(
                            value: 1,
                            child: Text('Send Custom Reply'),
                          ),
                          DropdownMenuItem(
                            value: 2,
                            child: Text('End Chatbot Session'),
                          ),
                        ],
                        onChanged: (val) {
                          if (val != null) {
                            setState(() => _unknownAction = val);
                          }
                        },
                      ),
                    ),
                  ),
                  if (_unknownAction == 1) ...[
                    const SizedBox(height: 16),
                    TextField(
                      controller: _replyController,
                      maxLines: 3,
                      decoration: const InputDecoration(
                        labelText: 'Fallback Reply Message',
                        hintText: 'e.g. Sorry, I didn\'t understand that. Please select a valid option.',
                        border: OutlineInputBorder(),
                      ),
                    ),
                  ],
                  const SizedBox(height: 32),
                  SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: ElevatedButton(
                      onPressed: _isSaving ? null : _saveSettings,
                      child: _isSaving
                          ? const CircularProgressIndicator(color: Colors.white)
                          : const Text('Save Settings', style: TextStyle(fontSize: 16)),
                    ),
                  ),
                ],
              ),
            ),
    );
  }

  @override
  void dispose() {
    _replyController.dispose();
    super.dispose();
  }
}
