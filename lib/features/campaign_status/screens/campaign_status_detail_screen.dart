import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/features/campaign_status/models/campaign_status_models.dart';
import 'package:autoreply/features/campaign_status/services/campaign_status_service.dart';
import 'package:flutter/material.dart';

class CampaignStatusDetailScreen extends StatefulWidget {
  final CampaignStatusSummary summary;

  const CampaignStatusDetailScreen({super.key, required this.summary});

  @override
  State<CampaignStatusDetailScreen> createState() => _CampaignStatusDetailScreenState();
}

class _CampaignStatusDetailScreenState extends State<CampaignStatusDetailScreen> {
  final CampaignStatusService _service = CampaignStatusService();
  late Future<CampaignStatusDetail> _future;

  bool _startingCampaign = false;
  bool _stoppingCampaign = false;
  bool _deletingCampaign = false;

  @override
  void initState() {
    super.initState();
    _future = _service.fetchCampaignStatusDetail(widget.summary.id);
  }

  Future<void> _refresh() async {
    setState(() {
      _future = _service.fetchCampaignStatusDetail(widget.summary.id);
    });
    await _future;
  }

  String _formatScheduledTime(BuildContext context, int timestamp) {
    final dt = DateTime.fromMillisecondsSinceEpoch(timestamp * 1000).toLocal();
    final date = '${dt.year}-${dt.month.toString().padLeft(2, '0')}-${dt.day.toString().padLeft(2, '0')}';
    final time = TimeOfDay.fromDateTime(dt).format(context);
    return '$date $time';
  }

