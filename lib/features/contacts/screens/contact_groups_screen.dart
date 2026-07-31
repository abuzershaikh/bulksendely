import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/data/local/contact_storage.dart';
import 'package:autoreply/data/models/contact_group_model.dart';
import 'package:autoreply/features/contacts/csv_import/screens/csv_import_screen.dart';
import 'package:autoreply/features/contacts/sheets_import/screens/sheets_import_screen.dart';
import 'package:autoreply/features/contacts/vcf_import/screens/vcf_import_screen.dart';
import 'package:autoreply/features/subscription/models/app_user_subscription.dart';
import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:autoreply/features/sync/services/server_sync_service.dart';
import 'package:flutter/material.dart';
import 'package:flutter_contacts/flutter_contacts.dart';
import 'package:permission_handler/permission_handler.dart';

class ContactGroupsScreen extends StatefulWidget {
  const ContactGroupsScreen({super.key});

  @override
  State<ContactGroupsScreen> createState() => _ContactGroupsScreenState();
}

class _ContactGroupsScreenState extends State<ContactGroupsScreen> {
  void _refresh() => setState(() {});

  Future<void> _handleCsvImport() async {
    await Navigator.push(
      context,
      MaterialPageRoute(builder: (context) => const CsvImportScreen()),
    );
    _refresh();
  }

  Future<void> _handleVcfImport() async {
    await Navigator.push(
      context,
      MaterialPageRoute(builder: (context) => const VcfImportScreen()),
    );
    _refresh();
  }

  Future<void> _handleSheetsImport() async {
    await Navigator.push(
      context,
      MaterialPageRoute(builder: (context) => const SheetsImportScreen()),
    );
    _refresh();
  }

  Future<void> _handleTextImport() async {
    await Navigator.push(
      context,
      MaterialPageRoute(builder: (context) => const SheetsImportScreen()),
    );
    _refresh();
  }

  Future<void> _handlePhoneImport() async {
    final status = await Permission.contacts.request();
    if (!status.isGranted) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Contacts permission is required')),
      );
      return;
    }

    if (mounted) {
      _showLoadingDialog();
    }

    try {
      final phoneContacts = await FlutterContacts.getAll(
        properties: {ContactProperty.phone},
      );

      final extracted = <ContactModel>[];
      for (final contact in phoneContacts) {
        if (contact.phones.isEmpty) {
          continue;
        }

        var number = contact.phones.first.number;
        number = number.replaceAll(RegExp(r'[^\d+]'), '');
        if (number.isEmpty) {
          continue;
        }

        final displayName = contact.displayName?.trim() ?? '';
        extracted.add(
          ContactModel(
            name: displayName.isEmpty ? 'Unknown' : displayName,
            number: number,
          ),
        );
      }

      if (mounted) {
        Navigator.pop(context);
      }

      if (extracted.isEmpty) {
        if (!mounted) {
          return;
        }
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('No phone contacts found to import')),
        );
        return;
      }

      if (!mounted) {
        return;
      }

      await Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => PhoneImportPreviewScreen(contacts: extracted),
        ),
      );
      _refresh();
    } catch (e) {
      if (!mounted) {
        return;
      }
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to load phone contacts: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  void _showLoadingDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => const Center(
        child: Card(
          child: Padding(
            padding: EdgeInsets.all(20),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                CircularProgressIndicator(),
                SizedBox(height: 16),
                Text('Extracting Contacts...'),
              ],
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final groups = ContactStorage().groups;

    return Scaffold(
      backgroundColor: AppColors.lighterBg,
      appBar: AppBar(
        title: const Text(
          'Contact Groups',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        elevation: 0.5,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: Colors.grey.shade200),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.03),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Import Contacts From',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                      color: Color(0xFF2C3E50),
                    ),
                  ),
                  const SizedBox(height: 20),
                  SizedBox(
                    height: 100,
                    child: ListView(
                      scrollDirection: Axis.horizontal,
                      children: [
                        _ImportIcon(
                          icon: Icons.contacts_rounded,
                          label: 'Phone',
                          color: Colors.teal,
                          onTap: _handlePhoneImport,
                        ),
                        const SizedBox(width: 16),
                        _ImportIcon(
                          icon: Icons.description_rounded,
                          label: 'CSV',
                          color: Colors.purple,
                          onTap: _handleCsvImport,
                        ),
                        const SizedBox(width: 16),
                        _ImportIcon(
                          icon: Icons.insert_drive_file_rounded,
                          label: 'VCF',
                          color: Colors.blue,
                          onTap: _handleVcfImport,
                        ),
                        const SizedBox(width: 16),
                        _ImportIcon(
                          icon: Icons.grid_on_rounded,
                          label: 'Sheets',
                          color: Colors.green,
                          onTap: _handleSheetsImport,
                        ),
                        const SizedBox(width: 16),
                        _ImportIcon(
                          icon: Icons.paste_rounded,
                          label: 'Text',
                          color: Colors.orange,
                          onTap: _handleTextImport,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 32),
            const Text(
              'My Lists',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 18,
                color: Color(0xFF2C3E50),
              ),
            ),
            const SizedBox(height: 16),
            if (groups.isEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 40),
                child: Center(
                  child: Column(
                    children: [
                      Icon(
                        Icons.folder_open_rounded,
                        size: 60,
                        color: Colors.grey.shade300,
                      ),
                      const SizedBox(height: 12),
                      Text(
                        'No contact groups yet',
                        style: TextStyle(color: Colors.grey.shade500),
                      ),
                    ],
                  ),
                ),
              )
            else
              ...groups.map(
                (g) => _GroupCard(
                  group: g,
                  onDelete: () {
                    ContactStorage().removeGroup(g.id);
                    _refresh();
                  },
                ),
              ),
            const SizedBox(height: 80),
          ],
        ),
      ),
    );
  }
}

