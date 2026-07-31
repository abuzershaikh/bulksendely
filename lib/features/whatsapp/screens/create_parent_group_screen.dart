import 'dart:async';

import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/features/whatsapp/models/parent_whatsapp_group_model.dart';
import 'package:autoreply/features/whatsapp/models/whatsapp_group_model.dart';
import 'package:autoreply/features/whatsapp/services/parent_whatsapp_group_storage.dart';
import 'package:autoreply/features/whatsapp/services/whatsapp_api_service.dart';
import 'package:flutter/material.dart';

class CreateParentGroupScreen extends StatefulWidget {
  const CreateParentGroupScreen({super.key});

  @override
  State<CreateParentGroupScreen> createState() =>
      _CreateParentGroupScreenState();
}

class _CreateParentGroupScreenState extends State<CreateParentGroupScreen> {
  static const int _maxLinkedNumbers = 10;

  final WhatsappApiService _api = WhatsappApiService();
  final ParentWhatsappGroupStorage _storage = ParentWhatsappGroupStorage();
  final TextEditingController _nameCtrl = TextEditingController();

  bool _isLoading = true;
  bool _isSaving = false;
  List<ActiveWhatsappInstance> _instances = [];
  Map<String, List<WhatsappGroupModel>> _instanceGroups = {};
  Map<String, String> _instanceLoadHints = {};
  String? _expandedInstanceId;
  StreamSubscription<WhatsappGroupsUpdate>? _groupsUpdateSubscription;

  // Track selected groups. Key: instanceId_groupId, Value: LinkedWhatsappGroupItem
  final Map<String, LinkedWhatsappGroupItem> _selectedGroups = {};

  String _displayLinkedNumber(ActiveWhatsappInstance instance) {
    return WhatsappApiService.formatLinkedNumber(instance.linkedNumber) ??
        instance.instanceId;
  }

  @override
  void initState() {
    super.initState();
    _groupsUpdateSubscription = _api.groupUpdates.listen(_applyGroupsUpdate);
    _loadData();
  }

  @override
  void dispose() {
    _groupsUpdateSubscription?.cancel();
    _nameCtrl.dispose();
    super.dispose();
  }

  void _applyGroupsUpdate(WhatsappGroupsUpdate update) {
    if (!mounted ||
        !_instances.any(
          (instance) => instance.instanceId == update.instanceId,
        )) {
      return;
    }

    setState(() {
      _instanceGroups = {..._instanceGroups, update.instanceId: update.groups};
      _instanceLoadHints = Map<String, String>.from(_instanceLoadHints)
        ..remove(update.instanceId);
    });
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final instances = (await _api.getInstances())
          .take(_maxLinkedNumbers)
          .toList();
      if (!mounted) return;

      final instanceLoadHints = <String, String>{};
      final groupedResults = await Future.wait(
        instances.map((instance) async {
          try {
            final groups = await _api.fetchGroups(
              instanceId: instance.instanceId,
            );
            return MapEntry(instance.instanceId, groups);
          } on WhatsappSessionConnectingException catch (e) {
            instanceLoadHints[instance.instanceId] = e.message;
            return MapEntry(instance.instanceId, <WhatsappGroupModel>[]);
          } catch (_) {
            instanceLoadHints[instance.instanceId] =
                'Failed to load groups. Try refreshing.';
            return MapEntry(instance.instanceId, <WhatsappGroupModel>[]);
          }
        }),
      );

      if (!mounted) return;
      setState(() {
        _instances = instances;
        _instanceGroups = {
          for (final item in groupedResults) item.key: item.value,
        };
        _instanceLoadHints = instanceLoadHints;
        _isLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Failed to load instances: $e')));
    }
  }

  void _toggleGroupSelection(
    ActiveWhatsappInstance instance,
    WhatsappGroupModel group,
  ) {
    setState(() {
      final key = '${instance.instanceId}_${group.id}';
      if (_selectedGroups.containsKey(key)) {
        _selectedGroups.remove(key);
      } else {
        _selectedGroups[key] = LinkedWhatsappGroupItem(
          instanceId: instance.instanceId,
          instanceName: instance.linkedName ?? 'Unknown',
          instanceNumber: instance.linkedNumber ?? 'Unknown',
          groupId: group.id,
          groupName: group.name,
        );
      }
    });
  }

  void _saveParentGroup() async {
    if (_isSaving) {
      return;
    }

    final name = _nameCtrl.text.trim();
    if (name.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Enter a name for the parent group')),
      );
      return;
    }

    if (_selectedGroups.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Select at least one group')),
      );
      return;
    }

    final newGroup = ParentWhatsappGroupModel(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      name: name,
      linkedGroups: _selectedGroups.values.toList(),
    );

    setState(() => _isSaving = true);
    try {
      await _storage.saveGroup(newGroup);
    } catch (e) {
      if (!mounted) return;
      setState(() => _isSaving = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to save parent group: $e')),
      );
      return;
    }

