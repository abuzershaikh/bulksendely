import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/data/models/contact_group_model.dart';
import 'package:autoreply/features/campaigns/models/one_shot_range_model.dart';
import 'package:autoreply/features/campaigns/services/one_shot_storage.dart';
import 'package:autoreply/features/campaigns/widgets/one_shot_settings_dialog.dart';
import 'package:autoreply/features/contacts/screens/contact_groups_screen.dart';
import 'package:autoreply/data/local/contact_storage.dart';
import 'package:flutter/material.dart';

class SelectContactsOneShotScreen extends StatefulWidget {
  final ContactGroupModel? initialGroup;

  const SelectContactsOneShotScreen({super.key, this.initialGroup});

  @override
  State<SelectContactsOneShotScreen> createState() =>
      _SelectContactsOneShotScreenState();
}

class _SelectContactsOneShotScreenState
    extends State<SelectContactsOneShotScreen> {
  final ContactStorage _contactStorage = ContactStorage();
  final OneShotStorage _oneShotStorage = OneShotStorage.instance;

  ContactGroupModel? _selectedGroup;
  List<OneShotRange> _ranges = [];
  OneShotRange? _selectedRange;

  Set<String> _sentNumbers = {};
  Set<String> _selectedContactNumbers = {};

  bool _isSearching = false;
  String _searchQuery = '';
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _initData();
  }

  Future<void> _initData() async {
    await _oneShotStorage.init();
    final groups = _contactStorage.groups;
    if (groups.isNotEmpty) {
      _selectedGroup = widget.initialGroup != null &&
              groups.any((g) => g.id == widget.initialGroup?.id)
          ? widget.initialGroup
          : groups.first;
    }
    await _loadGroupData();
  }

  Future<void> _loadGroupData() async {
    if (_selectedGroup == null) {
      setState(() {
        _ranges = [];
        _selectedRange = null;
        _selectedContactNumbers.clear();
      });
      return;
    }

    final sent = await _oneShotStorage.getSentNumbersForGroup(_selectedGroup!.id);
    final contactNumbers =
        _selectedGroup!.contacts.map((c) => c.number).toList();

    final ranges = _oneShotStorage.calculateRanges(
      totalContacts: _selectedGroup!.contacts.length,
      sentNumbers: sent,
      contactNumbers: contactNumbers,
    );

    setState(() {
      _sentNumbers = sent;
      _ranges = ranges;
      if (ranges.isNotEmpty) {
        // Select first non-sent range or first range
        _selectedRange = ranges.firstWhere(
          (r) => r.status != OneShotRangeStatus.sent,
          orElse: () => ranges.first,
        );
        _applyRangeSelection(_selectedRange);
      } else {
        _selectedRange = null;
        _selectedContactNumbers.clear();
      }
    });
  }

  void _applyRangeSelection(OneShotRange? range) {
    if (range == null || _selectedGroup == null) {
      _selectedContactNumbers.clear();
      return;
    }

    final contacts = _selectedGroup!.contacts;
    final startIndex = (range.startIndex - 1).clamp(0, contacts.length);
    final endIndex = range.endIndex.clamp(0, contacts.length);

    final selected = <String>{};
    for (var i = startIndex; i < endIndex; i++) {
      selected.add(contacts[i].number);
    }

    setState(() {
      _selectedRange = range;
      _selectedContactNumbers = selected;
    });
  }

  void _openSettings() {
    showDialog(
      context: context,
      builder: (ctx) => OneShotSettingsDialog(
        selectedGroupId: _selectedGroup?.id,
        selectedGroupName: _selectedGroup?.name,
        onSettingsUpdated: () {
          _loadGroupData();
        },
        onProgressReset: () {
          _loadGroupData();
        },
      ),
    );
  }

  List<ContactModel> _getFilteredContacts() {
    if (_selectedGroup == null || _selectedRange == null) return [];
    final contacts = _selectedGroup!.contacts;
    final startIndex = (_selectedRange!.startIndex - 1).clamp(0, contacts.length);
    final endIndex = _selectedRange!.endIndex.clamp(0, contacts.length);

    final rangeContacts = contacts.sublist(startIndex, endIndex);

    if (_searchQuery.trim().isEmpty) {
      return rangeContacts;
    }

    final query = _searchQuery.toLowerCase().trim();
    return rangeContacts.where((c) {
      return c.name.toLowerCase().contains(query) ||
          c.number.contains(query);
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final groups = _contactStorage.groups;
    final rangeContacts = _getFilteredContacts();

    return Scaffold(
      backgroundColor: const Color(0xFF004D40), // Dark Teal App Header Theme as in screenshot
      appBar: AppBar(
        backgroundColor: const Color(0xFF004D40),
        foregroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded),
          onPressed: () => Navigator.pop(context),
        ),
        title: _isSearching
            ? TextField(
                controller: _searchController,
                autofocus: true,
                style: const TextStyle(color: Colors.white),
                decoration: const InputDecoration(
                  hintText: 'Search contacts...',
                  hintStyle: TextStyle(color: Colors.white70),
                  border: InputBorder.none,
                ),
                onChanged: (val) => setState(() => _searchQuery = val),
              )
            : const Text(
                'Select Contacts',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
              ),
        actions: [
          IconButton(
            icon: Icon(_isSearching ? Icons.close_rounded : Icons.search_rounded),
            onPressed: () {
              setState(() {
                if (_isSearching) {
                  _searchQuery = '';
                  _searchController.clear();
                }
                _isSearching = !_isSearching;
              });
            },
          ),
          IconButton(
            icon: const Icon(Icons.settings_rounded),
            onPressed: _openSettings,
          ),
        ],
      ),
      body: Container(
        color: const Color(0xFFF4F6F9),
        child: Column(
          children: [
            // ── Top Dropdowns Container ──
            Container(
              padding: const EdgeInsets.all(16),
              decoration: const BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black12,
                    blurRadius: 6,
                    offset: Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Dropdown 1: Select Group or Campaign
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Select Group or Campaign',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF004D40),
                        ),
                      ),
                      GestureDetector(
                        onTap: () async {
                          await Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (ctx) => const ContactGroupsScreen(),
                            ),
                          );
                          setState(() {});
                          _loadGroupData();
                        },
                        child: const Text(
                          'Add New +',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: AppColors.primaryBlue,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 14),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFF004D40), width: 1.5),
                    ),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<ContactGroupModel>(
                        isExpanded: true,
                        value: _selectedGroup,
                        hint: const Text('Select a group'),
                        icon: const Icon(Icons.arrow_drop_down_rounded, color: Color(0xFF004D40)),
                        items: groups.map((g) {
                          return DropdownMenuItem<ContactGroupModel>(
                            value: g,
                            child: Text(
                              '${g.name} (${g.contacts.length} contacts)',
                              style: const TextStyle(fontWeight: FontWeight.w500),
                            ),
                          );
                        }).toList(),
                        onChanged: (val) {
                          setState(() => _selectedGroup = val);
                          _loadGroupData();
                        },
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Dropdown 2: Select One Shot Range
                  const Text(
                    'Select One Shot Range (You can change from settings)',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF004D40),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 14),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFF004D40), width: 1.5),
                    ),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<OneShotRange>(
                        isExpanded: true,
                        value: _selectedRange,
                        hint: const Text('Select One Shot Range'),
                        icon: const Icon(Icons.arrow_drop_down_rounded, color: Color(0xFF004D40)),
                        items: _ranges.map((r) {
                          return DropdownMenuItem<OneShotRange>(
                            value: r,
                            child: Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    r.label,
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      color: r.status == OneShotRangeStatus.sent
                                          ? Colors.green.shade700
                                          : Colors.black87,
                                    ),
                                  ),
                                ),
                                if (r.status == OneShotRangeStatus.sent)
                                  const Icon(Icons.check_circle_rounded, color: Colors.green, size: 18),
                              ],
                            ),
                          );
                        }).toList(),
                        onChanged: (val) {
                          _applyRangeSelection(val);
                        },
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // ── Contacts Count Subheading ──
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    '${_selectedContactNumbers.length} Contacts Selected',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      color: Colors.grey.shade800,
                    ),
                  ),
                  Row(
                    children: [
                      Checkbox(
                        value: rangeContacts.isNotEmpty &&
                            rangeContacts.every((c) =>
                                _selectedContactNumbers.contains(c.number)),
                        activeColor: const Color(0xFF004D40),
                        onChanged: (val) {
                          setState(() {
                            if (val == true) {
                              _selectedContactNumbers
                                  .addAll(rangeContacts.map((c) => c.number));
                            } else {
                              _selectedContactNumbers
                                  .removeAll(rangeContacts.map((c) => c.number));
                            }
                          });
                        },
                      ),
                      const Text('Select All', style: TextStyle(fontSize: 12)),
                    ],
                  ),
                ],
              ),
            ),

            // ── Contact List ──
            Expanded(
              child: rangeContacts.isEmpty
                  ? Center(
                      child: Text(
                        'No contacts found in this range',
                        style: TextStyle(color: Colors.grey.shade600),
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      itemCount: rangeContacts.length,
                      itemBuilder: (context, index) {
                        final contact = rangeContacts[index];
                        final isChecked =
                            _selectedContactNumbers.contains(contact.number);
                        final cleanNum =
                            contact.number.replaceAll(RegExp(r'[^0-9]'), '');
                        final isSent = _sentNumbers.contains(cleanNum);

                        return Card(
                          margin: const EdgeInsets.only(bottom: 8),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                            side: BorderSide(
                              color: isChecked
                                  ? const Color(0xFF004D40).withValues(alpha: 0.4)
                                  : Colors.grey.shade200,
                            ),
                          ),
                          child: ListTile(
                            contentPadding:
                                const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
                            leading: CircleAvatar(
                              backgroundColor: isSent
                                  ? Colors.green.withValues(alpha: 0.15)
                                  : const Color(0xFF004D40).withValues(alpha: 0.1),
                              child: Icon(
                                isSent
                                    ? Icons.check_circle_rounded
                                    : Icons.person_rounded,
                                color: isSent ? Colors.green : const Color(0xFF004D40),
                              ),
                            ),
                            title: Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    contact.name.isNotEmpty ? contact.name : 'Contact',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 15,
                                    ),
                                  ),
                                ),
                                if (isSent)
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 8, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: Colors.green.withValues(alpha: 0.1),
                                      borderRadius: BorderRadius.circular(10),
                                    ),
                                    child: const Text(
                                      'Sent ✅',
                                      style: TextStyle(
                                        fontSize: 10,
                                        fontWeight: FontWeight.bold,
                                        color: Colors.green,
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                            subtitle: Text(
                              contact.number,
                              style: TextStyle(
                                fontSize: 13,
                                color: Colors.grey.shade700,
                              ),
                            ),
                            trailing: Checkbox(
                              value: isChecked,
                              activeColor: const Color(0xFF004D40),
                              onChanged: (val) {
                                setState(() {
                                  if (val == true) {
                                    _selectedContactNumbers.add(contact.number);
                                  } else {
                                    _selectedContactNumbers.remove(contact.number);
                                  }
                                });
                              },
                            ),
                          ),
                        );
                      },
                    ),
            ),

            // ── Bottom Next Button ──
            Padding(
              padding: const EdgeInsets.all(16),
              child: ElevatedButton(
                onPressed: _selectedContactNumbers.isEmpty
                    ? null
                    : () {
                        // Return selected contacts group copy with chosen range contacts
                        if (_selectedGroup != null) {
                          final selectedContactsList = _selectedGroup!.contacts
                              .where((c) => _selectedContactNumbers.contains(c.number))
                              .toList();

                          final resultGroup = ContactGroupModel(
                            id: _selectedGroup!.id,
                            serverId: _selectedGroup!.serverId,
                            name:
                                '${_selectedGroup!.name} (${_selectedRange?.startIndex}-${_selectedRange?.endIndex})',
                            contacts: selectedContactsList,
                            createdAt: _selectedGroup!.createdAt,
                            source: _selectedGroup!.source,
                          );

                          Navigator.pop(context, resultGroup);
                        }
                      },
                style: ElevatedButton.styleFrom(
                  minimumSize: const Size(160, 48),
                  backgroundColor: const Color(0xFF004D40),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(24),
                  ),
                  elevation: 4,
                ),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.arrow_forward_rounded, size: 18),
                    SizedBox(width: 8),
                    Text(
                      'NEXT',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                        letterSpacing: 1,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