  Widget _buildStatusBadge(String status) {
    Color color;
    IconData icon;
    String text = status.toUpperCase();

    switch (status.toLowerCase()) {
      case 'sent':
      case 'delivered':
      case 'read':
        color = Colors.green;
        icon = Icons.check_circle_rounded;
        break;
      case 'failed':
      case 'error':
        color = Colors.red;
        icon = Icons.error_rounded;
        break;
      case 'queued':
      case 'pending':
        color = Colors.orange;
        icon = Icons.schedule_rounded;
        break;
      default:
        color = Colors.grey;
        icon = Icons.help_outline_rounded;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: color),
          const SizedBox(width: 4),
          Text(
            text,
            style: TextStyle(
              color: color,
              fontSize: 12,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Campaign Details',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
            ),
            Text(
              widget.summary.campaignName,
              style: const TextStyle(fontSize: 13, color: Colors.grey),
            ),
          ],
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
      body: FutureBuilder<CampaignStatusDetail>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text(
                  'Failed to load details.\n${snapshot.error}',
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: Colors.red),
                ),
              ),
            );
          }

          final detail = snapshot.data;
          if (detail == null) {
            return const Center(child: Text('No details available.'));
          }

          return Column(
            children: [
              // Header Card
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                margin: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.05),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Target Group: ${detail.summary.targetName}',
                      style: TextStyle(color: Colors.grey.shade700, fontSize: 15),
                    ),
                    if (detail.summary.isScheduled) ...[
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: Colors.orange.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: Colors.orange.shade200),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.schedule, size: 16, color: Colors.orange),
                            const SizedBox(width: 8),
                            Text(
                              'Scheduled for: ${_formatScheduledTime(context, detail.summary.scheduledAt)}',
                              style: const TextStyle(
                                color: Colors.orange,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                    const SizedBox(height: 16),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        OutlinedButton.icon(
                          onPressed: _stoppingCampaign
                              ? null
                              : () async {
                                  setState(() => _stoppingCampaign = true);
                                  try {
                                    await _service.stopCampaign(detail.summary.id);
                                    if (context.mounted) {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        const SnackBar(content: Text('Campaign stopped'), backgroundColor: Colors.green),
                                      );
                                      _refresh();
                                    }
                                  } catch (e) {
                                    if (context.mounted) {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        SnackBar(content: Text('Stop failed: $e'), backgroundColor: Colors.red),
                                      );
                                    }
                                  } finally {
                                    if (mounted) setState(() => _stoppingCampaign = false);
                                  }
                                },
                          icon: _stoppingCampaign
                              ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))
                              : const Icon(Icons.pause_rounded),
                          label: const Text('Stop'),
                        ),
                        OutlinedButton.icon(
                          onPressed: _startingCampaign
                              ? null
                              : () async {
                                  setState(() => _startingCampaign = true);
                                  try {
                                    await _service.startCampaign(detail.summary.id);
                                    if (context.mounted) {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        const SnackBar(content: Text('Campaign resumed'), backgroundColor: Colors.green),
                                      );
                                      _refresh();
                                    }
                                  } catch (e) {
                                    if (context.mounted) {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        SnackBar(content: Text('Resume failed: $e'), backgroundColor: Colors.red),
                                      );
                                    }
                                  } finally {
                                    if (mounted) setState(() => _startingCampaign = false);
                                  }
                                },
                          icon: _startingCampaign
                              ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))
                              : const Icon(Icons.play_arrow_rounded),
                          label: const Text('Resume'),
                        ),
                        OutlinedButton.icon(
                          onPressed: _deletingCampaign
                              ? null
                              : () async {
                                  setState(() => _deletingCampaign = true);
                                  try {
                                    await _service.deleteCampaign(detail.summary.id);
                                    if (context.mounted) {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        const SnackBar(content: Text('Campaign deleted'), backgroundColor: Colors.green),
                                      );
                                      Navigator.pop(context, true); // Return true to trigger refresh in previous screen
                                    }
                                  } catch (e) {
                                    if (context.mounted) {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        SnackBar(content: Text('Delete failed: $e'), backgroundColor: Colors.red),
                                      );
                                    }
                                  } finally {
                                    if (mounted) setState(() => _deletingCampaign = false);
                                  }
                                },
                          style: OutlinedButton.styleFrom(foregroundColor: Colors.red),
                          icon: _deletingCampaign
                              ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))
                              : const Icon(Icons.delete_outline_rounded),
                          label: const Text('Delete'),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              // Excel-like Table
              Expanded(
                child: Container(
                  margin: const EdgeInsets.symmetric(horizontal: 16).copyWith(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    border: Border.all(color: Colors.grey.shade300),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: SingleChildScrollView(
                        scrollDirection: Axis.vertical,
                        child: Theme(
                          data: Theme.of(context).copyWith(
                            dividerColor: Colors.grey.shade300,
                          ),
                          child: DataTable(
                            headingRowColor: WidgetStateProperty.all(Colors.grey.shade100),
                            dataRowMaxHeight: double.infinity,
                            dataRowMinHeight: 48,
                            headingTextStyle: const TextStyle(
                              fontWeight: FontWeight.bold,
                              color: Colors.black87,
                            ),
                            border: TableBorder.symmetric(
                              inside: BorderSide(color: Colors.grey.shade300, width: 1),
                            ),
                            columns: const [
                              DataColumn(label: Text('#')),
                              DataColumn(label: Text('Name')),
                              DataColumn(label: Text('Number')),
                              DataColumn(label: Text('Status')),
                              DataColumn(label: Text('Remarks / Error')),
                            ],
                            rows: detail.items.map((item) {
                              return DataRow(
                                color: WidgetStateProperty.resolveWith<Color?>(
                                  (states) => item.status.toLowerCase() == 'failed'
                                      ? Colors.red.withValues(alpha: 0.05)
                                      : null,
                                ),
                                cells: [
                                  DataCell(Text(item.index.toString())),
                                  DataCell(
                                    Padding(
                                      padding: const EdgeInsets.symmetric(vertical: 8.0),
                                      child: Text(
                                        item.name.isEmpty ? '-' : item.name,
                                        style: const TextStyle(fontWeight: FontWeight.w500),
                                      ),
                                    )
                                  ),
                                  DataCell(Text(item.number)),
                                  DataCell(_buildStatusBadge(item.status)),
                                  DataCell(
                                    Padding(
                                      padding: const EdgeInsets.symmetric(vertical: 8.0),
                                      child: SizedBox(
                                        width: 250, // Keep error column reasonably wide but constrained
                                        child: Text(
                                          item.error.isEmpty ? '-' : item.error,
                                          style: TextStyle(
                                            color: item.error.isEmpty ? Colors.grey : Colors.red.shade700,
                                            fontSize: 13,
                                          ),
                                          softWrap: true,
                                        ),
                                      ),
                                    ),
                                  ),
                                ],
                              );
                            }).toList(),
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
