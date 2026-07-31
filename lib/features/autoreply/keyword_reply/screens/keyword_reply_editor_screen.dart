import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/data/models/button_template_model.dart';
import 'package:autoreply/features/autoreply/keyword_reply/models/keyword_reply_model.dart';
import 'package:autoreply/features/autoreply/keyword_reply/storage/keyword_reply_storage.dart';
import 'package:autoreply/features/autoreply/template/storage/autoreply_template_storage.dart';
import 'package:autoreply/features/subscription/models/app_user_subscription.dart';
import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:autoreply/features/subscription/widgets/premium_feature_dialog.dart';
import 'package:autoreply/features/sync/services/server_sync_service.dart';
import 'package:autoreply/features/templates/widgets/whatsapp_preview_card.dart';
import 'package:flutter/material.dart';

class KeywordReplyEditorScreen extends StatefulWidget {
  final KeywordReplyFlow? existingFlow;

  const KeywordReplyEditorScreen({super.key, this.existingFlow});

  @override
  State<KeywordReplyEditorScreen> createState() =>
      _KeywordReplyEditorScreenState();
}

class _KeywordReplyEditorScreenState extends State<KeywordReplyEditorScreen> {
  final KeywordReplyStorage _storage = KeywordReplyStorage();
  final AutoReplyTemplateStorage _templateStorage = AutoReplyTemplateStorage();
  final ServerSyncService _syncService = ServerSyncService();
  final SubscriptionService _subscriptionService = SubscriptionService.instance;
  final TextEditingController _nameCtrl = TextEditingController();
  final TextEditingController _keywordCtrl = TextEditingController();

  bool _isActive = true;
  bool _saving = false;
  final List<KeywordReplyStep> _steps = [];
  final List<String> _keywords = [];

  bool get _isPremiumUser =>
      _subscriptionService.currentUserNotifier.value?.isPremium ?? false;

  bool get _freeStepLimitReachedForAdd =>
      !_isPremiumUser &&
      _steps.length >= AppUserSubscription.freeAutoReplyLimit;

  Future<void> _showAutoReplyLimitDialog() async {
    await PremiumFeatureDialog.show(
      context,
      message:
          'Free users can send only 5 auto-reply messages. Upgrade to premium for unlimited auto-replies.',
    );
  }

