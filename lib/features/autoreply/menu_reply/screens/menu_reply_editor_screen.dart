import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/data/models/button_template_model.dart';
import 'package:autoreply/features/autoreply/menu_reply/models/menu_reply_model.dart';
import 'package:autoreply/features/autoreply/menu_reply/services/menu_reply_builder_service.dart';
import 'package:autoreply/features/autoreply/menu_reply/storage/menu_reply_storage.dart';
import 'package:autoreply/features/autoreply/template/storage/autoreply_template_storage.dart';
import 'package:autoreply/features/media/services/cloudflare_upload_service.dart';
import 'package:autoreply/features/sync/services/server_sync_service.dart';
import 'package:autoreply/features/templates/widgets/whatsapp_preview_card.dart';
import 'package:flutter/material.dart';

class MenuReplyEditorScreen extends StatefulWidget {
  final MenuReplyFlow? existingFlow;

  const MenuReplyEditorScreen({super.key, this.existingFlow});

  @override
  State<MenuReplyEditorScreen> createState() => _MenuReplyEditorScreenState();
}

class _MenuReplyEditorScreenState extends State<MenuReplyEditorScreen> {
  final MenuReplyStorage _storage = MenuReplyStorage();
  final AutoReplyTemplateStorage _templateStorage = AutoReplyTemplateStorage();
  final CloudflareUploadService _uploadService = CloudflareUploadService();
  final ServerSyncService _syncService = ServerSyncService();
  final MenuReplyBuilderService _builder = MenuReplyBuilderService();
  final TextEditingController _nameCtrl = TextEditingController();
  final TextEditingController _keywordCtrl = TextEditingController();

  bool _isActive = true;
  bool _saving = false;
  final List<String> _keywords = [];
  List<MenuReplyNode> _nodes = [];
  String _rootNodeId = '';

