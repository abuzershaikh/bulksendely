import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/features/media/services/cloudflare_upload_service.dart';
import 'package:autoreply/features/whatsapp/models/parent_whatsapp_group_model.dart';
import 'package:autoreply/features/whatsapp/screens/create_parent_group_screen.dart';
import 'package:autoreply/features/whatsapp/services/linked_group_broadcast_service.dart';
import 'package:autoreply/features/whatsapp/services/parent_whatsapp_group_storage.dart';
import 'package:autoreply/features/whatsapp/services/whatsapp_api_service.dart';
import 'package:flutter/material.dart';

class ParentGroupListScreen extends StatefulWidget {
  const ParentGroupListScreen({super.key});

  @override
  State<ParentGroupListScreen> createState() => _ParentGroupListScreenState();
}

class _ParentGroupListScreenState extends State<ParentGroupListScreen> {
  final ParentWhatsappGroupStorage _storage = ParentWhatsappGroupStorage();
  final LinkedGroupBroadcastService _broadcastService =
      LinkedGroupBroadcastService();
  final CloudflareUploadService _uploadService = CloudflareUploadService();
  final TextEditingController _messageCtrl = TextEditingController();
  final TextEditingController _mediaCaptionCtrl = TextEditingController();

  final Set<String> _selectedParentGroupIds = <String>{};
  final Set<String> _selectedTargetKeys = <String>{};
  bool _isSending = false;
  bool _isUploading = false;
  _ComposerMode _composerMode = _ComposerMode.text;
  _ComposerMediaType _mediaType = _ComposerMediaType.image;
  String _mediaUrl = '';
  String _mediaFilename = '';

  String _targetKey(LinkedWhatsappGroupItem item) {
    return '${item.instanceId}__${item.groupId}';
  }

  List<LinkedWhatsappGroupItem> _uniqueTargets(ParentWhatsappGroupModel group) {
    final seen = <String>{};
    final items = <LinkedWhatsappGroupItem>[];
    for (final item in group.linkedGroups) {
      final key = _targetKey(item);
      if (seen.add(key)) {
        items.add(item);
      }
    }
    return items;
  }

  int _instanceCount(ParentWhatsappGroupModel group) {
    return group.linkedGroups.map((item) => item.instanceId).toSet().length;
  }

  String _instanceLabel(List<LinkedWhatsappGroupItem> items) {
    if (items.isEmpty) {
      return 'Unknown Number';
    }
    final first = items.first;
    return WhatsappApiService.formatLinkedNumber(first.instanceNumber) ??
        (first.instanceNumber.isNotEmpty
            ? first.instanceNumber
            : first.instanceName);
  }

  List<ParentWhatsappGroupModel> get _selectedParentGroups => _storage.groups
      .where((group) => _selectedParentGroupIds.contains(group.id))
      .toList(growable: false);

  List<LinkedWhatsappGroupItem> get _selectedParentTargets {
    final seen = <String>{};
    final items = <LinkedWhatsappGroupItem>[];
    for (final group in _selectedParentGroups) {
      for (final target in group.linkedGroups) {
        final key = _targetKey(target);
        if (seen.add(key)) {
          items.add(target);
        }
      }
    }
    return items;
  }

  int _selectedGroupCount() {
    return _selectedParentTargets
        .where((item) => _selectedTargetKeys.contains(_targetKey(item)))
        .length;
  }

  int _selectedNumberCount() {
    return _selectedParentTargets
        .where((item) => _selectedTargetKeys.contains(_targetKey(item)))
        .map((item) => item.instanceId)
        .toSet()
        .length;
  }

  bool _isTargetSelected(LinkedWhatsappGroupItem item) {
    return _selectedTargetKeys.contains(_targetKey(item));
  }

  bool _isInstanceFullySelected(List<LinkedWhatsappGroupItem> items) {
    return items.isNotEmpty && items.every(_isTargetSelected);
  }

  bool _isAllSelected(List<LinkedWhatsappGroupItem> items) {
    return items.isNotEmpty && items.every(_isTargetSelected);
  }

  void _toggleTargetSelection(LinkedWhatsappGroupItem item, bool selected) {
    setState(() {
      if (selected) {
        _selectedTargetKeys.add(_targetKey(item));
      } else {
        _selectedTargetKeys.remove(_targetKey(item));
      }
    });
  }

  void _toggleInstanceSelection(
    List<LinkedWhatsappGroupItem> items,
    bool selected,
  ) {
    setState(() {
      for (final item in items) {
        final key = _targetKey(item);
        if (selected) {
          _selectedTargetKeys.add(key);
        } else {
          _selectedTargetKeys.remove(key);
        }
      }
    });
  }

