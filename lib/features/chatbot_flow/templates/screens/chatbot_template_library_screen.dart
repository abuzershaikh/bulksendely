import 'package:flutter/material.dart';
import 'package:autoreply/data/models/button_template_model.dart';
import 'package:autoreply/core/theme/app_colors.dart';
import '../storage/chatbot_template_storage.dart';
import 'package:autoreply/features/templates/widgets/whatsapp_preview_card.dart';
import 'package:autoreply/features/templates/screens/create_template_screen.dart';
import 'package:autoreply/features/templates/screens/create_list_template_screen.dart';
import 'create_chatbot_template_screen.dart';
import 'package:autoreply/features/sync/services/server_sync_service.dart';

class ChatbotTemplateLibraryScreen extends StatefulWidget {
  const ChatbotTemplateLibraryScreen({super.key});

  @override
  State<ChatbotTemplateLibraryScreen> createState() => _ChatbotTemplateLibraryScreenState();
}

class _ChatbotTemplateLibraryScreenState extends State<ChatbotTemplateLibraryScreen> {
  final ChatbotTemplateStorage _storage = ChatbotTemplateStorage();
  final ServerSyncService _syncService = ServerSyncService();
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _storage.load().then((_) {
      if (mounted) setState(() {});
    });
  }

  void _refresh() => setState(() {});

  Future<void> _syncTemplateInBackground(ButtonTemplateModel template) async {
    try {
      final serverId = await _syncService.syncChatbotTemplate(template);
      if (serverId != null && serverId.isNotEmpty) {
        final updated = template.copyWith(serverId: serverId);
        await _storage.addTemplate(updated);
        if (mounted) _refresh();
      }
    } catch (e) {
      debugPrint('Background sync failed for chatbot template: $e');
    }
  }

  Future<void> _handleDelete(BuildContext context, ButtonTemplateModel template) async {
    if (template.serverId == null || template.serverId!.isEmpty) {
      // Just local
      await _storage.deleteTemplate(template.id);
      _refresh();
      return;
    }

    setState(() => _isLoading = true);
    try {
      final result = await _syncService.deleteChatbotTemplate(template.serverId!);
      setState(() => _isLoading = false);

      if (result == 'in_use') {
        if (!context.mounted) return;
        final confirm = await showDialog<bool>(
          context: context,
          builder: (ctx) => AlertDialog(
            title: const Text('Template in Use'),
            content: const Text('This template is currently used in an active flow. Are you sure you want to delete it? The flow may break.'),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
              TextButton(
                onPressed: () => Navigator.pop(ctx, true),
                child: const Text('Force Delete', style: TextStyle(color: Colors.red)),
              ),
            ],
          ),
        );
        if (confirm == true) {
          setState(() => _isLoading = true);
          await _syncService.deleteChatbotTemplate(template.serverId!, force: true);
          await _storage.deleteTemplate(template.id);
          setState(() => _isLoading = false);
          _refresh();
        }
      } else {
        await _storage.deleteTemplate(template.id);
        _refresh();
      }
    } catch (e) {
      setState(() => _isLoading = false);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  void _showTemplatePreview(BuildContext context, ButtonTemplateModel template) {
    showDialog<void>(
      context: context,
      builder: (context) {
        final isList = template.templateType == TemplateLibraryType.list;
        return Dialog(
          insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
          backgroundColor: Colors.transparent,
          child: Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(28),
              gradient: const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [Color(0xFFEFF7F2), Color(0xFFDDEEE5)],
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.14),
                  blurRadius: 28,
                  offset: const Offset(0, 16),
                ),
              ],
            ),
            child: Padding(
              padding: const EdgeInsets.all(18),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 42,
                        height: 42,
                        decoration: BoxDecoration(
                          color: const Color(0xFF25D366).withOpacity(0.14),
                          borderRadius: BorderRadius.circular(14),
                        ),
                        child: const Icon(
                          Icons.remove_red_eye_rounded,
                          color: Color(0xFF128C7E),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Live Preview',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w800,
                                color: Color(0xFF1A1D26),
                              ),
                            ),
                            Text(
                              template.name,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.grey.shade700,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ],
                        ),
                      ),
                      IconButton(
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(Icons.close_rounded),
                        color: Colors.grey.shade700,
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(24),
                      gradient: const LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [Color(0xFFD7F5E3), Color(0xFFEEF9F2)],
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const CircleAvatar(
                              radius: 16,
                              backgroundColor: Color(0xFF25D366),
                              child: Icon(
                                Icons.chat_bubble_rounded,
                                size: 16,
                                color: Colors.white,
                              ),
                            ),
                            const SizedBox(width: 8),
                            Text(
                              'WhatsApp Canvas',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                                color: Colors.grey.shade800,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 14),
                        Center(
                          child: WhatsappPreviewCard(
                            title: template.title,
                            caption: template.caption,
                            footer: template.footer,
                            imageUrl: template.imageUrl,
                            buttons: template.buttons,
                            isListTemplate: isList,
                            listButtonText: template.listButtonText,
                            sections: template.sections,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final templates = _storage.templates;

    return Scaffold(
      backgroundColor: AppColors.lighterBg,
      appBar: AppBar(
        title: const Text(
          'Chatbot Templates',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 20),
        ),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        centerTitle: true,
      ),
      body: templates.isEmpty
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.speaker_notes_off_rounded, size: 80, color: Colors.grey.shade300),
                  const SizedBox(height: 16),
                  Text('No templates saved', style: TextStyle(color: Colors.grey.shade500, fontSize: 16)),
                ],
              ),
            )
          : GridView.builder(
              padding: const EdgeInsets.all(16),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                crossAxisSpacing: 16,
                mainAxisSpacing: 16,
                childAspectRatio: 0.78,
              ),
              itemCount: templates.length,
              itemBuilder: (context, index) {
                final t = templates[index];
                return _TemplateCard(
                  template: t,
                  onPreview: () => _showTemplatePreview(context, t),
                  onEdit: () async {
                    if (t.templateType == TemplateLibraryType.list) {
                      await Navigator.push(context, MaterialPageRoute(builder: (context) => CreateListTemplateScreen(initialTemplate: t, screenTitle: 'Edit Chatbot List Template', onSaveTemplate: ChatbotTemplateStorage().addTemplate)));
                    } else {
                      await Navigator.push(context, MaterialPageRoute(builder: (context) => CreateChatbotTemplateScreen(initialTemplate: t, screenTitle: 'Edit Chatbot Template')));
                    }
                    _refresh();
                  },
                  onDelete: () async {
                    await _handleDelete(context, t);
                  },
                );
              },
            ),
      floatingActionButton: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          FloatingActionButton.extended(
            heroTag: 'chatbot_list_template_fab',
            onPressed: () async {
              await Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => CreateListTemplateScreen(
                    screenTitle: 'Create Chatbot List Template',
                    onSaveTemplate: (t) async {
                      await _storage.addTemplate(t);
                    },
                  ),
                ),
              );
              _refresh();
            },
            backgroundColor: Colors.green,
            icon: const Icon(Icons.list_alt_rounded, color: Colors.white),
            label: const Text('List', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
          const SizedBox(height: 12),
          FloatingActionButton.extended(
            heroTag: 'chatbot_button_template_fab',
            onPressed: () async {
              await Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => CreateTemplateScreen(
                    screenTitle: 'Create Chatbot Button Template',
                    onSaveTemplate: (t) async {
                      await _storage.addTemplate(t);
                    },
                  ),
                ),
              );
              _refresh();
            },
            backgroundColor: AppColors.primaryBlue,
            icon: const Icon(Icons.smart_button_outlined, color: Colors.white),
            label: const Text('Button', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }
}

