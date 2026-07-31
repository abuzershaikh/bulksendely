import 'dart:io';

import 'package:autoreply/data/models/contact_group_model.dart';
import 'package:csv/csv.dart';

class CsvImportService {
  /// Parse CSV file and return list of contacts
  /// Supports multiple formats:
  /// 1. Name, Phone Number
  /// 2. Phone Number only
  /// 3. Any column containing phone numbers
  /// 4. Comma or semicolon separated files
  static Future<List<ContactModel>> importFromCsv(File file) async {
    try {
      final input = await file.readAsString();
      final fields = _parseCsvRows(input);

      if (fields.isEmpty) {
        throw Exception('CSV file is empty');
      }

      final contacts = <ContactModel>[];
      var startIndex = 0;

      if (fields.isNotEmpty && fields.first.isNotEmpty && _isHeaderRow(fields.first)) {
        startIndex = 1;
      }

      for (var i = startIndex; i < fields.length; i++) {
        final row = fields[i];
        if (row.isEmpty) continue;

        final contact = _extractContactFromRow(row);
        if (contact != null) {
          contacts.add(contact);
        }
      }

      return contacts;
    } catch (e) {
      throw Exception('Failed to parse CSV: $e');
    }
  }

  static List<List<dynamic>> _parseCsvRows(String rawInput) {
    final input = rawInput.replaceFirst('\uFEFF', '').trim();
    if (input.isEmpty) {
      return const [];
    }

    final commaRows = const CsvToListConverter(
      shouldParseNumbers: false,
      eol: '\n',
    ).convert(input);

    final semicolonRows = const CsvToListConverter(
      shouldParseNumbers: false,
      fieldDelimiter: ';',
      eol: '\n',
    ).convert(input);

    final commaWidth = commaRows.fold<int>(0, (max, row) => row.length > max ? row.length : max);
    final semicolonWidth = semicolonRows.fold<int>(0, (max, row) => row.length > max ? row.length : max);

    return semicolonWidth > commaWidth ? semicolonRows : commaRows;
  }

  /// Check if a row is likely a header row
  static bool _isHeaderRow(List<dynamic> row) {
    for (final cell in row) {
      final cellStr = _stringValue(cell).toLowerCase().trim();
      if (cellStr.contains('name') ||
          cellStr.contains('phone') ||
          cellStr.contains('number') ||
          cellStr.contains('mobile') ||
          cellStr.contains('contact')) {
        return true;
      }
    }
    return false;
  }

  /// Extract contact from a row (flexible format detection)
  static ContactModel? _extractContactFromRow(List<dynamic> row) {
    String? name;
    String? number;

    for (var i = 0; i < row.length; i++) {
      final cellValue = _stringValue(row[i]).trim();
      if (cellValue.isEmpty) continue;

      final normalized = _normalizePhone(cellValue);

      if (normalized.length >= 8 && _looksLikePhoneNumber(cellValue)) {
        number = normalized;

        for (var j = 0; j < row.length; j++) {
          if (j == i) continue;

          final possibleName = _stringValue(row[j]).trim();
          if (possibleName.isNotEmpty && !_looksLikePhoneNumber(possibleName)) {
            name = possibleName;
            break;
          }
        }

        if (name == null || name.isEmpty) {
          name = 'Contact ${number.substring(number.length > 4 ? number.length - 4 : 0)}';
        }

        break;
      }
    }

    if ((number == null || number.length < 8) && row.length > 1) {
      final merged = _mergeSplitNumberCells(row);
      if (merged != null && merged.length >= 8) {
        number = merged;
        name = _extractNameFromRow(row);
        if (name == null || name.isEmpty) {
          name = 'Contact ${number.substring(number.length > 4 ? number.length - 4 : 0)}';
        }
      }
    }

    if (number != null && number.length >= 8) {
      return ContactModel(
        name: name ?? 'Unknown',
        number: number,
      );
    }

    return null;
  }

  static String _stringValue(dynamic value) {
    if (value == null) {
      return '';
    }

    if (value is String) {
      return value;
    }

    if (value is num) {
      if (value is double && value == value.roundToDouble()) {
        return value.toStringAsFixed(0);
      }
      return value.toString();
    }

    return value.toString();
  }

  static String _normalizePhone(String value) {
    final trimmed = value.trim();
    if (trimmed.isEmpty) {
      return '';
    }

    var candidate = trimmed;
    final scientificNotation = RegExp(r'^[\+\-]?\d+(\.\d+)?[eE][\+\-]?\d+$');
    if (scientificNotation.hasMatch(candidate)) {
      final parsed = num.tryParse(candidate);
      if (parsed != null) {
        candidate = parsed.toStringAsFixed(0);
      }
    }

    final startsWithPlus = candidate.startsWith('+');
    var normalized = candidate.replaceAll(RegExp(r'[^\d+]'), '');
    if (startsWithPlus) {
      normalized = '+${normalized.replaceAll('+', '')}';
    } else {
      normalized = normalized.replaceAll('+', '');
    }
    return normalized;
  }

  /// Check if a string looks like a phone number
  static bool _looksLikePhoneNumber(String value) {
    final cleaned = value.replaceAll(RegExp(r'[\s\-\(\)\+\,\.]'), '');
    if (cleaned.isEmpty) {
      return false;
    }

    final digitCount = cleaned.replaceAll(RegExp(r'[^\d]'), '').length;
    return digitCount >= 8 && (digitCount / cleaned.length) >= 0.7;
  }

  static String? _mergeSplitNumberCells(List<dynamic> row) {
    final numericFragments = <String>[];
    var hasLeadingPlus = false;

    for (final cell in row) {
      final raw = _stringValue(cell).trim();
      if (raw.isEmpty) continue;
      if (!_isNumericFragment(raw)) continue;

      if (raw.startsWith('+') && numericFragments.isEmpty) {
        hasLeadingPlus = true;
      }

      final digits = raw.replaceAll(RegExp(r'[^\d]'), '');
      if (digits.isNotEmpty) {
        numericFragments.add(digits);
      }
    }

    if (numericFragments.length < 2) {
      return null;
    }

    final mergedDigits = numericFragments.join();
    if (mergedDigits.length < 8) {
      return null;
    }

    return hasLeadingPlus ? '+$mergedDigits' : mergedDigits;
  }

  static bool _isNumericFragment(String value) {
    final cleaned = value.trim();
    if (cleaned.isEmpty) {
      return false;
    }

    if (RegExp(r'[A-Za-z]').hasMatch(cleaned)) {
      return false;
    }

    final digitCount = cleaned.replaceAll(RegExp(r'[^\d]'), '').length;
    return digitCount > 0;
  }

  static String? _extractNameFromRow(List<dynamic> row) {
    for (final cell in row) {
      final value = _stringValue(cell).trim();
      if (value.isEmpty) continue;
      if (!_looksLikePhoneNumber(value) && !_isNumericFragment(value)) {
        return value;
      }
    }
    return null;
  }

  /// Validate CSV file format
  static Future<bool> validateCsvFile(File file) async {
    try {
      final contacts = await importFromCsv(file);
      return contacts.isNotEmpty;
    } catch (_) {
      return false;
    }
  }
}