  @override
  void initState() {
    super.initState();
    _templateStorage.load();
    final flow = widget.existingFlow;
    if (flow != null) {
      _nameCtrl.text = flow.name;
      _isActive = flow.isActive;
      _keywords.addAll(flow.keywords);
      _nodes = List<MenuReplyNode>.from(flow.nodes);
      _rootNodeId = flow.rootNodeId;
    } else {
      final fresh = _builder.newFlow();
      _nodes = List<MenuReplyNode>.from(fresh.nodes);
      _rootNodeId = fresh.rootNodeId;
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

  MenuReplyNode? _findNode(String id) {
    for (final node in _nodes) {
      if (node.id == id) return node;
    }
    return null;
  }

  void _updateNode(MenuReplyNode node) {
    setState(() {
      _nodes = _nodes.map((item) => item.id == node.id ? node : item).toList();
    });
  }

  void _addNode() {
    setState(() {
      _nodes = [..._nodes, _builder.newNode(name: 'Submenu ${_nodes.length}')];
    });
  }

  void _deleteNode(MenuReplyNode node) {
    if (_nodes.length <= 1 || node.id == _rootNodeId) return;
    setState(() {
      _nodes = _nodes.where((item) => item.id != node.id).map((item) {
        final updatedRows = item.rows.map((row) {
          if (row.action.type == MenuReplyActionType.openNode &&
              row.action.nextNodeId == node.id) {
            return row.copyWith(action: row.action.copyWith(nextNodeId: ''));
          }
          return row;
        }).toList();
        return item.copyWith(rows: updatedRows);
      }).toList();
    });
  }

  Future<void> _pickMedia(int nodeIndex, int rowIndex, int stepIndex) async {
    final step = _nodes[nodeIndex].rows[rowIndex].action.steps[stepIndex];
    final mediaType = step.mediaType ?? MenuReplyMediaType.image;
    final uploaded = await _uploadService.pickAndUpload(
      folder: 'menu-reply/${mediaType.name}',
      allowedExtensions: _allowedExtensions(mediaType),
    );
    if (uploaded == null || !mounted) return;

    final steps = List<MenuReplyStep>.from(
      _nodes[nodeIndex].rows[rowIndex].action.steps,
    );
    steps[stepIndex] = steps[stepIndex].copyWith(
      mediaUrl: uploaded.url,
      filename: uploaded.filename,
    );
    _updateRowAction(
      nodeIndex,
      rowIndex,
      _nodes[nodeIndex].rows[rowIndex].action.copyWith(steps: steps),
    );
  }

  List<String> _allowedExtensions(MenuReplyMediaType type) {
    switch (type) {
      case MenuReplyMediaType.image:
        return ['jpg', 'jpeg', 'png', 'webp'];
      case MenuReplyMediaType.video:
        return ['mp4', 'mov', 'mkv', 'webm'];
      case MenuReplyMediaType.audio:
        return ['mp3', 'wav', 'aac', 'ogg', 'm4a'];
      case MenuReplyMediaType.document:
        return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    }
  }

  void _updateRowAction(int nodeIndex, int rowIndex, MenuReplyAction action) {
    final rows = List<MenuReplyRow>.from(_nodes[nodeIndex].rows);
    rows[rowIndex] = rows[rowIndex].copyWith(action: action);
    _updateNode(_nodes[nodeIndex].copyWith(rows: rows));
  }

  void _show(String text) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(text)));
  }

  Future<void> _save() async {
    if (_nameCtrl.text.trim().isEmpty) {
      _show('Flow name required');
      return;
    }
    if (_keywords.isEmpty) {
      _show('Add at least one trigger keyword');
      return;
    }

    for (final node in _nodes) {
      if (node.body.trim().isEmpty) {
        _show('Every menu node needs body text');
        return;
      }
      if (node.rows.isEmpty) {
        _show('Every menu node needs at least one option');
        return;
      }
      for (final row in node.rows) {
        if (row.title.trim().isEmpty) {
          _show('Every menu option needs a title');
          return;
        }
        if (row.action.type == MenuReplyActionType.openNode) {
          if (row.action.nextNodeId.trim().isEmpty) {
            _show('Open submenu rows must select a target node');
            return;
          }
        } else {
          if (row.action.steps.isEmpty) {
            _show('Reply rows need at least one step');
            return;
          }
          for (final step in row.action.steps) {
            if (step.type == MenuReplyStepType.text &&
                step.text.trim().isEmpty) {
              _show('Text reply step cannot be empty');
              return;
            }
            if (step.type == MenuReplyStepType.template &&
                step.templateId.trim().isEmpty) {
              _show('Template reply step must select a template');
              return;
            }
            if (step.type == MenuReplyStepType.media &&
                step.mediaUrl.trim().isEmpty) {
              _show('Media reply step must choose media');
              return;
            }
          }
        }
      }
    }

    final flow = MenuReplyFlow(
      id:
          widget.existingFlow?.id ??
          DateTime.now().millisecondsSinceEpoch.toString(),
      name: _nameCtrl.text.trim(),
      isActive: _isActive,
      keywords: List<String>.from(_keywords),
      rootNodeId: _rootNodeId,
      nodes: List<MenuReplyNode>.from(_nodes),
      createdAt:
          widget.existingFlow?.createdAt ??
          DateTime.now().millisecondsSinceEpoch,
      serverId: widget.existingFlow?.serverId,
    );

    setState(() => _saving = true);
    _storage.addOrUpdate(flow);

    try {
      final serverId = await _syncService.syncMenuReply(flow);
      _storage.addOrUpdate(flow.copyWith(serverId: serverId));
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
    } finally {
      if (mounted) {
        setState(() => _saving = false);
        Navigator.pop(context);
      }
    }
  }

  ButtonTemplateModel? _templateForSteps(List<MenuReplyStep> steps) {
    for (final step in steps) {
      if (step.type != MenuReplyStepType.template || step.templateId.isEmpty) {
        continue;
      }
      for (final template in _templateStorage.templates) {
        if (template.serverId == step.templateId ||
            template.id == step.templateId) {
          return template;
        }
      }
    }
    return null;
  }

  Widget _buildNodeCard(int nodeIndex) {
    final node = _nodes[nodeIndex];
    final isRoot = node.id == _rootNodeId;

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: isRoot
              ? AppColors.primaryBlue.withValues(alpha: 0.35)
              : Colors.grey.shade300,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: TextFormField(
                  initialValue: node.name,
                  decoration: _input(isRoot ? 'Root Menu' : 'Submenu name'),
                  onChanged: (value) => _updateNode(node.copyWith(name: value)),
                ),
              ),
              const SizedBox(width: 10),
              if (!isRoot)
                IconButton(
                  onPressed: () => _deleteNode(node),
                  icon: const Icon(
                    Icons.delete_outline,
                    color: Colors.redAccent,
                  ),
                ),
            ],
          ),
          const SizedBox(height: 12),
          TextFormField(
            initialValue: node.title,
            decoration: _input('Header title'),
            onChanged: (value) => _updateNode(node.copyWith(title: value)),
          ),
          const SizedBox(height: 12),
          TextFormField(
            initialValue: node.body,
            maxLines: 3,
            decoration: _input('Menu body'),
            onChanged: (value) => _updateNode(node.copyWith(body: value)),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextFormField(
                  initialValue: node.footer,
                  decoration: _input('Footer'),
                  onChanged: (value) =>
                      _updateNode(node.copyWith(footer: value)),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: TextFormField(
                  initialValue: node.buttonText,
                  decoration: _input('Button text'),
                  onChanged: (value) =>
                      _updateNode(node.copyWith(buttonText: value)),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Text(
                'Menu Items',
                style: TextStyle(
                  color: Colors.grey.shade700,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const Spacer(),
              TextButton.icon(
                onPressed: () {
                  _updateNode(
                    node.copyWith(rows: [...node.rows, _builder.newReplyRow()]),
                  );
                },
                icon: const Icon(Icons.add),
                label: const Text('Add Item'),
              ),
            ],
          ),
          ...node.rows.asMap().entries.map(
            (entry) => _buildRowCard(nodeIndex, entry.key),
          ),
        ],
      ),
    );
  }

  Widget _buildRowCard(int nodeIndex, int rowIndex) {
    final node = _nodes[nodeIndex];
    final row = node.rows[rowIndex];
    final action = row.action;

    return Container(
      margin: const EdgeInsets.only(top: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF8F9FB),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(
                'Option ${rowIndex + 1}',
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
              const Spacer(),
              IconButton(
                onPressed: () {
                  final rows = List<MenuReplyRow>.from(node.rows)
                    ..removeAt(rowIndex);
                  _updateNode(node.copyWith(rows: rows));
                },
                icon: const Icon(Icons.delete_outline, color: Colors.redAccent),
              ),
            ],
          ),
          TextFormField(
            initialValue: row.title,
            decoration: _input('Option title'),
            onChanged: (value) {
              final rows = List<MenuReplyRow>.from(node.rows);
              rows[rowIndex] = rows[rowIndex].copyWith(title: value);
              _updateNode(node.copyWith(rows: rows));
            },
          ),
          const SizedBox(height: 10),
          TextFormField(
            initialValue: row.description,
            decoration: _input('Option description'),
            onChanged: (value) {
              final rows = List<MenuReplyRow>.from(node.rows);
              rows[rowIndex] = rows[rowIndex].copyWith(description: value);
              _updateNode(node.copyWith(rows: rows));
            },
          ),
          const SizedBox(height: 10),
          DropdownButtonFormField<MenuReplyActionType>(
            value: action.type,
            decoration: _input('Action'),
            items: MenuReplyActionType.values
                .map(
                  (value) => DropdownMenuItem(
                    value: value,
                    child: Text(
                      value == MenuReplyActionType.openNode
                          ? 'Open Submenu'
                          : 'Send Reply',
                    ),
                  ),
                )
                .toList(),
            onChanged: (value) {
              if (value == null) return;
              _updateRowAction(
                nodeIndex,
                rowIndex,
                MenuReplyAction(
                  type: value,
                  nextNodeId: value == MenuReplyActionType.openNode
                      ? action.nextNodeId
                      : '',
                  steps: value == MenuReplyActionType.sendReply
                      ? (action.steps.isEmpty
                            ? [_builder.newTextStep()]
                            : action.steps)
                      : const [],
                ),
              );
            },
          ),
          if (action.type == MenuReplyActionType.openNode) ...[
            const SizedBox(height: 10),
            DropdownButtonFormField<String>(
              value: action.nextNodeId.isEmpty ? null : action.nextNodeId,
              decoration: _input('Target submenu'),
              items: _nodes
                  .where((item) => item.id != node.id)
                  .map(
                    (item) => DropdownMenuItem(
                      value: item.id,
                      child: Text(
                        item.name.isEmpty ? 'Untitled node' : item.name,
                      ),
                    ),
                  )
                  .toList(),
              onChanged: (value) {
                _updateRowAction(
                  nodeIndex,
                  rowIndex,
                  action.copyWith(nextNodeId: value ?? ''),
                );
              },
            ),
          ],
          if (action.type == MenuReplyActionType.sendReply) ...[
            const SizedBox(height: 12),
            Row(
              children: [
                const Text(
                  'Reply Steps',
                  style: TextStyle(fontWeight: FontWeight.bold),
                ),
                const Spacer(),
                TextButton.icon(
                  onPressed: () {
                    final steps = [...action.steps, _builder.newTextStep()];
                    _updateRowAction(
                      nodeIndex,
                      rowIndex,
                      action.copyWith(steps: steps),
                    );
                  },
                  icon: const Icon(Icons.add),
                  label: const Text('Add Reply'),
                ),
              ],
            ),
            ...action.steps.asMap().entries.map(
              (entry) => _buildStepCard(nodeIndex, rowIndex, entry.key),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildStepCard(int nodeIndex, int rowIndex, int stepIndex) {
    final row = _nodes[nodeIndex].rows[rowIndex];
    final step = row.action.steps[stepIndex];
    final templates = _templateStorage.templates;

    return Container(
      margin: const EdgeInsets.only(top: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade300),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(
                'Step ${stepIndex + 1}',
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
              const Spacer(),
              DropdownButton<MenuReplyStepType>(
                value: step.type,
                underline: const SizedBox.shrink(),
                items: MenuReplyStepType.values
                    .map(
                      (value) => DropdownMenuItem(
                        value: value,
                        child: Text(value.name),
                      ),
                    )
                    .toList(),
                onChanged: (value) {
                  if (value == null) return;
                  final steps = List<MenuReplyStep>.from(row.action.steps);
                  steps[stepIndex] = MenuReplyStep(
                    id: step.id,
                    type: value,
                    mediaType: value == MenuReplyStepType.media
                        ? MenuReplyMediaType.image
                        : null,
                  );
                  _updateRowAction(
                    nodeIndex,
                    rowIndex,
                    row.action.copyWith(steps: steps),
                  );
                },
              ),
              IconButton(
                onPressed: () {
                  final steps = List<MenuReplyStep>.from(row.action.steps)
                    ..removeAt(stepIndex);
                  _updateRowAction(
                    nodeIndex,
                    rowIndex,
                    row.action.copyWith(steps: steps),
                  );
                },
                icon: const Icon(Icons.delete_outline, color: Colors.redAccent),
              ),
            ],
          ),
          if (step.type == MenuReplyStepType.text)
            TextFormField(
              initialValue: step.text,
              maxLines: 4,
              decoration: _input('Reply text'),
              onChanged: (value) {
                final steps = List<MenuReplyStep>.from(row.action.steps);
                steps[stepIndex] = step.copyWith(text: value);
                _updateRowAction(
                  nodeIndex,
                  rowIndex,
                  row.action.copyWith(steps: steps),
                );
              },
            ),
          if (step.type == MenuReplyStepType.template)
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
                final steps = List<MenuReplyStep>.from(row.action.steps);
                steps[stepIndex] = step.copyWith(
                  templateId: value,
                  templateName: template.name,
                );
                _updateRowAction(
                  nodeIndex,
                  rowIndex,
                  row.action.copyWith(steps: steps),
                );
              },
            ),
          if (step.type == MenuReplyStepType.media) ...[
            DropdownButtonFormField<MenuReplyMediaType>(
              value: step.mediaType ?? MenuReplyMediaType.image,
              decoration: _input('Media type'),
              items: MenuReplyMediaType.values
                  .map(
                    (value) =>
                        DropdownMenuItem(value: value, child: Text(value.name)),
                  )
                  .toList(),
              onChanged: (value) {
                if (value == null) return;
                final steps = List<MenuReplyStep>.from(row.action.steps);
                steps[stepIndex] = step.copyWith(mediaType: value);
                _updateRowAction(
                  nodeIndex,
                  rowIndex,
                  row.action.copyWith(steps: steps),
                );
              },
            ),
            const SizedBox(height: 10),
            OutlinedButton.icon(
              onPressed: () => _pickMedia(nodeIndex, rowIndex, stepIndex),
              icon: const Icon(Icons.upload_file_rounded),
              label: Text(
                step.filename.isEmpty ? 'Choose Media' : step.filename,
              ),
            ),
            const SizedBox(height: 10),
            TextFormField(
              initialValue: step.text,
              maxLines: 3,
              decoration: _input('Caption / Message'),
              onChanged: (value) {
                final steps = List<MenuReplyStep>.from(row.action.steps);
                steps[stepIndex] = step.copyWith(text: value);
                _updateRowAction(
                  nodeIndex,
                  rowIndex,
                  row.action.copyWith(steps: steps),
                );
              },
            ),
          ],
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

  @override
  Widget build(BuildContext context) {
    final previewNode = _findNode(_rootNodeId);
    final previewRow = previewNode?.rows.isNotEmpty == true
        ? previewNode!.rows.first
        : null;
    final previewAction = previewRow?.action;
    final previewTemplate = _templateForSteps(previewAction?.steps ?? []);

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: Text(
          widget.existingFlow == null ? 'Create Menu Reply' : 'Edit Menu Reply',
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
        onPressed: _addNode,
        backgroundColor: AppColors.primaryBlue,
        icon: const Icon(Icons.account_tree_rounded, color: Colors.white),
        label: const Text(
          'Add Submenu',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
      ),
      body: SafeArea(
        top: false,
        child: Column(
          children: [
          SizedBox(
            height: MediaQuery.of(context).size.height * 0.35,
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
                    Colors.white.withValues(alpha: 0.88),
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
                      child: _IncomingBubble(
                        text: _keywords.isEmpty ? 'menu' : _keywords.first,
                      ),
                    ),
                    const SizedBox(height: 10),
                    _MenuPreviewCard(node: previewNode),
                    const SizedBox(height: 10),
                    if (previewRow != null)
                      Align(
                        alignment: Alignment.centerRight,
                        child: _IncomingBubble(text: previewRow.title),
                      ),
                    const SizedBox(height: 10),
                    if (previewAction?.type == MenuReplyActionType.openNode)
                      _MenuPreviewCard(
                        node: _findNode(previewAction?.nextNodeId ?? ''),
                      )
                    else if (previewTemplate != null)
                      WhatsappPreviewCard(
                        title: previewTemplate.title,
                        caption: previewTemplate.caption,
                        footer: previewTemplate.footer,
                        imageUrl: previewTemplate.imageUrl,
                        buttons: previewTemplate.buttons,
                        isListTemplate:
                            previewTemplate.templateType ==
                            TemplateLibraryType.list,
                        listButtonText: previewTemplate.listButtonText,
                        sections: previewTemplate.sections,
                      )
                    else
                      _ReplyPreviewCard(
                        steps: previewAction?.steps ?? const [],
                      ),
                  ],
                ),
              ),
            ),
          ),
          Expanded(
            child: ListView(
              keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
              padding: EdgeInsets.fromLTRB(
                16,
                16,
                16,
                110 + MediaQuery.of(context).padding.bottom,
              ),
              children: [
                TextField(
                  controller: _nameCtrl,
                  decoration: _input('Flow name'),
                ),
                const SizedBox(height: 12),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  value: _isActive,
                  title: const Text('Enable this menu flow'),
                  subtitle: const Text(
                    'Trigger menu when incoming message matches keywords',
                  ),
                  onChanged: (value) => setState(() => _isActive = value),
                ),
                const SizedBox(height: 12),
                LayoutBuilder(
                  builder: (context, constraints) {
                    final narrow = constraints.maxWidth < 360;
                    if (narrow) {
                      return Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          TextField(
                            controller: _keywordCtrl,
                            decoration: _input('Trigger keyword'),
                            onSubmitted: (_) => _addKeyword(),
                          ),
                          const SizedBox(height: 12),
                          ElevatedButton(
                            onPressed: _addKeyword,
                            child: const Text('Add'),
                          ),
                        ],
                      );
                    }

                    return Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: _keywordCtrl,
                            decoration: _input('Trigger keyword'),
                            onSubmitted: (_) => _addKeyword(),
                          ),
                        ),
                        const SizedBox(width: 12),
                        ElevatedButton(
                          onPressed: _addKeyword,
                          child: const Text('Add'),
                        ),
                      ],
                    );
                  },
                ),
                if (_keywords.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: _keywords
                        .map(
                          (keyword) => Chip(
                            label: Text(keyword),
                            onDeleted: () =>
                                setState(() => _keywords.remove(keyword)),
                          ),
                        )
                        .toList(),
                  ),
                ],
                const SizedBox(height: 16),
                ..._nodes.asMap().entries.map(
                  (entry) => _buildNodeCard(entry.key),
                ),
              ],
            ),
          ),
          ],
        ),
      ),
    );
  }
}

