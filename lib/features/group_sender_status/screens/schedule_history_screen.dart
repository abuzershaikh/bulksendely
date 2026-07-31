import 'dart:async';

import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/features/group_sender_status/models/group_sender_status_models.dart';
import 'package:autoreply/features/group_sender_status/services/group_sender_status_service.dart';
import 'package:flutter/material.dart';

class ScheduleHistoryScreen extends StatefulWidget {
  const ScheduleHistoryScreen({super.key});

  @override
  State<ScheduleHistoryScreen> createState() => _ScheduleHistoryScreenState();
}

class _ScheduleHistoryScreenState extends State<ScheduleHistoryScreen> {
  final GroupSenderStatusService _service = GroupSenderStatusService();
  late Future<List<GroupSenderStatusSummary>> _future;
  Timer? _autoRefreshTimer;

  @override
  void initState() {
    super.initState();
    _future = _service.fetchGroupSenderStatuses();
    _autoRefreshTimer = Timer.periodic(const Duration(seconds: 5), (_) {
      _refreshSilently();
    });
  }

  Future<void> _refresh() async {
    setState(() {
      _future = _service.fetchGroupSenderStatuses();
    });
    await _future;
  }

  Future<void> _refreshSilently() async {
    if (!mounted) return;
    setState(() {
      _future = _service.fetchGroupSenderStatuses();
    });
    try {
      await _future;
    } catch (_) {}
  }

  @override
  void dispose() {
    _autoRefreshTimer?.cancel();
    super.dispose();
  }

  int _countByStatus(List<GroupSenderStatusSummary> items, String status) {
    return items
        .where((item) => item.queueStatus.toLowerCase() == status)
        .length;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: const Text(
          'Schedule History',
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
        child: FutureBuilder<List<GroupSenderStatusSummary>>(
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
                          'Failed to load schedule history.\n${snapshot.error}',
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ),
                  ),
                ],
              );
            }

            final items = snapshot.data ?? const <GroupSenderStatusSummary>[];
            final total = items.length;
            final pending =
                _countByStatus(items, 'queued') + _countByStatus(items, 'processing');
            final completed = _countByStatus(items, 'completed');
            final failed = _countByStatus(items, 'failed');
            final processing = _countByStatus(items, 'processing');

            if (items.isEmpty) {
              return ListView(
                children: const [
                  SizedBox(height: 180),
                  Icon(Icons.schedule_rounded, size: 72, color: Colors.grey),
                  SizedBox(height: 12),
                  Center(
                    child: Text(
                      'No schedule history yet',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              );
            }

            return ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    _summaryCard('Total', '$total', Colors.blueGrey),
                    _summaryCard('Pending', '$pending', Colors.orange),
                    _summaryCard('Processing', '$processing', Colors.indigo),
                    _summaryCard('Completed', '$completed', Colors.green),
                    _summaryCard('Failed', '$failed', Colors.red),
                  ],
                ),
                const SizedBox(height: 14),
                ...items.map((item) {
                  final status = item.queueStatus.toLowerCase();
                  final statusColor = status == 'completed'
                      ? Colors.green
                      : status == 'queued'
                          ? Colors.orange
                          : status == 'processing'
                              ? Colors.indigo
                              : Colors.red;
                  return Container(
                    margin: const EdgeInsets.only(bottom: 10),
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: Colors.grey.shade300),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                item.batchName,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w700,
                                  fontSize: 14,
                                ),
                              ),
                            ),
                            _pill(item.queueStatus, statusColor),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            _pill('Sent ${item.sentCount}/${item.targetCount}', Colors.green),
                            _pill('Pending ${item.pendingCount}', Colors.orange),
                            _pill('Failed ${item.failedCount}', Colors.red),
                            if (item.nextRunInSec > 0)
                              _pill('Next retry ${item.nextRunInSec}s', Colors.indigo),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Created: ${item.createdAt}',
                          style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                        ),
                        if (item.lastError.trim().isNotEmpty) ...[
                          const SizedBox(height: 6),
                          Text(
                            'Last error: ${item.lastError}',
                            style: const TextStyle(fontSize: 12, color: Colors.red),
                          ),
                        ],
                      ],
                    ),
                  );
                }),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _summaryCard(String label, String value, Color color) {
    return Container(
      width: 110,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade300),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            value,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey.shade700,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }

  Widget _pill(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: color == Colors.blueGrey ? AppColors.primaryBlue : color,
        ),
      ),
    );
  }
}