  @override
  void initState() {
    super.initState();
    _templateStorage.load();
    final flow = widget.existingFlow;
    if (flow != null) {
      _nameCtrl.text = flow.name;
      _isActive = flow.isActive;
      _steps.addAll(flow.steps);
      _keywordCtrl.text = flow.keywords.join(', ');
    } else {
      _steps.add(
        KeywordReplyStep(
          id: DateTime.now().microsecondsSinceEpoch.toString(),
          type: KeywordReplyStepType.text,
        ),
      );
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _keywordCtrl.dispose();
    super.dispose();
  }

  void _addKeyword() {
    final value = _keywordCtrl.text.trim().toLowerCase();
    if (value.isEmpty || _keywords.contains(value)) return;
    setState(() {
      _keywords.add(value);
      _keywordCtrl.clear();
    });
  }

  Future<void> _save() async {
    if (_nameCtrl.text.trim().isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Flow name required')));
      return;
    }
    _keywords.clear();
    if (_keywordCtrl.text.trim().isNotEmpty) {
      final parts = _keywordCtrl.text.split(',');
      for (final p in parts) {
        final val = p.trim().toLowerCase();
        if (val.isNotEmpty && !_keywords.contains(val)) {
          _keywords.add(val);
        }
      }
    }

    if (_keywords.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter incoming keyword(s)')),
      );
      return;
    }

    if (!_isPremiumUser &&
        _steps.length > AppUserSubscription.freeAutoReplyLimit) {
      await _showAutoReplyLimitDialog();
      return;
    }

    for (final step in _steps) {
      if (step.type == KeywordReplyStepType.text && step.text.trim().isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Reply text cannot be empty')),
        );
        return;
      }
      if (step.type == KeywordReplyStepType.template &&
          step.templateId.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Select a template for template step')),
        );
        return;
      }
    }

    final flow = KeywordReplyFlow(
      id:
          widget.existingFlow?.id ??
          DateTime.now().millisecondsSinceEpoch.toString(),
      name: _nameCtrl.text.trim(),
      isActive: _isActive,
      keywords: List<String>.from(_keywords),
      steps: List<KeywordReplyStep>.from(_steps),
      createdAt:
          widget.existingFlow?.createdAt ??
          DateTime.now().millisecondsSinceEpoch,
      serverId: widget.existingFlow?.serverId,
    );

    setState(() => _saving = true);
    _storage.addOrUpdate(flow);

    try {
      final serverId = await _syncService.syncKeywordReply(flow);
      _storage.addOrUpdate(flow.copyWith(serverId: serverId));
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Saved'), backgroundColor: Colors.green),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Saved locally. Sync failed: $e'),
          backgroundColor: Colors.orange,
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _saving = false);
        Navigator.pop(context);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final templates = _templateStorage.templates;
    final previewStep = _steps.isNotEmpty ? _steps.first : null;

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: Text(
          widget.existingFlow == null
              ? 'Create Keyword Reply'
              : 'Edit Keyword Reply',
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        actions: [
          TextButton(
            onPressed: _saving ? null : _save,
            child: Text(_saving ? 'Saving...' : 'Save'),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          if (_freeStepLimitReachedForAdd) {
            await _showAutoReplyLimitDialog();
            return;
          }
          setState(() {
            _steps.add(
              KeywordReplyStep(
                id: DateTime.now().microsecondsSinceEpoch.toString(),
                type: KeywordReplyStepType.text,
              ),
            );
          });
        },
        backgroundColor: const Color(0xFF00A884), // WhatsApp-like green for visibility
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text(
          'Add Reply',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
      ),
      body: Column(
        children: [
          SizedBox(
            height: MediaQuery.of(context).size.height * 0.22, // Reduced height
            child: Container(
              width: double.infinity,
              decoration: BoxDecoration(
                color: const Color(0xFFECE5DD),
                image: DecorationImage(
                  image: const NetworkImage(
                    'https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png',
                  ),
                  fit: BoxFit.cover,
                  colorFilter: ColorFilter.mode(
                    Colors.white.withValues(alpha: 0.85),
                    BlendMode.srcOver,
                  ),
                ),
              ),
              child: Padding(
                padding: const EdgeInsets.fromLTRB(12, 18, 12, 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Align(
                      alignment: Alignment.centerRight,
                      child: _IncomingPreviewBubble(
                        text: _keywordCtrl.text.trim().isNotEmpty
                            ? _keywordCtrl.text.trim()
                            : _keywords.isEmpty
                                ? 'Incoming keyword...'
                                : _keywords.first,
                      ),
                    ),
                    const SizedBox(height: 10),
                    if (previewStep == null)
                      const SizedBox.shrink()
                    else if (previewStep.type == KeywordReplyStepType.text)
                      _ReplyPreviewBubble(
                        text: previewStep.text.isEmpty
                            ? 'Reply preview...'
                            : previewStep.text,
                      )
                    else
                      _TemplateReplyPreview(
                        template: templates
                            .cast<ButtonTemplateModel?>()
                            .firstWhere(
                              (item) =>
                                  item?.serverId == previewStep.templateId ||
                                  item?.id == previewStep.templateId,
                              orElse: () => null,
                            ),
                        templateName: previewStep.templateName,
                      ),
                  ],
                ),
              ),
            ),
          ),
          Expanded(
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
              children: [
                TextField(
                  controller: _nameCtrl,
                  decoration: _input('Flow name'),
                ),
                const SizedBox(height: 12),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  value: _isActive,
                  title: const Text('Enable this keyword flow'),
                  subtitle: const Text(
                    'Trigger reply when incoming message matches keywords',
                  ),
                  onChanged: (value) => setState(() => _isActive = value),
                ),
                const SizedBox(height: 12),
                const Text(
                  'Incoming Keywords (comma separated)',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: _keywordCtrl,
                  decoration: _input('e.g. hello, hi, price'),
                  onChanged: (val) {
                    setState(() {}); // Update preview in real-time
                  },
                ),
                const SizedBox(height: 16),
                ..._steps.asMap().entries.map((entry) {
                  final index = entry.key;
                  final step = entry.value;
                  return _buildStepCard(index, step, templates);
                }),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStepCard(
    int index,
    KeywordReplyStep step,
    List<ButtonTemplateModel> templates,
  ) {
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade300),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(
                'Reply Step ${index + 1}',
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
              const Spacer(),
              DropdownButton<KeywordReplyStepType>(
                value: step.type,
                underline: const SizedBox.shrink(),
                items: KeywordReplyStepType.values
                    .map(
                      (value) => DropdownMenuItem(
                        value: value,
                        child: Text(value.name),
                      ),
                    )
                    .toList(),
                onChanged: (value) {
                  if (value == null) return;
                  setState(() {
                    _steps[index] = _steps[index].copyWith(
                      type: value,
                      text: '',
                      templateId: '',
                      templateName: '',
                    );
                  });
                },
              ),
              IconButton(
                onPressed: () => setState(() => _steps.removeAt(index)),
                icon: const Icon(Icons.delete_outline, color: Colors.redAccent),
              ),
            ],
          ),
          const SizedBox(height: 10),
          if (step.type == KeywordReplyStepType.text)
            TextFormField(
              initialValue: step.text,
              maxLines: 4,
              decoration: _input('Reply text'),
              onChanged: (value) => setState(
                () => _steps[index] = _steps[index].copyWith(text: value),
              ),
            ),
          if (step.type == KeywordReplyStepType.template)
            DropdownButtonFormField<String>(
              value: step.templateId.isEmpty ? null : step.templateId,
              decoration: _input('Select AutoReply template'),
              items: templates
                  .map(
                    (template) => DropdownMenuItem(
                      value: template.serverId?.isNotEmpty == true
                          ? template.serverId
                          : template.id,
                      child: Text(template.name),
                    ),
                  )
                  .toList(),
              onChanged: (value) {
                if (value == null) return;
                final template = templates.firstWhere(
                  (item) => item.serverId == value || item.id == value,
                  orElse: () => templates.first,
                );
                setState(() {
                  _steps[index] = _steps[index].copyWith(
                    templateId: value,
                    templateName: template.name,
                  );
                });
              },
            ),
        ],
      ),
    );
  }

  InputDecoration _input(String hint) {
    return InputDecoration(
      hintText: hint,
      filled: true,
      fillColor: const Color(0xFFF8F9FB),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Colors.grey.shade300),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Colors.grey.shade300),
      ),
      focusedBorder: const OutlineInputBorder(
        borderRadius: BorderRadius.all(Radius.circular(12)),
        borderSide: BorderSide(color: AppColors.primaryBlue),
      ),
    );
  }
}

