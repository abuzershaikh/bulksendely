import 'dart:convert';

import 'package:autoreply/data/models/button_template_model.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class TemplateStorage extends ChangeNotifier {
  static const String _storageKey = 'message_templates_v1';
  static final TemplateStorage _instance = TemplateStorage._internal();

  factory TemplateStorage() {
    return _instance;
  }

  TemplateStorage._internal();

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

    _ensureSampleTemplates();
    await _save();

    _isLoaded = true;
    notifyListeners();
  }

  void addTemplate(ButtonTemplateModel template) {
    final index = _templates.indexWhere((t) => t.id == template.id);
    if (index != -1) {
      _templates[index] = template;
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

  void _ensureSampleTemplates() {
    final demoTemplates = <ButtonTemplateModel>[
      ButtonTemplateModel(
        id: 'sample_button_offer_template',
        name: 'Bulksendly Demo Buttons',
        title: 'Welcome to Bulksendly',
        caption:
            'Hi there!\nThanks for reaching out to Bulksendly.\nChoose an option below to test our smart button message experience.',
        footer: 'Demo template for quick testing',
        buttons: [
          TemplateButton(
            type: ButtonTemplateType.text,
            displayText: 'View Plans',
          ),
          TemplateButton(
            type: ButtonTemplateType.call,
            displayText: 'Talk to Sales',
            phoneNumber: '+919137167857',
          ),
          TemplateButton(
            type: ButtonTemplateType.link,
            displayText: 'Get Demo',
            url: 'https://example.com',
          ),
        ],
      ),
      ButtonTemplateModel(
        id: 'sample_list_offer_template',
        templateType: TemplateLibraryType.list,
        name: 'Bulksendly Demo List',
        title: 'Bulksendly Quick Menu',
        caption:
            'Welcome! Open this demo list to preview a real WhatsApp-style menu template for offers, support, and onboarding.',
        footer: 'Demo list template for quick testing',
        buttons: [],
        listButtonText: 'Open Menu',
        sections: [
          ListTemplateSection(
            title: 'Getting Started',
            rows: [
              ListTemplateRow(
                id: 'demo_list_row_pricing',
                title: 'Pricing Plans',
                description: 'Compare starter, growth, and pro packages',
              ),
              ListTemplateRow(
                id: 'demo_list_row_demo',
                title: 'Book Live Demo',
                description: 'Schedule a walkthrough with our team',
              ),
            ],
          ),
          ListTemplateSection(
            title: 'Customer Help',
            rows: [
              ListTemplateRow(
                id: 'demo_list_row_support',
                title: 'Talk to Support',
                description: 'Get setup help and troubleshooting guidance',
              ),
              ListTemplateRow(
                id: 'demo_list_row_whatsapp',
                title: 'Connect WhatsApp',
                description: 'See how device linking works step by step',
              ),
            ],
          ),
        ],
      ),
    ];

    for (final template in demoTemplates.reversed) {
      final existingIndex = _templates.indexWhere((item) => item.id == template.id);
      if (existingIndex >= 0) {
        _templates[existingIndex] = template;
      } else {
        _templates.insert(0, template);
      }
    }
  }
}
