import 'dart:async';

import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/features/group_sender_status/models/group_sender_status_models.dart';
import 'package:autoreply/features/group_sender_status/services/group_sender_status_service.dart';
import 'package:flutter/material.dart';

class GroupSenderStatusScreen extends StatefulWidget {
  const GroupSenderStatusScreen({super.key});

  @override
  State<GroupSenderStatusScreen> createState() => _GroupSenderStatusScreenState();
}

class _GroupSenderStatusScreenState extends State<GroupSenderStatusScreen> {
  final GroupSenderStatusService _service = GroupSenderStatusService();
  List<GroupSenderStatusSummary> _items = const [];
  bool _initialLoading = true;
  String? _loadError;
  Timer? _autoRefreshTimer;
  bool _refreshInProgress = false;

  @override
  void initState() {
    super.initState();
    _loadStatuses(showLoader: true);
    _autoRefreshTimer = Timer.periodic(const Duration(seconds: 5), (_) {
      _refreshSilently();
    });
  }

  Future<void> _loadStatuses({required bool showLoader}) async {
    if (!mounted) return;
    if (showLoader) {
      setState(() {
        _initialLoading = true;
        _loadError = null;
      });
    }
    try {
      final data = await _service.fetchGroupSenderStatuses();
      if (!mounted) return;
      setState(() {
        _items = data;
        _loadError = null;
        _initialLoading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _loadError = 'Failed to load group sender status.\n$error';
        _initialLoading = false;
      });
    }
  }

  Future<void> _refresh() async {
    await _loadStatuses(showLoader: _items.isEmpty);
  }

  Future<void> _refreshSilently() async {
    if (!mounted || _refreshInProgress) return;
    _refreshInProgress = true;
    try {
      final data = await _service.fetchGroupSenderStatuses();
      if (!mounted) return;
      setState(() {
        _items = data;
        _loadError = null;
      });
    } catch (_) {
      // Keep silent auto refresh non-blocking.
    } finally {
      _refreshInProgress = false;
    }
  }

  @override
  void dispose() {
    _autoRefreshTimer?.cancel();
    super.dispose();
  }

  Future<void> _openDetail(GroupSenderStatusSummary summary) async {
    final deleted = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        builder: (_) => GroupStatusDetailScreen(
          summary: summary,
          service: _service,
        ),
      ),
    );
    if (deleted == true && mounted) {
      await _refresh();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: const Text(
          'Group Sender Status',
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
        child: _buildBody(),
      ),
    );
  }

  Widget _buildBody() {
    if (_initialLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_loadError != null && _items.isEmpty) {
      return ListView(
        children: [
          SizedBox(
            height: MediaQuery.of(context).size.height * 0.6,
            child: Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text(
                  _loadError!,
                  textAlign: TextAlign.center,
                ),
              ),
            ),
          ),
        ],
      );
    }

    if (_items.isEmpty) {
      return ListView(
        children: const [
          SizedBox(height: 180),
          Icon(Icons.hub_outlined, size: 72, color: Colors.grey),
          SizedBox(height: 12),
          Center(
            child: Text(
              'No group sender history yet',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
            ),
          ),
        ],
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _items.length,
      itemBuilder: (context, index) {
        final item = _items[index];
        return Padding(
          padding: const EdgeInsets.only(bottom: 14),
          child: _GroupSenderStatusCard(
            summary: item,
            onTap: () => _openDetail(item),
          ),
        );
      },
    );
  }
}

class _GroupSenderStatusCard extends StatelessWidget {
  final GroupSenderStatusSummary summary;
  final VoidCallback onTap;

