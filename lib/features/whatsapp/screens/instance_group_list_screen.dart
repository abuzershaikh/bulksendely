import 'dart:async';

import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/features/group_sender_status/models/group_sender_status_models.dart';
import 'package:autoreply/features/group_sender_status/screens/group_sender_status_screen.dart';
import 'package:autoreply/features/group_sender_status/screens/schedule_history_screen.dart';
import 'package:autoreply/features/group_sender_status/services/group_sender_status_service.dart';
import 'package:autoreply/features/media/services/cloudflare_upload_service.dart';
import 'package:autoreply/features/whatsapp/models/whatsapp_group_model.dart';
import 'package:autoreply/features/whatsapp/screens/parent_group_list_screen.dart';
import 'package:autoreply/features/whatsapp/services/linked_group_broadcast_service.dart';
import 'package:autoreply/features/whatsapp/services/whatsapp_api_service.dart';
import 'package:flutter/material.dart';

class InstanceGroupListScreen extends StatefulWidget {
  const InstanceGroupListScreen({super.key});

  @override
  State<InstanceGroupListScreen> createState() =>
      _InstanceGroupListScreenState();
}

class _InstanceGroupListScreenState extends State<InstanceGroupListScreen> {
  final WhatsappApiService _api = WhatsappApiService();
  final LinkedGroupBroadcastService _broadcastService =
      LinkedGroupBroadcastService();
  final CloudflareUploadService _uploadService = CloudflareUploadService();
  final GroupSenderStatusService _groupSenderStatusService =
      GroupSenderStatusService();
  final TextEditingController _messageCtrl = TextEditingController();
  final TextEditingController _mediaCaptionCtrl = TextEditingController();
  final TextEditingController _gapSecondsCtrl = TextEditingController(
    text: '0',
  );

  bool _isLoading = true;
  bool _isSending = false;
  bool _isUploading = false;
  DateTime? _scheduledAt;
  List<_InstanceGroupSection> _sections = const [];
  final Set<String> _selectedKeys = <String>{};
  final Set<String> _expandedSectionIds = <String>{};
  _ComposerMode _composerMode = _ComposerMode.text;
  _ComposerMediaType _mediaType = _ComposerMediaType.image;
  String _mediaUrl = '';
  String _mediaFilename = '';
  String _mediaMimeType = '';
  _ForwardMessage? _pendingForwardMessage;
  DateTime? _forwardLoadedAt;
  DateTime? _forwardSendBlockedUntil;
  bool _isForwardSelectionSettling = false;
  Timer? _liveStatusTimer;
  StreamSubscription<WhatsappGroupsUpdate>? _groupsUpdateSubscription;
  _LiveGroupSendProgress? _liveProgress;
  bool _isRefreshingLiveProgress = false;
  String _liveProgressError = '';
  bool _didAutoExpandSections = false;

  @override
  void initState() {
    super.initState();
    _groupsUpdateSubscription = _api.groupUpdates.listen(_applyGroupsUpdate);
    _loadSections();
  }

  @override
  void dispose() {
    _liveStatusTimer?.cancel();
    _groupsUpdateSubscription?.cancel();
    _messageCtrl.dispose();
    _mediaCaptionCtrl.dispose();
    _gapSecondsCtrl.dispose();
    super.dispose();
  }

  void _applyGroupsUpdate(WhatsappGroupsUpdate update) {
    if (!mounted) return;
    final sectionIndex = _sections.indexWhere(
      (section) => section.instance.instanceId == update.instanceId,
    );
    if (sectionIndex < 0) return;

    final updatedSections = List<_InstanceGroupSection>.from(_sections);
    final current = updatedSections[sectionIndex];
    updatedSections[sectionIndex] = _InstanceGroupSection(
      instance: current.instance,
      groups: update.groups,
      loadHint: null,
    );
    final validKeys = updatedSections
        .expand(_selectableTargetsForSection)
        .map((target) => target.key)
        .toSet();

    setState(() {
      _sections = updatedSections;
      _selectedKeys.removeWhere((key) => !validKeys.contains(key));
    });
  }

