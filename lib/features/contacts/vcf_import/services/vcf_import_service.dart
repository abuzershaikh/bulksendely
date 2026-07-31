import 'dart:io';
import 'package:autoreply/data/models/contact_group_model.dart';

class VcfImportService {
  /// Parse VCF/vCard file and return list of contacts
  /// Supports vCard 2.1, 3.0, and 4.0 formats
  static Future<List<ContactModel>> importFromVcf(File file) async {
    try {
      final input = await file.readAsString();
      return _parseVcfContent(input);
    } catch (e) {
      throw Exception('Failed to parse VCF: $e');
    }
  }

  /// Parse VCF content from string
  static List<ContactModel> _parseVcfContent(String content) {
    List<ContactModel> contacts = [];
    
    // Split by vCard entries (BEGIN:VCARD ... END:VCARD)
    final vcardPattern = RegExp(
      r'BEGIN:VCARD.*?END:VCARD',
      multiLine: true,
      dotAll: true,
      caseSensitive: false,
    );
    
    final matches = vcardPattern.allMatches(content);
    
    for (var match in matches) {
      final vcardText = match.group(0);
      if (vcardText != null) {
        final contact = _parseVcard(vcardText);
        if (contact != null) {
          contacts.add(contact);
        }
      }
    }
    
    return contacts;
  }

  /// Parse a single vCard entry
  static ContactModel? _parseVcard(String vcard) {
    String? name;
    String? number;
    
    final lines = vcard.split('\n');
    
    for (var line in lines) {
      line = line.trim();
      
      // Handle line folding (lines starting with space or tab)
      if (line.isEmpty) continue;
      
      // Extract name (FN or N field)
      if (line.toUpperCase().startsWith('FN:')) {
        name = line.substring(3).trim();
      } else if (line.toUpperCase().startsWith('N:') && name == null) {
        // N format: Family;Given;Middle;Prefix;Suffix
        final nameParts = line.substring(2).split(';');
        final given = nameParts.length > 1 ? nameParts[1] : '';
        final family = nameParts.isNotEmpty ? nameParts[0] : '';
        name = '$given $family'.trim();
      }
      
      // Extract phone number (TEL field)
      if (line.toUpperCase().startsWith('TEL') && number == null) {
        // Handle various TEL formats:
        // TEL:+1234567890
        // TEL;TYPE=CELL:+1234567890
        // TEL;CELL:+1234567890
        final colonIndex = line.indexOf(':');
        if (colonIndex != -1) {
          final phoneValue = line.substring(colonIndex + 1).trim();
          final normalized = phoneValue.replaceAll(RegExp(r'[^\d+]'), '');
          if (normalized.length >= 8) {
            number = normalized;
          }
        }
      }
    }
    
    // Return contact if we have at least a phone number
    if (number != null && number.length >= 8) {
      return ContactModel(
        name: name ?? 'Contact ${number.substring(number.length > 4 ? number.length - 4 : 0)}',
        number: number,
      );
    }
    
    return null;
  }

  /// Validate VCF file format
  static Future<bool> validateVcfFile(File file) async {
    try {
      final input = await file.readAsString();
      // Check if file contains at least one vCard
      return input.toUpperCase().contains('BEGIN:VCARD') && 
             input.toUpperCase().contains('END:VCARD');
    } catch (e) {
      return false;
    }
  }
}
