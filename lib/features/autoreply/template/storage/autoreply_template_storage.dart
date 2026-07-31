import 'dart:convert';

import 'package:autoreply/data/models/button_template_model.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AutoReplyTemplateStorage extends ChangeNotifier {
  static const String _storageKey = 'autoreply_templates_v1';
  static final AutoReplyTemplateStorage _instance =
      AutoReplyTemplateStorage._internal();

  factory AutoReplyTemplateStorage() {
    return _instance;
  }

  AutoReplyTemplateStorage._internal();

  final List<ButtonTemplateModel> _templates = [];
  bool _isLoaded = false;

  List<ButtonTemplateModel> get templates => List.unmodifiable(_templates);
  bool get isLoaded => _isLoaded;

  Future<void> load() async {
    if (_isLoaded) return;

    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_storageKey);
    if (raw != null && raw.isNotEmpty) {
      final decoded = jsonDecode(raw) as List<dynamic>;
      _templates
        ..clear()
        ..addAll(
          decoded
              .whereType<Map>()
              .map((item) => ButtonTemplateModel.fromJson(Map<String, dynamic>.from(item))),
        );
    }

    _isLoaded = true;
    notifyListeners();
  }

  void addTemplate(ButtonTemplateModel template) {
    final existingIndex = _templates.indexWhere(
      (t) => t.id == template.id || t.name == template.name,
    );
    if (existingIndex >= 0) {
      _templates[existingIndex] = template;
    } else {
      _templates.add(template);
    }
    _save();
    notifyListeners();
  }

  void removeTemplate(String id) {
    _templates.removeWhere((t) => t.id == id);
    _save();
    notifyListeners();
  }

  Future<void> _save() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      _storageKey,
      jsonEncode(_templates.map((template) => template.toJson()).toList()),
    );
  }
}
