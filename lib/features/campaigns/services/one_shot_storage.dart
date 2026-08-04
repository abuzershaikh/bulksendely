import 'dart:convert';
import 'package:autoreply/features/campaigns/models/one_shot_range_model.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class OneShotStorage {
  static final OneShotStorage instance = OneShotStorage._();
  OneShotStorage._();

  static const String _settingsKey = 'oneshot_settings_v1';
  static const String _sentNumbersKeyPrefix = 'oneshot_sent_numbers_';
  static const String _historyKey = 'oneshot_history_logs_v1';

  OneShotSettings _settings = const OneShotSettings();
  bool _initialized = false;

  OneShotSettings get settings => _settings;

  Future<void> init() async {
    if (_initialized) return;
    try {
      final prefs = await SharedPreferences.getInstance();
      final rawSettings = prefs.getString(_settingsKey);
      if (rawSettings != null && rawSettings.isNotEmpty) {
        _settings = OneShotSettings.fromJson(jsonDecode(rawSettings));
      }
    } catch (e) {
      debugPrint('Error initializing OneShotStorage: $e');
    }
    _initialized = true;
  }

  Future<void> updateSettings(OneShotSettings newSettings) async {
    _settings = newSettings;
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_settingsKey, jsonEncode(newSettings.toJson()));
    } catch (e) {
      debugPrint('Error saving OneShotSettings: $e');
    }
  }

  Future<Set<String>> getSentNumbersForGroup(String groupId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final list = prefs.getStringList('$_sentNumbersKeyPrefix$groupId') ?? [];
      return list.toSet();
    } catch (e) {
      return {};
    }
  }

  Future<void> markNumbersSent(String groupId, List<String> numbers) async {
    if (numbers.isEmpty) return;
    try {
      final prefs = await SharedPreferences.getInstance();
      final current = (prefs.getStringList('$_sentNumbersKeyPrefix$groupId') ?? []).toSet();
      current.addAll(numbers.map((n) => n.replaceAll(RegExp(r'[^0-9]'), '')));
      await prefs.setStringList('$_sentNumbersKeyPrefix$groupId', current.toList());
    } catch (e) {
      debugPrint('Error marking numbers sent: $e');
    }
  }

  Future<void> resetGroupProgress(String groupId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('$_sentNumbersKeyPrefix$groupId');
    } catch (e) {
      debugPrint('Error resetting group progress: $e');
    }
  }

  Future<void> addHistoryLog(OneShotHistoryLog log) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final rawLogs = prefs.getStringList(_historyKey) ?? [];
      rawLogs.insert(0, jsonEncode(log.toJson()));
      // Keep last 100 logs
      if (rawLogs.length > 100) {
        rawLogs.removeRange(100, rawLogs.length);
      }
      await prefs.setStringList(_historyKey, rawLogs);
    } catch (e) {
      debugPrint('Error adding history log: $e');
    }
  }

  Future<List<OneShotHistoryLog>> getHistoryLogs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final rawLogs = prefs.getStringList(_historyKey) ?? [];
      return rawLogs
          .map((item) => OneShotHistoryLog.fromJson(jsonDecode(item)))
          .toList();
    } catch (e) {
      return [];
    }
  }

  /// Calculates the list of OneShotRanges for a contact list of `totalContacts`
  List<OneShotRange> calculateRanges({
    required int totalContacts,
    required Set<String> sentNumbers,
    required List<String> contactNumbers,
  }) {
    if (totalContacts <= 0) return [];
    final batchSize = _settings.rangeSize > 0 ? _settings.rangeSize : 50;
    final List<OneShotRange> ranges = [];

    for (var start = 1; start <= totalContacts; start += batchSize) {
      final end = (start + batchSize - 1) > totalContacts ? totalContacts : (start + batchSize - 1);
      final rangeTotal = end - start + 1;

      // Count how many contacts in this range [start-1 .. end-1] are already sent
      int sentInRange = 0;
      for (var i = start - 1; i < end && i < contactNumbers.length; i++) {
        final cleanNum = contactNumbers[i].replaceAll(RegExp(r'[^0-9]'), '');
        if (sentNumbers.contains(cleanNum)) {
          sentInRange++;
        }
      }

      OneShotRangeStatus status = OneShotRangeStatus.pending;
      if (sentInRange == rangeTotal) {
        status = OneShotRangeStatus.sent;
      } else if (sentInRange > 0) {
        status = OneShotRangeStatus.partial;
      }

      ranges.add(
        OneShotRange(
          startIndex: start,
          endIndex: end,
          totalCount: rangeTotal,
          status: status,
          sentCount: sentInRange,
        ),
      );
    }

    return ranges;
  }
}
