import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/features/group_bulk_sender/models/group_batch_model.dart';
import 'package:autoreply/features/group_bulk_sender/services/group_batch_storage.dart';
import 'package:autoreply/features/group_bulk_sender/services/group_bulk_sender_service.dart';
import 'package:autoreply/features/media/services/cloudflare_upload_service.dart';
import 'package:autoreply/features/whatsapp/models/whatsapp_group_model.dart';
import 'package:autoreply/features/whatsapp/services/whatsapp_api_service.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

class GroupBulkSenderScreen extends StatefulWidget {
  const GroupBulkSenderScreen({super.key});

  @override
  State<GroupBulkSenderScreen> createState() => _GroupBulkSenderScreenState();
}

class _GroupBulkSenderScreenState extends State<GroupBulkSenderScreen> {
  final WhatsappApiService _whatsappApiService = WhatsappApiService();
  final GroupBatchStorage _batchStorage = GroupBatchStorage();
  final GroupBulkSenderService _senderService = GroupBulkSenderService();
  final CloudflareUploadService _uploadService = CloudflareUploadService();

  final TextEditingController _batchNameCtrl = TextEditingController();
  final TextEditingController _textMessageCtrl = TextEditingController();
  final TextEditingController _mediaCaptionCtrl = TextEditingController();

  List<WhatsappGroupModel> _availableGroups = [];
  final Set<String> _selectedGroupIds = <String>{};
  GroupBatchModel? _selectedBatch;
  bool _loadingGroups = true;
  bool _creatingBatch = false;
  bool _sending = false;
  bool _uploading = false;
  int _delaySeconds = 5;
  GroupBroadcastMessageMode _messageMode = GroupBroadcastMessageMode.text;
  GroupBroadcastMediaType _mediaType = GroupBroadcastMediaType.image;
  String _mediaUrl = '';
  String _mediaFilename = '';

  @override
  void initState() {
    super.initState();
    _initialize();
  }

  @override
  void dispose() {
    _batchNameCtrl.dispose();
    _textMessageCtrl.dispose();
    _mediaCaptionCtrl.dispose();
    super.dispose();
  }

  Future<void> _initialize() async {
    await _batchStorage.load();
    await _loadGroups();
    if (!mounted) return;
    setState(() {
      _selectedBatch = _batchStorage.batches.isNotEmpty ? _batchStorage.batches.first : null;
    });
  }

  Future<void> _loadGroups() async {
    setState(() => _loadingGroups = true);
    try {
      final groups = await _whatsappApiService.fetchGroups();
      if (!mounted) return;
      setState(() {
        _availableGroups = groups;
        _loadingGroups = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loadingGroups = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to load groups: $e')),
      );
    }
  }

  Future<void> _saveBatch() async {
    final name = _batchNameCtrl.text.trim();
    final groups = _availableGroups
        .where((group) => _selectedGroupIds.contains(group.id))
        .toList();

    if (name.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Parent group name required')),
      );
      return;
    }

    if (groups.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Select at least one group')),
      );
      return;
    }

    final batch = GroupBatchModel(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      name: name,
      groups: groups,
      createdAt: DateTime.now().millisecondsSinceEpoch,
    );

