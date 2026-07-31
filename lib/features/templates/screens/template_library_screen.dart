import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/data/local/template_storage.dart';
import 'package:autoreply/data/models/button_template_model.dart';
import 'package:autoreply/features/templates/screens/create_list_template_screen.dart';
import 'package:autoreply/features/templates/screens/create_template_screen.dart';
import 'package:autoreply/features/templates/widgets/whatsapp_preview_card.dart';
import 'package:flutter/material.dart';

class TemplateLibraryScreen extends StatefulWidget {
  const TemplateLibraryScreen({super.key});

  @override
  State<TemplateLibraryScreen> createState() => _TemplateLibraryScreenState();
}

class _TemplateLibraryScreenState extends State<TemplateLibraryScreen> {
  void _refresh() => setState(() {});

  @override
  Widget build(BuildContext context) {
    final templates = TemplateStorage().templates;

    return Scaffold(
      backgroundColor: AppColors.lighterBg,
      appBar: AppBar(
        title: const Text('Template Library', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 20)),
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
                      await Navigator.push(context, MaterialPageRoute(builder: (context) => CreateListTemplateScreen(initialTemplate: t, screenTitle: 'Edit List Template')));
                    } else {
                      await Navigator.push(context, MaterialPageRoute(builder: (context) => CreateTemplateScreen(initialTemplate: t, screenTitle: 'Edit Template')));
                    }
                    _refresh();
                  },
                  onDelete: () {
                    TemplateStorage().removeTemplate(t.id);
                    _refresh();
                  },
                );
              },
            ),
      floatingActionButton: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          FloatingActionButton.extended(
            heroTag: 'list_template_fab',
            onPressed: () async {
              await Navigator.push(context, MaterialPageRoute(builder: (context) => const CreateListTemplateScreen()));
              _refresh();
            },
            backgroundColor: Colors.green,
            icon: const Icon(Icons.list_alt_rounded, color: Colors.white),
            label: const Text('List', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
          const SizedBox(height: 12),
          FloatingActionButton.extended(
            heroTag: 'button_template_fab',
            onPressed: () async {
              await Navigator.push(context, MaterialPageRoute(builder: (context) => const CreateTemplateScreen()));
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
                  color: Colors.black.withValues(alpha: 0.14),
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
                          color: const Color(0xFF25D366).withValues(alpha: 0.14),
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
                            CircleAvatar(
                              radius: 16,
                              backgroundColor: const Color(0xFF25D366),
                              child: const Icon(
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
    final listRows = template.sections.fold<int>(0, (sum, section) => sum + section.rows.length);
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
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
                          ? 'Menu style message with multiple selectable rows.'
                          : 'Quick action message with tap buttons.',
                      style: TextStyle(color: Colors.grey.shade600, fontSize: 11),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Flexible(
                    child: Text(
                      template.caption,
                      style: TextStyle(color: Colors.grey.shade500, fontSize: 10),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  const Spacer(),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
                        decoration: BoxDecoration(
                          color: (isList ? Colors.green : AppColors.primaryBlue).withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          isList ? 'List Menu' : 'Button Msg',
                          style: TextStyle(
                            color: isList ? Colors.green : AppColors.primaryBlue,
                            fontSize: 9,
                            fontWeight: FontWeight.bold,
                          ),
                          ),
                        ),
                      Row(
                        children: [
                          InkWell(
                            onTap: onPreview,
                            child: const Icon(
                              Icons.remove_red_eye_outlined,
                              color: Color(0xFF128C7E),
                              size: 17,
                            ),
                          ),
                          const SizedBox(width: 10),
                          InkWell(
                            onTap: onEdit,
                            child: const Icon(
                              Icons.edit_outlined,
                              color: Colors.blueAccent,
                              size: 16,
                            ),
                          ),
                          const SizedBox(width: 10),
                          InkWell(
                            onTap: onDelete,
                            child: const Icon(
                              Icons.delete_outline,
                              color: Colors.redAccent,
                              size: 16,
                            ),
                          ),
                        ],
                      )
                    ],
                  )
                ],
              ),
            ),
          )
        ],
      ),
    );
  }
}