  const _GroupSenderStatusCard({
    required this.summary,
    required this.onTap,
  });

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
                      Icons.hub_rounded,
                      color: AppColors.primaryBlue,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      summary.batchName,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                      ),
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
                  _GroupStatusPill(
                    label: '${summary.sentCount}/${summary.targetCount} sent',
                    color: Colors.green,
                  ),
                  _GroupStatusPill(
                    label: '${summary.pendingCount} pending',
                    color: summary.pendingCount > 0
                        ? Colors.orange
                        : Colors.blueGrey,
                  ),
                  if (summary.cooldownActive)
                    const _GroupStatusPill(
                      label: 'Cooldown active',
                      color: Colors.deepOrange,
                    ),
                  if (summary.nextRunInSec > 0)
                    _GroupStatusPill(
                      label: 'Retry in ${summary.nextRunInSec}s',
                      color: Colors.indigo,
                    ),
                  _GroupStatusPill(
                    label: '${summary.failedCount} failed',
                    color: summary.failedCount > 0 ? Colors.red : Colors.blueGrey,
                  ),
                  _GroupStatusPill(
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
                      style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                    ),
                  ),
                  Expanded(
                    child: Text(
                      summary.createdAt,
                      textAlign: TextAlign.right,
                      style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
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

class GroupStatusDetailScreen extends StatefulWidget {
  final GroupSenderStatusSummary summary;
  final GroupSenderStatusService service;

  const GroupStatusDetailScreen({
    super.key,
    required this.summary,
    required this.service,
  });

  @override
  State<GroupStatusDetailScreen> createState() => _GroupStatusDetailScreenState();
}

class _GroupStatusDetailScreenState extends State<GroupStatusDetailScreen> {
  GroupSenderStatusDetail? _detail;
  bool _initialLoading = true;
  String? _loadError;
  bool _retryingAll = false;
  bool _startingCampaign = false;
  bool _stoppingCampaign = false;
  bool _deletingCampaign = false;
  final Set<String> _retryingGroupIds = <String>{};
  Timer? _autoRefreshTimer;
  bool _reloadInProgress = false;

  @override
  void initState() {
    super.initState();
    _loadDetail(showLoader: true);
    _autoRefreshTimer = Timer.periodic(const Duration(seconds: 5), (_) {
      _reloadSilently();
    });
  }

  Future<void> _loadDetail({required bool showLoader}) async {
    if (!mounted) return;
    if (showLoader) {
      setState(() {
        _initialLoading = true;
        _loadError = null;
      });
    }
    try {
      final data = await widget.service.fetchGroupSenderStatusDetail(widget.summary.id);
      if (!mounted) return;
      setState(() {
        _detail = data;
        _loadError = null;
        _initialLoading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _loadError = 'Failed to load detail: $error';
        _initialLoading = false;
      });
    }
  }

  Future<void> _reload() async {
    await _loadDetail(showLoader: _detail == null);
  }

  Future<void> _reloadSilently() async {
    if (!mounted || _reloadInProgress) return;
    _reloadInProgress = true;
    try {
      final data = await widget.service.fetchGroupSenderStatusDetail(widget.summary.id);
      if (!mounted) return;
      setState(() {
        _detail = data;
        _loadError = null;
      });
    } catch (_) {
      // Keep silent auto refresh non-blocking.
    } finally {
      _reloadInProgress = false;
    }
  }

  @override
  void dispose() {
    _autoRefreshTimer?.cancel();
    super.dispose();
  }

  Future<void> _retryFailed({String? groupId}) async {
    if (groupId == null) {
      setState(() => _retryingAll = true);
    } else {
      setState(() => _retryingGroupIds.add(groupId));
    }
    try {
      await widget.service.retryFailed(
        campaignId: widget.summary.id,
        groupId: groupId,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            groupId == null
                ? 'Retry queued for failed groups'
                : 'Retry queued for selected group',
          ),
          backgroundColor: Colors.green,
        ),
      );
      await _reload();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Retry failed: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) {
        setState(() {
          _retryingAll = false;
          if (groupId != null) _retryingGroupIds.remove(groupId);
        });
      }
    }
  }

  Future<void> _toggleCampaignState({required bool start}) async {
    setState(() {
      if (start) {
        _startingCampaign = true;
      } else {
        _stoppingCampaign = true;
      }
    });
    try {
      if (start) {
        await widget.service.startCampaign(widget.summary.id);
      } else {
        await widget.service.stopCampaign(widget.summary.id);
      }
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(start ? 'Campaign started' : 'Campaign stopped'),
          backgroundColor: Colors.green,
        ),
      );
      await _reload();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Campaign action failed: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) {
        setState(() {
          _startingCampaign = false;
          _stoppingCampaign = false;
        });
      }
    }
  }

  Future<void> _deleteCampaign() async {
    setState(() => _deletingCampaign = true);
    try {
      await widget.service.deleteCampaign(widget.summary.id);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Campaign deleted'), backgroundColor: Colors.green),
      );
      Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Delete failed: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) {
        setState(() => _deletingCampaign = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: const Text(
          'Group Status',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        actions: [
          IconButton(
            onPressed: _reload,
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: _buildDetailBody(),
    );
  }

  Widget _buildDetailBody() {
    if (_initialLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_loadError != null && _detail == null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Text(
            _loadError!,
            textAlign: TextAlign.center,
          ),
        ),
      );
    }
    if (_detail == null) {
      return const SizedBox.shrink();
    }
    return _GroupSenderStatusSheet(
      detail: _detail!,
      retryingAll: _retryingAll,
      startingCampaign: _startingCampaign,
      stoppingCampaign: _stoppingCampaign,
      deletingCampaign: _deletingCampaign,
      retryingGroupIds: _retryingGroupIds,
      onStartCampaign: () => _toggleCampaignState(start: true),
      onStopCampaign: () => _toggleCampaignState(start: false),
      onDeleteCampaign: _deleteCampaign,
      onRetryAll: () => _retryFailed(),
      onRetryGroup: (groupId) => _retryFailed(groupId: groupId),
    );
  }
}

class _GroupSenderStatusSheet extends StatelessWidget {
  final GroupSenderStatusDetail detail;
  final bool retryingAll;
  final bool startingCampaign;
  final bool stoppingCampaign;
  final bool deletingCampaign;
  final Set<String> retryingGroupIds;
  final VoidCallback onStartCampaign;
  final VoidCallback onStopCampaign;
  final VoidCallback onDeleteCampaign;
  final VoidCallback onRetryAll;
  final ValueChanged<String> onRetryGroup;

