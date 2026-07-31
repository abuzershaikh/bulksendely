import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/data/local/template_storage.dart';
import 'package:autoreply/data/models/button_template_model.dart';
import 'package:autoreply/features/sync/services/server_sync_service.dart';
import 'package:autoreply/features/templates/widgets/whatsapp_preview_card.dart';
import 'package:flutter/material.dart';

class CreateTemplateScreen extends StatefulWidget {
  final String screenTitle;
  final void Function(ButtonTemplateModel template)? onSaveTemplate;
  final ButtonTemplateModel? initialTemplate;

  const CreateTemplateScreen({
    super.key,
    this.screenTitle = 'Create Template',
    this.onSaveTemplate,
    this.initialTemplate,
  });

  @override
  State<CreateTemplateScreen> createState() => _CreateTemplateScreenState();
}

class _CreateTemplateScreenState extends State<CreateTemplateScreen> {
  final _formKey = GlobalKey<FormState>();
  final _syncService = ServerSyncService();

  final _nameCtrl = TextEditingController();
  final _titleCtrl = TextEditingController();
  final _captionCtrl = TextEditingController();
  final _footerCtrl = TextEditingController();
  final _imgUrlCtrl = TextEditingController();

  final List<TemplateButton> _buttons = [];

  // Preview expand state
  bool _previewExpanded = false;

  @override
  void initState() {
    super.initState();
    if (widget.initialTemplate != null) {
      _nameCtrl.text = widget.initialTemplate!.name;
      _titleCtrl.text = widget.initialTemplate!.title;
      _captionCtrl.text = widget.initialTemplate!.caption;
      _footerCtrl.text = widget.initialTemplate!.footer;
      _imgUrlCtrl.text = widget.initialTemplate!.imageUrl ?? '';
      _buttons.addAll(widget.initialTemplate!.buttons);
    }
  }

  void _onFieldChanged(String _) => setState(() {});