class _IncomingBubble extends StatelessWidget {
  final String text;

  const _IncomingBubble({required this.text});

  @override
  Widget build(BuildContext context) {
    return Container(
      constraints: const BoxConstraints(maxWidth: 220),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(text),
    );
  }
}

class _MenuPreviewCard extends StatelessWidget {
  final MenuReplyNode? node;

  const _MenuPreviewCard({required this.node});

  @override
  Widget build(BuildContext context) {
    final rows = node?.rows ?? const [];
    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        width: 280,
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: const Color(0xFFD9FDD3),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if ((node?.title ?? '').isNotEmpty)
              Text(
                node!.title,
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
            if ((node?.body ?? '').isNotEmpty) ...[
              if ((node?.title ?? '').isNotEmpty) const SizedBox(height: 6),
              Text(node!.body),
            ],
            const SizedBox(height: 10),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                (node?.buttonText ?? '').isEmpty ? 'Select' : node!.buttonText,
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
            ),
            if (rows.isNotEmpty) ...[
              const SizedBox(height: 8),
              ...rows
                  .take(3)
                  .map(
                    (row) => Padding(
                      padding: const EdgeInsets.only(top: 6),
                      child: Text(
                        '• ${row.title}',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: Colors.grey.shade700,
                          fontSize: 12,
                        ),
                      ),
                    ),
                  ),
            ],
          ],
        ),
      ),
    );
  }
}

class _ReplyPreviewCard extends StatelessWidget {
  final List<MenuReplyStep> steps;

  const _ReplyPreviewCard({required this.steps});

  @override
  Widget build(BuildContext context) {
    final step = steps.isEmpty ? null : steps.first;
    String preview = 'Reply preview...';

    if (step != null) {
      switch (step.type) {
        case MenuReplyStepType.media:
          preview = step.filename.isNotEmpty
              ? 'Media: ${step.filename}'
              : 'Media reply preview...';
          break;
        case MenuReplyStepType.template:
          preview = step.templateName.isNotEmpty
              ? 'Template: ${step.templateName}'
              : 'Template reply preview...';
          break;
        case MenuReplyStepType.text:
          preview = step.text.isNotEmpty ? step.text : 'Reply preview...';
          break;
      }
    }

    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        constraints: const BoxConstraints(maxWidth: 260),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: const Color(0xFFD9FDD3),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Text(preview),
      ),
    );
  }
}