    if (!mounted) return;
    setState(() => _isSaving = false);
    Navigator.of(context).pop(true);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: const Text(
          'Create Parent Group',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        actions: [
          IconButton(
            onPressed: _isSaving ? null : _saveParentGroup,
            icon: const Icon(Icons.check_rounded, color: AppColors.primaryBlue),
          ),
        ],
      ),
      bottomNavigationBar: _isLoading
          ? null
          : SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: SizedBox(
                  width: double.infinity,
                  height: 54,
                  child: ElevatedButton(
                    onPressed: _isSaving ? null : _saveParentGroup,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primaryBlue,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                    child: Text(
                      _isSaving
                          ? 'Saving Parent Group...'
                          : 'Save Parent Group (${_selectedGroups.length} selected)',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ),
              ),
            ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      TextField(
                        controller: _nameCtrl,
                        decoration: InputDecoration(
                          hintText:
                              'Parent Group Name (e.g. My Marketing Group)',
                          filled: true,
                          fillColor: Colors.white,
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                            borderSide: BorderSide(color: Colors.grey.shade300),
                          ),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                            borderSide: BorderSide(color: Colors.grey.shade300),
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: Colors.grey.shade300),
                        ),
                        child: Text(
                          'Up to $_maxLinkedNumbers linked numbers shown below. All groups are listed. Admin-only groups stay visible but cannot be selected from this account.',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey.shade700,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: ExpansionPanelList.radio(
                      elevation: 0,
                      expandedHeaderPadding: EdgeInsets.zero,
                      animationDuration: const Duration(milliseconds: 220),
                      initialOpenPanelValue: _expandedInstanceId,
                      children: _instances.map((instance) {
                        final groups =
                            _instanceGroups[instance.instanceId] ?? [];
                        final loadHint =
                            _instanceLoadHints[instance.instanceId];

                        return ExpansionPanelRadio(
                          value: instance.instanceId,
                          canTapOnHeader: true,
                          headerBuilder: (context, isExpanded) {
                            return Container(
                              margin: const EdgeInsets.only(bottom: 12),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: Colors.grey.shade300),
                              ),
                              child: ListTile(
                                title: Text(
                                  _displayLinkedNumber(instance),
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                subtitle: Text(
                                  '${groups.length} groups available${(instance.linkedName ?? '').isNotEmpty ? ' • ${instance.linkedName}' : ''}',
                                ),
                                trailing: Icon(
                                  isExpanded
                                      ? Icons.expand_less_rounded
                                      : Icons.expand_more_rounded,
                                ),
                              ),
                            );
                          },
                          body: Container(
                            margin: const EdgeInsets.only(bottom: 16),
                            padding: const EdgeInsets.only(
                              left: 12,
                              right: 12,
                              bottom: 12,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: Colors.grey.shade300),
                            ),
                            child: groups.isEmpty
                                ? Padding(
                                    padding: const EdgeInsets.all(12),
                                    child: Text(loadHint ?? 'No groups found'),
                                  )
                                : Builder(
                                    builder: (context) {
                                      final screenH = MediaQuery.sizeOf(
                                        context,
                                      ).height;
                                      final innerH = (screenH * 0.38).clamp(
                                        200.0,
                                        360.0,
                                      );

                                      return SizedBox(
                                        height: innerH,
                                        child: Scrollbar(
                                          child: ListView.builder(
                                            padding: EdgeInsets.zero,
                                            itemCount: groups.length,
                                            itemBuilder: (context, idx) {
                                              final group = groups[idx];
                                              final key =
                                                  '${instance.instanceId}_${group.id}';
                                              final isSelected = _selectedGroups
                                                  .containsKey(key);

                                              return CheckboxListTile(
                                                dense: true,
                                                visualDensity:
                                                    VisualDensity.compact,
                                                title: Text(
                                                  group.name,
                                                  style: TextStyle(
                                                    fontSize: 14,
                                                    color: group.isSelectable
                                                        ? null
                                                        : Colors.grey.shade600,
                                                  ),
                                                ),
                                                subtitle: Text(
                                                  group.isSelectable
                                                      ? '${group.participantCount} members'
                                                      : '${group.participantCount} members | Only admins can send here',
                                                  style: TextStyle(
                                                    fontSize: 12,
                                                    color: group.isSelectable
                                                        ? Colors.grey.shade500
                                                        : Colors
                                                              .orange
                                                              .shade700,
                                                  ),
                                                ),
                                                value: isSelected,
                                                activeColor:
                                                    AppColors.primaryBlue,
                                                onChanged: group.isSelectable
                                                    ? (_) =>
                                                          _toggleGroupSelection(
                                                            instance,
                                                            group,
                                                          )
                                                    : null,
                                              );
                                            },
                                          ),
                                        ),
                                      );
                                    },
                                  ),
                          ),
                        );
                      }).toList(),
                    ),
                  ),
                ),
              ],
            ),
    );
  }
}