  // ── Add Button via Bottom Sheet ──
  void _showAddMenu() {
    final maxButtonsReached = _buttons.length >= 3;

    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 40, height: 4,
                  decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
                ),
                const SizedBox(height: 16),
                const Text('Add Element', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                const SizedBox(height: 12),
                _MenuTile(
                  icon: Icons.image_outlined, color: Colors.teal,
                  title: 'Header Image', subtitle: 'Add image URL',
                  onTap: () { Navigator.pop(ctx); _showImageUrlDialog(); },
                ),
                _MenuTile(
                  icon: Icons.reply_rounded, color: Colors.blue,
                  title: 'Quick Reply Button', subtitle: 'Text reply button',
                  enabled: !maxButtonsReached,
                  onTap: () { Navigator.pop(ctx); _showButtonDialog(ButtonTemplateType.text); },

                ),
                _MenuTile(
                  icon: Icons.link, color: Colors.orange,
                  title: 'Link Button', subtitle: 'Open URL',
                  enabled: !maxButtonsReached,
                  onTap: () { Navigator.pop(ctx); _showButtonDialog(ButtonTemplateType.link); },

                ),
                _MenuTile(
                  icon: Icons.phone_rounded, color: Colors.green,
                  title: 'Call Button', subtitle: 'Direct phone call',
                  enabled: !maxButtonsReached,
                  onTap: () { Navigator.pop(ctx); _showButtonDialog(ButtonTemplateType.call); },

                ),
                _MenuTile(
                  icon: Icons.copy_rounded, color: Colors.purple,
                  title: 'Copy Code Button', subtitle: 'Copy text to clipboard',
                  enabled: !maxButtonsReached,
                  onTap: () { Navigator.pop(ctx); _showButtonDialog(ButtonTemplateType.copy); },

                ),
              ],
            ),
          ),
        );
      },
    );
  }

  void _showImageUrlDialog() {
    final ctrl = TextEditingController(text: _imgUrlCtrl.text);
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Header Image', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        content: TextField(
          controller: ctrl,
          style: const TextStyle(fontSize: 13),
          decoration: InputDecoration(
            hintText: 'https://example.com/image.jpg',
            hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 13),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            isDense: true,
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          TextButton(
            onPressed: () {
              _imgUrlCtrl.text = ctrl.text;
              setState(() {});
              Navigator.pop(ctx);
            },
            child: const Text('Apply'),
          ),
        ],
      ),
    );
  }

  void _showButtonDialog(ButtonTemplateType preselectedType) {
    final textCtrl = TextEditingController();
    final actionCtrl = TextEditingController();
    ButtonTemplateType selectedType = preselectedType;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return StatefulBuilder(builder: (ctx, setModalState) {
          return Padding(
            padding: EdgeInsets.only(
              bottom: MediaQuery.of(ctx).viewInsets.bottom,
              left: 20, right: 20, top: 20,
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 40, height: 4,
                    decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  'Add ${_typeLabel(selectedType)}',
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 14),
                TextField(
                  controller: textCtrl,
                  maxLength: 20,
                  style: const TextStyle(fontSize: 13),
                  decoration: InputDecoration(
                    labelText: 'Button Text',
                    labelStyle: const TextStyle(fontSize: 13),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    isDense: true,
                    counterStyle: const TextStyle(fontSize: 10),
                  ),
                ),
                const SizedBox(height: 10),
                if (selectedType == ButtonTemplateType.link)
                  _compactField(actionCtrl, 'URL', 'https://...'),
                if (selectedType == ButtonTemplateType.call)
                  _compactField(actionCtrl, 'Phone Number', '+91...', keyboard: TextInputType.phone),
                if (selectedType == ButtonTemplateType.copy)
                  _compactField(actionCtrl, 'Code / Text to Copy', 'ABC123'),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  height: 42,
                  child: ElevatedButton(
                    onPressed: () {
                      final buttonText = textCtrl.text.trim();
                      final actionValue = actionCtrl.text.trim();
                      final requiresAction = selectedType != ButtonTemplateType.text;

                      if (buttonText.isEmpty) return;
                      if (requiresAction && actionValue.isEmpty) return;

                      setState(() {
                        _buttons.add(TemplateButton(
                          type: selectedType,
                          displayText: buttonText,
                          url: selectedType == ButtonTemplateType.link ? actionValue : null,
                          phoneNumber: selectedType == ButtonTemplateType.call ? actionValue : null,
                          copyText: selectedType == ButtonTemplateType.copy ? actionValue : null,
                        ));
                      });
                      Navigator.pop(ctx);
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primaryBlue,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    child: const Text('Add', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  ),
                ),
                const SizedBox(height: 16),
              ],
            ),
          );
        });
      },
    );
  }

  Widget _compactField(TextEditingController ctrl, String label, String hint, {TextInputType? keyboard}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: TextField(
        controller: ctrl,
        keyboardType: keyboard,
        style: const TextStyle(fontSize: 13),
        decoration: InputDecoration(
          labelText: label,
          labelStyle: const TextStyle(fontSize: 13),
          hintText: hint,
          hintStyle: TextStyle(fontSize: 12, color: Colors.grey.shade400),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          isDense: true,
        ),
      ),
    );
  }

  String _typeLabel(ButtonTemplateType t) {
    switch (t) {
      case ButtonTemplateType.text: return 'Quick Reply';
      case ButtonTemplateType.link: return 'Link Button';
      case ButtonTemplateType.call: return 'Call Button';
      case ButtonTemplateType.copy: return 'Copy Code';
    }
  }

  IconData _typeIcon(ButtonTemplateType t) {
    switch (t) {
      case ButtonTemplateType.text: return Icons.reply_rounded;
      case ButtonTemplateType.link: return Icons.link;
      case ButtonTemplateType.call: return Icons.phone_rounded;
      case ButtonTemplateType.copy: return Icons.copy_rounded;
    }
  }

  Future<void> _saveTemplate() async {
    if (!_formKey.currentState!.validate()) return;
    if (_buttons.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('At least one button is required to sync the template'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }
    if (_buttons.length > 3) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Only up to 3 buttons are allowed'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    final newTemplate = ButtonTemplateModel(
      id: widget.initialTemplate?.id ?? DateTime.now().millisecondsSinceEpoch.toString(),
      serverId: widget.initialTemplate?.serverId,
      name: _nameCtrl.text.trim(),
      title: _titleCtrl.text.trim(),
      caption: _captionCtrl.text.trim(),
      footer: _footerCtrl.text.trim(),
      imageUrl: _imgUrlCtrl.text.trim().isNotEmpty ? _imgUrlCtrl.text.trim() : null,
      buttons: _buttons,
    );

    final saveTemplate = widget.onSaveTemplate ?? TemplateStorage().addTemplate;
    ButtonTemplateModel templateToStore = newTemplate;
    saveTemplate(templateToStore);

    try {
      final serverId = await _syncService.syncTemplate(newTemplate);
      if (serverId != null && serverId.isNotEmpty) {
        templateToStore = newTemplate.copyWith(serverId: serverId);
        saveTemplate(templateToStore);
      }
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Saved'),
          backgroundColor: Colors.green,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Saved locally. Sync failed: $e'),
          backgroundColor: Colors.orange,
        ),
      );
    }

    if (!mounted) return;
    Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    final keyboardHeight = MediaQuery.of(context).viewInsets.bottom;
    final isKeyboardOpen = keyboardHeight > 50;
    final screenHeight = MediaQuery.of(context).size.height;

    // Auto-calculate preview height based on keyboard state
    double previewHeight;
    if (isKeyboardOpen) {
      previewHeight = 0; // hide preview completely when keyboard is up
    } else if (_previewExpanded) {
      previewHeight = screenHeight * 0.65;
    } else {
      previewHeight = screenHeight * 0.38;
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      resizeToAvoidBottomInset: true,
      appBar: AppBar(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        elevation: 0.5,
        titleSpacing: 0,
        title: Text(
          widget.screenTitle,
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1A1D26)),
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 8),
            child: TextButton.icon(
              onPressed: _saveTemplate,
              icon: const Icon(Icons.check_circle_outline, size: 18),
              label: const Text('Save', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              style: TextButton.styleFrom(foregroundColor: AppColors.primaryBlue),
            ),
          ),
        ],
      ),
      // FAB for the "+" menu
      floatingActionButton: FloatingActionButton(
        onPressed: _showAddMenu,
        backgroundColor: AppColors.primaryBlue,
        mini: true,
        child: const Icon(Icons.add, color: Colors.white, size: 22),
      ),
      body: Column(
        children: [
          // ── TOP: PREVIEW (collapses when keyboard opens) ──
          AnimatedContainer(
            duration: const Duration(milliseconds: 250),
            curve: Curves.easeInOut,
            height: previewHeight,
            clipBehavior: Clip.hardEdge,
            decoration: const BoxDecoration(),
            child: Stack(
              children: [
                // Preview area
                Container(
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
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.fromLTRB(12, 36, 12, 12),
                    child: WhatsappPreviewCard(
                      title: _titleCtrl.text,
                      caption: _captionCtrl.text.isEmpty
                          ? 'Your message preview...'
                          : _captionCtrl.text,
                      footer: _footerCtrl.text,
                      imageUrl: _imgUrlCtrl.text,
                      buttons: _buttons,
                    ),
                  ),
                ),

                // Label overlay
                Positioned(
                  top: 8, left: 12,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.45),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: const Text(
                      'PREVIEW',
                      style: TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold, letterSpacing: 1),
                    ),
                  ),
                ),

                // Expand / Collapse toggle
                Positioned(
                  top: 6, right: 8,
                  child: GestureDetector(
                    onTap: () => setState(() => _previewExpanded = !_previewExpanded),
                    child: Container(
                      width: 30, height: 30,
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.35),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Icon(
                        _previewExpanded ? Icons.close_fullscreen_rounded : Icons.open_in_full_rounded,
                        color: Colors.white, size: 16,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),

          // ── BOTTOM: EDIT FORM (scrollable canvas) ──
          Expanded(
            child: Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.only(
                  topLeft: Radius.circular(20),
                  topRight: Radius.circular(20),
                ),
                boxShadow: [
                  BoxShadow(color: Color(0x15000000), blurRadius: 10, offset: Offset(0, -2)),
                ],
              ),
              child: Form(
                key: _formKey,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 20, 16, 80),
                  children: [
                    // ── Template Name ──
                    _SectionLabel(label: 'Template Name'),
                    const SizedBox(height: 6),
                    _buildSmallField(
                      controller: _nameCtrl,
                      hint: 'e.g., Promo Offer',
                      validator: (v) => (v == null || v.isEmpty) ? 'Required' : null,
                    ),
                    const SizedBox(height: 16),

                    // ── Message Content ──
                    _SectionLabel(label: 'Message Content'),
                    const SizedBox(height: 6),
                    _buildSmallField(
                      controller: _titleCtrl,
                      hint: 'Header title (bold, optional)',
                      onChanged: _onFieldChanged,
                    ),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _captionCtrl,
                      onChanged: _onFieldChanged,
                      maxLines: 3,
                      style: const TextStyle(fontSize: 13),
                      validator: (v) => (v == null || v.isEmpty) ? 'Message required' : null,
                      decoration: InputDecoration(
                        hintText: 'Main message body...',
                        hintStyle: TextStyle(fontSize: 13, color: Colors.grey.shade400),
                        filled: true,
                        fillColor: const Color(0xFFF8F9FB),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(10),
                          borderSide: BorderSide(color: Colors.grey.shade200),
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(10),
                          borderSide: BorderSide(color: Colors.grey.shade200),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(10),
                          borderSide: const BorderSide(color: AppColors.primaryBlue),
                        ),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        isDense: true,
                      ),
                    ),
                    const SizedBox(height: 8),
                    _buildSmallField(
                      controller: _footerCtrl,
                      hint: 'Footer text (optional)',
                      onChanged: _onFieldChanged,
                    ),
                    const SizedBox(height: 16),

                    // ── Image Preview Chip ──
                    if (_imgUrlCtrl.text.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                          decoration: BoxDecoration(
                            color: Colors.teal.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: Colors.teal.withValues(alpha: 0.2)),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.image_outlined, size: 16, color: Colors.teal),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  _imgUrlCtrl.text,
                                  style: const TextStyle(fontSize: 11, color: Colors.teal),
                                  maxLines: 1, overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              GestureDetector(
                                onTap: () { _imgUrlCtrl.clear(); setState(() {}); },
                                child: const Icon(Icons.close, size: 14, color: Colors.teal),
                              ),
                            ],
                          ),
                        ),
                      ),

                    // ── Buttons List ──
                    if (_buttons.isNotEmpty) ...[
                      _SectionLabel(label: 'Buttons (${_buttons.length})'),
                      const SizedBox(height: 6),
                      ..._buttons.asMap().entries.map((e) {
                        final i = e.key;
                        final b = e.value;
                        return Container(
                          margin: const EdgeInsets.only(bottom: 6),
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF8F9FB),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: Colors.grey.shade200),
                          ),
                          child: Row(
                            children: [
                              Icon(_typeIcon(b.type), size: 16, color: AppColors.primaryBlue),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(b.displayText, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                                    Text(
                                      _typeLabel(b.type),
                                      style: TextStyle(fontSize: 10, color: Colors.grey.shade500),
                                    ),
                                  ],
                                ),
                              ),
                              GestureDetector(
                                onTap: () => setState(() => _buttons.removeAt(i)),
                                child: Container(
                                  width: 24, height: 24,
                                  decoration: BoxDecoration(
                                    color: Colors.red.withValues(alpha: 0.08),
                                    borderRadius: BorderRadius.circular(6),
                                  ),
                                  child: const Icon(Icons.close, size: 13, color: Colors.redAccent),
                                ),
                              ),
                            ],
                          ),
                        );
                      }),
                      const SizedBox(height: 8),
                    ],

                    // ── Hint: Use + button ──
                    Center(
                      child: Text(
                        'Tap  +  to add image or buttons',
                        style: TextStyle(fontSize: 11, color: Colors.grey.shade400),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSmallField({
    required TextEditingController controller,
    required String hint,
    String? Function(String?)? validator,
    void Function(String)? onChanged,
  }) {
    return TextFormField(
      controller: controller,
      onChanged: onChanged,
      validator: validator,
      style: const TextStyle(fontSize: 13),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: TextStyle(fontSize: 13, color: Colors.grey.shade400),
        filled: true,
        fillColor: const Color(0xFFF8F9FB),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: Colors.grey.shade200),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: Colors.grey.shade200),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: AppColors.primaryBlue),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        isDense: true,
      ),
    );
  }
}

