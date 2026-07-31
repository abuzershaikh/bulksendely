import 'package:http/http.dart' as http;
import 'package:csv/csv.dart';
import 'package:autoreply/data/models/contact_group_model.dart';

class SheetsImportService {
  /// Parse pasted text containing phone numbers separated by commas or lines.
  static List<ContactModel> parsePastedContacts(String rawText) {
    final normalizedText = rawText.trim();
    if (normalizedText.isEmpty) {
      return const <ContactModel>[];
    }

    final segments = normalizedText
        .split(RegExp(r'[\r\n,;]+'))
        .map((segment) => segment.trim())
        .where((segment) => segment.isNotEmpty);

    final seenNumbers = <String>{};
    final contacts = <ContactModel>[];

    for (final segment in segments) {
      final number = _normalizePhoneNumber(segment);
      if (number == null || !seenNumbers.add(number)) {
        continue;
      }

      contacts.add(
        ContactModel(
          name:
              'Contact ${number.substring(number.length > 4 ? number.length - 4 : 0)}',
          number: number,
        ),
      );
    }

    return contacts;
  }

  /// Import contacts from Google Sheets URL
  /// Supports both regular and published sheet URLs
  static Future<List<ContactModel>> importFromSheetsUrl(String url) async {
    try {
      print('📊 Starting Google Sheets import...');
      print('🔗 Original URL: $url');

      // Convert Google Sheets URL to CSV export URL
      final csvUrl = _convertToCsvUrl(url);
      print('📥 CSV Export URL: $csvUrl');

      // Fetch CSV data
      print('🌐 Fetching data from Google Sheets...');
      final response = await http
          .get(
            Uri.parse(csvUrl),
            headers: {
              'User-Agent':
                  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            },
          )
          .timeout(
            const Duration(seconds: 30),
            onTimeout: () {
              throw Exception(
                'Request timeout. Please check your internet connection.',
              );
            },
          );

      print('📡 Response Status: ${response.statusCode}');
      print('📄 Response Length: ${response.body.length} bytes');

      if (response.statusCode != 200) {
        print('❌ Error Response: ${response.body}');
        throw Exception(
          'Failed to fetch data. Status: ${response.statusCode}\nMake sure the sheet is shared publicly.',
        );
      }

      if (response.body.isEmpty) {
        throw Exception('Sheet is empty or not accessible.');
      }

      // Parse CSV content
      print('🔍 Parsing CSV content...');
      final contacts = _parseCsvContent(response.body);
      print('✅ Successfully parsed ${contacts.length} contacts');

      return contacts;
    } catch (e) {
      print('❌ Import Error: $e');
      if (e.toString().contains('SocketException')) {
        throw Exception('No internet connection. Please check your network.');
      } else if (e.toString().contains('TimeoutException')) {
        throw Exception('Request timeout. Please try again.');
      } else if (e.toString().contains('FormatException')) {
        throw Exception('Invalid URL format.');
      }
      rethrow;
    }
  }

  /// Convert Google Sheets URL to CSV export URL
  static String _convertToCsvUrl(String url) {
    // Extract sheet ID from various URL formats
    String? sheetId;
    String? gid;

    // Format 1: https://docs.google.com/spreadsheets/d/{ID}/edit...
    final pattern1 = RegExp(r'/spreadsheets/d/([a-zA-Z0-9-_]+)');
    final match1 = pattern1.firstMatch(url);
    if (match1 != null) {
      sheetId = match1.group(1);
    }

    // Extract gid (sheet tab) if present
    final gidPattern = RegExp(r'[#&]gid=([0-9]+)');
    final gidMatch = gidPattern.firstMatch(url);
    if (gidMatch != null) {
      gid = gidMatch.group(1);
    }

    if (sheetId == null) {
      throw Exception(
        'Invalid Google Sheets URL. Please provide a valid sheet link.',
      );
    }

    // Build CSV export URL
    String csvUrl =
        'https://docs.google.com/spreadsheets/d/$sheetId/export?format=csv';
    if (gid != null) {
      csvUrl += '&gid=$gid';
    }

    return csvUrl;
  }

  /// Parse CSV content (similar to CSV import service)
  static List<ContactModel> _parseCsvContent(String content) {
    final fields = const CsvToListConverter().convert(content);

    if (fields.isEmpty) {
      throw Exception('Sheet is empty');
    }

    List<ContactModel> contacts = [];
    int startIndex = 0;

    // Detect if first row is header
    if (fields.isNotEmpty && fields[0].isNotEmpty) {
      bool isHeader = _isHeaderRow(fields[0]);
      if (isHeader) {
        startIndex = 1;
      }
    }

    // Parse contacts
    for (int i = startIndex; i < fields.length; i++) {
      final row = fields[i];

      if (row.isEmpty) continue;

      final contact = _extractContactFromRow(row);
      if (contact != null) {
        contacts.add(contact);
      }
    }

    return contacts;
  }

  /// Check if a row is likely a header row
  static bool _isHeaderRow(List<dynamic> row) {
    for (var cell in row) {
      String cellStr = cell.toString().toLowerCase().trim();
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

  /// Extract contact from a row
  static ContactModel? _extractContactFromRow(List<dynamic> row) {
    String? name;
    String? number;

    for (int i = 0; i < row.length; i++) {
      String cellValue = row[i].toString().trim();

      final normalized = _normalizePhoneNumber(cellValue);

      if (normalized != null && _looksLikePhoneNumber(cellValue)) {
        number = normalized;

        for (int j = 0; j < row.length; j++) {
          if (j != i) {
            String possibleName = row[j].toString().trim();
            if (possibleName.isNotEmpty &&
                !_looksLikePhoneNumber(possibleName)) {
              name = possibleName;
              break;
            }
          }
        }

        if (name == null || name.isEmpty) {
          name =
              'Contact ${number.substring(number.length > 4 ? number.length - 4 : 0)}';
        }

        break;
      }
    }

    if (number != null && number.length >= 8) {
      return ContactModel(name: name ?? 'Unknown', number: number);
    }

    return null;
  }

  /// Check if a string looks like a phone number
  static bool _looksLikePhoneNumber(String value) {
    String cleaned = value.replaceAll(RegExp(r'[\s\-\(\)\+]'), '');
    int digitCount = cleaned.replaceAll(RegExp(r'[^\d]'), '').length;
    return digitCount >= 8 && (digitCount / cleaned.length) >= 0.7;
  }

  static String? _normalizePhoneNumber(String value) {
    final trimmed = value.trim();
    if (trimmed.isEmpty) {
      return null;
    }

    final hasLeadingPlus = trimmed.startsWith('+');
    final digitsOnly = trimmed.replaceAll(RegExp(r'[^\d]'), '');
    if (digitsOnly.length < 8) {
      return null;
    }

    return hasLeadingPlus ? '+$digitsOnly' : digitsOnly;
  }

  /// Validate Google Sheets URL
  static bool isValidSheetsUrl(String url) {
    return url.contains('docs.google.com/spreadsheets') ||
        url.contains('drive.google.com');
  }
}
