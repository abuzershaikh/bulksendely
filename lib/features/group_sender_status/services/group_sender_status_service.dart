import 'package:autoreply/core/network/api_client.dart';
import 'package:autoreply/features/group_sender_status/models/group_sender_status_models.dart';

class GroupSenderStatusService {
  Future<void> saveGroupSenderStatus(Map<String, dynamic> payload) async {
    final response = await ApiClient.postToAdmin(
      'admin_api/save_group_sender_status',
      {
        'api_key': ApiClient.adminApiKey,
        ...payload,
      },
    );

    if (response['status'] != 'success') {
      throw Exception(response['message'] ?? 'Failed to save group sender status');
    }
  }

  Future<List<GroupSenderStatusSummary>> fetchGroupSenderStatuses() async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.postToAdmin(
      'admin_api/list_group_sender_status',
      {
        'api_key': ApiClient.adminApiKey,
        'access_token': accessToken,
      },
    );

    if (response['status'] != 'success') {
      throw Exception(response['message'] ?? 'Failed to fetch group sender statuses');
    }

    return ((response['data'] as List?) ?? const [])
        .whereType<Map>()
        .map(
          (item) => GroupSenderStatusSummary.fromJson(
            Map<String, dynamic>.from(item),
          ),
        )
        .toList();
  }

  Future<GroupSenderStatusDetail> fetchGroupSenderStatusDetail(String id) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.postToAdmin(
      'admin_api/get_group_sender_status_detail',
      {
        'api_key': ApiClient.adminApiKey,
        'access_token': accessToken,
        'id': id,
      },
    );

    if (response['status'] != 'success') {
      throw Exception(response['message'] ?? 'Failed to fetch group sender details');
    }

    return GroupSenderStatusDetail.fromJson(
      Map<String, dynamic>.from((response['data'] as Map?) ?? const {}),
    );
  }

  Future<List<Map<String, dynamic>>> fetchRecentForwardMessages({
    int limit = 20,
  }) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.postToAdmin(
      'admin_api/list_recent_forward_messages',
      {
        'api_key': ApiClient.adminApiKey,
        'access_token': accessToken,
        'limit': limit,
      },
    );

    if (response['status'] != 'success') {
      throw Exception(response['message'] ?? 'Failed to load recent messages');
    }

    return ((response['data'] as List?) ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  Future<void> retryFailed({
    required String campaignId,
    String? groupId,
  }) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.post(
      'api/campaigns/retry_failed',
      {
        'access_token': accessToken,
        'campaign_id': campaignId,
        if (groupId != null && groupId.trim().isNotEmpty) 'group_id': groupId,
      },
    );

    if (response['status'] != 'success') {
      throw Exception(response['message'] ?? 'Retry failed');
    }
  }

  Future<void> stopCampaign(String campaignId) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.post(
      'api/campaigns/stop',
      {
        'access_token': accessToken,
        'campaign_id': campaignId,
      },
    );
    if (response['status'] != 'success') {
      throw Exception(response['message'] ?? 'Stop failed');
    }
  }

  Future<void> startCampaign(String campaignId) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.post(
      'api/campaigns/start',
      {
        'access_token': accessToken,
        'campaign_id': campaignId,
      },
    );
    if (response['status'] != 'success') {
      throw Exception(response['message'] ?? 'Start failed');
    }
  }

  Future<void> deleteCampaign(String campaignId) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.post(
      'api/campaigns/delete',
      {
        'access_token': accessToken,
        'campaign_id': campaignId,
      },
    );
    if (response['status'] != 'success') {
      throw Exception(response['message'] ?? 'Delete failed');
    }
  }
}