  Future<void> _loadSections() async {
    setState(() => _isLoading = true);
    try {
      final instances = await _api.getInstances();
      final sections = await Future.wait(
        instances.map((instance) async {
          try {
            final groups = await _api.fetchGroups(
              instanceId: instance.instanceId,
            );
            return _InstanceGroupSection(
              instance: instance,
              groups: groups,
              loadHint: null,
            );
          } on WhatsappSessionConnectingException catch (e) {
            return _InstanceGroupSection(
              instance: instance,
              groups: const [],
              loadHint: e.message,
            );
          } catch (e) {
            return _InstanceGroupSection(
              instance: instance,
              groups: const [],
              loadHint: 'Failed to load groups. ${e.toString()}',
            );
          }
        }),
      );

      if (!mounted) return;

      final validKeys = sections
          .expand(_selectableTargetsForSection)
          .map((target) => target.key)
          .toSet();
      final validSectionIds = sections
          .map((section) => section.instance.instanceId)
          .where((id) => id.isNotEmpty)
          .toSet();
      var nextExpandedIds = _expandedSectionIds
          .where(validSectionIds.contains)
          .toSet();

      // UX: keep sections collapsed by default; user can expand per number.
      if (!_didAutoExpandSections) {
        _didAutoExpandSections = true;
      }

      setState(() {
        _sections = sections;
        _selectedKeys.removeWhere((key) => !validKeys.contains(key));
        _expandedSectionIds
          ..clear()
          ..addAll(nextExpandedIds);
        _isLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _sections = const [];
        _isLoading = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to load linked groups: $e')),
      );
    }
  }

  String _displayLinkedNumber(ActiveWhatsappInstance instance) {
    return WhatsappApiService.formatLinkedNumber(instance.linkedNumber) ??
        instance.instanceId;
  }

  String _groupKey(String instanceId, String groupId) {
    return '$instanceId::$groupId';
  }

  List<LinkedGroupBroadcastTarget> _selectableTargetsForSection(
    _InstanceGroupSection section,
  ) {
    final numberLabel = _displayLinkedNumber(section.instance);
    return section.groups
        .where((group) => group.isSelectable && group.id.isNotEmpty)
        .map(
          (group) => LinkedGroupBroadcastTarget(
            instanceId: section.instance.instanceId,
            linkedNumber: numberLabel,
            linkedName: section.instance.linkedName ?? '',
            groupId: group.id,
            groupName: group.name,
          ),
        )
        .toList(growable: false);
  }

  List<LinkedGroupBroadcastTarget> _allSelectableTargets() {
    return _sections
        .expand(_selectableTargetsForSection)
        .toList(growable: false);
  }

  List<LinkedGroupBroadcastTarget> _selectedTargets() {
    return _allSelectableTargets()
        .where((target) => _selectedKeys.contains(target.key))
        .toList(growable: false);
  }

  int get _selectedGroupCount => _selectedTargets().length;

  int get _selectedNumberCount =>
      _selectedTargets().map((target) => target.instanceId).toSet().length;

  bool _isSectionExpanded(_InstanceGroupSection section) {
    return _expandedSectionIds.contains(section.instance.instanceId);
  }

  bool get _areAllSectionsExpanded {
    if (_sections.isEmpty) return false;
    return _sections.every(
      (section) => _expandedSectionIds.contains(section.instance.instanceId),
    );
  }

  bool _isGroupSelected(
    _InstanceGroupSection section,
    WhatsappGroupModel group,
  ) {
    return _selectedKeys.contains(
      _groupKey(section.instance.instanceId, group.id),
    );
  }

  bool _isSectionFullySelected(_InstanceGroupSection section) {
    final selectable = _selectableTargetsForSection(section);
    return selectable.isNotEmpty &&
        selectable.every((target) => _selectedKeys.contains(target.key));
  }

  bool get _isAllSelected {
    final selectable = _allSelectableTargets();
    return selectable.isNotEmpty &&
        selectable.every((target) => _selectedKeys.contains(target.key));
  }

  void _toggleAllSelections(bool selected) {
    final selectable = _allSelectableTargets();
    setState(() {
      if (selected) {
        _selectedKeys.addAll(selectable.map((target) => target.key));
      } else {
        _selectedKeys.removeAll(selectable.map((target) => target.key));
      }
    });
  }

  void _toggleSectionSelection(_InstanceGroupSection section, bool selected) {
    final selectable = _selectableTargetsForSection(section);
    setState(() {
      if (selected) {
        _selectedKeys.addAll(selectable.map((target) => target.key));
      } else {
        _selectedKeys.removeAll(selectable.map((target) => target.key));
      }
    });
  }

  void _toggleSectionExpanded(_InstanceGroupSection section) {
    final instanceId = section.instance.instanceId;
    setState(() {
      if (_expandedSectionIds.contains(instanceId)) {
        _expandedSectionIds.remove(instanceId);
      } else {
        _expandedSectionIds.add(instanceId);
      }
    });
  }

  void _toggleAllSectionsExpanded(bool expanded) {
    final instanceIds = _sections
        .map((section) => section.instance.instanceId)
        .where((id) => id.isNotEmpty);
    setState(() {
      if (expanded) {
        _expandedSectionIds.addAll(instanceIds);
      } else {
        _expandedSectionIds.removeAll(instanceIds);
      }
    });
  }

  void _toggleGroupSelection(
    _InstanceGroupSection section,
    WhatsappGroupModel group,
    bool selected,
  ) {
    if (!group.isSelectable) return;
    final key = _groupKey(section.instance.instanceId, group.id);
    setState(() {
      if (selected) {
        _selectedKeys.add(key);
      } else {
        _selectedKeys.remove(key);
      }
    });
  }

  String _buildBatchName(List<LinkedGroupBroadcastTarget> targets) {
    final numberCount = targets
        .map((target) => target.instanceId)
        .toSet()
        .length;
    if (numberCount <= 1) {
      final first = targets.first;
      return 'Linked Groups ${first.numberLabel}';
    }
    return 'Linked Groups $numberCount Numbers';
  }

  Future<void> _startLiveProgress({
    required List<String> campaignIds,
    required int totalGroups,
    required int totalNumbers,
    required String batchName,
  }) async {
    _liveStatusTimer?.cancel();
    setState(() {
      _liveProgress = _LiveGroupSendProgress(
        campaignIds: campaignIds,
        batchName: batchName,
        totalGroups: totalGroups,
        totalNumbers: totalNumbers,
        sentCount: 0,
        failedCount: 0,
        pendingCount: totalGroups,
        queueStatus: 'Queued on server',
        nextRunInSec: 0,
        lastError: '',
        isActive: true,
      );
      _liveProgressError = '';
    });

    await _refreshLiveProgress();
    if (!mounted) return;
    _liveStatusTimer = Timer.periodic(const Duration(seconds: 4), (_) {
      _refreshLiveProgress();
    });
  }

  Future<void> _refreshLiveProgress() async {
    final liveProgress = _liveProgress;
    if (liveProgress == null || _isRefreshingLiveProgress) {
      return;
    }

    _isRefreshingLiveProgress = true;
    try {
      final summaries = await _groupSenderStatusService
          .fetchGroupSenderStatuses();
      if (!mounted) return;

      final matched = summaries
          .where((summary) => liveProgress.campaignIds.contains(summary.id))
          .toList(growable: false);

      if (matched.isEmpty) {
        setState(() {
          _liveProgressError = 'Waiting for live server status...';
        });
        return;
      }

      final sentCount = matched.fold<int>(
        0,
        (sum, summary) => sum + summary.sentCount,
      );
      final failedCount = matched.fold<int>(
        0,
        (sum, summary) => sum + summary.failedCount,
      );
      final pendingCount = matched.fold<int>(
        0,
        (sum, summary) => sum + summary.pendingCount,
      );
      final nextRunInSec = matched.fold<int>(
        0,
        (maxValue, summary) =>
            summary.nextRunInSec > maxValue ? summary.nextRunInSec : maxValue,
      );
      final hasActiveQueue = matched.any((summary) {
        final queueStatus = summary.queueStatus.toLowerCase();
        return summary.pendingCount > 0 ||
            queueStatus == 'queued' ||
            queueStatus == 'processing';
      });

      final lastErrors = matched
          .map((summary) => summary.lastError.trim())
          .where((error) => error.isNotEmpty)
          .toSet()
          .toList(growable: false);

      setState(() {
        _liveProgress = liveProgress.copyWith(
          sentCount: sentCount,
          failedCount: failedCount,
          pendingCount: pendingCount,
          queueStatus: _buildLiveQueueStatus(matched),
          nextRunInSec: nextRunInSec,
          lastError: lastErrors.isEmpty ? '' : lastErrors.first,
          isActive: hasActiveQueue,
        );
        _liveProgressError = '';
      });

      if (!hasActiveQueue) {
        _liveStatusTimer?.cancel();
      }
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _liveProgressError = 'Live status refresh failed: $error';
      });
    } finally {
      _isRefreshingLiveProgress = false;
    }
  }

  String _buildLiveQueueStatus(List<GroupSenderStatusSummary> summaries) {
    final queueStatuses = summaries
        .map((summary) => summary.queueStatus.toLowerCase())
        .toSet();

    if (queueStatuses.contains('processing')) {
      return 'Sending in progress';
    }
    if (queueStatuses.contains('queued')) {
      return 'Queued on server';
    }
    if (queueStatuses.contains('completed')) {
      return 'Completed';
    }
    return 'Live status available';
  }

  Widget _buildLiveProgressCard() {
    final liveProgress = _liveProgress;
    if (liveProgress == null) {
      return const SizedBox.shrink();
    }

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.primaryBlue.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: AppColors.primaryBlue.withValues(alpha: 0.25),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.query_stats_rounded,
                  color: AppColors.primaryBlue,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Live Group Status',
                      style: TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 14,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${liveProgress.totalGroups} groups across ${liveProgress.totalNumbers} numbers',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade700,
                      ),
                    ),
                  ],
                ),
              ),
              IconButton(
                onPressed: _refreshLiveProgress,
                icon: _isRefreshingLiveProgress
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.refresh_rounded),
                tooltip: 'Refresh live status',
              ),
            ],
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _LiveStatusPill(
                label: '${liveProgress.sentCount} sent',
                color: Colors.green,
              ),
              _LiveStatusPill(
                label: '${liveProgress.pendingCount} pending',
                color: liveProgress.pendingCount > 0
                    ? Colors.orange
                    : Colors.blueGrey,
              ),
              _LiveStatusPill(
                label: '${liveProgress.failedCount} failed',
                color: liveProgress.failedCount > 0
                    ? Colors.red
                    : Colors.blueGrey,
              ),
              _LiveStatusPill(
                label: liveProgress.queueStatus,
                color: AppColors.primaryBlue,
              ),
              if (liveProgress.nextRunInSec > 0)
                _LiveStatusPill(
                  label: 'Retry in ${liveProgress.nextRunInSec}s',
                  color: Colors.indigo,
                ),
            ],
          ),
          if (_liveProgressError.trim().isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              _liveProgressError,
              style: const TextStyle(fontSize: 12, color: Colors.deepOrange),
            ),
          ],
          if (liveProgress.lastError.trim().isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              'Last error: ${liveProgress.lastError}',
              style: const TextStyle(fontSize: 12, color: Colors.red),
            ),
          ],
          const SizedBox(height: 10),
          Align(
            alignment: Alignment.centerRight,
            child: TextButton.icon(
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const GroupSenderStatusScreen(),
                  ),
                );
              },
              icon: const Icon(Icons.open_in_new_rounded),
              label: Text(
                liveProgress.isActive ? 'Open live status' : 'Open history',
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _sendSelectedGroups({DateTime? scheduledAt}) async {
    if (_pendingForwardMessage != null) {
      final blockedUntil = _forwardSendBlockedUntil;
      if (blockedUntil != null && DateTime.now().isBefore(blockedUntil)) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Forward loaded. Click Send button to send.'),
          ),
        );
        return;
      }
      await _sendForwardMessage(_pendingForwardMessage!);
      return;
    }

    final message = _messageCtrl.text.trim();
    final caption = _mediaCaptionCtrl.text.trim();
    final targets = _selectedTargets();
    final parsedGapSeconds = int.tryParse(_gapSecondsCtrl.text.trim()) ?? 0;
    final delaySeconds = parsedGapSeconds < 0 ? 0 : parsedGapSeconds;
    final scheduleAtEpoch = scheduledAt == null
        ? 0
        : (scheduledAt.millisecondsSinceEpoch / 1000).floor();

    if (targets.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Select at least one group')),
      );
      return;
    }

    if (_composerMode == _ComposerMode.text && message.isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Write a message first')));
      return;
    }

    if (_composerMode == _ComposerMode.media && _mediaUrl.isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Choose media first')));
      return;
    }

    if (_composerMode == _ComposerMode.media && caption.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Write caption/message for media')),
      );
      return;
    }

    final selectedInstanceIds = targets
        .map((target) => target.instanceId)
        .where((id) => id.isNotEmpty)
        .toSet();
    final unhealthyInstances = <String>[];
    for (final instanceId in selectedInstanceIds) {
      try {
        final status = await _api.getConnectionStatus(instanceId);
        if (!status.connected) {
          unhealthyInstances.add(instanceId);
        }
      } catch (_) {
        unhealthyInstances.add(instanceId);
      }
    }

    if (unhealthyInstances.isNotEmpty) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'These linked numbers are not connected. Reconnect first: ${unhealthyInstances.join(', ')}',
          ),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    setState(() => _isSending = true);
    try {
      final result = _composerMode == _ComposerMode.text
          ? await _broadcastService.sendText(
              batchName: _buildBatchName(targets),
              message: message,
              targets: targets,
              delaySeconds: delaySeconds,
              scheduleAt: scheduleAtEpoch,
            )
          : await _broadcastService.sendMedia(
              batchName: _buildBatchName(targets),
              mediaType: _mediaType.name,
              mediaUrl: _mediaUrl,
              caption: caption,
              filename: _mediaFilename,
              mimeType: _resolveComposerMimeType(),
              targets: targets,
              delaySeconds: delaySeconds,
              scheduleAt: scheduleAtEpoch,
            );

      if (!mounted) return;
      _messageCtrl.clear();
      _mediaCaptionCtrl.clear();
      if (_composerMode == _ComposerMode.media) {
        setState(() {
          _mediaUrl = '';
          _mediaFilename = '';
          _mediaMimeType = '';
        });
      }

      final summary = result.queuedGroups > 0
          ? (scheduledAt == null
                ? 'Queued ${result.queuedGroups} groups across ${result.totalNumbers} numbers'
                : 'Scheduled ${result.queuedGroups} groups at ${_formatScheduleTime(scheduledAt)}')
          : 'Sent ${result.sentGroups} groups across ${result.totalNumbers} numbers';
      final suffix = result.usedFallback
          ? '${result.sentGroups > 0 ? ' | ${result.sentGroups} sent direct fallback' : ''}${result.failedGroups > 0 ? ' | ${result.failedGroups} failed' : ''}'
          : result.failedGroups > 0
          ? ' | ${result.failedGroups} failed'
          : '';

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('$summary$suffix'),
          backgroundColor: result.failedGroups > 0 && result.queuedGroups == 0
              ? Colors.orange
              : Colors.green,
        ),
      );

      if (result.campaignIds.isNotEmpty) {
        await _startLiveProgress(
          campaignIds: result.campaignIds,
          totalGroups: result.queuedGroups,
          totalNumbers: result.totalNumbers,
          batchName: _buildBatchName(targets),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to send selected groups: $e'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _isSending = false);
      }
    }
  }

  Future<void> _openForwardPicker() async {
    if (_isSending || _isUploading) return;
    final targets = _selectedTargets();
    if (targets.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Select at least one group')),
      );
      return;
    }

    final selected = await showDialog<_ForwardMessage>(
      context: context,
      builder: (context) => _ForwardPickerDialog(
        loader: () async {
          final rows = await _groupSenderStatusService
              .fetchRecentForwardMessages(limit: 20);
          return rows.map(_ForwardMessage.fromJson).toList(growable: false);
        },
      ),
    );
    if (selected == null || !mounted) return;
    setState(() {
      _isForwardSelectionSettling = true;
      _pendingForwardMessage = selected;
      _forwardLoadedAt = DateTime.now();
      _forwardSendBlockedUntil = DateTime.now().add(const Duration(seconds: 3));
      _composerMode = selected.kind == 'media'
          ? _ComposerMode.media
          : _ComposerMode.text;
      if (selected.kind == 'media') {
        _mediaUrl = selected.mediaUrl;
        _mediaFilename = selected.filename;
        _mediaMimeType = selected.mimeType;
        _mediaCaptionCtrl.text = selected.text.isNotEmpty
            ? selected.text
            : (selected.sourceMessage.isNotEmpty
                  ? '(Forward message loaded)'
                  : '');
      } else {
        _messageCtrl.text = selected.text.isNotEmpty
            ? selected.text
            : (selected.sourceMessage.isNotEmpty
                  ? '(Forward message loaded)'
                  : '');
      }
    });
    Future.delayed(const Duration(milliseconds: 700), () {
      if (!mounted) return;
      setState(() {
        _isForwardSelectionSettling = false;
      });
    });
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Forward loaded. Click Send button.')),
    );
  }

  Future<void> _sendForwardMessage(_ForwardMessage selected) async {
    final targets = _selectedTargets();
    final parsedGapSeconds = int.tryParse(_gapSecondsCtrl.text.trim()) ?? 0;
    final delaySeconds = parsedGapSeconds < 0 ? 0 : parsedGapSeconds;

    final canSendMedia =
        selected.kind == 'media' && selected.mediaUrl.trim().isNotEmpty;
    final shouldSendAsText = selected.kind != 'media' || !canSendMedia;
    final textPayload = selected.text.trim();
    if (shouldSendAsText && textPayload.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Selected message has no sendable text/media'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() => _isSending = true);
    try {
      final result = selected.sourceMessage.isNotEmpty
          ? await _broadcastService.sendForward(
              batchName: _buildBatchName(targets),
              sourceMessage: selected.sourceMessage,
              targets: targets,
              delaySeconds: delaySeconds,
            )
          : !shouldSendAsText
          ? await _broadcastService.sendMedia(
              batchName: _buildBatchName(targets),
              mediaType: selected.mediaType,
              mediaUrl: selected.mediaUrl,
              caption: selected.text,
              filename: selected.filename,
              mimeType: selected.mimeType,
              targets: targets,
              delaySeconds: delaySeconds,
            )
          : await _broadcastService.sendText(
              batchName: _buildBatchName(targets),
              message: textPayload,
              targets: targets,
              delaySeconds: delaySeconds,
            );

      if (!mounted) return;
      setState(() {
        _pendingForwardMessage = null;
        _forwardLoadedAt = null;
        _forwardSendBlockedUntil = null;
      });
      final summary = result.queuedGroups > 0
          ? 'Queued ${result.queuedGroups} groups across ${result.totalNumbers} numbers'
          : 'Sent ${result.sentGroups} groups across ${result.totalNumbers} numbers';
      final suffix = result.failedGroups > 0
          ? ' | ${result.failedGroups} failed'
          : '';

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Forwarded message. $summary$suffix'),
          backgroundColor: result.failedGroups > 0 && result.queuedGroups == 0
              ? Colors.orange
              : Colors.green,
        ),
      );

      if (result.campaignIds.isNotEmpty) {
        await _startLiveProgress(
          campaignIds: result.campaignIds,
          totalGroups: result.queuedGroups,
          totalNumbers: result.totalNumbers,
          batchName: _buildBatchName(targets),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Forward failed: $e'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _isSending = false);
      }
    }
  }

  Future<void> _pickScheduleDateTime() async {
    final now = DateTime.now();
    final initialDate = _scheduledAt ?? now.add(const Duration(minutes: 10));
    final date = await showDatePicker(
      context: context,
      initialDate: initialDate,
      firstDate: now,
      lastDate: now.add(const Duration(days: 30)),
    );
    if (date == null || !mounted) return;
    final initialTime = TimeOfDay.fromDateTime(initialDate);
    final time = await showTimePicker(
      context: context,
      initialTime: initialTime,
    );
    if (time == null || !mounted) return;
    final picked = DateTime(
      date.year,
      date.month,
      date.day,
      time.hour,
      time.minute,
    );
    if (picked.isBefore(DateTime.now().add(const Duration(seconds: 10)))) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Schedule time must be at least 10 seconds ahead'),
        ),
      );
      return;
    }
    setState(() => _scheduledAt = picked);
  }

  Future<void> _scheduleSelectedGroups() async {
    if (_scheduledAt == null) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Pick schedule time first')));
      return;
    }
    await _sendSelectedGroups(scheduledAt: _scheduledAt);
  }

  String _formatScheduleTime(DateTime dt) {
    final day = dt.day.toString().padLeft(2, '0');
    final month = dt.month.toString().padLeft(2, '0');
    final year = dt.year.toString();
    final hour = dt.hour.toString().padLeft(2, '0');
    final minute = dt.minute.toString().padLeft(2, '0');
    return '$day/$month/$year $hour:$minute';
  }

  Future<void> _pickAndUploadMedia() async {
    setState(() => _isUploading = true);
    try {
      final uploaded = await _uploadService.pickAndUpload(
        folder: 'linked-groups/${_mediaType.name}',
        allowedExtensions: _allowedExtensions(_mediaType),
      );
      if (!mounted || uploaded == null) return;
      setState(() {
        _mediaUrl = uploaded.url;
        _mediaFilename = uploaded.filename;
        _mediaMimeType = uploaded.contentType;
      });
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Processed ${uploaded.filename}')));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Upload failed: $e')));
    } finally {
      if (mounted) {
        setState(() => _isUploading = false);
      }
    }
  }

  List<String> _allowedExtensions(_ComposerMediaType type) {
    switch (type) {
      case _ComposerMediaType.image:
        return ['jpg', 'jpeg', 'png', 'webp'];
      case _ComposerMediaType.video:
        return ['mp4', 'mov', 'mkv', 'webm'];
    }
  }

  String _resolveComposerMimeType() {
    final explicit = _mediaMimeType.trim().toLowerCase();
    if (explicit.contains('/')) {
      return explicit;
    }
    switch (_mediaType) {
      case _ComposerMediaType.video:
        return 'video/mp4';
      case _ComposerMediaType.image:
        return 'image/jpeg';
    }
  }

  Widget _buildSectionCard(_InstanceGroupSection section) {
    final selectableTargets = _selectableTargetsForSection(section);
    final numberLabel = _displayLinkedNumber(section.instance);
    final linkedName = (section.instance.linkedName ?? '').trim();
    final allSelected = _isSectionFullySelected(section);
    final isExpanded = _isSectionExpanded(section);

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Colors.grey.shade300),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          InkWell(
            borderRadius: BorderRadius.circular(14),
            onTap: () => _toggleSectionExpanded(section),
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 2),
              child: Row(
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: AppColors.primaryBlue.withValues(alpha: 0.10),
                      borderRadius: BorderRadius.circular(13),
                    ),
                    child: const Icon(
                      Icons.groups_rounded,
                      color: AppColors.primaryBlue,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          numberLabel,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 15,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          linkedName.isNotEmpty
                              ? '$linkedName | ${selectableTargets.length}/${section.groups.length} selectable'
                              : '${selectableTargets.length}/${section.groups.length} selectable groups',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Checkbox(
                    value: allSelected,
                    activeColor: AppColors.primaryBlue,
                    visualDensity: VisualDensity.compact,
                    materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    onChanged: selectableTargets.isEmpty
                        ? null
                        : (value) =>
                              _toggleSectionSelection(section, value ?? false),
                  ),
                  AnimatedRotation(
                    turns: isExpanded ? 0.5 : 0,
                    duration: const Duration(milliseconds: 180),
                    child: Icon(
                      Icons.keyboard_arrow_down_rounded,
                      color: Colors.grey.shade700,
                    ),
                  ),
                ],
              ),
            ),
          ),
          if (isExpanded && section.groups.isEmpty) ...[
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF8F9FB),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Text(
                section.loadHint ?? 'No groups found for this linked number',
                style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
              ),
            ),
          ] else if (isExpanded) ...[
            const SizedBox(height: 12),
            Builder(
              builder: (context) {
                const maxVisible = 4;
                final visibleCount = section.groups.length < maxVisible
                    ? section.groups.length
                    : maxVisible;
                // Rough row height for the group tile.
                final listH = (visibleCount * 74.0).clamp(220.0, 360.0);

                return SizedBox(
                  height: listH,
                  child: Scrollbar(
                    child: ListView.builder(
                      padding: EdgeInsets.zero,
                      itemCount: section.groups.length,
                      itemBuilder: (context, index) {
                        final group = section.groups[index];
                        final isSelected = _isGroupSelected(section, group);
                        final selectable = group.isSelectable;
                        final subtitleParts = <String>[
                          '${group.participantCount} members',
                          if (!selectable) 'Only admins can send here',
                        ];

                        return Container(
                          margin: const EdgeInsets.only(bottom: 8),
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: isSelected
                                ? AppColors.primaryBlue.withValues(alpha: 0.06)
                                : const Color(0xFFF8F9FB),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(
                              color: isSelected
                                  ? AppColors.primaryBlue.withValues(
                                      alpha: 0.35,
                                    )
                                  : Colors.grey.shade300,
                            ),
                          ),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Checkbox(
                                value: isSelected,
                                activeColor: AppColors.primaryBlue,
                                visualDensity: VisualDensity.compact,
                                materialTapTargetSize:
                                    MaterialTapTargetSize.shrinkWrap,
                                onChanged: selectable
                                    ? (value) => _toggleGroupSelection(
                                        section,
                                        group,
                                        value ?? false,
                                      )
                                    : null,
                              ),
                              const SizedBox(width: 4),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      group.name,
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w700,
                                        fontSize: 14,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      subtitleParts.join(' | '),
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: selectable
                                            ? Colors.grey.shade600
                                            : Colors.orange.shade700,
                                      ),
                                    ),
                                    if (group.description
                                        .trim()
                                        .isNotEmpty) ...[
                                      const SizedBox(height: 4),
                                      Text(
                                        group.description.trim(),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                        style: TextStyle(
                                          fontSize: 12,
                                          color: Colors.grey.shade500,
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  ),
                );
              },
            ),
          ],
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final keyboardInset = MediaQuery.of(context).viewInsets.bottom;
    final bottomPadding = keyboardInset > 0 ? keyboardInset + 16 : 24.0;

    return Scaffold(
      resizeToAvoidBottomInset: true,
      backgroundColor: const Color(0xFFF5F7FA),
      body: SafeArea(
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : RefreshIndicator(
                onRefresh: _loadSections,
                child: SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  keyboardDismissBehavior:
                      ScrollViewKeyboardDismissBehavior.onDrag,
                  padding: EdgeInsets.fromLTRB(16, 16, 16, bottomPadding),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 8,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: Colors.grey.shade200),
                        ),
                        child: Row(
                          children: [
                            const Expanded(
                              child: Text(
                                'All Linked Groups',
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 16,
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Flexible(
                              child: SingleChildScrollView(
                                scrollDirection: Axis.horizontal,
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    IconButton(
                                      onPressed: _sections.isEmpty
                                          ? null
                                          : () => _toggleAllSelections(
                                              !_isAllSelected,
                                            ),
                                      icon: Icon(
                                        _isAllSelected
                                            ? Icons.check_box_rounded
                                            : Icons
                                                  .check_box_outline_blank_rounded,
                                      ),
                                      tooltip: _isAllSelected
                                          ? 'Unselect All Groups'
                                          : 'Select All Groups',
                                    ),
                                    IconButton(
                                      onPressed: _sections.isEmpty
                                          ? null
                                          : () => _toggleAllSectionsExpanded(
                                              !_areAllSectionsExpanded,
                                            ),
                                      icon: Icon(
                                        _areAllSectionsExpanded
                                            ? Icons.unfold_less_rounded
                                            : Icons.unfold_more_rounded,
                                      ),
                                      tooltip: _areAllSectionsExpanded
                                          ? 'Collapse All'
                                          : 'Expand All',
                                    ),
                                    IconButton(
                                      onPressed: _isLoading
                                          ? null
                                          : _loadSections,
                                      icon: const Icon(Icons.refresh_rounded),
                                    ),
                                    IconButton(
                                      onPressed: () {
                                        Navigator.push(
                                          context,
                                          MaterialPageRoute(
                                            builder: (_) =>
                                                const ScheduleHistoryScreen(),
                                          ),
                                        );
                                      },
                                      icon: const Icon(Icons.schedule_rounded),
                                      tooltip: 'Schedule History',
                                    ),
                                    IconButton(
                                      onPressed: () {
                                        Navigator.push(
                                          context,
                                          MaterialPageRoute(
                                            builder: (_) =>
                                                const ParentGroupListScreen(),
                                          ),
                                        );
                                      },
                                      icon: const Icon(Icons.hub_rounded),
                                      tooltip: 'Parent Groups',
                                    ),
                                    IconButton(
                                      onPressed: () {
                                        Navigator.push(
                                          context,
                                          MaterialPageRoute(
                                            builder: (_) =>
                                                const GroupSenderStatusScreen(),
                                          ),
                                        );
                                      },
                                      icon: const Icon(
                                        Icons.assessment_rounded,
                                      ),
                                      tooltip: 'Group Status',
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 12),
                      if (_sections.isEmpty)
                        Container(
                          padding: const EdgeInsets.all(24),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: Colors.grey.shade200),
                          ),
                          child: Column(
                            children: [
                              Icon(
                                Icons.link_off_rounded,
                                size: 72,
                                color: Colors.grey.shade300,
                              ),
                              const SizedBox(height: 12),
                              Text(
                                'No linked numbers found',
                                style: TextStyle(
                                  fontSize: 18,
                                  color: Colors.grey.shade600,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                'Link a number from the WhatsApp Connect screen first, then all linked groups will appear here together.',
                                textAlign: TextAlign.center,
                                style: TextStyle(color: Colors.grey.shade500),
                              ),
                            ],
                          ),
                        )
                      else ...[
                        ..._sections.map(_buildSectionCard),
                        const SizedBox(height: 12),
                        Container(
                          padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.06),
                                blurRadius: 10,
                                offset: const Offset(0, -4),
                              ),
                            ],
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _buildLiveProgressCard(),
                              Text(
                                'Selected: $_selectedGroupCount groups from $_selectedNumberCount numbers',
                                style: const TextStyle(
                                  fontWeight: FontWeight.w700,
                                  fontSize: 13,
                                ),
                              ),
                              const SizedBox(height: 10),
                              Row(
                                children: [
                                  Expanded(
                                    child: _composerChip(
                                      label: 'Text',
                                      selected:
                                          _composerMode == _ComposerMode.text,
                                      onTap: () => setState(() {
                                        _composerMode = _ComposerMode.text;
                                        _pendingForwardMessage = null;
                                        _forwardLoadedAt = null;
                                        _forwardSendBlockedUntil = null;
                                        _mediaMimeType = '';
                                      }),
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: _composerChip(
                                      label: 'Media',
                                      selected:
                                          _composerMode == _ComposerMode.media,
                                      onTap: () => setState(() {
                                        _composerMode = _ComposerMode.media;
                                        _pendingForwardMessage = null;
                                        _forwardLoadedAt = null;
                                        _forwardSendBlockedUntil = null;
                                      }),
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: OutlinedButton.icon(
                                      onPressed: (_isSending || _isUploading)
                                          ? null
                                          : _openForwardPicker,
                                      icon: const Icon(
                                        Icons.forward_rounded,
                                        size: 18,
                                      ),
                                      label: const Text('Forward'),
                                      style: OutlinedButton.styleFrom(
                                        padding: const EdgeInsets.symmetric(
                                          vertical: 10,
                                        ),
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(
                                            12,
                                          ),
                                        ),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 10),
                              if (_composerMode == _ComposerMode.text)
                                TextField(
                                  controller: _messageCtrl,
                                  maxLines: 4,
                                  decoration: _composerInput(
                                    'Write one message - it will be sent to all selected groups...',
                                  ),
                                )
                              else ...[
                                DropdownButtonFormField<_ComposerMediaType>(
                                  initialValue: _mediaType,
                                  decoration: _composerInput('Media type'),
                                  items: _ComposerMediaType.values
                                      .map(
                                        (type) => DropdownMenuItem(
                                          value: type,
                                          child: Text(type.label),
                                        ),
                                      )
                                      .toList(),
                                  onChanged: (value) {
                                    if (value == null) return;
                                    setState(() {
                                      _mediaType = value;
                                      _mediaUrl = '';
                                      _mediaFilename = '';
                                      _mediaMimeType = '';
                                    });
                                  },
                                ),
                                const SizedBox(height: 10),
                                OutlinedButton.icon(
                                  onPressed: _isUploading
                                      ? null
                                      : _pickAndUploadMedia,
                                  icon: _isUploading
                                      ? const SizedBox(
                                          width: 16,
                                          height: 16,
                                          child: CircularProgressIndicator(
                                            strokeWidth: 2,
                                          ),
                                        )
                                      : const Icon(Icons.upload_file_rounded),
                                  label: Text(
                                    _isUploading
                                        ? 'Processing...'
                                        : 'Choose Media',
                                  ),
                                ),
                                if (_mediaFilename.isNotEmpty) ...[
                                  const SizedBox(height: 8),
                                  Text(
                                    _mediaFilename,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Colors.grey.shade700,
                                    ),
                                  ),
                                ],
                                const SizedBox(height: 10),
                                TextField(
                                  controller: _mediaCaptionCtrl,
                                  maxLines: 3,
                                  decoration: _composerInput(
                                    'Caption / text (required with media)',
                                  ),
                                ),
                              ],
                              const SizedBox(height: 10),
                              TextField(
                                controller: _gapSecondsCtrl,
                                keyboardType: TextInputType.number,
                                decoration: _composerInput(
                                  'Sending gap in seconds (0 = no manual gap)',
                                ),
                              ),
                              const SizedBox(height: 10),
                              Row(
                                children: [
                                  Expanded(
                                    child: OutlinedButton.icon(
                                      onPressed: (_isSending || _isUploading)
                                          ? null
                                          : _isForwardSelectionSettling
                                          ? null
                                          : _pickScheduleDateTime,
                                      icon: const Icon(Icons.schedule_rounded),
                                      label: Text(
                                        _scheduledAt == null
                                            ? 'Pick Schedule Time'
                                            : _formatScheduleTime(
                                                _scheduledAt!,
                                              ),
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  ElevatedButton.icon(
                                    onPressed: (_isSending || _isUploading)
                                        ? null
                                        : _isForwardSelectionSettling
                                        ? null
                                        : _scheduleSelectedGroups,
                                    icon: const Icon(Icons.alarm_add_rounded),
                                    label: const Text('Schedule'),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: Colors.indigo,
                                      foregroundColor: Colors.white,
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              SizedBox(
                                width: double.infinity,
                                height: 48,
                                child: ElevatedButton.icon(
                                  onPressed: (_isSending || _isUploading)
                                      ? null
                                      : _isForwardSelectionSettling
                                      ? null
                                      : () => _sendSelectedGroups(),
                                  icon: _isSending
                                      ? const SizedBox(
                                          width: 16,
                                          height: 16,
                                          child: CircularProgressIndicator(
                                            strokeWidth: 2,
                                            color: Colors.white,
                                          ),
                                        )
                                      : const Icon(Icons.send_rounded),
                                  label: Text(
                                    _isSending
                                        ? 'Sending to selected groups...'
                                        : _pendingForwardMessage != null
                                        ? 'Send Forward to Selected Groups'
                                        : _composerMode == _ComposerMode.text
                                        ? 'Send to Selected Groups'
                                        : 'Send ${_mediaType.label} to Selected Groups',
                                  ),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: AppColors.primaryBlue,
                                    foregroundColor: Colors.white,
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(14),
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
      ),
    );
  }
}

class _InstanceGroupSection {
  final ActiveWhatsappInstance instance;
  final List<WhatsappGroupModel> groups;
  final String? loadHint;

  const _InstanceGroupSection({
    required this.instance,
    required this.groups,
    required this.loadHint,
  });
}

InputDecoration _composerInput(String hintText) {
  return InputDecoration(
    hintText: hintText,
    filled: true,
    fillColor: const Color(0xFFF8F9FB),
    border: OutlineInputBorder(
      borderRadius: BorderRadius.circular(14),
      borderSide: BorderSide(color: Colors.grey.shade300),
    ),
    enabledBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(14),
      borderSide: BorderSide(color: Colors.grey.shade300),
    ),
  );
}

Widget _composerChip({
  required String label,
  required bool selected,
  required VoidCallback onTap,
}) {
  return InkWell(
    onTap: onTap,
    borderRadius: BorderRadius.circular(12),
    child: Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: BoxDecoration(
        color: selected
            ? AppColors.primaryBlue.withValues(alpha: 0.1)
            : const Color(0xFFF8F9FB),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: selected ? AppColors.primaryBlue : Colors.grey.shade300,
        ),
      ),
      child: Center(
        child: Text(
          label,
          style: TextStyle(
            fontWeight: FontWeight.w700,
            color: selected ? AppColors.primaryBlue : Colors.grey.shade700,
          ),
        ),
      ),
    ),
  );
}

enum _ComposerMode { text, media }

enum _ComposerMediaType {
  image,
  video;

  String get label => this == _ComposerMediaType.image ? 'Image' : 'Video';
}

class _ForwardMessage {
  final String id;
  final String kind;
  final String text;
  final String mediaUrl;
  final String mediaType;
  final String filename;
  final String mimeType;
  final String createdAt;
  final String batchName;
  final Map<String, dynamic> sourceMessage;

  const _ForwardMessage({
    required this.id,
    required this.kind,
    required this.text,
    required this.mediaUrl,
    required this.mediaType,
    required this.filename,
    required this.mimeType,
    required this.createdAt,
    required this.batchName,
    required this.sourceMessage,
  });

  factory _ForwardMessage.fromJson(Map<String, dynamic> json) {
    final mediaUrl = json['media_url']?.toString() ?? '';
    final filename = json['filename']?.toString() ?? '';
    final rawMediaType = (json['media_type']?.toString() ?? '').toLowerCase();
    final rawMimeType = (json['mime_type']?.toString() ?? '').toLowerCase();
    final mediaType = _inferMediaType(
      mediaType: rawMediaType,
      mimeType: rawMimeType,
      mediaUrl: mediaUrl,
      filename: filename,
    );
    final mimeType = _inferMimeType(
      mediaType: mediaType,
      mimeType: rawMimeType,
      mediaUrl: mediaUrl,
      filename: filename,
    );
    final rawKind = (json['kind']?.toString() ?? 'text').toLowerCase();
    final normalizedKind = (rawKind == 'media' && mediaUrl.trim().isNotEmpty)
        ? 'media'
        : 'text';
    final sourceMessageRaw = json['source_message'];
    final sourceMessage = sourceMessageRaw is Map
        ? Map<String, dynamic>.from(sourceMessageRaw)
        : const <String, dynamic>{};
    return _ForwardMessage(
      id: json['id']?.toString() ?? '',
      kind: normalizedKind,
      text: json['text']?.toString() ?? '',
      mediaUrl: mediaUrl,
      mediaType: mediaType,
      filename: filename,
      mimeType: mimeType,
      createdAt: json['created_at']?.toString() ?? '',
      batchName: json['batch_name']?.toString() ?? '',
      sourceMessage: sourceMessage,
    );
  }

  static String _inferMediaType({
    required String mediaType,
    required String mimeType,
    required String mediaUrl,
    required String filename,
  }) {
    final normalized = mediaType.trim();
    if (normalized == 'image' ||
        normalized == 'video' ||
        normalized == 'audio' ||
        normalized == 'document') {
      return normalized;
    }
    if (mimeType.startsWith('video/')) return 'video';
    if (mimeType.startsWith('audio/')) return 'audio';
    if (mimeType.startsWith('image/')) return 'image';
    if (mimeType.startsWith('application/')) return 'document';

    final probe = '${filename.toLowerCase()} ${mediaUrl.toLowerCase()}';
    if (probe.contains('.mp4') ||
        probe.contains('.mov') ||
        probe.contains('.mkv') ||
        probe.contains('.webm')) {
      return 'video';
    }
    if (probe.contains('.mp3') ||
        probe.contains('.wav') ||
        probe.contains('.ogg') ||
        probe.contains('.m4a')) {
      return 'audio';
    }
    if (probe.contains('.jpg') ||
        probe.contains('.jpeg') ||
        probe.contains('.png') ||
        probe.contains('.webp') ||
        probe.contains('.gif')) {
      return 'image';
    }
    return 'document';
  }

  static String _inferMimeType({
    required String mediaType,
    required String mimeType,
    required String mediaUrl,
    required String filename,
  }) {
    if (mimeType.contains('/')) {
      return mimeType;
    }
    final probe = '${filename.toLowerCase()} ${mediaUrl.toLowerCase()}';
    if (probe.contains('.jpg') || probe.contains('.jpeg')) return 'image/jpeg';
    if (probe.contains('.png')) return 'image/png';
    if (probe.contains('.webp')) return 'image/webp';
    if (probe.contains('.gif')) return 'image/gif';
    if (probe.contains('.mp4')) return 'video/mp4';
    if (probe.contains('.mov')) return 'video/quicktime';
    if (probe.contains('.mkv')) return 'video/x-matroska';
    if (probe.contains('.webm')) return 'video/webm';
    if (probe.contains('.mp3')) return 'audio/mpeg';
    if (probe.contains('.wav')) return 'audio/wav';
    if (probe.contains('.ogg')) return 'audio/ogg';
    if (probe.contains('.m4a')) return 'audio/mp4';
    if (probe.contains('.pdf')) return 'application/pdf';

    switch (mediaType) {
      case 'video':
        return 'video/mp4';
      case 'audio':
        return 'audio/mpeg';
      case 'image':
        return 'image/jpeg';
      default:
        return 'application/octet-stream';
    }
  }
}

class _ForwardPickerDialog extends StatefulWidget {
  final Future<List<_ForwardMessage>> Function() loader;

  const _ForwardPickerDialog({required this.loader});

  @override
  State<_ForwardPickerDialog> createState() => _ForwardPickerDialogState();
}

class _ForwardPickerDialogState extends State<_ForwardPickerDialog> {
  int? _selectedIndex;
  List<_ForwardMessage> _items = const [];
  late final Future<List<_ForwardMessage>> _future;

  @override
  void initState() {
    super.initState();
    // Important: do not call loader() on every rebuild, otherwise selection resets.
    _future = widget.loader();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Select Recent Message'),
      content: SizedBox(
        width: 420,
        child: FutureBuilder<List<_ForwardMessage>>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const SizedBox(
                height: 180,
                child: Center(child: CircularProgressIndicator()),
              );
            }
            if (snapshot.hasError) {
              return SizedBox(
                height: 160,
                child: Center(
                  child: Text(
                    'Failed to load recent messages.\n${snapshot.error}',
                    textAlign: TextAlign.center,
                  ),
                ),
              );
            }
            final items = snapshot.data ?? const [];
            _items = items;
            if (items.isEmpty) {
              return const SizedBox(
                height: 120,
                child: Center(child: Text('No recent messages found')),
              );
            }

            return SizedBox(
              height: 420,
              child: ListView.separated(
                itemCount: items.length,
                separatorBuilder: (context, index) => const Divider(height: 1),
                itemBuilder: (context, index) {
                  final item = items[index];
                  final isSelected = _selectedIndex == index;
                  return ListTile(
                    selected: isSelected,
                    selectedTileColor: AppColors.primaryBlue.withValues(
                      alpha: 0.08,
                    ),
                    onTap: () {
                      // UX: tap to select and immediately load.
                      Navigator.pop(context, item);
                    },
                    leading: item.kind == 'media'
                        ? ClipRRect(
                            borderRadius: BorderRadius.circular(8),
                            child: Image.network(
                              item.mediaUrl,
                              width: 44,
                              height: 44,
                              fit: BoxFit.cover,
                              errorBuilder: (context, error, stackTrace) =>
                                  Container(
                                    width: 44,
                                    height: 44,
                                    alignment: Alignment.center,
                                    color: Colors.grey.shade200,
                                    child: const Icon(
                                      Icons.broken_image_rounded,
                                      size: 20,
                                    ),
                                  ),
                            ),
                          )
                        : CircleAvatar(
                            radius: 22,
                            backgroundColor: Colors.blue.shade50,
                            child: const Icon(Icons.message_rounded),
                          ),
                    title: Text(
                      item.text.isEmpty
                          ? (item.kind == 'media'
                                ? '(media without caption)'
                                : '(empty)')
                          : item.text,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    subtitle: Text(
                      '${item.kind == 'media' ? 'Media' : 'Text'} • ${item.createdAt}',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    trailing: const Icon(
                      Icons.arrow_forward_ios_rounded,
                      size: 14,
                    ),
                  );
                },
              ),
            );
          },
        ),
      ),
      actions: [
        TextButton(
          onPressed: () {
            final index = _selectedIndex;
            if (_items.isEmpty ||
                index == null ||
                index < 0 ||
                index >= _items.length) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Select a message first')),
              );
              return;
            }
            Navigator.pop(context, _items[index]);
          },
          child: const Text('Load'),
        ),
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Close'),
        ),
      ],
    );
  }
}

class _LiveGroupSendProgress {
  final List<String> campaignIds;
  final String batchName;
  final int totalGroups;
  final int totalNumbers;
  final int sentCount;
  final int failedCount;
  final int pendingCount;
  final String queueStatus;
  final int nextRunInSec;
  final String lastError;
  final bool isActive;

  const _LiveGroupSendProgress({
    required this.campaignIds,
    required this.batchName,
    required this.totalGroups,
    required this.totalNumbers,
    required this.sentCount,
    required this.failedCount,
    required this.pendingCount,
    required this.queueStatus,
    required this.nextRunInSec,
    required this.lastError,
    required this.isActive,
  });

  _LiveGroupSendProgress copyWith({
    int? sentCount,
    int? failedCount,
    int? pendingCount,
    String? queueStatus,
    int? nextRunInSec,
    String? lastError,
    bool? isActive,
  }) {
    return _LiveGroupSendProgress(
      campaignIds: campaignIds,
      batchName: batchName,
      totalGroups: totalGroups,
      totalNumbers: totalNumbers,
      sentCount: sentCount ?? this.sentCount,
      failedCount: failedCount ?? this.failedCount,
      pendingCount: pendingCount ?? this.pendingCount,
      queueStatus: queueStatus ?? this.queueStatus,
      nextRunInSec: nextRunInSec ?? this.nextRunInSec,
      lastError: lastError ?? this.lastError,
      isActive: isActive ?? this.isActive,
    );
  }
}

class _LiveStatusPill extends StatelessWidget {
  final String label;
  final Color color;

  const _LiveStatusPill({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontSize: 12,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}