// ── Section Label ──
class _SectionLabel extends StatelessWidget {
  final String label;
  const _SectionLabel({required this.label});

  @override
  Widget build(BuildContext context) {
    return Text(
      label,
      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF555A6E), letterSpacing: 0.3),
    );
  }
}

// ── Add-Menu Tile ──
class _MenuTile extends StatelessWidget {
  final IconData icon;
  final Color color;
  final String title;
  final String subtitle;
  final VoidCallback onTap;
  final bool enabled;

  const _MenuTile({
    required this.icon,
    required this.color,
    required this.title,
    required this.subtitle,
    required this.onTap,
    this.enabled = true,
  });

  @override
  Widget build(BuildContext context) {
    return Opacity(
      opacity: enabled ? 1.0 : 0.4,
      child: ListTile(
        dense: true,
        enabled: enabled,
        onTap: enabled ? onTap : null,
        leading: Container(
          width: 36, height: 36,
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(icon, size: 18, color: color),
        ),
        title: Text(title, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
        subtitle: Text(subtitle, style: TextStyle(fontSize: 11, color: Colors.grey.shade500)),
        trailing: enabled
            ? Icon(Icons.arrow_forward_ios_rounded, size: 13, color: Colors.grey.shade400)
            : const Text('Max', style: TextStyle(fontSize: 10, color: Colors.redAccent)),
      ),
    );
  }
}
