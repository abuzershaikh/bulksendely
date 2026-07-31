import 'dart:convert';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:autoreply/features/subscription/services/subscription_service.dart';

class ApiClient {
  static const String adminApiKey = "67a576fc-e0fd-4299-848b-45e8a50a50e1";
  static const String baseUrl = "http://139.84.138.48";
  static const String adminBaseUrl = "http://139.84.138.48";
  static const Duration _requestTimeout = Duration(seconds: 20);

  static Future<String> requireWaziperAccessToken() async {
    final accessToken = await SubscriptionService.instance.ensureWaziperAccessToken();
    if (accessToken.isEmpty) {
      throw Exception('Unable to load your WhatsApp workspace. Please sign in again.');
    }
    return accessToken;
  }

  static Future<Map<String, dynamic>> post(
    String endpoint,
    Map<String, dynamic> body, {
    Duration timeout = _requestTimeout,
  }) async {
    final url = Uri.parse('$baseUrl/$endpoint');

    try {
      final response = await http.post(
        url,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: jsonEncode(body),
      ).timeout(timeout);

      print('API Response [$endpoint]: ${response.body}');

      final decoded = _decodeJsonOrThrow(response.body, endpoint);

      if (response.statusCode == 200) {
        return decoded;
      }

      throw Exception(
        decoded['message']?.toString().isNotEmpty == true
            ? decoded['message'].toString()
            : 'Server Error: ${response.statusCode} ${response.reasonPhrase ?? ''}'.trim(),
      );
    } on TimeoutException {
      throw Exception('Connection timeout. Please check server or internet connection.');
    } catch (e) {
      throw Exception('Network Error: $e');
    }
  }

  // Fallback testing delay
  static Future<void> simulateDelay([int milliseconds = 1500]) async {
    await Future.delayed(Duration(milliseconds: milliseconds));
  }

  static Future<Map<String, dynamic>> get(
    String endpoint, [
    Map<String, dynamic>? query,
    Duration timeout = _requestTimeout,
  ]) async {
    final uri = Uri.parse('$baseUrl/$endpoint').replace(
      queryParameters: query?.map(
        (key, value) => MapEntry(key, value?.toString() ?? ''),
      ),
    );

    try {
      final response = await http.get(
        uri,
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      ).timeout(timeout);
      print('API Response [$endpoint]: ${response.body}');

      final decoded = _decodeJsonOrThrow(response.body, endpoint);

      if (response.statusCode == 200) {
        return decoded;
      }

      throw Exception(
        decoded['message']?.toString().isNotEmpty == true
            ? decoded['message'].toString()
            : 'Server Error: ${response.statusCode} ${response.reasonPhrase ?? ''}'.trim(),
      );
    } on TimeoutException {
      throw Exception('Connection timeout. Please check server or internet connection.');
    } catch (e) {
      throw Exception('Network Error: $e');
    }
  }

  static Future<Map<String, dynamic>> postToAdmin(String endpoint, Map<String, dynamic> body) async {
    final url = Uri.parse('$adminBaseUrl/$endpoint');

    try {
      // Field debugging: shows that request was actually initiated.
      print('ADMIN API REQ [$endpoint] -> $url');
      final response = await http.post(
        url,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: jsonEncode(body),
      ).timeout(_requestTimeout);

      final raw = response.body;
      // Helpful in field debugging when Admin API is slow or down.
      print('ADMIN API [$endpoint] ${response.statusCode} (${raw.length} bytes)');
      final trimmed = raw.trimLeft();
      if (trimmed.startsWith('<!DOCTYPE') || trimmed.startsWith('<html')) {
        throw Exception(
          'Admin API endpoint not deployed or routed: $endpoint. Server returned HTML page instead of JSON.',
        );
      }

      if (response.statusCode == 200) {
        return jsonDecode(raw);
      } else {
        throw Exception('Server Error: ${response.statusCode} ${response.reasonPhrase ?? ''}'.trim());
      }
    } on TimeoutException {
      print('ADMIN API TIMEOUT [$endpoint]');
      throw Exception('Connection timeout. Please check server or internet connection.');
    } catch (e) {
      print('ADMIN API ERROR [$endpoint] $e');
      throw Exception('Network Error: $e');
    }
  }

  static Map<String, dynamic> _decodeJsonOrThrow(String raw, String endpoint) {
    final trimmed = raw.trimLeft();
    if (trimmed.startsWith('<!DOCTYPE') || trimmed.startsWith('<html')) {
      throw Exception(
        'Server returned HTML for $endpoint instead of JSON. Check the route and backend response.',
      );
    }

    final decoded = jsonDecode(raw);
    if (decoded is Map<String, dynamic>) {
      return decoded;
    }

    if (decoded is Map) {
      return Map<String, dynamic>.from(decoded);
    }

    throw Exception('Invalid JSON payload returned by $endpoint');
  }
}