  void _toggleAllTargets(List<LinkedWhatsappGroupItem> items, bool selected) {
    setState(() {
      for (final item in items) {
        final key = _targetKey(item);
        if (selected) {
          _selectedTargetKeys.add(key);
        } else {
          _selectedTargetKeys.remove(key);
        }
      }
    });
  }

  void _toggleParentGroupSelection(ParentWhatsappGroupModel group) {
    final targetKeys = _uniqueTargets(group).map(_targetKey).toSet();
    setState(() {
      final isSelected = _selectedParentGroupIds.contains(group.id);
      if (isSelected) {
        _selectedParentGroupIds.remove(group.id);
      } else {
        _selectedParentGroupIds.add(group.id);
        _selectedTargetKeys.addAll(targetKeys);
      }

      final allowedKeys = _selectedParentTargets.map(_targetKey).toSet();
      _selectedTargetKeys.removeWhere((key) => !allowedKeys.contains(key));
    });
  }

  @override
  void initState() {
    super.initState();
    _storage.addListener(_onStorageUpdated);
    _loadGroups();
  }

  @override
  void dispose() {
    _storage.removeListener(_onStorageUpdated);
    _messageCtrl.dispose();
    _mediaCaptionCtrl.dispose();
    super.dispose();
  }

  void _onStorageUpdated() {
    if (!mounted) return;
    setState(() {
      final validParentIds = _storage.groups.map((group) => group.id).toSet();
      _selectedParentGroupIds.removeWhere((id) => !validParentIds.contains(id));
      final availableKeys = _selectedParentTargets.map(_targetKey).toSet();
      _selectedTargetKeys.removeWhere((key) => !availableKeys.contains(key));
    });
  }