class PhoneImportPreviewScreen extends StatefulWidget {
  final List<ContactModel> contacts;

  const PhoneImportPreviewScreen({super.key, required this.contacts});

  @override
  State<PhoneImportPreviewScreen> createState() =>
      _PhoneImportPreviewScreenState();
}

class _PhoneImportPreviewScreenState extends State<PhoneImportPreviewScreen> {
  static const String _newGroupValue = '__new_group__';
  final SubscriptionService _subscriptionService = SubscriptionService.instance;
  final ServerSyncService _syncService = ServerSyncService();
  final TextEditingController _groupNameController = TextEditingController();
  final Set<int> _selectedContactIndexes = <int>{};
  String _selectedTargetGroupId = _newGroupValue;
  bool _isImporting = false;

  int get _displayCount => widget.contacts.length;

  bool get _isAllSelected =>
      widget.contacts.isNotEmpty &&
      _selectedContactIndexes.length == widget.contacts.length;

  List<ContactModel> get _selectedContacts {
    final sortedIndexes = _selectedContactIndexes.toList()..sort();
    return sortedIndexes.map((index) => widget.contacts[index]).toList();
  }

  List<ContactGroupModel> get _existingGroups => ContactStorage().groups;
  bool get _isCreatingNewGroup => _selectedTargetGroupId == _newGroupValue;

  @override
  void initState() {
    super.initState();
    _selectedContactIndexes.addAll(
      List<int>.generate(widget.contacts.length, (index) => index),
    );
  }

  @override
  void dispose() {
    _groupNameController.dispose();
    super.dispose();
  }

  void _selectAllContacts(bool value) {
    setState(() {
      if (value) {
        _selectedContactIndexes
          ..clear()
          ..addAll(
            List<int>.generate(widget.contacts.length, (index) => index),
          );
      } else {
        _selectedContactIndexes.clear();
      }
    });
  }

  void _toggleContactSelection(int index, bool value) {
    setState(() {
      if (value) {
        _selectedContactIndexes.add(index);
      } else {
        _selectedContactIndexes.remove(index);
      }
    });
  }

