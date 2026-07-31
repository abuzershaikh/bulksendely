import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:autoreply/data/models/button_template_model.dart';

class ChatbotTemplateStorage {
  static const String _key = 'chatbot_templates';
  
  static final ChatbotTemplateStorage _instance = ChatbotTemplateStorage._internal();
  factory ChatbotTemplateStorage() => _instance;
  ChatbotTemplateStorage._internal();

  List<ButtonTemplateModel> _templates = [];
  List<ButtonTemplateModel> get templates => List.unmodifiable(_templates);

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    final String? jsonString = prefs.getString(_key);
    
    if (jsonString != null) {
      try {
        final List<dynamic> jsonList = jsonDecode(jsonString);
        _templates = jsonList.map((json) => ButtonTemplateModel.fromJson(json)).toList();
      } catch (e) {
        _templates = [];
      }
    }
  }

  Future<void> _save() async {
    final prefs = await SharedPreferences.getInstance();
    final String jsonString = jsonEncode(_templates.map((t) => t.toJson()).toList());
    await prefs.setString(_key, jsonString);
  }

  Future<void> addTemplate(ButtonTemplateModel template) async {
    final index = _templates.indexWhere((t) => t.id == template.id);
    if (index != -1) {
      _templates[index] = template;
    } else {
      _templates.add(template);
    }
    await _save();
  }

  Future<void> updateTemplate(String id, ButtonTemplateModel newTemplate) async {
    final index = _templates.indexWhere((t) => t.id == id);
    if (index != -1) {
      _templates[index] = newTemplate;
      await _save();
    }
  }

  Future<void> deleteTemplate(String id) async {
    _templates.removeWhere((t) => t.id == id);
    await _save();
  }
}