  Future<void> _loadGroups({bool forceRefresh = false}) async {
    try {
      await _storage.load(forceRefresh: forceRefresh);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to load parent groups: $e')),
      );
    }
  }

  Future<void> _sendMessage() async {
    final selectedGroups = _selectedParentGroups;
    final message = _messageCtrl.text.trim();
    final caption = _mediaCaptionCtrl.text.trim();

    if (selectedGroups.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Select at least one parent group first')),
      );
      return;
    }

    if (_selectedTargetKeys.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Select at least one linked group')),
      );
      return;
    }

    if (_composerMode == _ComposerMode.text && message.isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Write a message first')));
      return;
    }

    if (_composerMode == _ComposerMode.media && _mediaUrl.isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Choose media first')));
      return;
    }

    if (_composerMode == _ComposerMode.media && caption.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Write caption/message for media')),
      );
      return;
    }

    final targets = _selectedParentTargets
        .where((item) => _selectedTargetKeys.contains(_targetKey(item)))
        .toList(growable: false);

    if (targets.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Select at least one linked group')),
      );
      return;
    }

    setState(() => _isSending = true);
    try {
      final mappedTargets = targets
          .map(
            (item) => LinkedGroupBroadcastTarget(
              instanceId: item.instanceId,
              linkedNumber:
                  WhatsappApiService.formatLinkedNumber(item.instanceNumber) ??
                  item.instanceNumber,
              linkedName: item.instanceName,
              groupId: item.groupId,
              groupName: item.groupName,
            ),
          )
          .toList(growable: false);
      final result = _composerMode == _ComposerMode.text
          ? await _broadcastService.sendText(
              batchName: selectedGroups.length == 1
                  ? selectedGroups.first.name
                  : 'Parent Groups (${selectedGroups.length})',
              message: message,
              targets: mappedTargets,
              delaySeconds: 0,
            )
          : await _broadcastService.sendMedia(
              batchName: selectedGroups.length == 1
                  ? selectedGroups.first.name
                  : 'Parent Groups (${selectedGroups.length})',
              mediaType: _mediaType.name,
              mediaUrl: _mediaUrl,
              caption: caption,
              filename: _mediaFilename,
              targets: mappedTargets,
              delaySeconds: 0,
            );

      if (!mounted) return;
      _messageCtrl.clear();
      _mediaCaptionCtrl.clear();
      if (_composerMode == _ComposerMode.media) {
        setState(() {
          _mediaUrl = '';
          _mediaFilename = '';
        });
      }
      final summary = result.queuedGroups > 0
          ? 'Queued ${result.queuedGroups} groups across ${result.totalNumbers} numbers'
          : 'Sent ${result.sentGroups} groups across ${result.totalNumbers} numbers';
      final suffix = result.usedFallback
          ? '${result.sentGroups > 0 ? ' | ${result.sentGroups} sent direct fallback' : ''}${result.failedGroups > 0 ? ' | ${result.failedGroups} failed' : ''}'
          : result.failedGroups > 0
          ? ' | ${result.failedGroups} failed'
          : '';

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('$summary$suffix'),
          backgroundColor: result.failedGroups > 0 && result.queuedGroups == 0
              ? Colors.orange
              : Colors.green,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to send selected groups: $e'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _isSending = false);
      }
    }
  }

  Future<void> _pickAndUploadMedia() async {
    setState(() => _isUploading = true);
    try {
      final uploaded = await _uploadService.pickAndUpload(
        folder: 'parent-groups/${_mediaType.name}',
        allowedExtensions: _allowedExtensions(_mediaType),
      );
      if (!mounted || uploaded == null) return;
      setState(() {
        _mediaUrl = uploaded.url;
        _mediaFilename = uploaded.filename;
      });
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Processed ${uploaded.filename}')));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Upload failed: $e')));
    } finally {
      if (mounted) {
        setState(() => _isUploading = false);
      }
    }
  }

  List<String> _allowedExtensions(_ComposerMediaType type) {
    switch (type) {
      case _ComposerMediaType.image:
        return ['jpg', 'jpeg', 'png', 'webp'];
      case _ComposerMediaType.video:
        return ['mp4', 'mov', 'mkv', 'webm'];
    }
  }

  Future<void> _deleteGroup(ParentWhatsappGroupModel group) async {
    try {
      await _storage.removeGroup(group.id);
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Deleted ${group.name}')));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to delete ${group.name}: $e')),
      );
    }
  }

  Widget _buildLinkedGroupSections(List<LinkedWhatsappGroupItem> targets) {
    final grouped = <String, List<LinkedWhatsappGroupItem>>{};
    for (final item in targets) {
      grouped
          .putIfAbsent(item.instanceId, () => <LinkedWhatsappGroupItem>[])
          .add(item);
    }
    final entries = grouped.entries.toList();

    return Column(
      children: entries.map((entry) {
        final items = entry.value;
        final numberLabel = _instanceLabel(items);
        final selected = _isInstanceFullySelected(items);

        return Container(
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.all(12),
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
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          numberLabel,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 14,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${items.length} groups linked with this number',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Checkbox(
                    value: selected,
                    activeColor: AppColors.primaryBlue,
                    visualDensity: VisualDensity.compact,
                    materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    onChanged: (value) =>
                        _toggleInstanceSelection(items, value ?? false),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              ...items.map((linkedGroup) {
                final isSelected = _isTargetSelected(linkedGroup);
                return Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: isSelected
                        ? AppColors.primaryBlue.withValues(alpha: 0.06)
                        : const Color(0xFFF8F9FB),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                      color: isSelected
                          ? AppColors.primaryBlue.withValues(alpha: 0.3)
                          : Colors.grey.shade300,
                    ),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Checkbox(
                        value: isSelected,
                        activeColor: AppColors.primaryBlue,
                        visualDensity: VisualDensity.compact,
                        materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        onChanged: (value) =>
                            _toggleTargetSelection(linkedGroup, value ?? false),
                      ),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              linkedGroup.groupName,
                              style: const TextStyle(
                                fontWeight: FontWeight.w700,
                                fontSize: 14,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Linked number: $numberLabel',
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.grey.shade600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                );
              }),
            ],
          ),
        );
      }).toList(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final groups = _storage.groups;
    final mediaQuery = MediaQuery.of(context);
    final screenWidth = mediaQuery.size.width;
    final bottomInset = mediaQuery.viewInsets.bottom;
    final keyboardOpen = bottomInset > 0;
    final visibleHeight =
        mediaQuery.size.height -
        mediaQuery.padding.top -
        mediaQuery.padding.bottom -
        kToolbarHeight -
        bottomInset;
    final composerMaxHeight =
        (visibleHeight *
                (keyboardOpen
                    ? (screenWidth < 380 ? 0.82 : 0.76)
                    : (screenWidth < 380 ? 0.60 : 0.55)))
            .clamp(220.0, 520.0)
            .toDouble();

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: const Text(
          'Parent Groups',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        actions: [
          IconButton(
            onPressed: () => _loadGroups(forceRefresh: true),
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      floatingActionButton: Padding(
        padding: EdgeInsets.only(
          bottom: _selectedParentGroupIds.isNotEmpty && !keyboardOpen
              ? (screenWidth < 380 ? 248.0 : 228.0)
              : 0.0,
        ),
        child: FloatingActionButton.extended(
          onPressed: () async {
            await Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => const CreateParentGroupScreen(),
              ),
            );
            await _loadGroups(forceRefresh: true);
          },
          backgroundColor: AppColors.primaryBlue,
          foregroundColor: Colors.white,
          icon: const Icon(Icons.add_rounded),
          label: const Text('Create Parent Group'),
        ),
      ),
      body: Column(
        children: [
          Expanded(
            child: !_storage.isLoaded
                ? const Center(child: CircularProgressIndicator())
                : groups.isEmpty
                ? Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.hub_rounded,
                            size: 72,
                            color: Colors.grey.shade300,
                          ),
                          const SizedBox(height: 12),
                          Text(
                            'No Parent Groups',
                            style: TextStyle(
                              fontSize: 18,
                              color: Colors.grey.shade600,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Create a parent group, then select linked groups from one or many numbers to send one message together.',
                            textAlign: TextAlign.center,
                            style: TextStyle(color: Colors.grey.shade500),
                          ),
                        ],
                      ),
                    ),
                  )
                : ListView.builder(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 16,
                    ),
                    itemCount: groups.length,
                    itemBuilder: (context, index) {
                      final group = groups[index];
                      final isSelected = _selectedParentGroupIds.contains(
                        group.id,
                      );

                      return GestureDetector(
                        onTap: () => _toggleParentGroupSelection(group),
                        child: Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: isSelected
                                ? AppColors.primaryBlue.withValues(alpha: 0.08)
                                : Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: isSelected
                                  ? AppColors.primaryBlue
                                  : Colors.grey.shade300,
                              width: isSelected ? 1.6 : 1,
                            ),
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 48,
                                height: 48,
                                decoration: BoxDecoration(
                                  color:
                                      (isSelected
                                              ? AppColors.primaryBlue
                                              : Colors.green)
                                          .withValues(alpha: 0.12),
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                child: Icon(
                                  Icons.hub_rounded,
                                  color: isSelected
                                      ? AppColors.primaryBlue
                                      : Colors.green,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      group.name,
                                      style: const TextStyle(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 16,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      '${group.linkedGroups.length} groups from ${_instanceCount(group)} linked numbers',
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: Colors.grey.shade600,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              if (isSelected)
                                const Icon(
                                  Icons.check_circle_rounded,
                                  color: AppColors.primaryBlue,
                                ),
                              IconButton(
                                icon: const Icon(
                                  Icons.delete_outline_rounded,
                                  color: Colors.redAccent,
                                ),
                                onPressed: () async => _deleteGroup(group),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
          ),
          if (_selectedParentGroupIds.isNotEmpty)
            SafeArea(
              top: false,
              child: ConstrainedBox(
                constraints: BoxConstraints(maxHeight: composerMaxHeight),
                child: Container(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.05),
                        blurRadius: 10,
                        offset: const Offset(0, -4),
                      ),
                    ],
                  ),
                  child: SingleChildScrollView(
                    keyboardDismissBehavior:
                        ScrollViewKeyboardDismissBehavior.onDrag,
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        // ── Header: selected summary ──────────────────────────
                        Align(
                          alignment: Alignment.centerLeft,
                          child: Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: Text(
                              'Selected: ${_selectedParentGroups.length} parent groups (${_selectedGroupCount()} of ${_selectedParentTargets.length} groups, ${_selectedNumberCount()} numbers)',
                              maxLines: keyboardOpen ? 1 : 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                        ),
                        if (!keyboardOpen)
                          Align(
                            alignment: Alignment.centerLeft,
                            child: Padding(
                              padding: const EdgeInsets.only(bottom: 12),
                              child: Text(
                                'Every linked number is grouped below. Use Select All or tick groups manually before sending one message.',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                            ),
                          ),
                        // ── Select All bar ────────────────────────────────────
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF8F9FB),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: Colors.grey.shade300),
                          ),
                          child: Row(
                            children: [
                              Checkbox(
                                value: _isAllSelected(_selectedParentTargets),
                                activeColor: AppColors.primaryBlue,
                                visualDensity: VisualDensity.compact,
                                materialTapTargetSize:
                                    MaterialTapTargetSize.shrinkWrap,
                                onChanged: (value) => _toggleAllTargets(
                                  _selectedParentTargets,
                                  value ?? false,
                                ),
                              ),
                              const Expanded(
                                child: Text(
                                  'Select All Groups',
                                  style: TextStyle(fontWeight: FontWeight.w700),
                                ),
                              ),
                              Text(
                                '${_selectedGroupCount()}/${_selectedParentTargets.length}',
                                style: TextStyle(
                                  fontWeight: FontWeight.w700,
                                  color: Colors.grey.shade700,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 10),
                        // ── FIXED-HEIGHT scrollable group list ────────────────
                        ConstrainedBox(
                          constraints: BoxConstraints(
                            maxHeight: keyboardOpen ? 140.0 : 220.0,
                          ),
                          child: SingleChildScrollView(
                            physics: const ClampingScrollPhysics(),
                            child: _buildLinkedGroupSections(
                              _selectedParentTargets,
                            ),
                          ),
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: _composerChip(
                                label: 'Text',
                                selected: _composerMode == _ComposerMode.text,
                                onTap: () => setState(
                                  () => _composerMode = _ComposerMode.text,
                                ),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: _composerChip(
                                label: 'Media',
                                selected: _composerMode == _ComposerMode.media,
                                onTap: () => setState(
                                  () => _composerMode = _ComposerMode.media,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        if (_composerMode == _ComposerMode.text)
                          TextField(
                            controller: _messageCtrl,
                            minLines: screenWidth < 380 ? 3 : 4,
                            maxLines: screenWidth < 380 ? 3 : 4,
                            scrollPadding: EdgeInsets.only(
                              bottom: bottomInset + 120,
                            ),
                            decoration: _composerInput(
                              'Write one message - it will be sent to all groups of selected number cards...',
                            ),
                          )
                        else ...[
                          DropdownButtonFormField<_ComposerMediaType>(
                            initialValue: _mediaType,
                            decoration: _composerInput('Media type'),
                            items: _ComposerMediaType.values
                                .map(
                                  (type) => DropdownMenuItem(
                                    value: type,
                                    child: Text(type.label),
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
                          const SizedBox(height: 10),
                          OutlinedButton.icon(
                            onPressed: _isUploading
                                ? null
                                : _pickAndUploadMedia,
                            icon: _isUploading
                                ? const SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                    ),
                                  )
                                : const Icon(Icons.upload_file_rounded),
                            label: Text(
                              _isUploading ? 'Processing...' : 'Choose Media',
                            ),
                          ),
                          if (_mediaFilename.isNotEmpty) ...[
                            const SizedBox(height: 8),
                            Text(
                              _mediaFilename,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.grey.shade700,
                              ),
                            ),
                          ],
                          const SizedBox(height: 10),
                          TextField(
                            controller: _mediaCaptionCtrl,
                            maxLines: 3,
                            decoration: _composerInput(
                              'Caption / text (required with media)',
                            ),
                          ),
                        ],
                        const SizedBox(height: 12),
                        SizedBox(
                          width: double.infinity,
                          height: 48,
                          child: ElevatedButton.icon(
                            onPressed: (_isSending || _isUploading)
                                ? null
                                : _sendMessage,
                            icon: (_isSending || _isUploading)
                                ? const SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : const Icon(Icons.send_rounded),
                            label: Text(
                              (_isSending || _isUploading)
                                  ? 'Sending to selected groups...'
                                  : _composerMode == _ComposerMode.text
                                  ? 'Send to Selected Groups'
                                  : 'Send ${_mediaType.label} to Selected Groups',
                            ),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.primaryBlue,
                              foregroundColor: Colors.white,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(14),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

InputDecoration _composerInput(String hintText) {
  return InputDecoration(
    hintText: hintText,
    filled: true,
    fillColor: const Color(0xFFF8F9FB),
    border: OutlineInputBorder(
      borderRadius: BorderRadius.circular(14),
      borderSide: BorderSide(color: Colors.grey.shade300),
    ),
    enabledBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(14),
      borderSide: BorderSide(color: Colors.grey.shade300),
    ),
  );
}

Widget _composerChip({
  required String label,
  required bool selected,
  required VoidCallback onTap,
}) {
  return InkWell(
    onTap: onTap,
    borderRadius: BorderRadius.circular(12),
    child: Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: BoxDecoration(
        color: selected
            ? AppColors.primaryBlue.withValues(alpha: 0.1)
            : const Color(0xFFF8F9FB),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: selected ? AppColors.primaryBlue : Colors.grey.shade300,
        ),
      ),
      child: Center(
        child: Text(
          label,
          style: TextStyle(
            fontWeight: FontWeight.w700,
            color: selected ? AppColors.primaryBlue : Colors.grey.shade700,
          ),
        ),
      ),
    ),
  );
}

enum _ComposerMode { text, media }

enum _ComposerMediaType {
  image,
  video;

  String get label => this == _ComposerMediaType.image ? 'Image' : 'Video';
}
