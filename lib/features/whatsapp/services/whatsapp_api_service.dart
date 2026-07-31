import 'dart:async';

import 'package:autoreply/core/network/api_client.dart';
import 'package:autoreply/data/local/linked_whatsapp_storage.dart';
import 'package:autoreply/features/whatsapp/models/whatsapp_group_model.dart';

class WhatsappPairingSession {
  final String instanceId;
  final String pairCode;
  final String phone;
  final String? qrCode;

  const WhatsappPairingSession({
    required this.instanceId,
    required this.pairCode,
    required this.phone,
    this.qrCode,
  });
}

class WhatsappConnectionStatus {
  final bool connected;
  final String? name;
  final String? wid;
  final String? avatar;
  final String? reason;
  final String? wsState;
  final String? code;

  const WhatsappConnectionStatus({
    required this.connected,
    this.name,
    this.wid,
    this.avatar,
    this.reason,
    this.wsState,
    this.code,
  });
}

class WhatsappSessionConnectingException implements Exception {
  final String message;
  final String? wsState;

  const WhatsappSessionConnectingException(this.message, {this.wsState});

  @override
  String toString() => message;
}

class ActiveWhatsappInstance {
  final String instanceId;
  final String? linkedNumber;
  final String? linkedName;
  final String? wsState;
  final bool connected;
  final bool healthy;

  const ActiveWhatsappInstance({
    required this.instanceId,
    this.linkedNumber,
    this.linkedName,
    this.wsState,
    this.connected = false,
    this.healthy = false,
  });
}

class WhatsappGroupsUpdate {
  final String instanceId;
  final List<WhatsappGroupModel> groups;

  const WhatsappGroupsUpdate({required this.instanceId, required this.groups});
}

class WhatsappApiService {
  static final StreamController<WhatsappGroupsUpdate> _groupUpdatesController =
      StreamController.broadcast();

  final LinkedWhatsappStorage _linkedWhatsappStorage = LinkedWhatsappStorage();

  Stream<WhatsappGroupsUpdate> get groupUpdates =>
      _groupUpdatesController.stream;

  bool _isInvalidatedSessionError(Object error) {
    return error.toString().toLowerCase().contains('invalidated');
  }

  static String? formatLinkedNumber(String? value) {
    if (value == null) return null;

    final raw = value.trim();
    if (raw.isEmpty) return null;

    var normalized = raw;
    final uri = Uri.tryParse(raw);

    if (uri != null && uri.hasScheme) {
      normalized = uri.pathSegments.isNotEmpty
          ? uri.pathSegments.last
          : (uri.path.isNotEmpty ? uri.path : raw);
    }

    if (normalized.contains('@')) {
      normalized = normalized.split('@').first;
    }

    normalized = normalized.replaceAll(RegExp(r'[^0-9+]'), '');

    if (normalized.startsWith('00')) {
      normalized = '+${normalized.substring(2)}';
    }

    return normalized.isEmpty ? raw : normalized;
  }

  static String _normalizePairingPhoneNumber(
    String phoneNumber, {
    String? countryCode,
  }) {
    final hasExplicitPlus = phoneNumber.trim().startsWith('+');
    var phoneDigits = phoneNumber.replaceAll(RegExp(r'[^0-9]'), '');
    final countryDigits = (countryCode ?? '').replaceAll(RegExp(r'[^0-9]'), '');

    phoneDigits = phoneDigits.replaceFirst(RegExp(r'^0+'), '');
    if (phoneDigits.isEmpty) return '';

    if (phoneDigits.startsWith(countryDigits)) {
      return hasExplicitPlus || countryDigits.isNotEmpty
          ? '+$phoneDigits'
          : phoneDigits;
    }

    if (countryDigits.isEmpty) {
      return hasExplicitPlus ? '+$phoneDigits' : phoneDigits;
    }

    return '+$countryDigits$phoneDigits';
  }

  static String _normalizePairingCode(String value) {
    final normalized = value
        .replaceAll(RegExp(r'[^a-zA-Z0-9]'), '')
        .toUpperCase();
    if (normalized.length <= 4) return normalized;
    return '${normalized.substring(0, 4)} ${normalized.substring(4)}';
  }

  Future<WhatsappPairingSession> requestPairingCode(
    String phoneNumber, {
    String? countryCode,
  }) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final cleanedPhone = _normalizePairingPhoneNumber(
      phoneNumber,
      countryCode: countryCode,
    );
    if (cleanedPhone.isEmpty) {
      throw Exception('Valid phone number is required');
    }

