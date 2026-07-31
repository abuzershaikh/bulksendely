import 'dart:convert';

import 'package:autoreply/features/group_bulk_sender/models/group_batch_model.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class GroupBatchStorage extends ChangeNotifier {
  static const String _storageKey = 'group_batches_v1';
  static final GroupBatchStorage _instance = GroupBatchStorage._internal();

  factory GroupBatchStorage() => _instance;

  GroupBatchStorage._internal();

  final List<GroupBatchModel> _batches = [];
  bool _loaded = false;

  List<GroupBatchModel> get batches => List.unmodifiable(_batches);

  Future<void> load() async {
    if (_loaded) return;
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_storageKey);
    if (raw != null && raw.isNotEmpty) {
      final decoded = jsonDecode(raw) as List<dynamic>;
      _batches
        ..clear()
        ..addAll(
          decoded
              .whereType<Map>()
              .map((item) => GroupBatchModel.fromJson(Map<String, dynamic>.from(item))),
        );
    }
    _loaded = true;
    notifyListeners();
  }

  void addOrUpdate(GroupBatchModel batch) {
    final index = _batches.indexWhere((item) => item.id == batch.id);
    if (index >= 0) {
      _batches[index] = batch;
    } else {
      _batches.add(batch);
    }
    _save();
    notifyListeners();
  }

  void remove(String id) {
    _batches.removeWhere((item) => item.id == id);
    _save();
    notifyListeners();
  }

  Future<void> _save() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      _storageKey,
      jsonEncode(_batches.map((batch) => batch.toJson()).toList()),
    );
  }
}
