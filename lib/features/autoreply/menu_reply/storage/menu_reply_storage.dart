import 'dart:convert';

import 'package:autoreply/features/autoreply/menu_reply/models/menu_reply_model.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class MenuReplyStorage extends ChangeNotifier {
  static const String _storageKey = 'menu_reply_flows_v1';
  static final MenuReplyStorage _instance = MenuReplyStorage._internal();

  factory MenuReplyStorage() => _instance;

  MenuReplyStorage._internal();

  final List<MenuReplyFlow> _flows = [];
  bool _isLoaded = false;

  List<MenuReplyFlow> get flows => List.unmodifiable(_flows);

  Future<void> load() async {
    if (_isLoaded) return;
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_storageKey);
    if (raw != null && raw.isNotEmpty) {
      final decoded = jsonDecode(raw) as List<dynamic>;
      _flows
        ..clear()
        ..addAll(
          decoded.whereType<Map>().map(
            (item) => MenuReplyFlow.fromJson(Map<String, dynamic>.from(item)),
          ),
        );
    }
    _isLoaded = true;
    notifyListeners();
  }

  void addOrUpdate(MenuReplyFlow flow) {
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

  MenuReplyFlow duplicate(MenuReplyFlow flow) {
    final copy = flow.copyWith(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      name: '${flow.name} Copy',
      createdAt: DateTime.now().millisecondsSinceEpoch,
      serverId: null,
    );
    addOrUpdate(copy);
    return copy;
  }

  Future<void> _save() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      _storageKey,
      jsonEncode(_flows.map((item) => item.toJson()).toList()),
    );
  }
}