  Future<void> _importContacts() async {
    final selectedContacts = _selectedContacts;
    if (selectedContacts.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select at least one contact')),
      );
      return;
    }

    final storage = ContactStorage();
    ContactGroupModel? selectedExistingGroup;
    final groupName = _groupNameController.text.trim();
    if (_isCreatingNewGroup) {
      if (groupName.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Please enter a group name')),
        );
        return;
      }
    } else {
      try {
        selectedExistingGroup = _existingGroups.firstWhere(
          (group) => group.id == _selectedTargetGroupId,
        );
      } catch (_) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Please select an existing group')),
        );
        return;
      }
    }

    final isPremium =
        _subscriptionService.currentUserNotifier.value?.isPremium ?? false;
    final maxFreeContacts = AppUserSubscription.freeMessageLimit;
    final existingCount = storage.uniqueContactCount;
    final remainingSlots = maxFreeContacts - existingCount;
    final additionalUniqueCount = storage.additionalUniqueContactCount(
      selectedContacts,
    );

    if (!isPremium && remainingSlots <= 0 && additionalUniqueCount > 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Free plan 10-number limit reached. Delete old contacts first or upgrade to premium.',
          ),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    final importContacts = !isPremium && additionalUniqueCount > remainingSlots
        ? storage.limitContactsToUniqueSlots(selectedContacts, remainingSlots)
        : selectedContacts;

    if (!isPremium && additionalUniqueCount > remainingSlots) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Free user can keep only 10 total numbers. Imported first $remainingSlots selected contacts.',
          ),
          backgroundColor: Colors.orange,
        ),
      );
    }

    setState(() {
      _isImporting = true;
    });

    var importedCount = importContacts.length;

    if (_isCreatingNewGroup) {
      final newGroup = ContactGroupModel(
        id: DateTime.now().millisecondsSinceEpoch.toString(),
        name: groupName,
        contacts: importContacts,
        createdAt: DateTime.now(),
        source: 'Phone',
      );

      storage.addGroup(newGroup);

      try {
        final serverId = await _syncService.syncContactGroup(newGroup);
        if (serverId != null) {
          storage.addGroup(newGroup.copyWith(serverId: serverId.toString()));
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Contacts imported locally. Sync failed: $e'),
              backgroundColor: Colors.orange,
            ),
          );
        }
      }
    } else {
      final beforeCount = selectedExistingGroup!.contacts.length;
      final updatedGroup = storage.mergeContactsIntoGroup(
        groupId: selectedExistingGroup.id,
        contacts: importContacts,
      );
      importedCount = updatedGroup.contacts.length - beforeCount;
    }

    if (!mounted) {
      return;
    }

    setState(() {
      _isImporting = false;
    });

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          _isCreatingNewGroup
              ? 'Imported $importedCount contacts!'
              : 'Added $importedCount new contacts to ${selectedExistingGroup!.name}!',
        ),
        backgroundColor: Colors.green,
      ),
    );
    Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.lighterBg,
      appBar: AppBar(
        title: const Text(
          'Import from Phone',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        elevation: 0.5,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            DropdownButtonFormField<String>(
              initialValue: _selectedTargetGroupId,
              decoration: InputDecoration(
                labelText: 'Save To',
                prefixIcon: const Icon(Icons.folder_copy_rounded),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                filled: true,
                fillColor: Colors.white,
              ),
              items: [
                const DropdownMenuItem<String>(
                  value: _newGroupValue,
                  child: Text('Create New Group'),
                ),
                ..._existingGroups.map(
                  (group) => DropdownMenuItem<String>(
                    value: group.id,
                    child: Text('Add to ${group.name}'),
                  ),
                ),
              ],
              onChanged: (value) {
                setState(() {
                  _selectedTargetGroupId = value ?? _newGroupValue;
                });
              },
            ),
            if (_isCreatingNewGroup) ...[
              const SizedBox(height: 16),
              TextField(
                controller: _groupNameController,
                decoration: InputDecoration(
                  labelText: 'Group Name',
                  hintText: 'Enter a name for this contact group',
                  prefixIcon: const Icon(Icons.group_rounded),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  filled: true,
                  fillColor: Colors.white,
                ),
              ),
            ],
            const SizedBox(height: 24),
            Text(
              'Preview (${widget.contacts.length} contacts)',
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16,
                color: Color(0xFF2C3E50),
              ),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Checkbox(
                  value: _isAllSelected,
                  onChanged: (value) => _selectAllContacts(value ?? false),
                ),
                const SizedBox(width: 6),
                Text(
                  'Select All (${_selectedContactIndexes.length}/${widget.contacts.length})',
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Container(
              constraints: const BoxConstraints(maxHeight: 360),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.grey.shade200),
              ),
              child: ListView.separated(
                shrinkWrap: true,
                itemCount: _displayCount,
                separatorBuilder: (_, __) =>
                    Divider(height: 1, color: Colors.grey.shade200),
                itemBuilder: (context, index) {
                  final contact = widget.contacts[index];
                  final isSelected = _selectedContactIndexes.contains(index);
                  return ListTile(
                    onTap: () => _toggleContactSelection(index, !isSelected),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 8),
                    leading: CircleAvatar(
                      backgroundColor: Colors.teal.shade100,
                      child: Text(
                        contact.name[0].toUpperCase(),
                        style: TextStyle(
                          color: Colors.teal.shade700,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    title: Text(
                      contact.name,
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                    subtitle: Text(
                      contact.number,
                      style: TextStyle(color: Colors.grey.shade600),
                    ),
                    trailing: Checkbox(
                      value: isSelected,
                      onChanged: (value) =>
                          _toggleContactSelection(index, value ?? false),
                    ),
                    dense: true,
                  );
                },
              ),
            ),

            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _isImporting ? null : _importContacts,
                icon: _isImporting
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.check_circle_rounded),
                label: Text(_isImporting ? 'Importing...' : 'Import Contacts'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ImportIcon extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  const _ImportIcon({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: color.withValues(alpha: 0.1),
              boxShadow: [
                BoxShadow(
                  color: color.withValues(alpha: 0.2),
                  blurRadius: 15,
                  spreadRadius: 1,
                ),
              ],
            ),
            child: Icon(icon, color: color, size: 28),
          ),
          const SizedBox(height: 8),
          Text(
            label,
            style: const TextStyle(
              fontWeight: FontWeight.w600,
              fontSize: 13,
              color: Color(0xFF555A6E),
            ),
          ),
        ],
      ),
    );
  }
}

