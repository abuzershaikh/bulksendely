import 'dart:convert';

import 'package:autoreply/features/autoreply/keyword_reply/models/keyword_reply_model.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class KeywordReplyStorage extends ChangeNotifier {
  static const String _storageKey = 'keyword_reply_flows_v1';
  static final KeywordReplyStorage _instance = KeywordReplyStorage._internal();

  factory KeywordReplyStorage() => _instance;

  KeywordReplyStorage._internal();

  final List<KeywordReplyFlow> _flows = [];
  bool _isLoaded = false;

  List<KeywordReplyFlow> get flows => List.unmodifiable(_flows);

  Future<void> load() async {
    if (_isLoaded) return;
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_storageKey);
    if (raw != null && raw.isNotEmpty) {
      final decoded = jsonDecode(raw) as List<dynamic>;
      _flows
        ..clear()
        ..addAll(
          decoded
              .whereType<Map>()
              .map((item) => KeywordReplyFlow.fromJson(Map<String, dynamic>.from(item))),
        );
    }
    _isLoaded = true;
    notifyListeners();
  }

  void addOrUpdate(KeywordReplyFlow flow) {
    final index = _flows.indexWhere((item) => item.id == flow.id);
    if (index >= 0) {
      _flows[index] = flow;
    } else {
      _flows.add(flow);
    }
    _save();
    notifyListeners();
  }

  void remove(String id) {
    _flows.removeWhere((item) => item.id == id);
    _save();
    notifyListeners();
  }

  Future<void> _save() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      _storageKey,
      jsonEncode(_flows.map((item) => item.toJson()).toList()),
    );
  }
}
