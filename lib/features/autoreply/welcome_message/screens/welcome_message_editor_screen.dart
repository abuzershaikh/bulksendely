import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/data/models/button_template_model.dart';
import 'package:autoreply/features/autoreply/template/storage/autoreply_template_storage.dart';
import 'package:autoreply/features/autoreply/welcome_message/models/welcome_message_model.dart';
import 'package:autoreply/features/autoreply/welcome_message/storage/welcome_message_storage.dart';
import 'package:autoreply/features/media/services/cloudflare_upload_service.dart';
import 'package:autoreply/features/subscription/models/app_user_subscription.dart';
import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:autoreply/features/subscription/widgets/premium_feature_dialog.dart';
import 'package:autoreply/features/sync/services/server_sync_service.dart';
import 'package:flutter/material.dart';

class WelcomeMessageEditorScreen extends StatefulWidget {
  final WelcomeMessageFlow? existingFlow;

  const WelcomeMessageEditorScreen({super.key, this.existingFlow});

  @override
  State<WelcomeMessageEditorScreen> createState() =>
      _WelcomeMessageEditorScreenState();
}

class _WelcomeMessageEditorScreenState extends State<WelcomeMessageEditorScreen>
    with SingleTickerProviderStateMixin {
  final WelcomeMessageStorage _storage = WelcomeMessageStorage();
  final AutoReplyTemplateStorage _templateStorage = AutoReplyTemplateStorage();
  final CloudflareUploadService _uploadService = CloudflareUploadService();
  final ServerSyncService _syncService = ServerSyncService();
  final SubscriptionService _subscriptionService = SubscriptionService.instance;
  final TextEditingController _nameCtrl = TextEditingController();

  late AnimationController _fabPulseController;
  bool _isActive = true;
  bool _saving = false;
  final List<WelcomeMessageStep> _steps = [];

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
    _fabPulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1400),
      lowerBound: 0.96,
      upperBound: 1.02,
    )..repeat(reverse: true);
    _templateStorage.load();
    final flow = widget.existingFlow;
    if (flow != null) {
      _nameCtrl.text = flow.name;
      _isActive = flow.isActive;
      _steps.addAll(flow.steps);
    }
  }

  @override
  void dispose() {
    _fabPulseController.dispose();
    _nameCtrl.dispose();
    super.dispose();
  }

  Future<void> _addStep(WelcomeMessageStepType type) async {
    if (_freeStepLimitReachedForAdd) {
      await _showAutoReplyLimitDialog();
      return;
    }

    setState(() {
      _steps.add(
        WelcomeMessageStep(
          id: DateTime.now().microsecondsSinceEpoch.toString(),
          type: type,
          mediaType: type == WelcomeMessageStepType.media
              ? WelcomeMessageMediaType.image
              : null,
        ),
      );
    });
  }

  Future<void> _pickMedia(int index, WelcomeMessageMediaType mediaType) async {
    final uploaded = await _uploadService.pickAndUpload(
      folder: 'welcome-message/${mediaType.name}',
      allowedExtensions: _allowedExtensions(mediaType),
    );
    if (uploaded == null || !mounted) return;
    setState(() {
      _steps[index] = _steps[index].copyWith(
        mediaType: mediaType,
        mediaUrl: uploaded.url,
        filename: uploaded.filename,
      );
    });
  }

  List<String> _allowedExtensions(WelcomeMessageMediaType type) {
    switch (type) {
      case WelcomeMessageMediaType.image:
        return ['jpg', 'jpeg', 'png', 'webp'];
      case WelcomeMessageMediaType.video:
        return ['mp4', 'mov', 'mkv', 'webm'];
      case WelcomeMessageMediaType.audio:
        return ['mp3', 'wav', 'aac', 'ogg', 'm4a'];
      case WelcomeMessageMediaType.document:
        return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    }
  }

  Future<void> _save() async {
    if (_nameCtrl.text.trim().isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Flow name required')));
      return;
    }
    if (_steps.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Add at least one message step')),
      );
      return;
    }

    if (!_isPremiumUser &&
        _steps.length > AppUserSubscription.freeAutoReplyLimit) {
      await _showAutoReplyLimitDialog();
      return;
    }

    for (final step in _steps) {
      if (step.type == WelcomeMessageStepType.text &&
          step.text.trim().isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Text step cannot be empty')),
        );
        return;
      }
      if (step.type == WelcomeMessageStepType.media && step.mediaUrl.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Choose media for media step')),
        );
        return;
      }
      if (step.type == WelcomeMessageStepType.template &&
          step.templateId.trim().isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Select template for template step')),
        );
        return;
      }
    }

    final flow = WelcomeMessageFlow(
      id:
          widget.existingFlow?.id ??
          DateTime.now().millisecondsSinceEpoch.toString(),
      name: _nameCtrl.text.trim(),
      isActive: _isActive,
      steps: List<WelcomeMessageStep>.from(_steps),
      createdAt:
          widget.existingFlow?.createdAt ??
          DateTime.now().millisecondsSinceEpoch,
      serverId: widget.existingFlow?.serverId,
    );

    setState(() => _saving = true);
    _storage.addOrUpdate(flow);

    try {
      final serverId = await _syncService.syncWelcomeMessage(flow);
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

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: Text(
          widget.existingFlow == null
              ? 'Create Welcome Message'
              : 'Edit Welcome Message',
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
      floatingActionButton: PopupMenuButton<WelcomeMessageStepType>(
        offset: const Offset(0, -220),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        color: Colors.white,
        itemBuilder: (context) => const [
          PopupMenuItem(
            value: WelcomeMessageStepType.text,
            child: Text('Add Text'),
          ),
          PopupMenuItem(
            value: WelcomeMessageStepType.media,
            child: Text('Add Media'),
          ),
          PopupMenuItem(
            value: WelcomeMessageStepType.template,
            child: Text('Add Template'),
          ),
        ],
        onSelected: (type) {
          _addStep(type);
        },
        child: ScaleTransition(
          scale: _fabPulseController,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [Color(0xFF667EEA), Color(0xFF764BA2)],
              ),
              borderRadius: BorderRadius.circular(18),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFF667EEA).withValues(alpha: 0.35),
                  blurRadius: 16,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
            child: const Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.add_circle_rounded, color: Colors.white, size: 22),
                SizedBox(width: 10),
                Text(
                  'Add Message',
                  style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                    letterSpacing: 0.2,
                  ),
                ),
                SizedBox(width: 8),
                Icon(
                  Icons.keyboard_arrow_up_rounded,
                  color: Colors.white,
                  size: 20,
                ),
              ],
            ),
          ),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
        children: [
          TextField(controller: _nameCtrl, decoration: _input('Flow name')),
          const SizedBox(height: 12),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            value: _isActive,
            title: const Text('Enable this welcome flow'),
            subtitle: const Text('Only new incoming numbers should receive it'),
            onChanged: (value) => setState(() => _isActive = value),
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
            ),
            child: Text(
              'When a new number sends its first message and no previous welcome log exists on server, this sequence will be used.',
              style: TextStyle(color: Colors.grey.shade700),
            ),
          ),
          const SizedBox(height: 16),
          ..._steps.asMap().entries.map((entry) {
            final index = entry.key;
            final step = entry.value;
            return _buildStepCard(index, step, templates);
          }),
        ],
      ),
    );
  }

  Widget _buildStepCard(
    int index,
    WelcomeMessageStep step,
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
                'Step ${index + 1}',
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
              const Spacer(),
              IconButton(
                onPressed: () => setState(() => _steps.removeAt(index)),
                icon: const Icon(Icons.delete_outline, color: Colors.redAccent),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            step.type.name.toUpperCase(),
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: AppColors.primaryBlue,
            ),
          ),
          const SizedBox(height: 10),
          if (step.type == WelcomeMessageStepType.text)
            TextFormField(
              initialValue: step.text,
              maxLines: 4,
              decoration: _input('Welcome message text'),
              onChanged: (value) => setState(
                () => _steps[index] = _steps[index].copyWith(text: value),
              ),
            ),
          if (step.type == WelcomeMessageStepType.media) ...[
            DropdownButtonFormField<WelcomeMessageMediaType>(
              value: step.mediaType ?? WelcomeMessageMediaType.image,
              decoration: _input('Media type'),
              items: WelcomeMessageMediaType.values
                  .map(
                    (value) =>
                        DropdownMenuItem(value: value, child: Text(value.name)),
                  )
                  .toList(),
              onChanged: (value) {
                if (value == null) return;
                setState(() {
                  _steps[index] = _steps[index].copyWith(mediaType: value);
                });
              },
            ),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: () => _pickMedia(
                index,
                step.mediaType ?? WelcomeMessageMediaType.image,
              ),
              icon: const Icon(Icons.upload_file_rounded),
              label: const Text('Choose Media'),
            ),
            if (step.filename.isNotEmpty) ...[
              const SizedBox(height: 10),
              Text(step.filename, overflow: TextOverflow.ellipsis),
            ],
            const SizedBox(height: 12),
            TextFormField(
              initialValue: step.text,
              maxLines: 3,
              decoration: _input('Caption / Message'),
              onChanged: (value) => setState(
                () => _steps[index] = _steps[index].copyWith(text: value),
              ),
            ),
          ],
          if (step.type == WelcomeMessageStepType.template)
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