class _TemplateCard extends StatelessWidget {
  final ButtonTemplateModel template;
  final VoidCallback onPreview;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _TemplateCard({
    required this.template,
    required this.onPreview,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final isList = template.templateType == TemplateLibraryType.list;
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          )
        ],
      ),
      clipBehavior: Clip.hardEdge,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header Image or Placeholder
          Container(
            height: 64,
            decoration: BoxDecoration(
              color: AppColors.lightBg,
              borderRadius: const BorderRadius.only(topLeft: Radius.circular(16), topRight: Radius.circular(16)),
              image: template.imageUrl != null && template.imageUrl!.isNotEmpty
                  ? DecorationImage(image: NetworkImage(template.imageUrl!), fit: BoxFit.cover)
                  : null,
            ),
            child: template.imageUrl == null || template.imageUrl!.isEmpty
                ? const Center(child: Icon(Icons.image_outlined, color: Colors.grey, size: 28))
                : null,
          ),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(template.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13), maxLines: 1, overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 3),
                  Text(
                    isList ? 'List message' : 'Button message',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: isList ? Colors.green.shade700 : AppColors.primaryBlue,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Flexible(
                    child: Text(
                      isList
                          ? 'Menu style message with selectable rows.'
                          : 'Quick action message with tap buttons.',
                      style: TextStyle(color: Colors.grey.shade600, fontSize: 11),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const Divider(height: 1, color: Color(0xFFEEEEEE)),
          Row(
            children: [
              Expanded(
                child: InkWell(
                  onTap: onPreview,
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.remove_red_eye_rounded, size: 16, color: Colors.blue.shade600),
                        const SizedBox(width: 4),
                        Text('Preview', style: TextStyle(color: Colors.blue.shade600, fontSize: 12, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                ),
              ),
              Container(width: 1, height: 20, color: const Color(0xFFEEEEEE)),
              Expanded(
                child: InkWell(
                  onTap: onEdit,
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.edit_rounded, size: 16, color: Colors.green.shade600),
                        const SizedBox(width: 4),
                        Text('Edit', style: TextStyle(color: Colors.green.shade600, fontSize: 12, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                ),
              ),
              Container(width: 1, height: 20, color: const Color(0xFFEEEEEE)),
              Expanded(
                child: InkWell(
                  onTap: onDelete,
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.delete_outline_rounded, size: 16, color: Colors.red.shade400),
                        const SizedBox(width: 4),
                        Text('Delete', style: TextStyle(color: Colors.red.shade400, fontSize: 12, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
