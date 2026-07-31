import 'package:autoreply/core/network/api_client.dart';
import 'package:autoreply/features/campaign_status/models/campaign_status_models.dart';

class CampaignStatusService {
  Future<void> saveCampaignStatus(Map<String, dynamic> payload) async {
    final response = await ApiClient.postToAdmin(
      'admin_api/save_campaign_status',
      {'api_key': ApiClient.adminApiKey, ...payload},
    );

    if (response['status'] != 'success') {
      throw Exception(response['message'] ?? 'Failed to save campaign status');
    }
  }

  Future<List<CampaignStatusSummary>> fetchCampaignStatuses() async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.postToAdmin(
      'admin_api/list_campaign_status',
      {'api_key': ApiClient.adminApiKey, 'access_token': accessToken},
    );

    if (response['status'] != 'success') {
      throw Exception(
        response['message'] ?? 'Failed to fetch campaign statuses',
      );
    }

    return ((response['data'] as List?) ?? const [])
        .whereType<Map>()
        .map(
          (item) =>
              CampaignStatusSummary.fromJson(Map<String, dynamic>.from(item)),
        )
        .toList();
  }

  Future<CampaignStatusDetail> fetchCampaignStatusDetail(String id) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.postToAdmin(
      'admin_api/get_campaign_status_detail',
      {'api_key': ApiClient.adminApiKey, 'access_token': accessToken, 'id': id},
    );

    if (response['status'] != 'success') {
      throw Exception(
        response['message'] ?? 'Failed to fetch campaign details',
      );
    }

    return CampaignStatusDetail.fromJson(
      Map<String, dynamic>.from((response['data'] as Map?) ?? const {}),
    );
  }

  Future<void> stopCampaign(String campaignId) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.post('api/campaigns/stop', {
      'access_token': accessToken,
      'campaign_id': campaignId,
    });

    if (response['status'] != 'success') {
      throw Exception(response['message'] ?? 'Stop failed');
    }
  }

  Future<void> deleteCampaign(String campaignId) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.post('api/campaigns/delete', {
      'access_token': accessToken,
      'campaign_id': campaignId,
    });

    if (response['status'] != 'success') {
      throw Exception(response['message'] ?? 'Delete failed');
    }
  }

  Future<void> startCampaign(String campaignId) async {
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.post('api/campaigns/start', {
      'access_token': accessToken,
      'campaign_id': campaignId,
    });

    if (response['status'] != 'success') {
      throw Exception(response['message'] ?? 'Start failed');
    }
  }
}
