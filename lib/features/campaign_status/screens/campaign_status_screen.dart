import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/features/campaign_status/models/campaign_status_models.dart';
import 'package:autoreply/features/campaign_status/services/campaign_status_service.dart';
import 'package:flutter/material.dart';
import 'campaign_status_detail_screen.dart';

class CampaignStatusScreen extends StatefulWidget {
  const CampaignStatusScreen({super.key});

  @override
  State<CampaignStatusScreen> createState() => _CampaignStatusScreenState();
}

class _CampaignStatusScreenState extends State<CampaignStatusScreen> {
  final CampaignStatusService _service = CampaignStatusService();
  late Future<List<CampaignStatusSummary>> _future;
  bool _startingCampaign = false;
  bool _stoppingCampaign = false;
  bool _deletingCampaign = false;

  @override
  void initState() {
    super.initState();
    _future = _service.fetchCampaignStatuses();
  }

  Future<void> _refresh() async {
    setState(() {
      _future = _service.fetchCampaignStatuses();
    });
    await _future;
  }

  Future<void> _openDetail(CampaignStatusSummary summary) async {
    final result = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => CampaignStatusDetailScreen(summary: summary),
      ),
    );
    
    // If deleted, we should refresh the list
    if (result == true) {
      _refresh();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: const Text(
          'Campaign Status',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        actions: [
          IconButton(
            onPressed: _refresh,
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: FutureBuilder<List<CampaignStatusSummary>>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const Center(child: CircularProgressIndicator());
            }

            if (snapshot.hasError) {
              return ListView(
                children: [
                  SizedBox(
                    height: MediaQuery.of(context).size.height * 0.6,
                    child: Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Text(
                          'Failed to load campaign status.\n${snapshot.error}',
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ),
                  ),
                ],
              );
            }

            final items = snapshot.data ?? const [];
            if (items.isEmpty) {
              return ListView(
                children: const [
                  SizedBox(height: 180),
                  Icon(Icons.campaign_outlined, size: 72, color: Colors.grey),
                  SizedBox(height: 12),
                  Center(
                    child: Text(
                      'No campaign history yet',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              itemBuilder: (context, index) {
                final item = items[index];
                return Padding(
                  padding: const EdgeInsets.only(bottom: 14),
                  child: _CampaignStatusCard(
                    summary: item,
                    onTap: () => _openDetail(item),
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }
}

class _CampaignStatusCard extends StatelessWidget {
  final CampaignStatusSummary summary;
  final VoidCallback onTap;

  const _CampaignStatusCard({required this.summary, required this.onTap});

  String _formatScheduledTime(BuildContext context, int timestamp) {
    final dt = DateTime.fromMillisecondsSinceEpoch(timestamp * 1000).toLocal();
    final date = '${dt.year}-${dt.month.toString().padLeft(2, '0')}-${dt.day.toString().padLeft(2, '0')}';
    final time = TimeOfDay.fromDateTime(dt).format(context);
    return '$date $time';
  }

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: Colors.grey.shade200),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppColors.primaryBlue.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(
                      Icons.campaign_rounded,
                      color: AppColors.primaryBlue,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          summary.campaignName,
                          style: const TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          summary.targetName,
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const Icon(Icons.chevron_right_rounded),
                ],
              ),
              const SizedBox(height: 14),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  if (summary.isScheduled)
                    _StatusPill(
                      label: 'Scheduled: ${_formatScheduledTime(context, summary.scheduledAt)}',
                      color: Colors.orange,
                    ),
                  if (!summary.isScheduled)
                  _StatusPill(
                    label: '${summary.sentCount}/${summary.targetCount} sent',
                    color: Colors.green,
                  ),
                  _StatusPill(
                    label: '${summary.failedCount} failed',
                    color: summary.failedCount > 0
                        ? Colors.red
                        : Colors.blueGrey,
                  ),
                  _StatusPill(
                    label: summary.messageLabel,
                    color: AppColors.primaryBlue,
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'Delay: ${summary.delaySeconds}s',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade600,
                      ),
                    ),
                  ),
                  Expanded(
                    child: Text(
                      summary.createdAt,
                      textAlign: TextAlign.right,
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade600,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}



class _StatusPill extends StatelessWidget {
  final String label;
  final Color color;

  const _StatusPill({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }
}
