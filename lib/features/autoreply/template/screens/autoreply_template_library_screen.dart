import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/data/models/button_template_model.dart';
import 'package:autoreply/features/subscription/models/app_user_subscription.dart';
import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:autoreply/features/autoreply/template/screens/create_autoreply_list_template_screen.dart';
import 'package:autoreply/features/autoreply/template/screens/create_autoreply_template_screen.dart';
import 'package:autoreply/features/autoreply/template/storage/autoreply_template_storage.dart';
import 'package:flutter/material.dart';

class AutoReplyTemplateLibraryScreen extends StatefulWidget {
  const AutoReplyTemplateLibraryScreen({super.key});

  @override
  State<AutoReplyTemplateLibraryScreen> createState() =>
      _AutoReplyTemplateLibraryScreenState();
}

class _AutoReplyTemplateLibraryScreenState
    extends State<AutoReplyTemplateLibraryScreen> {
  final AutoReplyTemplateStorage _storage = AutoReplyTemplateStorage();
  final SubscriptionService _subscriptionService = SubscriptionService.instance;

  bool get _isPremiumUser =>
      _subscriptionService.currentUserNotifier.value?.isPremium ?? false;

  @override
  void initState() {
    super.initState();
    _storage.load().then((_) {
      if (mounted) setState(() {});
    });
  }

  void _refresh() => setState(() {});

  Future<void> _openCreateTemplate({required bool isListTemplate, ButtonTemplateModel? initialTemplate}) async {
    if (initialTemplate == null && !_isPremiumUser &&
        _storage.templates.length >= AppUserSubscription.freeAutoReplyLimit) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Free users can create up to 5 auto-reply templates. Upgrade for unlimited.',
          ),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => isListTemplate
            ? CreateAutoReplyListTemplateScreen(initialTemplate: initialTemplate)
            : CreateAutoReplyTemplateScreen(initialTemplate: initialTemplate),
      ),
    );
    _refresh();
  }

  @override
  Widget build(BuildContext context) {
    final templates = _storage.templates;

    return Scaffold(
      backgroundColor: AppColors.lighterBg,
      appBar: AppBar(
        title: const Text(
          'AutoReply Templates',
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
                  Icon(
                    Icons.mark_chat_read_outlined,
                    size: 80,
                    color: Colors.grey.shade300,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'No AutoReply templates saved',
                    style: TextStyle(color: Colors.grey.shade500, fontSize: 16),
                  ),
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
                return _AutoReplyTemplateCard(
                  template: t,
                  onEdit: () => _openCreateTemplate(isListTemplate: t.templateType == TemplateLibraryType.list, initialTemplate: t),
                  onDelete: () {
                    _storage.removeTemplate(t.id);
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
            heroTag: 'autoreply_list_template_fab',
            onPressed: () => _openCreateTemplate(isListTemplate: true),
            backgroundColor: Colors.green,
            icon: const Icon(Icons.list_alt_rounded, color: Colors.white),
            label: const Text(
              'List',
              style: TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
          const SizedBox(height: 12),
          FloatingActionButton.extended(
            heroTag: 'autoreply_button_template_fab',
            onPressed: () => _openCreateTemplate(isListTemplate: false),
            backgroundColor: AppColors.primaryBlue,
            icon: const Icon(Icons.smart_button_outlined, color: Colors.white),
            label: const Text(
              'Button',
              style: TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _AutoReplyTemplateCard extends StatelessWidget {
  final ButtonTemplateModel template;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _AutoReplyTemplateCard({
    required this.template,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final isList = template.templateType == TemplateLibraryType.list;
    final listRows = template.sections.fold<int>(
      0,
      (sum, section) => sum + section.rows.length,
    );

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      clipBehavior: Clip.hardEdge,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            height: 64,
            decoration: BoxDecoration(
              color: AppColors.lightBg,
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(16),
                topRight: Radius.circular(16),
              ),
              image: template.imageUrl != null && template.imageUrl!.isNotEmpty
                  ? DecorationImage(
                      image: NetworkImage(template.imageUrl!),
                      fit: BoxFit.cover,
                    )
                  : null,
            ),
            child: template.imageUrl == null || template.imageUrl!.isEmpty
                ? const Center(
                    child: Icon(
                      Icons.image_outlined,
                      color: Colors.grey,
                      size: 28,
                    ),
                  )
                : null,
          ),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    template.name,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 13,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 3),
                  Flexible(
                    child: Text(
                      template.caption,
                      style: TextStyle(
                        color: Colors.grey.shade600,
                        fontSize: 11,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  const Spacer(),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 6,
                          vertical: 3,
                        ),
                        decoration: BoxDecoration(
                          color: (isList ? Colors.green : AppColors.primaryBlue)
                              .withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          isList
                              ? '$listRows Rows'
                              : '${template.buttons.length} Btn',
                          style: TextStyle(
                            color: isList
                                ? Colors.green
                                : AppColors.primaryBlue,
                            fontSize: 9,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      Row(
                        children: [
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
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
