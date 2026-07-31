import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/data/local/template_storage.dart';
import 'package:autoreply/data/models/button_template_model.dart';
import 'package:autoreply/features/sync/services/server_sync_service.dart';
import 'package:flutter/material.dart';

class CreateListTemplateScreen extends StatefulWidget {
  final String screenTitle;
  final void Function(ButtonTemplateModel template)? onSaveTemplate;
  final ButtonTemplateModel? initialTemplate;

  const CreateListTemplateScreen({
    super.key,
    this.screenTitle = 'Create List Template',
    this.onSaveTemplate,
    this.initialTemplate,
  });

  @override
  State<CreateListTemplateScreen> createState() => _CreateListTemplateScreenState();
}

class _CreateListTemplateScreenState extends State<CreateListTemplateScreen> {
  final _formKey = GlobalKey<FormState>();
  final _syncService = ServerSyncService();

  final _nameCtrl = TextEditingController();
  final _titleCtrl = TextEditingController();
  final _captionCtrl = TextEditingController();
  final _footerCtrl = TextEditingController();
  final _buttonTextCtrl = TextEditingController(text: 'Select');

  final List<_SectionDraft> _sections = [_SectionDraft()];

  @override
  void initState() {
    super.initState();
    if (widget.initialTemplate != null) {
      _nameCtrl.text = widget.initialTemplate!.name;
      _titleCtrl.text = widget.initialTemplate!.title;
      _captionCtrl.text = widget.initialTemplate!.caption;
      _footerCtrl.text = widget.initialTemplate!.footer;
      _buttonTextCtrl.text = widget.initialTemplate!.listButtonText ?? 'Select';
      
      _sections.clear();
      for (final s in widget.initialTemplate!.sections) {
        final draftRows = s.rows.map((r) {
          final row = _RowDraft();
          row.titleCtrl.text = r.title;
          row.descriptionCtrl.text = r.description ?? '';
          return row;
        }).toList();
        
        final section = _SectionDraft();
        section.titleCtrl.text = s.title;
        section.rows.clear();
        section.rows.addAll(draftRows);
        _sections.add(section);
      }
    }
  }

