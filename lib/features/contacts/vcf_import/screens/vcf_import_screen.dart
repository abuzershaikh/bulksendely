import 'dart:io';
import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/data/local/contact_storage.dart';
import 'package:autoreply/data/models/contact_group_model.dart';
import 'package:autoreply/features/subscription/models/app_user_subscription.dart';
import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:autoreply/features/sync/services/server_sync_service.dart';
import 'package:autoreply/features/contacts/vcf_import/services/vcf_import_service.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

class VcfImportScreen extends StatefulWidget {
  const VcfImportScreen({super.key});

  @override
  State<VcfImportScreen> createState() => _VcfImportScreenState();
}

class _VcfImportScreenState extends State<VcfImportScreen> {
  static const String _newGroupValue = '__new_group__';
  final _syncService = ServerSyncService();
  final _subscriptionService = SubscriptionService.instance;
  File? _selectedFile;
  List<ContactModel>? _previewContacts;
  final Set<int> _selectedContactIndexes = <int>{};
  String _selectedTargetGroupId = _newGroupValue;
  bool _isLoading = false;
  String? _errorMessage;
  final _groupNameController = TextEditingController();

  List<ContactGroupModel> get _existingGroups => ContactStorage().groups;
  bool get _isCreatingNewGroup => _selectedTargetGroupId == _newGroupValue;

  int get _displayCount {
    final contacts = _previewContacts;
    if (contacts == null) {
      return 0;
    }
    return contacts.length;
  }

  bool get _isAllSelected {
    final contacts = _previewContacts;
    if (contacts == null || contacts.isEmpty) {
      return false;
    }
    return _selectedContactIndexes.length == contacts.length;
  }

  List<ContactModel> get _selectedContacts {
    final contacts = _previewContacts;
    if (contacts == null || contacts.isEmpty) {
      return const <ContactModel>[];
    }
    final sortedIndexes = _selectedContactIndexes.toList()..sort();
    return sortedIndexes.map((index) => contacts[index]).toList();
  }

  void _selectAllContacts(bool value) {
    final contacts = _previewContacts;
    if (contacts == null) {
      return;
    }
    setState(() {
      if (value) {
        _selectedContactIndexes
          ..clear()
          ..addAll(List<int>.generate(contacts.length, (index) => index));
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

  @override
  void dispose() {
    _groupNameController.dispose();
    super.dispose();
  }

  Future<void> _pickVcfFile() async {
    try {
      setState(() {
        _isLoading = true;
        _errorMessage = null;
      });

      FilePickerResult? result = await FilePicker.platform.pickFiles(
        type: FileType.custom,
        allowedExtensions: ['vcf', 'vcard'],
      );

      if (result != null && result.files.single.path != null) {
        final file = File(result.files.single.path!);

        // Validate file
        final isValid = await VcfImportService.validateVcfFile(file);
        if (!isValid) {
          setState(() {
            _errorMessage =
                'Invalid VCF format. Please select a valid vCard file.';
            _isLoading = false;
          });
          return;
        }

        // Parse and preview
        final contacts = await VcfImportService.importFromVcf(file);

        if (contacts.isEmpty) {
          setState(() {
            _errorMessage = 'No valid contacts found in VCF file.';
            _isLoading = false;
          });
          return;
        }

        setState(() {
          _selectedFile = file;
          _previewContacts = contacts;
          _selectedContactIndexes
            ..clear()
            ..addAll(List<int>.generate(contacts.length, (index) => index));
          _isLoading = false;
        });
      } else {
        setState(() {
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Error reading file: $e';
        _isLoading = false;
      });
    }
  }

  Future<void> _importContacts() async {
    if (_previewContacts == null || _previewContacts!.isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('No contacts to import')));
      return;
    }

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

    var importedCount = importContacts.length;

    if (_isCreatingNewGroup) {
      final newGroup = ContactGroupModel(
        id: DateTime.now().millisecondsSinceEpoch.toString(),
        name: groupName,
        contacts: importContacts,
        createdAt: DateTime.now(),
        source: 'VCF',
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

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _isCreatingNewGroup
                ? 'Successfully imported $importedCount contacts!'
                : 'Added $importedCount new contacts to ${selectedExistingGroup!.name}!',
          ),
          backgroundColor: Colors.green,
        ),
      );
      Navigator.pop(context);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.lighterBg,
      appBar: AppBar(
        title: const Text(
          'Import from VCF',
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
            // Instructions Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.blue.shade50,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.blue.shade200),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.info_outline, color: Colors.blue.shade700),
                      const SizedBox(width: 8),
                      Text(
                        'VCF/vCard Format',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                          color: Colors.blue.shade900,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'Import contacts from VCF (vCard) files:\n'
                    '• Supports vCard 2.1, 3.0, and 4.0\n'
                    '• Can contain single or multiple contacts\n'
                    '• Commonly exported from phones and email clients\n'
                    '• File extensions: .vcf or .vcard',
                    style: TextStyle(color: Colors.blue.shade800, fontSize: 13),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 24),

            // File Picker Button
            Center(
              child: ElevatedButton.icon(
                onPressed: _isLoading ? null : _pickVcfFile,
                icon: _isLoading
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.upload_file_rounded),
                label: Text(
                  _selectedFile == null ? 'Select VCF File' : 'Change File',
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 32,
                    vertical: 16,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
              ),
            ),

            if (_errorMessage != null) ...[
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.red.shade50,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.red.shade200),
                ),
                child: Row(
                  children: [
                    Icon(Icons.error_outline, color: Colors.red.shade700),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        _errorMessage!,
                        style: TextStyle(color: Colors.red.shade900),
                      ),
                    ),
                  ],
                ),
              ),
            ],

            if (_selectedFile != null && _previewContacts != null) ...[
              const SizedBox(height: 24),

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

              // Preview Section
              Text(
                'Preview (${_previewContacts!.length} contacts)',
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
                    'Select All (${_selectedContactIndexes.length}/${_previewContacts!.length})',
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              Container(
                constraints: const BoxConstraints(maxHeight: 300),
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
                    final contact = _previewContacts![index];
                    final isSelected = _selectedContactIndexes.contains(index);
                    return ListTile(
                      onTap: () => _toggleContactSelection(index, !isSelected),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 8),
                      leading: CircleAvatar(
                        backgroundColor: Colors.blue.shade100,
                        child: Text(
                          contact.name[0].toUpperCase(),
                          style: TextStyle(
                            color: Colors.blue.shade700,
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

              // Import Button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _importContacts,
                  icon: const Icon(Icons.check_circle_rounded),
                  label: const Text('Import Contacts'),
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

            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }
}
