import 'dart:convert';

import 'package:firebase_auth/firebase_auth.dart';
import 'package:shared_preferences/shared_preferences.dart';

class LinkedWhatsappAccount {
  final String instanceId;
  final String? linkedNumber;
  final String? linkedName;

  const LinkedWhatsappAccount({
    required this.instanceId,
    this.linkedNumber,
    this.linkedName,
  });

  Map<String, dynamic> toJson() {
    return {
      'instanceId': instanceId,
      'linkedNumber': linkedNumber,
      'linkedName': linkedName,
    };
  }

  factory LinkedWhatsappAccount.fromJson(Map<String, dynamic> json) {
    return LinkedWhatsappAccount(
      instanceId: json['instanceId']?.toString() ?? '',
      linkedNumber: json['linkedNumber']?.toString(),
      linkedName: json['linkedName']?.toString(),
    );
  }
}

class LinkedWhatsappStorage {
  static const String _storageKey = 'linked_whatsapp_account_v1';
  static const String _legacyStorageKey = 'linked_whatsapp_account_v1';

  Future<String> _storageKeyForCurrentUser() async {
    final uid = FirebaseAuth.instance.currentUser?.uid ?? '';
    if (uid.isEmpty) {
      return _storageKey;
    }
    return '${_storageKey}_$uid';
  }

  Future<void> save(LinkedWhatsappAccount account) async {
    final prefs = await SharedPreferences.getInstance();
    final storageKey = await _storageKeyForCurrentUser();
    await prefs.setString(storageKey, jsonEncode(account.toJson()));
  }

  Future<LinkedWhatsappAccount?> load() async {
    final prefs = await SharedPreferences.getInstance();
    final storageKey = await _storageKeyForCurrentUser();
    final raw = prefs.getString(storageKey) ?? prefs.getString(_legacyStorageKey);
    if (raw == null || raw.isEmpty) {
      return null;
    }

    return LinkedWhatsappAccount.fromJson(
      Map<String, dynamic>.from(jsonDecode(raw) as Map),
    );
  }

  Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    final storageKey = await _storageKeyForCurrentUser();
    await prefs.remove(storageKey);
    if (storageKey != _legacyStorageKey) {
      await prefs.remove(_legacyStorageKey);
    }
  }
}