  const _GroupSenderStatusSheet({
    required this.detail,
    required this.retryingAll,
    required this.startingCampaign,
    required this.stoppingCampaign,
    required this.deletingCampaign,
    required this.retryingGroupIds,
    required this.onStartCampaign,
    required this.onStopCampaign,
    required this.onDeleteCampaign,
    required this.onRetryAll,
    required this.onRetryGroup,
  });

  @override
  Widget build(BuildContext context) {
    final queueStatus = detail.summary.queueStatus.toLowerCase().trim();
    final canStart = queueStatus == 'paused';
    final canStop = queueStatus == 'queued' || queueStatus == 'processing';
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
      children: [
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Colors.grey.shade300),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                detail.summary.batchName,
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 6),
              Text(
                'Instance: ${detail.summary.instanceId}',
                style: TextStyle(color: Colors.grey.shade700),
              ),
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _GroupStatusPill(
                    label: '${detail.sentCount} sent',
                    color: Colors.green,
                  ),
                  _GroupStatusPill(
                    label: '${detail.pendingCount} pending',
                    color: detail.pendingCount > 0
                        ? Colors.orange
                        : Colors.blueGrey,
                  ),
                  _GroupStatusPill(
                    label: '${detail.failedCount} failed',
                    color: detail.failedCount > 0 ? Colors.red : Colors.blueGrey,
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  OutlinedButton.icon(
                    onPressed: canStart && !startingCampaign && !stoppingCampaign
                        ? onStartCampaign
                        : null,
                    icon: startingCampaign
                        ? const SizedBox(
                            width: 14,
                            height: 14,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.play_arrow_rounded),
                    label: const Text('Start'),
                  ),
                  OutlinedButton.icon(
                    onPressed: canStop && !startingCampaign && !stoppingCampaign
                        ? onStopCampaign
                        : null,
                    icon: stoppingCampaign
                        ? const SizedBox(
                            width: 14,
                            height: 14,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.pause_rounded),
                    label: const Text('Stop'),
                  ),
                  OutlinedButton.icon(
                    onPressed: deletingCampaign ? null : onDeleteCampaign,
                    style: OutlinedButton.styleFrom(foregroundColor: Colors.red),
                    icon: deletingCampaign
                        ? const SizedBox(
                            width: 14,
                            height: 14,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.delete_outline_rounded),
                    label: const Text('Delete'),
                  ),
                ],
              ),
              if (detail.failedCount > 0) ...[
                const SizedBox(height: 10),
                OutlinedButton.icon(
                  onPressed: retryingAll ? null : onRetryAll,
                  icon: retryingAll
                      ? const SizedBox(
                          width: 14,
                          height: 14,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.refresh_rounded),
                  label: const Text('Retry Failed Groups'),
                ),
              ],
              if (detail.summary.cooldownActive ||
                  detail.summary.nextRunInSec > 0 ||
                  detail.summary.lastError.trim().isNotEmpty) ...[
                const SizedBox(height: 10),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8F9FB),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.grey.shade300),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Queue: ${detail.summary.queueStatus.isEmpty ? '-' : detail.summary.queueStatus}',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
                      ),
                      if (detail.summary.nextRunInSec > 0)
                        Text(
                          'Next retry in ${detail.summary.nextRunInSec}s',
                          style: const TextStyle(fontSize: 12),
                        ),
                      if (detail.summary.cooldownActive)
                        const Text(
                          'Cooldown active due to timeout spike',
                          style: TextStyle(fontSize: 12, color: Colors.deepOrange),
                        ),
                      if (detail.summary.lastError.trim().isNotEmpty)
                        Text(
                          'Last error: ${detail.summary.lastError}',
                          style: const TextStyle(fontSize: 12, color: Colors.red),
                        ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
        const SizedBox(height: 14),
        ...detail.items.map((item) {
          final status = item.status.toLowerCase();
          final statusColor = status == 'sent'
              ? Colors.green
              : status == 'queued'
              ? Colors.orange
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
                        item.groupName.isEmpty ? '-' : item.groupName,
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                    _GroupStatusPill(label: status, color: statusColor),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  item.groupId,
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                ),
                if (item.error.isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Text(
                    item.error,
                    style: const TextStyle(fontSize: 12, color: Colors.red),
                  ),
                ],
                if (status == 'failed') ...[
                  const SizedBox(height: 8),
                  Align(
                    alignment: Alignment.centerRight,
                    child: OutlinedButton.icon(
                      onPressed: retryingGroupIds.contains(item.groupId)
                          ? null
                          : () => onRetryGroup(item.groupId),
                      icon: retryingGroupIds.contains(item.groupId)
                          ? const SizedBox(
                              width: 12,
                              height: 12,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.replay_rounded, size: 16),
                      label: const Text('Retry'),
                    ),
                  ),
                ],
              ],
            ),
          );
        }),
      ],
    );
  }
}

class _GroupStatusPill extends StatelessWidget {
  final String label;
  final Color color;

  const _GroupStatusPill({
    required this.label,
    required this.color,
  });

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
