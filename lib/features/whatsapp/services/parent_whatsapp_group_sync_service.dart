import 'package:autoreply/core/network/api_client.dart';
import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:autoreply/features/whatsapp/models/parent_whatsapp_group_model.dart';

class ParentWhatsappGroupSyncService {
  final SubscriptionService _subscriptionService = SubscriptionService.instance;

  Future<List<ParentWhatsappGroupModel>> fetchGroups() async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.postToAdmin(
      'admin_api/list_whatsapp_parent_groups',
      {'api_key': ApiClient.adminApiKey, 'access_token': accessToken},
    );

    if (response['status'] != 'success') {
      throw Exception(
        response['message'] ?? 'Failed to fetch parent WhatsApp groups',
      );
    }

    return ((response['data'] as List?) ?? const [])
        .whereType<Map>()
        .map((item) {
          final raw = Map<String, dynamic>.from(item);
          return ParentWhatsappGroupModel.fromJson({
            'id': raw['id']?.toString() ?? '',
            'name': raw['name']?.toString() ?? '',
            'linkedGroups': raw['linked_groups'] ?? raw['linkedGroups'] ?? [],
          });
        })
        .where((group) => group.id.isNotEmpty && group.name.isNotEmpty)
        .toList(growable: false);
  }

  Future<void> saveGroup(ParentWhatsappGroupModel group) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response =
        await ApiClient.postToAdmin('admin_api/save_whatsapp_parent_group', {
          'api_key': ApiClient.adminApiKey,
          'access_token': accessToken,
          'user_email': _subscriptionService.currentUser?.email ?? '',
          'id': group.id,
          'name': group.name,
          'linked_groups': group.linkedGroups
              .map((item) => item.toJson())
              .toList(),
        });

    if (response['status'] != 'success') {
      throw Exception(
        response['message'] ?? 'Failed to save parent WhatsApp group',
      );
    }
  }

  Future<void> deleteGroup(String id) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.postToAdmin(
      'admin_api/delete_whatsapp_parent_group',
      {'api_key': ApiClient.adminApiKey, 'access_token': accessToken, 'id': id},
    );

    if (response['status'] != 'success') {
      throw Exception(
        response['message'] ?? 'Failed to delete parent WhatsApp group',
      );
    }
  }
}