    final response = await ApiClient.post('Android_api/request_pairing', {
      'access_token': accessToken,
      'phone_number': cleanedPhone,
      if ((countryCode ?? '').trim().isNotEmpty) 'country_code': countryCode,
    }, timeout: const Duration(seconds: 90));

    if (response['status'] != 'success' || response['data'] == null) {
      throw Exception(response['message'] ?? 'Unable to start WhatsApp pairing');
    }

    final data = Map<String, dynamic>.from(response['data'] as Map);
    final instanceId = (data['instance_id'] ?? '').toString();
    final pairCodeRaw = (data['pairing_code'] ?? data['pair_code'] ?? '').toString();
    final pairCode = _normalizePairingCode(pairCodeRaw);

    if (instanceId.isEmpty) {
      throw Exception('Instance ID missing from response');
    }

    if (pairCode.isEmpty) {
      throw Exception('Pairing code missing from response');
    }

    return WhatsappPairingSession(
      instanceId: instanceId,
      pairCode: pairCode,
      phone: cleanedPhone,
      qrCode: null,
    );
  }

  Future<WhatsappConnectionStatus> getConnectionStatus(
    String instanceId,
  ) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.get('session/health', {
      'access_token': accessToken,
      'instance_id': instanceId,
    });

    if (response['status'] == 'success' && response['health'] != null) {
      final data = Map<String, dynamic>.from(response['health'] as Map);
      return WhatsappConnectionStatus(
        connected: data['healthy'] == true,
        name: data['name']?.toString() ?? data['user']?.toString(),
        wid: data['wid']?.toString() ?? data['user']?.toString(),
        avatar: null,
        reason: data['reason']?.toString(),
        wsState: data['wsState']?.toString(),
        code: data['code']?.toString(),
      );
    }

    return const WhatsappConnectionStatus(connected: false);
  }

  Future<ActiveWhatsappInstance?> getActiveInstance() async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.get('api/active_instance', {
      'access_token': accessToken,
    });

    if (response['status'] != 'success' || response['data'] == null) {
      return null;
    }

    final data = Map<String, dynamic>.from(response['data'] as Map);
    return ActiveWhatsappInstance(
      instanceId: data['instance_id']?.toString() ?? '',
      linkedNumber: data['wid']?.toString() ?? data['user']?.toString(),
      linkedName: data['name']?.toString(),
      wsState: data['wsState']?.toString(),
      connected: data['connected'] == true,
      healthy: data['healthy'] == true,
    );
  }

  Future<List<ActiveWhatsappInstance>> getInstances() async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.get('api/instances', {
      'access_token': accessToken,
    });

    if (response['status'] != 'success' || response['data'] == null) {
      return const [];
    }

    final data = Map<String, dynamic>.from(response['data'] as Map);
    final rawInstances = data['instances'];
    if (rawInstances is! List) {
      return const [];
    }

    return rawInstances
        .map((item) => Map<String, dynamic>.from(item as Map))
        .map(
          (item) => ActiveWhatsappInstance(
            instanceId: item['instance_id']?.toString() ?? '',
            linkedNumber: item['linkedNumber']?.toString(),
            linkedName: item['linkedName']?.toString(),
            wsState: item['wsState']?.toString(),
            connected: item['connected'] == true,
            healthy: item['healthy'] == true,
          ),
        )
        .where((item) => item.instanceId.isNotEmpty)
        .toList(growable: false);
  }

  Future<ActiveWhatsappInstance?> getSavedOrActiveInstance() async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final savedAccount = await _linkedWhatsappStorage.load();
    if (savedAccount != null && savedAccount.instanceId.isNotEmpty) {
      try {
        final details = await ApiClient.get('instance', {
          'access_token': accessToken,
          'instance_id': savedAccount.instanceId,
        });

        if (details['status'] == 'success' && details['data'] != null) {
          final data = Map<String, dynamic>.from(details['data'] as Map);
          final refreshedInstance = ActiveWhatsappInstance(
            instanceId: savedAccount.instanceId,
            linkedNumber: data['id']?.toString() ?? savedAccount.linkedNumber,
            linkedName: data['name']?.toString() ?? savedAccount.linkedName,
            wsState: 'open',
            connected: true,
            healthy: true,
          );
          await _linkedWhatsappStorage.save(
            LinkedWhatsappAccount(
              instanceId: refreshedInstance.instanceId,
              linkedNumber: refreshedInstance.linkedNumber,
              linkedName: refreshedInstance.linkedName,
            ),
          );
          return refreshedInstance;
        }
      } catch (error) {
        if (_isInvalidatedSessionError(error)) {
          await _linkedWhatsappStorage.clear();
          return null;
        }
      }
    }

    try {
      final activeInstance = await getActiveInstance();
      if (activeInstance != null && activeInstance.instanceId.isNotEmpty) {
        await _linkedWhatsappStorage.save(
          LinkedWhatsappAccount(
            instanceId: activeInstance.instanceId,
            linkedNumber: activeInstance.linkedNumber,
            linkedName: activeInstance.linkedName,
          ),
        );
        return activeInstance;
      }
    } catch (error) {
      if (_isInvalidatedSessionError(error)) {
        await _linkedWhatsappStorage.clear();
        return null;
      }
    }

    if (savedAccount != null && savedAccount.instanceId.isNotEmpty) {
      return ActiveWhatsappInstance(
        instanceId: savedAccount.instanceId,
        linkedNumber: savedAccount.linkedNumber,
        linkedName: savedAccount.linkedName,
        wsState: 'offline',
        connected: false,
        healthy: false,
      );
    }

    return null;
  }

  Future<void> saveLinkedInstance({
    required String instanceId,
    String? linkedNumber,
    String? linkedName,
  }) async {
    await _linkedWhatsappStorage.save(
      LinkedWhatsappAccount(
        instanceId: instanceId,
        linkedNumber: linkedNumber,
        linkedName: linkedName,
      ),
    );
  }

  Future<void> clearLinkedInstance() async {
    await _linkedWhatsappStorage.clear();
  }

  Future<void> cleanupAllInstances() async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.get('api/cleanup_instances', {
      'access_token': accessToken,
    });

    if (response['status'] != 'success') {
      throw Exception(
        response['message'] ?? 'Failed to clean WhatsApp instances',
      );
    }

    await _linkedWhatsappStorage.clear();
  }

  Future<void> logoutInstance(String instanceId) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.post('Android_api/logout_instance', {
      'access_token': accessToken,
      'instance_id': instanceId,
    });

    if (response['status'] != 'success') {
      throw Exception(
        response['message'] ?? 'Failed to logout WhatsApp session',
      );
    }

    await _linkedWhatsappStorage.clear();
  }

  Future<void> reconnectInstance(String instanceId) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.post('api/reconnect', {
      'access_token': accessToken,
      'instance_id': instanceId,
    });

    if (response['status'] != 'success') {
      throw Exception(
        response['message'] ?? 'Failed to reconnect WhatsApp session',
      );
    }
  }

  Future<WhatsappPairingSession> resetInstanceAndRequestPairing(
    String instanceId,
  ) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.post('Android_api/reset_instance', {
      'access_token': accessToken,
      'instance_id': instanceId,
    });

    if (response['status'] != 'success') {
      throw Exception(
        response['message'] ?? 'Failed to reset WhatsApp session',
      );
    }

    await _linkedWhatsappStorage.clear();

    final data = response['data'] is Map
        ? Map<String, dynamic>.from(response['data'] as Map)
        : <String, dynamic>{};
    final newInstanceId = (data['instance_id'] ??
            data['new_instance_id'] ??
            '')
        .toString();

    return WhatsappPairingSession(
      instanceId: newInstanceId,
      pairCode: '',
      phone: '',
      qrCode: null,
    );
  }

  Future<List<WhatsappGroupModel>> fetchGroups({String? instanceId}) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final effectiveInstanceId =
        instanceId ??
        (await getActiveInstance() ?? await getSavedOrActiveInstance())
            ?.instanceId ??
        '';

    if (effectiveInstanceId.isEmpty) {
      throw Exception('No active WhatsApp instance found');
    }

    Map<String, dynamic> response;
    try {
      response = await ApiClient.get('get_groups', {
        'access_token': accessToken,
        'instance_id': effectiveInstanceId,
      });
    } catch (e) {
      if (_isInvalidatedSessionError(e)) {
        await _linkedWhatsappStorage.clear();
      }
      rethrow;
    }

    if (response['status'] != 'success') {
      final message =
          response['message']?.toString() ?? 'Failed to fetch groups';
      if (message.toLowerCase().contains('connecting')) {
        throw WhatsappSessionConnectingException(
          message,
          wsState: response['code']?.toString(),
        );
      }
      if (message.toLowerCase().contains('invalidated')) {
        await _linkedWhatsappStorage.clear();
      }
      throw Exception(message);
    }

    final rawGroups = (response['data'] as List?) ?? const [];
    final groups = rawGroups
        .whereType<Map>()
        .map(
          (group) =>
              WhatsappGroupModel.fromJson(Map<String, dynamic>.from(group)),
        )
        .where((group) => group.id.isNotEmpty)
        .toList(growable: false);

    _groupUpdatesController.add(
      WhatsappGroupsUpdate(instanceId: effectiveInstanceId, groups: groups),
    );

    return groups;
  }
}