class _IncomingPreviewBubble extends StatelessWidget {
  final String text;

  const _IncomingPreviewBubble({required this.text});

  @override
  Widget build(BuildContext context) {
    return Container(
      constraints: const BoxConstraints(maxWidth: 250),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(text),
    );
  }
}

class _ReplyPreviewBubble extends StatelessWidget {
  final String text;

  const _ReplyPreviewBubble({required this.text});

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        constraints: const BoxConstraints(maxWidth: 250),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: const Color(0xFFD9FDD3),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Text(text),
      ),
    );
  }
}

class _TemplateReplyPreview extends StatelessWidget {
  final ButtonTemplateModel? template;
  final String templateName;

  const _TemplateReplyPreview({
    required this.template,
    required this.templateName,
  });

  @override
  Widget build(BuildContext context) {
    if (template == null) {
      return _ReplyPreviewBubble(
        text: templateName.isEmpty ? 'Template reply preview...' : templateName,
      );
    }

    return WhatsappPreviewCard(
      title: template!.title,
      caption: template!.caption.isEmpty
          ? 'Template reply preview...'
          : template!.caption,
      footer: template!.footer,
      imageUrl: template!.imageUrl,
      buttons: template!.buttons,
      isListTemplate: template!.templateType == TemplateLibraryType.list,
      listButtonText: template!.listButtonText,
      sections: template!.sections,
    );
  }
}
