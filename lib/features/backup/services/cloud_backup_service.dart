import 'dart:async';
import 'dart:convert';
import 'package:autoreply/core/network/api_client.dart';
import 'package:autoreply/data/local/contact_storage.dart';
import 'package:autoreply/data/local/template_storage.dart';
import 'package:autoreply/data/models/button_template_model.dart';
import 'package:autoreply/data/models/contact_group_model.dart';
import 'package:autoreply/features/auth/google_auth/services/google_auth_service.dart';
import 'package:autoreply/features/autoreply/keyword_reply/models/keyword_reply_model.dart';
import 'package:autoreply/features/autoreply/keyword_reply/storage/keyword_reply_storage.dart';
import 'package:autoreply/features/autoreply/menu_reply/models/menu_reply_model.dart';
import 'package:autoreply/features/autoreply/menu_reply/storage/menu_reply_storage.dart';
import 'package:autoreply/features/autoreply/template/storage/autoreply_template_storage.dart';
import 'package:autoreply/features/autoreply/welcome_message/models/welcome_message_model.dart';
import 'package:autoreply/features/autoreply/welcome_message/storage/welcome_message_storage.dart';
import 'package:autoreply/features/campaigns/models/one_shot_range_model.dart';
import 'package:autoreply/features/campaigns/services/one_shot_storage.dart';
import 'package:autoreply/features/chatbot_flow/templates/storage/chatbot_template_storage.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class CloudBackupService {
  static final CloudBackupService instance = CloudBackupService._();
  CloudBackupService._();

  static const String _lastBackupKey = 'cloud_last_backup_time_v1';
  static const String _autoBackupKey = 'cloud_auto_backup_enabled_v1';

  final GoogleAuthService _authService = GoogleAuthService();

  ValueNotifier<DateTime?> lastBackupNotifier = ValueNotifier<DateTime?>(null);
  ValueNotifier<bool> isBackingUpNotifier = ValueNotifier<bool>(false);

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    final rawTime = prefs.getString(_lastBackupKey);
    if (rawTime != null) {
      lastBackupNotifier.value = DateTime.tryParse(rawTime);
    }
  }

  Future<bool> isAutoBackupEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(_autoBackupKey) ?? true;
  }

  Future<void> setAutoBackupEnabled(bool enabled) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_autoBackupKey, enabled);
  }

  Future<String?> getCurrentUserEmail() async {
    final info = await _authService.getUserInfo();
    final email = info['email']?.trim();
    if (email != null && email.isNotEmpty) {
      return email.toLowerCase();
    }
    return null;
  }

  /// Exports all local app data into a structured JSON Map payload
  Future<Map<String, dynamic>> exportAllData() async {
    final contactStorage = ContactStorage();
    final templateStorage = TemplateStorage();
    final keywordStorage = KeywordReplyStorage();
    final menuStorage = MenuReplyStorage();
    final autoreplyTemplateStorage = AutoReplyTemplateStorage();
    final welcomeStorage = WelcomeMessageStorage();
    final chatbotStorage = ChatbotTemplateStorage();
    final oneShotStorage = OneShotStorage.instance;

    await contactStorage.load();
    await templateStorage.load();
    await keywordStorage.load();
    await menuStorage.load();
    await autoreplyTemplateStorage.load();
    await welcomeStorage.load();
    await chatbotStorage.load();
    await oneShotStorage.init();

    // 1. Contacts
    final contactsList = contactStorage.groups.map((g) => g.toJson()).toList();

    // 2. Templates
    final templates = templateStorage.templates.map((t) => t.toJson()).toList();

    // 3. AutoReply Rules
    final keywords = keywordStorage.flows.map((k) => k.toJson()).toList();
    final menus = menuStorage.flows.map((m) => m.toJson()).toList();
    final autoreplyTemplates = autoreplyTemplateStorage.templates.map((t) => t.toJson()).toList();
    final welcomeMessages = welcomeStorage.flows.map((w) => w.toJson()).toList();

    // 4. Chatbot Templates
    final chatbotTemplates = chatbotStorage.templates.map((c) => c.toJson()).toList();

    // 5. OneShot Settings & Logs
    final historyLogs = (await oneShotStorage.getHistoryLogs()).map((h) => h.toJson()).toList();

    final userEmail = await getCurrentUserEmail();

    return {
      'metadata': {
        'version': 1,
        'userEmail': userEmail ?? '',
        'exportedAt': DateTime.now().toIso8601String(),
        'appName': 'Bulksendly',
      },
      'contacts': contactsList,
      'templates': templates,
      'autoreply': {
        'keywords': keywords,
        'menus': menus,
        'autoreplyTemplates': autoreplyTemplates,
        'welcomeMessages': welcomeMessages,
      },
      'chatbot': {
        'chatbotTemplates': chatbotTemplates,
      },
      'oneShot': {
        'settings': oneShotStorage.settings.toJson(),
        'historyLogs': historyLogs,
      },
    };
  }

  /// Backs up all local data to VPS cloud server
  Future<bool> performBackup() async {
    final email = await getCurrentUserEmail();
    if (email == null || email.isEmpty) {
      throw Exception('User is not logged in. Please sign in with Email.');
    }

    isBackingUpNotifier.value = true;
    try {
      final payloadData = await exportAllData();

      final response = await ApiClient.post(
        'api/user/backup',
        {
          'user_email': email,
          'backup_data': jsonEncode(payloadData),
        },
      );

      if (response['status'] == 'success') {
        final now = DateTime.now();
        lastBackupNotifier.value = now;
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString(_lastBackupKey, now.toIso8601String());
        return true;
      } else {
        throw Exception(response['message'] ?? 'Failed to save cloud backup.');
      }
    } catch (e) {
      debugPrint('Cloud Backup Failed: $e');
      rethrow;
    } finally {
      isBackingUpNotifier.value = false;
    }
  }

  /// Fetches latest cloud backup payload from VPS
  Future<Map<String, dynamic>?> fetchCloudBackup({String? userEmail}) async {
    final email = userEmail ?? await getCurrentUserEmail();
    if (email == null || email.isEmpty) {
      return null;
    }

    try {
      final response = await ApiClient.post(
        'api/user/restore',
        {'user_email': email},
      );

      if (response['status'] == 'success' && response['data'] != null) {
        final data = response['data'];
        var backupData = data['backup_data'];
        if (backupData is String) {
          backupData = jsonDecode(backupData);
        }
        if (backupData is Map<String, dynamic>) {
          return backupData;
        }
      }
    } catch (e) {
      debugPrint('Fetch Cloud Backup Failed: $e');
    }
    return null;
  }

  /// Restores all data from JSON payload into local storage engines
  Future<int> restoreAllData(Map<String, dynamic> payload) async {
    int restoredItemsCount = 0;

    try {
      // 1. Restore Contacts
      final rawContacts = payload['contacts'];
      if (rawContacts is List) {
        final contactStorage = ContactStorage();
        await contactStorage.load();
        for (final item in rawContacts) {
          if (item is Map<String, dynamic>) {
            final group = ContactGroupModel.fromJson(item);
            contactStorage.addGroup(group);
            restoredItemsCount += group.contacts.length;
          }
        }
      }

      // 2. Restore Templates
      final rawTemplates = payload['templates'];
      if (rawTemplates is List) {
        final templateStorage = TemplateStorage();
        await templateStorage.load();
        for (final item in rawTemplates) {
          if (item is Map<String, dynamic>) {
            templateStorage.addTemplate(ButtonTemplateModel.fromJson(item));
            restoredItemsCount++;
          }
        }
      }

      // 3. Restore AutoReply Rules
      final rawAutoreply = payload['autoreply'];
      if (rawAutoreply is Map<String, dynamic>) {
        final keywordStorage = KeywordReplyStorage();
        final menuStorage = MenuReplyStorage();
        final autoreplyTemplateStorage = AutoReplyTemplateStorage();
        final welcomeStorage = WelcomeMessageStorage();

        await keywordStorage.load();
        await menuStorage.load();
        await autoreplyTemplateStorage.load();
        await welcomeStorage.load();

        final keywords = rawAutoreply['keywords'];
        if (keywords is List) {
          for (final item in keywords) {
            if (item is Map<String, dynamic>) {
              keywordStorage.addOrUpdate(KeywordReplyFlow.fromJson(item));
              restoredItemsCount++;
            }
          }
        }

        final menus = rawAutoreply['menus'];
        if (menus is List) {
          for (final item in menus) {
            if (item is Map<String, dynamic>) {
              menuStorage.addOrUpdate(MenuReplyFlow.fromJson(item));
              restoredItemsCount++;
            }
          }
        }

        final autoreplyTemplates = rawAutoreply['autoreplyTemplates'];
        if (autoreplyTemplates is List) {
          for (final item in autoreplyTemplates) {
            if (item is Map<String, dynamic>) {
              autoreplyTemplateStorage.addTemplate(ButtonTemplateModel.fromJson(item));
              restoredItemsCount++;
            }
          }
        }

        final welcomeMessages = rawAutoreply['welcomeMessages'];
        if (welcomeMessages is List) {
          for (final item in welcomeMessages) {
            if (item is Map<String, dynamic>) {
              welcomeStorage.addOrUpdate(WelcomeMessageFlow.fromJson(item));
              restoredItemsCount++;
            }
          }
        }
      }

      // 4. Restore Chatbot Templates
      final rawChatbot = payload['chatbot'];
      if (rawChatbot is Map<String, dynamic>) {
        final chatbotStorage = ChatbotTemplateStorage();
        await chatbotStorage.load();

        final chatbotTemplates = rawChatbot['chatbotTemplates'];
        if (chatbotTemplates is List) {
          for (final item in chatbotTemplates) {
            if (item is Map<String, dynamic>) {
              await chatbotStorage.addTemplate(ButtonTemplateModel.fromJson(item));
              restoredItemsCount++;
            }
          }
        }
      }

      // 5. Restore OneShot Settings
      final rawOneShot = payload['oneShot'];
      if (rawOneShot is Map<String, dynamic>) {
        final oneShotStorage = OneShotStorage.instance;
        await oneShotStorage.init();

        final settingsMap = rawOneShot['settings'];
        if (settingsMap is Map<String, dynamic>) {
          await oneShotStorage.updateSettings(OneShotSettings.fromJson(settingsMap));
        }

        final historyLogs = rawOneShot['historyLogs'];
        if (historyLogs is List) {
          for (final item in historyLogs) {
            if (item is Map<String, dynamic>) {
              await oneShotStorage.addHistoryLog(OneShotHistoryLog.fromJson(item));
            }
          }
        }
      }

      // Update Last Backup Time
      final now = DateTime.now();
      lastBackupNotifier.value = now;
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_lastBackupKey, now.toIso8601String());

    } catch (e) {
      debugPrint('Restore All Data Error: $e');
      rethrow;
    }

    return restoredItemsCount;
  }

  /// Triggers an automatic background backup if enabled
  Future<void> autoBackupIfNeeded() async {
    final enabled = await isAutoBackupEnabled();
    if (!enabled) return;

    try {
      await performBackup();
    } catch (e) {
      debugPrint('Auto backup skipped or failed: $e');
    }
  }
}
