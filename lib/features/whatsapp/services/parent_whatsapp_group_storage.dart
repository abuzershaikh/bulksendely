import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:autoreply/features/whatsapp/models/parent_whatsapp_group_model.dart';
import 'package:autoreply/features/whatsapp/services/parent_whatsapp_group_sync_service.dart';

class ParentWhatsappGroupStorage extends ChangeNotifier {
  static const String _storageKey = 'parent_whatsapp_groups_v1';
  static final ParentWhatsappGroupStorage _instance =
      ParentWhatsappGroupStorage._internal();

  factory ParentWhatsappGroupStorage() => _instance;

  ParentWhatsappGroupStorage._internal();

  final ParentWhatsappGroupSyncService _syncService =
      ParentWhatsappGroupSyncService();
  final List<ParentWhatsappGroupModel> _groups = [];
  bool _isLoaded = false;

  List<ParentWhatsappGroupModel> get groups => List.unmodifiable(_groups);
  bool get isLoaded => _isLoaded;

  Future<void> load({bool forceRefresh = false}) async {
    if (_isLoaded && !forceRefresh) return;

    final localGroups = await _readLocalGroups();

    try {
      final remoteGroups = await _syncService.fetchGroups();
      final mergedGroups = await _mergeLocalGroups(
        localGroups: localGroups,
        remoteGroups: remoteGroups,
      );
      _replaceGroups(mergedGroups);
      await _saveLocal();
    } catch (_) {
      if (localGroups.isEmpty) {
        rethrow;
      }
      _replaceGroups(localGroups);
    }

    _isLoaded = true;
    notifyListeners();
  }

  Future<void> saveGroup(ParentWhatsappGroupModel group) async {
    if (!_isLoaded) {
      await load();
    }

    await _syncService.saveGroup(group);
    final index = _groups.indexWhere((g) => g.id == group.id);
    if (index >= 0) {
      _groups[index] = group;
    } else {
      _groups.add(group);
    }
    await _saveLocal();
    notifyListeners();
  }

  Future<void> removeGroup(String id) async {
    if (!_isLoaded) {
      await load();
    }

    await _syncService.deleteGroup(id);
    _groups.removeWhere((g) => g.id == id);
    await _saveLocal();
    notifyListeners();
  }

  Future<List<ParentWhatsappGroupModel>> _readLocalGroups() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_storageKey);
    if (raw == null || raw.isEmpty) {
      return <ParentWhatsappGroupModel>[];
    }

    final decoded = jsonDecode(raw) as List<dynamic>;
    return decoded
        .whereType<Map>()
        .map(
          (item) => ParentWhatsappGroupModel.fromJson(
            Map<String, dynamic>.from(item),
          ),
        )
        .where((group) => group.id.isNotEmpty)
        .toList(growable: false);
  }

  Future<List<ParentWhatsappGroupModel>> _mergeLocalGroups({
    required List<ParentWhatsappGroupModel> localGroups,
    required List<ParentWhatsappGroupModel> remoteGroups,
  }) async {
    if (localGroups.isEmpty) {
      return remoteGroups;
    }

    final merged = <String, ParentWhatsappGroupModel>{
      for (final group in remoteGroups) group.id: group,
    };

    for (final localGroup in localGroups) {
      if (merged.containsKey(localGroup.id)) {
        continue;
      }

      merged[localGroup.id] = localGroup;
      try {
        await _syncService.saveGroup(localGroup);
      } catch (_) {
        // Keep the local copy visible even if the first sync fails.
      }
    }

    return merged.values.toList(growable: false);
  }

  void _replaceGroups(List<ParentWhatsappGroupModel> items) {
    _groups
      ..clear()
      ..addAll(items);
  }

  Future<void> _saveLocal() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      _storageKey,
      jsonEncode(_groups.map((group) => group.toJson()).toList()),
    );
  }
}