  Future<void> _saveTemplate() async {
    if (!_formKey.currentState!.validate()) return;

    final sections = <ListTemplateSection>[];
    for (var i = 0; i < _sections.length; i++) {
      final section = _sections[i];
      final title = section.titleCtrl.text.trim();
      final rows = <ListTemplateRow>[];

      if (title.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Section ${i + 1} title is required')),
        );
        return;
      }

      for (var j = 0; j < section.rows.length; j++) {
        final row = section.rows[j];
        final rowTitle = row.titleCtrl.text.trim();
        final rowDescription = row.descriptionCtrl.text.trim();
        if (rowTitle.isEmpty) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Section ${i + 1}, row ${j + 1} title is required')),
          );
          return;
        }
        rows.add(
          ListTemplateRow(
            id: DateTime.now().microsecondsSinceEpoch.toString(),
            title: rowTitle,
            description: rowDescription,
          ),
        );
      }

      if (rows.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Section ${i + 1} needs at least one row')),
        );
        return;
      }

      sections.add(ListTemplateSection(title: title, rows: rows));
    }

    final template = ButtonTemplateModel(
      id: widget.initialTemplate?.id ?? DateTime.now().millisecondsSinceEpoch.toString(),
      serverId: widget.initialTemplate?.serverId,
      templateType: TemplateLibraryType.list,
      name: _nameCtrl.text.trim(),
      title: _titleCtrl.text.trim(),
      caption: _captionCtrl.text.trim(),
      footer: _footerCtrl.text.trim(),
      buttons: const [],
      listButtonText: _buttonTextCtrl.text.trim(),
      sections: sections,
    );

    final saveTemplate = widget.onSaveTemplate ?? TemplateStorage().addTemplate;
    ButtonTemplateModel templateToStore = template;
    saveTemplate(templateToStore);

    try {
      final serverId = await _syncService.syncTemplate(template);
      if (serverId != null && serverId.isNotEmpty) {
        templateToStore = template.copyWith(serverId: serverId);
        saveTemplate(templateToStore);
      }
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
    }

    if (!mounted) return;
    Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        elevation: 0.5,
        title: Text(widget.screenTitle, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        actions: [
          TextButton.icon(
            onPressed: _saveTemplate,
            icon: const Icon(Icons.check_circle_outline, size: 18),
            label: const Text('Save'),
            style: TextButton.styleFrom(foregroundColor: AppColors.primaryBlue),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.small(
        onPressed: () => setState(() => _sections.add(_SectionDraft())),
        backgroundColor: AppColors.primaryBlue,
        child: const Icon(Icons.add, color: Colors.white),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
          children: [
            _field(_nameCtrl, 'Template Name', required: true),
            const SizedBox(height: 12),
            _field(_titleCtrl, 'Header title'),
            const SizedBox(height: 12),
            _field(_captionCtrl, 'Message body', required: true, maxLines: 3),
            const SizedBox(height: 12),
            _field(_footerCtrl, 'Footer'),
            const SizedBox(height: 12),
            _field(_buttonTextCtrl, 'List button text', required: true),
            const SizedBox(height: 20),
            const Text('Sections', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 10),
            ..._sections.asMap().entries.map((entry) {
              final sectionIndex = entry.key;
              final section = entry.value;
              return _SectionCard(
                sectionIndex: sectionIndex,
                draft: section,
                onChanged: () => setState(() {}),
                onDelete: _sections.length == 1
                    ? null
                    : () => setState(() => _sections.removeAt(sectionIndex)),
              );
            }),
          ],
        ),
      ),
    );
  }

  Widget _field(
    TextEditingController controller,
    String hint, {
    bool required = false,
    int maxLines = 1,
  }) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      validator: required
          ? (value) => value == null || value.trim().isEmpty ? 'Required' : null
          : null,
      decoration: InputDecoration(
        hintText: hint,
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Colors.grey.shade300),
        ),
        focusedBorder: const OutlineInputBorder(
          borderRadius: BorderRadius.all(Radius.circular(12)),
          borderSide: BorderSide(color: AppColors.primaryBlue),
        ),
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  final int sectionIndex;
  final _SectionDraft draft;
  final VoidCallback onChanged;
  final VoidCallback? onDelete;

  const _SectionCard({
    required this.sectionIndex,
    required this.draft,
    required this.onChanged,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.grey.shade300),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text('Section ${sectionIndex + 1}', style: const TextStyle(fontWeight: FontWeight.bold)),
              const Spacer(),
              if (onDelete != null)
                IconButton(
                  onPressed: onDelete,
                  icon: const Icon(Icons.delete_outline, color: Colors.redAccent),
                ),
            ],
          ),
          TextField(
            controller: draft.titleCtrl,
            decoration: const InputDecoration(hintText: 'Section title'),
            onChanged: (_) => onChanged(),
          ),
          const SizedBox(height: 12),
          ...draft.rows.asMap().entries.map((entry) {
            final rowIndex = entry.key;
            final row = entry.value;
            return Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Column(
                children: [
                  TextField(
                    controller: row.titleCtrl,
                    decoration: InputDecoration(
                      hintText: 'Row ${rowIndex + 1} title',
                      suffixIcon: draft.rows.length == 1
                          ? null
                          : IconButton(
                              onPressed: () {
                                draft.rows.removeAt(rowIndex);
                                onChanged();
                              },
                              icon: const Icon(Icons.close),
                            ),
                    ),
                    onChanged: (_) => onChanged(),
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: row.descriptionCtrl,
                    decoration: const InputDecoration(hintText: 'Row description'),
                    onChanged: (_) => onChanged(),
                  ),
                ],
              ),
            );
          }),
          OutlinedButton.icon(
            onPressed: () {
              draft.rows.add(_RowDraft());
              onChanged();
            },
            icon: const Icon(Icons.add),
            label: const Text('Add Row'),
          ),
        ],
      ),
    );
  }
}

class _SectionDraft {
  final TextEditingController titleCtrl = TextEditingController();
  final List<_RowDraft> rows = [_RowDraft()];
}

class _RowDraft {
  final TextEditingController titleCtrl = TextEditingController();
  final TextEditingController descriptionCtrl = TextEditingController();
}
