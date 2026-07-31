import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/data/local/contact_storage.dart';
import 'package:autoreply/data/models/contact_group_model.dart';
import 'package:autoreply/features/subscription/models/app_user_subscription.dart';
import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:autoreply/features/sync/services/server_sync_service.dart';
import 'package:autoreply/features/contacts/sheets_import/services/sheets_import_service.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class SheetsImportScreen extends StatefulWidget {
  const SheetsImportScreen({super.key});

  @override
  State<SheetsImportScreen> createState() => _SheetsImportScreenState();
}

class _SheetsImportScreenState extends State<SheetsImportScreen> {
  static const String _newGroupValue = '__new_group__';
  final _syncService = ServerSyncService();
  final _subscriptionService = SubscriptionService.instance;
  final _urlController = TextEditingController();
  final _groupNameController = TextEditingController();
  List<ContactModel>? _previewContacts;
  final Set<int> _selectedContactIndexes = <int>{};
  String _selectedTargetGroupId = _newGroupValue;
  bool _isLoading = false;
  String? _errorMessage;

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
    _urlController.dispose();
    _groupNameController.dispose();
    super.dispose();
  }

  Future<void> _pasteFromClipboard() async {
    final clipboardData = await Clipboard.getData('text/plain');
    final pastedText = clipboardData?.text?.trim();
    if (pastedText == null || pastedText.isEmpty) return;

    if (SheetsImportService.isValidSheetsUrl(pastedText)) {
      setState(() {
        _urlController.text = pastedText;
        _errorMessage = null;
      });
      return;
    }

    final contacts = SheetsImportService.parsePastedContacts(pastedText);
    if (contacts.isEmpty) {
      setState(() {
        _urlController.text = pastedText;
        _errorMessage =
            'Paste a Google Sheets URL or phone numbers separated by commas / new lines.';
      });
      return;
    }

    setState(() {
      _urlController.text = pastedText;
      _previewContacts = contacts;
      _selectedContactIndexes
        ..clear()
        ..addAll(List<int>.generate(contacts.length, (index) => index));
      _errorMessage = null;
    });
  }

  Future<void> _fetchContacts() async {
    final input = _urlController.text.trim();

    if (input.isEmpty) {
      setState(() {
        _errorMessage =
            'Please enter a Google Sheets URL or paste phone numbers';
      });
      return;
    }

    if (!SheetsImportService.isValidSheetsUrl(input)) {
      final contacts = SheetsImportService.parsePastedContacts(input);
      if (contacts.isNotEmpty) {
        setState(() {
          _previewContacts = contacts;
          _selectedContactIndexes
            ..clear()
            ..addAll(List<int>.generate(contacts.length, (index) => index));
          _errorMessage = null;
          _isLoading = false;
        });
        return;
      }

      setState(() {
        _errorMessage =
            'Invalid Google Sheets URL. Or paste phone numbers with commas / new lines.';
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
      _previewContacts = null;
      _selectedContactIndexes.clear();
    });

    try {
      print('🚀 Starting import process...');
      final contacts = await SheetsImportService.importFromSheetsUrl(input);

      if (contacts.isEmpty) {
        setState(() {
          _errorMessage =
              'No valid contacts found in the sheet. Make sure it has Name and Phone columns.';
          _isLoading = false;
        });
        return;
      }

      setState(() {
        _previewContacts = contacts;
        _selectedContactIndexes
          ..clear()
          ..addAll(List<int>.generate(contacts.length, (index) => index));
        _isLoading = false;
      });

      print('✅ Import successful: ${contacts.length} contacts');
    } catch (e) {
      print('❌ Import failed: $e');
      setState(() {
        _errorMessage = _formatErrorMessage(e.toString());
        _isLoading = false;
      });
    }
  }

  String _formatErrorMessage(String error) {
    if (error.contains('No internet connection')) {
      return '🌐 No internet connection\nPlease check your network and try again.';
    } else if (error.contains('timeout')) {
      return '⏱️ Request timeout\nPlease check your internet speed and try again.';
    } else if (error.contains('Status: 403') || error.contains('Status: 401')) {
      return '🔒 Access denied\nMake sure the sheet is shared publicly:\n1. Click Share\n2. Change to "Anyone with the link"\n3. Set to "Viewer"';
    } else if (error.contains('Status: 404')) {
      return '❌ Sheet not found\nPlease check the URL and try again.';
    } else if (error.contains('Invalid Google Sheets URL')) {
      return '🔗 Invalid URL\nPlease provide a valid Google Sheets link.';
    } else if (error.contains('Sheet is empty')) {
      return '📄 Sheet is empty\nPlease add contacts to your sheet.';
    }
    return '❌ Error: ${error.replaceAll('Exception: ', '')}';
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

    final createdSource =
        SheetsImportService.isValidSheetsUrl(_urlController.text.trim())
        ? 'Google Sheets'
        : 'Text';
    var importedCount = importContacts.length;

    if (_isCreatingNewGroup) {
      final newGroup = ContactGroupModel(
        id: DateTime.now().millisecondsSinceEpoch.toString(),
        name: groupName,
        contacts: importContacts,
        createdAt: DateTime.now(),
        source: createdSource,
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

  Future<void> _openHelpGuide() async {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text(
          'How to Share Google Sheet',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
        ),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text(
                'Follow these steps:',
                style: TextStyle(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 12),
              _buildStep('1', 'Open your Google Sheet'),
              _buildStep('2', 'Click "Share" button (top-right)'),
              _buildStep('3', 'Change to "Anyone with the link"'),
              _buildStep('4', 'Set permission to "Viewer"'),
              _buildStep('5', 'Click "Copy link"'),
              _buildStep('6', 'Paste the link here'),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.amber.shade50,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.amber.shade200),
                ),
                child: Row(
                  children: [
                    Icon(
                      Icons.lightbulb_outline,
                      color: Colors.amber.shade700,
                      size: 20,
                    ),
                    const SizedBox(width: 8),
                    const Expanded(
                      child: Text(
                        'Sheet format: Name, Phone Number (or just phone numbers)',
                        style: TextStyle(fontSize: 12),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Got it!'),
          ),
        ],
      ),
    );
  }

  Widget _buildStep(String number, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 24,
            height: 24,
            decoration: BoxDecoration(
              color: Colors.green.shade100,
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(
                number,
                style: TextStyle(
                  color: Colors.green.shade700,
                  fontWeight: FontWeight.bold,
                  fontSize: 12,
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(child: Text(text, style: const TextStyle(fontSize: 14))),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.lighterBg,
      appBar: AppBar(
        title: const Text(
          'Import from Google Sheets',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        elevation: 0.5,
        actions: [
          IconButton(
            icon: const Icon(Icons.help_outline),
            onPressed: _openHelpGuide,
            tooltip: 'How to use',
          ),
        ],
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
                color: Colors.green.shade50,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.green.shade200),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.info_outline, color: Colors.green.shade700),
                      const SizedBox(width: 8),
                      Text(
                        'Google Sheets Import',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                          color: Colors.green.shade900,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'Import contacts directly from Google Sheets:\n'
                    '• Sheet must be shared (Anyone with link)\n'
                    '• Supports Name + Phone or Phone only\n'
                    '• Real-time sync from your sheet\n'
                    '• No file download needed\n\n'
                    '💡 Tip: Click help icon (?) for sharing guide',
                    style: TextStyle(
                      color: Colors.green.shade800,
                      fontSize: 13,
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 24),

            // URL Input
            TextField(
              controller: _urlController,
              decoration: InputDecoration(
                labelText: 'Google Sheets URL or phone numbers',
                hintText: 'Paste sheet link or numbers like +9199..., +9188...',
                prefixIcon: const Icon(Icons.upload_file_rounded),
                suffixIcon: IconButton(
                  icon: const Icon(Icons.content_paste),
                  onPressed: _pasteFromClipboard,
                  tooltip: 'Paste from clipboard',
                ),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                filled: true,
                fillColor: Colors.white,
              ),
              minLines: 2,
              maxLines: 4,
            ),

            const SizedBox(height: 16),

            // Fetch Button
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _isLoading ? null : _fetchContacts,
                icon: _isLoading
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.cloud_download_rounded),
                label: Text(_isLoading ? 'Fetching...' : 'Preview Contacts'),
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

            if (_errorMessage != null) ...[
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.red.shade50,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.red.shade200),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(Icons.error_outline, color: Colors.red.shade700),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            _errorMessage!,
                            style: TextStyle(
                              color: Colors.red.shade900,
                              fontSize: 13,
                            ),
                          ),
                        ),
                      ],
                    ),
                    if (_errorMessage!.contains('shared publicly')) ...[
                      const SizedBox(height: 12),
                      ElevatedButton.icon(
                        onPressed: _openHelpGuide,
                        icon: const Icon(Icons.help_outline, size: 16),
                        label: const Text(
                          'How to Share',
                          style: TextStyle(fontSize: 12),
                        ),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.red.shade100,
                          foregroundColor: Colors.red.shade900,
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 8,
                          ),
                          minimumSize: const Size(0, 0),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],

            if (_previewContacts != null) ...[
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
                        backgroundColor: Colors.green.shade100,
                        child: Text(
                          contact.name[0].toUpperCase(),
                          style: TextStyle(
                            color: Colors.green.shade700,
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
