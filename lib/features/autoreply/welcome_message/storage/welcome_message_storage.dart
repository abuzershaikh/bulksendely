import 'dart:convert';

import 'package:autoreply/features/autoreply/welcome_message/models/welcome_message_model.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class WelcomeMessageStorage extends ChangeNotifier {
  static const String _storageKey = 'welcome_message_flows_v1';
  static final WelcomeMessageStorage _instance = WelcomeMessageStorage._internal();

  factory WelcomeMessageStorage() => _instance;

  WelcomeMessageStorage._internal();

  final List<WelcomeMessageFlow> _flows = [];
  bool _isLoaded = false;

  List<WelcomeMessageFlow> get flows => List.unmodifiable(_flows);

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
              .map((item) => WelcomeMessageFlow.fromJson(Map<String, dynamic>.from(item))),
        );
    }
    _isLoaded = true;
    notifyListeners();
  }

  void addOrUpdate(WelcomeMessageFlow flow) {
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
