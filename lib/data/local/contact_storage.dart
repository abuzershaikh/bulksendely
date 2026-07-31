import 'dart:convert';

import 'package:autoreply/data/models/contact_group_model.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ContactStorage extends ChangeNotifier {
  static const String _storageKey = 'contact_groups_v1';
  static final ContactStorage _instance = ContactStorage._internal();

  factory ContactStorage() {
    return _instance;
  }

  ContactStorage._internal();

  final List<ContactGroupModel> _groups = [];
  bool _isLoaded = false;

  List<ContactGroupModel> get groups => List.unmodifiable(_groups);
  bool get isLoaded => _isLoaded;
  int get uniqueContactCount {
    final uniqueNumbers = _allNormalizedNumbers();
    return uniqueNumbers.length;
  }

  Set<String> _allNormalizedNumbers() {
    final uniqueNumbers = <String>{};
    for (final group in _groups) {
      for (final contact in group.contacts) {
        final key = _normalizedContactKey(contact);
        if (key.isNotEmpty) {
          uniqueNumbers.add(key);
        }
      }
    }
    return uniqueNumbers;
  }

  String _normalizedNumber(String value) {
    final normalized = value.replaceAll(RegExp(r'[^0-9]'), '');
    return normalized.isNotEmpty ? normalized : value.trim();
  }

  String _normalizedContactKey(ContactModel contact) {
    return _normalizedNumber(contact.number);
  }

  int additionalUniqueContactCount(Iterable<ContactModel> contacts) {
    final existingNumbers = _allNormalizedNumbers();
    var additionalCount = 0;

    for (final contact in contacts) {
      final key = _normalizedContactKey(contact);
      if (key.isEmpty || !existingNumbers.add(key)) {
        continue;
      }
      additionalCount++;
    }

    return additionalCount;
  }

  List<ContactModel> limitContactsToUniqueSlots(
    Iterable<ContactModel> contacts,
    int remainingSlots,
  ) {
    final existingNumbers = _allNormalizedNumbers();
    final limitedContacts = <ContactModel>[];
    var remaining = remainingSlots;

    for (final contact in contacts) {
      final key = _normalizedContactKey(contact);
      if (key.isEmpty) {
        continue;
      }

      if (existingNumbers.contains(key)) {
        limitedContacts.add(contact);
        continue;
      }

      if (remaining <= 0) {
        continue;
      }

      existingNumbers.add(key);
      limitedContacts.add(contact);
      remaining--;
    }

    return limitedContacts;
  }

  ContactGroupModel mergeContactsIntoGroup({
    required String groupId,
    required List<ContactModel> contacts,
  }) {
    final existingIndex = _groups.indexWhere((group) => group.id == groupId);
    if (existingIndex < 0) {
      throw StateError('Contact group not found');
    }

    final group = _groups[existingIndex];
    final seenNumbers = group.contacts
        .map(_normalizedContactKey)
        .where((key) => key.isNotEmpty)
        .toSet();
    final mergedContacts = List<ContactModel>.from(group.contacts);

    for (final contact in contacts) {
      final key = _normalizedContactKey(contact);
      if (key.isEmpty || !seenNumbers.add(key)) {
        continue;
      }
      mergedContacts.add(contact);
    }

    final updatedGroup = group.copyWith(contacts: mergedContacts);
    _groups[existingIndex] = updatedGroup;
    _save();
    notifyListeners();
    return updatedGroup;
  }

  Future<void> load() async {
    if (_isLoaded) return;

    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_storageKey);
    if (raw != null && raw.isNotEmpty) {
      final decoded = jsonDecode(raw) as List<dynamic>;
      _groups
        ..clear()
        ..addAll(
          decoded.whereType<Map>().map(
            (item) =>
                ContactGroupModel.fromJson(Map<String, dynamic>.from(item)),
          ),
        );
    }

    _isLoaded = true;
    notifyListeners();
  }

  void addGroup(ContactGroupModel group) {
    final existingIndex = _groups.indexWhere(
      (g) =>
          g.id == group.id ||
          (g.name == group.name && g.source == group.source),
    );
    if (existingIndex >= 0) {
      _groups[existingIndex] = group;
    } else {
      _groups.add(group);
    }
    _save();
    notifyListeners();
  }

  void removeGroup(String id) {
    _groups.removeWhere((g) => g.id == id);
    _save();
    notifyListeners();
  }

  Future<void> _save() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      _storageKey,
      jsonEncode(_groups.map((group) => group.toJson()).toList()),
    );
  }
}