class _GroupCard extends StatelessWidget {
  final ContactGroupModel group;
  final VoidCallback onDelete;

  const _GroupCard({required this.group, required this.onDelete});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade200),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: Colors.blueAccent.withValues(alpha: 0.1),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.group_rounded, color: Colors.blueAccent),
          ),
          const SizedBox(width: 16),
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
                  '${group.contacts.length} contacts',
                  style: TextStyle(color: Colors.grey.shade500, fontSize: 13),
                ),
              ],
            ),
          ),
          IconButton(
            icon: const Icon(
              Icons.person_add_alt_1_rounded,
              color: Colors.blueAccent,
            ),
            onPressed: () {},
          ),
          IconButton(
            icon: const Icon(
              Icons.download_rounded,
              color: Colors.deepPurpleAccent,
            ),
            onPressed: () {},
          ),
          PopupMenuButton(
            icon: const Icon(Icons.more_vert_rounded, color: Colors.grey),
            itemBuilder: (ctx) => [
              const PopupMenuItem(
                value: 'delete',
                child: Text(
                  'Delete Group',
                  style: TextStyle(color: Colors.red),
                ),
              ),
            ],
            onSelected: (val) {
              if (val == 'delete') {
                onDelete();
              }
            },
          ),
        ],
      ),
    );
  }
}