    _batchStorage.addOrUpdate(batch);
    setState(() {
      _creatingBatch = false;
      _selectedBatch = batch;
      _selectedGroupIds.clear();
      _batchNameCtrl.clear();
    });

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Parent group saved')),
    );
  }

  Future<void> _pickAndUploadMedia() async {
    setState(() => _uploading = true);
    try {
      final uploaded = await _uploadService.pickAndUpload(
        folder: 'group-bulk/${_mediaType.name}',
        type: FileType.any,
        allowedExtensions: _allowedExtensions(_mediaType),
      );
      if (!mounted || uploaded == null) return;
      setState(() {
        _mediaUrl = uploaded.url;
        _mediaFilename = uploaded.filename;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Processed ${uploaded.filename}')),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Upload failed: $e')),
      );
    } finally {
      if (mounted) {
        setState(() => _uploading = false);
      }
    }
  }

  List<String> _allowedExtensions(GroupBroadcastMediaType type) {
    switch (type) {
      case GroupBroadcastMediaType.image:
        return ['jpg', 'jpeg', 'png', 'webp'];
      case GroupBroadcastMediaType.video:
        return ['mp4', 'mov', 'mkv', 'webm'];
      case GroupBroadcastMediaType.audio:
        return ['mp3', 'wav', 'aac', 'ogg', 'm4a'];
      case GroupBroadcastMediaType.document:
        return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    }
  }

  Future<void> _send() async {
    final batch = _selectedBatch;
    if (batch == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Select a parent group first')),
      );
      return;
    }

    if (_messageMode == GroupBroadcastMessageMode.text &&
        _textMessageCtrl.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Write a text message')),
      );
      return;
    }

    if (_messageMode == GroupBroadcastMessageMode.media && _mediaUrl.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Choose media first')),
      );
      return;
    }

    setState(() => _sending = true);
    try {
      final result = _messageMode == GroupBroadcastMessageMode.text
          ? await _senderService.sendText(
              batch: batch,
              message: _textMessageCtrl.text.trim(),
              delaySeconds: _delaySeconds,
            )
          : await _senderService.sendMedia(
              batch: batch,
              mediaType: _mediaType,
              mediaUrl: _mediaUrl,
              caption: _mediaCaptionCtrl.text.trim(),
              filename: _mediaFilename,
              delaySeconds: _delaySeconds,
            );

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Sent ${result.sent}/${result.total} groups'),
          backgroundColor: result.failedGroupIds.isEmpty ? Colors.green : Colors.orange,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Bulk send failed: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) {
        setState(() => _sending = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final batches = _batchStorage.batches;
    final bottomInset = MediaQuery.of(context).padding.bottom;
    final bottomPadding = bottomInset > 0 ? bottomInset + 20 : 20.0;

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: const Text('Group Sender', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        actions: [
          IconButton(
            onPressed: _loadingGroups ? null : _loadGroups,
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: SafeArea(
        top: false,
        child: ListView(
          keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
          padding: EdgeInsets.fromLTRB(16, 16, 16, bottomPadding),
          children: [
            _sectionCard(
            title: 'Parent Groups',
            subtitle: 'Create multiple parent groups and reuse them anytime',
            trailing: ElevatedButton.icon(
              onPressed: () => setState(() => _creatingBatch = !_creatingBatch),
              icon: Icon(_creatingBatch ? Icons.close : Icons.add),
              label: Text(_creatingBatch ? 'Close' : 'New'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primaryBlue,
                foregroundColor: Colors.white,
              ),
            ),
            child: batches.isEmpty
                ? Text(
                    'No parent groups yet. Create one from your fetched groups.',
                    style: TextStyle(color: Colors.grey.shade600),
                  )
                : Column(
                    children: batches.map((batch) {
                      final selected = _selectedBatch?.id == batch.id;
                      return Container(
                        margin: const EdgeInsets.only(bottom: 12),
                        decoration: BoxDecoration(
                          color: selected ? AppColors.primaryBlue.withValues(alpha: 0.08) : Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: selected ? AppColors.primaryBlue : Colors.grey.shade300,
                          ),
                        ),
                        child: ListTile(
                          onTap: () => setState(() => _selectedBatch = batch),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
                          minVerticalPadding: 8,
                          title: Text(
                            batch.name,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontWeight: FontWeight.bold),
                          ),
                          subtitle: Text('${batch.groups.length} groups'),
                          trailing: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              if (selected)
                                const Padding(
                                  padding: EdgeInsets.only(right: 4),
                                  child: Icon(
                                    Icons.check_circle_rounded,
                                    color: AppColors.primaryBlue,
                                    size: 20,
                                  ),
                                ),
                              IconButton(
                                constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                                padding: EdgeInsets.zero,
                                visualDensity: VisualDensity.compact,
                                onPressed: () {
                                  _batchStorage.remove(batch.id);
                                  if (_selectedBatch?.id == batch.id) {
                                    setState(() => _selectedBatch =
                                        _batchStorage.batches.isNotEmpty ? _batchStorage.batches.first : null);
                                  } else {
                                    setState(() {});
                                  }
                                },
                                icon: const Icon(Icons.delete_outline, color: Colors.redAccent),
                              ),
                            ],
                          ),
                        ),
                      );
                    }).toList(),
                  ),
          ),
          if (_creatingBatch) ...[
            const SizedBox(height: 16),
            _sectionCard(
              title: 'Create Parent Group',
              subtitle: 'Select multiple groups and save them under one name',
              child: Column(
                children: [
                  TextField(
                    controller: _batchNameCtrl,
                    decoration: _input('Parent group name'),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      const Icon(Icons.checklist_rounded, size: 18, color: AppColors.primaryBlue),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Selected groups: ${_selectedGroupIds.length}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            color: AppColors.primaryBlue,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Container(
                    constraints: const BoxConstraints(maxHeight: 280),
                    child: _loadingGroups
                        ? const Center(child: CircularProgressIndicator())
                        : ListView(
                            shrinkWrap: true,
                            children: _availableGroups.map<Widget>((group) {
                              final checked = _selectedGroupIds.contains(group.id);
                              final selectable = group.isSelectable;
                              return CheckboxListTile(
                                value: checked,
                                onChanged: selectable
                                    ? (value) {
                                        setState(() {
                                          if (value == true) {
                                            _selectedGroupIds.add(group.id);
                                          } else {
                                            _selectedGroupIds.remove(group.id);
                                          }
                                        });
                                      }
                                    : null,
                                title: Text(
                                  group.name,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    color: Color(0xFF1A1D26),
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                                subtitle: Text(
                                  selectable
                                      ? '${group.participantCount} members'
                                      : 'Admin-only group. You cannot use it.',
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                controlAffinity: ListTileControlAffinity.leading,
                                contentPadding: const EdgeInsets.symmetric(horizontal: 8),
                              );
                            }).toList(),
                          ),
                  ),
                  const SizedBox(height: 12),
                  Align(
                    alignment: Alignment.centerRight,
                    child: ElevatedButton.icon(
                      onPressed: _saveBatch,
                      icon: const Icon(Icons.save_rounded),
                      label: const Text('Save Parent Group'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primaryBlue,
                        foregroundColor: Colors.white,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 16),
          _sectionCard(
            title: 'Selected Parent Group',
            subtitle: _selectedBatch == null
                ? 'Choose a parent group to start sending'
                : '${_selectedBatch!.groups.length} groups inside ${_selectedBatch!.name}',
            child: _selectedBatch == null
                ? Text('No parent group selected', style: TextStyle(color: Colors.grey.shade600))
                : Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: _selectedBatch!.groups.map((group) {
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: Row(
                          children: [
                            const Icon(Icons.groups_rounded, size: 16, color: AppColors.primaryBlue),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                group.name,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      );
                    }).toList(),
                  ),
          ),
          const SizedBox(height: 16),
          _sectionCard(
            title: 'Compose Message',
            subtitle: 'Text ya media bhejo. Media Cloudflare worker par upload hoke link banayega.',
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: _modeButton(
                        label: 'Text',
                        selected: _messageMode == GroupBroadcastMessageMode.text,
                        onTap: () => setState(() => _messageMode = GroupBroadcastMessageMode.text),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _modeButton(
                        label: 'Media',
                        selected: _messageMode == GroupBroadcastMessageMode.media,
                        onTap: () => setState(() => _messageMode = GroupBroadcastMessageMode.media),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                if (_messageMode == GroupBroadcastMessageMode.text)
                  TextField(
                    controller: _textMessageCtrl,
                    maxLines: 5,
                    decoration: _input('Write text message'),
                  )
                else ...[
                  DropdownButtonFormField<GroupBroadcastMediaType>(
                    value: _mediaType,
                    decoration: _input('Media type'),
                    items: GroupBroadcastMediaType.values
                        .map(
                          (type) => DropdownMenuItem(
                            value: type,
                            child: Text(type.name[0].toUpperCase() + type.name.substring(1)),
                          ),
                        )
                        .toList(),
                    onChanged: (value) {
                      if (value == null) return;
                      setState(() {
                        _mediaType = value;
                        _mediaUrl = '';
                        _mediaFilename = '';
                      });
                    },
                  ),
                  const SizedBox(height: 12),
                  OutlinedButton.icon(
                    onPressed: _uploading ? null : _pickAndUploadMedia,
                    icon: _uploading
                        ? const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.upload_file_rounded),
                    label: Text(_uploading ? 'Processing...' : 'Choose Media'),
                  ),
                  if (_mediaFilename.isNotEmpty) ...[
                    const SizedBox(height: 10),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.shade300),
                      ),
                      child: Text(_mediaFilename, overflow: TextOverflow.ellipsis),
                    ),
                  ],
                  const SizedBox(height: 12),
                  TextField(
                    controller: _mediaCaptionCtrl,
                    maxLines: 4,
                    decoration: _input('Caption / Message'),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 16),
          _sectionCard(
            title: 'Timing',
            subtitle: 'Set the delay between each group',
            child: Column(
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Delay between groups'),
                    Text('$_delaySeconds sec', style: const TextStyle(fontWeight: FontWeight.bold)),
                  ],
                ),
                Slider(
                  value: _delaySeconds.toDouble(),
                  min: 0,
                  max: 30,
                  divisions: 30,
                  activeColor: AppColors.primaryBlue,
                  onChanged: (value) => setState(() => _delaySeconds = value.toInt()),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
            SizedBox(
              child: ElevatedButton.icon(
              onPressed: _sending ? null : _send,
              icon: _sending
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Icon(Icons.send_rounded),
              label: Text(
                _sending ? 'Sending...' : 'Start Group Bulk Send',
              ),
              style: ElevatedButton.styleFrom(
                minimumSize: const Size.fromHeight(52),
                backgroundColor: AppColors.primaryBlue,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
            ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _sectionCard({
    required String title,
    required String subtitle,
    required Widget child,
    Widget? trailing,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: LayoutBuilder(
        builder: (context, constraints) {
          final stackHeader = trailing != null && constraints.maxWidth < 380;
          final headerText = Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              const SizedBox(height: 4),
              Text(
                subtitle,
                style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
              ),
            ],
          );

          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (stackHeader) ...[
                headerText,
                const SizedBox(height: 12),
                Align(alignment: Alignment.centerLeft, child: trailing!),
              ] else
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(child: headerText),
                    if (trailing != null) ...[
                      const SizedBox(width: 12),
                      Flexible(child: trailing),
                    ],
                  ],
                ),
              const SizedBox(height: 16),
              child,
            ],
          );
        },
      ),
    );
  }

  Widget _modeButton({
    required String label,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: selected ? AppColors.primaryBlue.withValues(alpha: 0.1) : const Color(0xFFF8F9FB),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: selected ? AppColors.primaryBlue : Colors.grey.shade300),
        ),
        child: Center(
          child: Text(
            label,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              color: selected ? AppColors.primaryBlue : Colors.grey.shade700,
            ),
          ),
        ),
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
