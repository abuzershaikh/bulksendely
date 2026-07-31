import 'dart:async';

import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:autoreply/features/sync/services/server_sync_service.dart';
import 'package:autoreply/features/whatsapp/models/whatsapp_group_model.dart';
import 'package:autoreply/features/whatsapp/services/whatsapp_api_service.dart';
import 'package:flutter/material.dart';

class GroupMessageScreen extends StatefulWidget {
  final ActiveWhatsappInstance? instance;

  const GroupMessageScreen({super.key, this.instance});

  @override
  State<GroupMessageScreen> createState() => _GroupMessageScreenState();
}

class _GroupMessageScreenState extends State<GroupMessageScreen> {
  final WhatsappApiService _api = WhatsappApiService();
  final ServerSyncService _syncService = ServerSyncService();
  final SubscriptionService _subscriptionService = SubscriptionService.instance;
  final TextEditingController _messageCtrl = TextEditingController();

  List<WhatsappGroupModel> _groups = [];
  WhatsappGroupModel? _selectedGroup;
  bool _isLoading = true;
  bool _isSending = false;
  bool _isScheduling = false;
  bool _isCheckingLock = false;
  bool _groupManualLocked = false;
  String _groupLockHint = '';
  String _search = '';
  DateTime? _scheduledAt;
  StreamSubscription<WhatsappGroupsUpdate>? _groupsUpdateSubscription;

  @override
  void initState() {
    super.initState();
    _groupsUpdateSubscription = _api.groupUpdates.listen((update) {
      final instanceId = widget.instance?.instanceId;
      if (!mounted || instanceId == null || update.instanceId != instanceId) {
        return;
      }
      final selectableGroups = update.groups
          .where((group) => group.isSelectable)
          .toList();
      setState(() {
        _groups = update.groups;
        if (_selectedGroup == null ||
            !update.groups.any((group) => group.id == _selectedGroup?.id)) {
          _selectedGroup = selectableGroups.isNotEmpty
              ? selectableGroups.first
              : null;
        }
      });
    });
    _loadGroups();
  }

  @override
  void dispose() {
    _groupsUpdateSubscription?.cancel();
    _messageCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadGroups() async {
    setState(() => _isLoading = true);
    try {
      final groups = await _api.fetchGroups(
        instanceId: widget.instance?.instanceId,
      );
      if (!mounted) return;
      final selectableGroups = groups
          .where((group) => group.isSelectable)
          .toList();
      setState(() {
        _groups = groups;
        _selectedGroup = selectableGroups.isNotEmpty
            ? selectableGroups.first
            : null;
        _isLoading = false;
      });
      await _refreshScheduleLockStatus();
    } catch (e) {
      if (!mounted) return;
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Failed to load groups: $e')));
    }
  }

  Future<void> _sendMessage() async {
    final group = _selectedGroup;
    final message = _messageCtrl.text.trim();

    if (group == null) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Select a group first')));
      return;
    }

    if (!group.isSelectable) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'This admin-only group cannot be used from your account',
          ),
        ),
      );
      return;
    }

    if (message.isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Write a message first')));
      return;
    }

    try {
      await _subscriptionService.ensurePremiumAccess(
        message:
            'This is a premium feature. Free users can view groups but cannot send messages in groups.',
      );
    } on SubscriptionAccessException catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(e.message)));
      return;
    }

    setState(() => _isSending = true);
    try {
      await _sendViaServerQueue();
      await _subscriptionService.recordSuccessfulSend(1);

      if (!mounted) return;
      final displayName = _displayGroupName(group);
      _messageCtrl.clear();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Message sent to $displayName'),
          backgroundColor: Colors.green,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Send failed: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) {
        setState(() => _isSending = false);
      }
    }
  }

  Future<void> _scheduleMessage() async {
    final group = _selectedGroup;
    final message = _messageCtrl.text.trim();
    final scheduledAt = _scheduledAt;

    if (group == null) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Select a group first')));
      return;
    }

    if (scheduledAt == null) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Pick schedule time first')));
      return;
    }

    if (scheduledAt.isBefore(DateTime.now().add(const Duration(seconds: 10)))) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Schedule time must be at least 10 seconds ahead'),
        ),
      );
      return;
    }

    if (message.isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Write a message first')));
      return;
    }

    try {
      await _subscriptionService.ensurePremiumAccess(
        message:
            'This is a premium feature. Free users can view groups but cannot send messages in groups.',
      );
    } on SubscriptionAccessException catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(e.message)));
      return;
    }

    setState(() => _isScheduling = true);
    try {
      await _sendViaServerQueue(scheduleAt: scheduledAt);
      if (!mounted) return;
      _messageCtrl.clear();
      setState(() => _scheduledAt = null);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Scheduled for ${_formatScheduleTime(scheduledAt)}'),
          backgroundColor: Colors.green,
        ),
      );
      await _refreshScheduleLockStatus();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Schedule failed: $e'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _isScheduling = false);
      }
    }
  }

  Future<void> _sendViaServerQueue({DateTime? scheduleAt}) async {
    final group = _selectedGroup!;
    final message = _messageCtrl.text.trim();
    final instanceId =
        widget.instance?.instanceId ?? await _syncService.getActiveInstanceId();

    final lockStatus = await _syncService.fetchGroupScheduleLockStatus(
      instanceId: instanceId,
      groupId: group.id,
    );
    final locked = lockStatus['locked'] == true;
    if (locked && scheduleAt == null) {
      final lock = (lockStatus['lock'] as Map?)?.cast<String, dynamic>() ?? {};
      final nextRunInSec = (lock['next_run_in_sec'] as num?)?.toInt() ?? 0;
      throw Exception(
        nextRunInSec > 0
            ? 'This group already has scheduled send. Try again in ${nextRunInSec}s.'
            : 'This group already has scheduled send. Manual send locked.',
      );
    }

    final recipients = [
      {
        'index': 1,
        'name': _displayGroupName(group),
        'number': group.id,
        'chat_id': group.id,
        'is_group': true,
      },
    ];

    final scheduleAtEpoch = scheduleAt == null
        ? 0
        : (scheduleAt.millisecondsSinceEpoch / 1000).floor();

    await _syncService.launchGroupCampaign(
      instanceId: instanceId,
      campaignName: scheduleAt == null
          ? 'Direct Group Send - ${_displayGroupName(group)}'
          : 'Scheduled Group Send - ${_displayGroupName(group)}',
      targetName: _displayGroupName(group),
      message: message,
      recipients: recipients,
      delaySeconds: 0,
      scheduleAt: scheduleAtEpoch,
      userEmail: _subscriptionService.currentUser?.email ?? '',
    );
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
    setState(() => _scheduledAt = picked);
  }

  Future<void> _refreshScheduleLockStatus() async {
    final group = _selectedGroup;
    if (group == null || _isCheckingLock) return;
    _isCheckingLock = true;
    try {
      final instanceId =
          widget.instance?.instanceId ??
          await _syncService.getActiveInstanceId();
      final lockStatus = await _syncService.fetchGroupScheduleLockStatus(
        instanceId: instanceId,
        groupId: group.id,
      );
      final locked = lockStatus['locked'] == true;
      final lock = (lockStatus['lock'] as Map?)?.cast<String, dynamic>() ?? {};
      final nextRunInSec = (lock['next_run_in_sec'] as num?)?.toInt() ?? 0;
      if (!mounted) return;
      setState(() {
        _groupManualLocked = locked;
        _groupLockHint = locked
            ? (nextRunInSec > 0
                  ? 'Manual send locked. Scheduled send in ${nextRunInSec}s.'
                  : 'Manual send locked due to scheduled campaign.')
            : '';
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _groupManualLocked = false;
        _groupLockHint = '';
      });
    } finally {
      _isCheckingLock = false;
    }
  }

  String _formatScheduleTime(DateTime dt) {
    final day = dt.day.toString().padLeft(2, '0');
    final month = dt.month.toString().padLeft(2, '0');
    final year = dt.year.toString();
    final hour = dt.hour.toString().padLeft(2, '0');
    final minute = dt.minute.toString().padLeft(2, '0');
    return '$day/$month/$year $hour:$minute';
  }

  String _displayGroupName(WhatsappGroupModel group) {
    final name = group.name.trim();
    if (name.isNotEmpty && !_looksLikeRawGroupValue(name)) {
      return name;
    }

    final description = group.description.trim();
    if (description.isNotEmpty && !_looksLikeRawGroupValue(description)) {
      return description;
    }

    return _fallbackFromGroupId(group.id);
  }

  bool _looksLikeRawGroupValue(String value) {
    final v = value.toLowerCase();
    return v.startsWith('http://') ||
        v.startsWith('https://') ||
        v.contains('chat.whatsapp.com/') ||
        v.endsWith('@g.us') ||
        v.endsWith('@temp') ||
        v.contains('@s.whatsapp.net');
  }

  String _fallbackFromGroupId(String groupId) {
    final id = groupId.trim();
    if (id.isEmpty) return 'Unnamed Group';
    if (id.contains('@')) return id.split('@').first;
    return id;
  }

  @override
  Widget build(BuildContext context) {
    final keyboardInset = MediaQuery.of(context).viewInsets.bottom;
    final filteredGroups = _groups.where((group) {
      final q = _search.toLowerCase();
      final displayName = _displayGroupName(group).toLowerCase();
      return displayName.contains(q) ||
          group.name.toLowerCase().contains(q) ||
          group.description.toLowerCase().contains(q);
    }).toList();

    return Scaffold(
      resizeToAvoidBottomInset: true,
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        toolbarHeight: 44,
        title: const Text(
          'Group Message',
          style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16),
        ),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        actions: [
          IconButton(
            onPressed: _isLoading ? null : _loadGroups,
            icon: const Icon(Icons.refresh_rounded, size: 20),
            visualDensity: VisualDensity.compact,
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(10, 6, 10, 4),
            child: SizedBox(
              height: 34,
              child: TextField(
                onChanged: (value) => setState(() => _search = value),
                style: const TextStyle(fontSize: 12),
                decoration: InputDecoration(
                  hintText: 'Search groups...',
                  hintStyle: TextStyle(
                    fontSize: 12,
                    color: Colors.grey.shade400,
                  ),
                  prefixIcon: const Padding(
                    padding: EdgeInsets.only(left: 8, right: 4),
                    child: Icon(Icons.search_rounded, size: 16),
                  ),
                  prefixIconConstraints: const BoxConstraints(
                    minWidth: 28,
                    minHeight: 28,
                  ),
                  contentPadding: const EdgeInsets.symmetric(
                    vertical: 0,
                    horizontal: 10,
                  ),
                  filled: true,
                  fillColor: Colors.white,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide: BorderSide(color: Colors.grey.shade300),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide: BorderSide(color: Colors.grey.shade300),
                  ),
                ),
              ),
            ),
          ),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : filteredGroups.isEmpty
                ? Center(
                    child: Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.groups_rounded,
                            size: 48,
                            color: Colors.grey.shade300,
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'No groups found',
                            style: TextStyle(
                              fontSize: 14,
                              color: Colors.grey.shade600,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Refresh after some WhatsApp group activity.',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey.shade500,
                            ),
                          ),
                        ],
                      ),
                    ),
                  )
                : ListView.builder(
                    padding: EdgeInsets.fromLTRB(
                      10,
                      0,
                      10,
                      keyboardInset > 0 ? 240 : 120,
                    ),
                    itemCount: filteredGroups.length,
                    itemBuilder: (context, index) {
                      final group = filteredGroups[index];
                      final displayName = _displayGroupName(group);
                      final selected = _selectedGroup?.id == group.id;
                      final selectable = group.isSelectable;
                      return GestureDetector(
                        onTap: selectable
                            ? () async {
                                setState(() => _selectedGroup = group);
                                await _refreshScheduleLockStatus();
                              }
                            : null,
                        child: Container(
                          margin: const EdgeInsets.only(bottom: 5),
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 7,
                          ),
                          decoration: BoxDecoration(
                            color: !selectable
                                ? Colors.grey.shade100
                                : selected
                                ? AppColors.primaryBlue.withValues(alpha: 0.08)
                                : Colors.white,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(
                              color: !selectable
                                  ? Colors.grey.shade300
                                  : selected
                                  ? AppColors.primaryBlue
                                  : Colors.grey.shade300,
                              width: selected ? 1.4 : 0.8,
                            ),
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 30,
                                height: 30,
                                decoration: BoxDecoration(
                                  color:
                                      (!selectable
                                              ? Colors.grey
                                              : (selected
                                                    ? AppColors.primaryBlue
                                                    : Colors.green))
                                          .withValues(alpha: 0.12),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Icon(
                                  Icons.groups_rounded,
                                  size: 16,
                                  color: !selectable
                                      ? Colors.grey
                                      : selected
                                      ? AppColors.primaryBlue
                                      : Colors.green,
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      displayName,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: TextStyle(
                                        fontWeight: FontWeight.w600,
                                        fontSize: 12,
                                        color: selectable
                                            ? Colors.black87
                                            : Colors.grey,
                                      ),
                                    ),
                                    Row(
                                      children: [
                                        if (group.description.isNotEmpty) ...[
                                          Flexible(
                                            child: Text(
                                              group.description,
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                              style: TextStyle(
                                                fontSize: 10,
                                                color: selectable
                                                    ? Colors.grey.shade500
                                                    : Colors.grey.shade400,
                                              ),
                                            ),
                                          ),
                                          Text(
                                            '  •  ',
                                            style: TextStyle(
                                              fontSize: 9,
                                              color: Colors.grey.shade400,
                                            ),
                                          ),
                                        ],
                                        Text(
                                          '${group.participantCount} members',
                                          style: TextStyle(
                                            fontSize: 9,
                                            color: Colors.grey.shade400,
                                          ),
                                        ),
                                      ],
                                    ),
                                    if (group.announce ||
                                        group.isCommunity ||
                                        group.isCommunityAnnounce)
                                      Padding(
                                        padding: const EdgeInsets.only(top: 2),
                                        child: Wrap(
                                          spacing: 4,
                                          children: [
                                            if (group.announce)
                                              _TypePill(
                                                label: group.userIsAdmin
                                                    ? 'Admin Only'
                                                    : 'Read Only',
                                                color: group.userIsAdmin
                                                    ? Colors.blue
                                                    : Colors.redAccent,
                                              ),
                                            if (group.isCommunity)
                                              _TypePill(
                                                label: 'Community',
                                                color: Colors.deepPurple,
                                              ),
                                            if (group.isCommunityAnnounce)
                                              _TypePill(
                                                label: 'Announcement',
                                                color: Colors.orange,
                                              ),
                                          ],
                                        ),
                                      ),
                                    if (group.announce && !group.userIsAdmin)
                                      Padding(
                                        padding: const EdgeInsets.only(top: 2),
                                        child: Text(
                                          'Only admins can send',
                                          style: TextStyle(
                                            fontSize: 9,
                                            color: Colors.red.shade400,
                                          ),
                                        ),
                                      ),
                                  ],
                                ),
                              ),
                              if (selected && selectable)
                                const Icon(
                                  Icons.check_circle_rounded,
                                  size: 18,
                                  color: AppColors.primaryBlue,
                                ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
          ),
          SafeArea(
            top: false,
            child: Container(
              padding: const EdgeInsets.fromLTRB(10, 5, 10, 6),
              decoration: BoxDecoration(
                color: Colors.white,
                border: Border(top: BorderSide(color: Colors.grey.shade200)),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (_selectedGroup != null)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 4),
                      child: Row(
                        children: [
                          Icon(
                            Icons.arrow_forward_rounded,
                            size: 11,
                            color: Colors.grey.shade500,
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              _displayGroupName(_selectedGroup!),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontWeight: FontWeight.w500,
                                fontSize: 11,
                                color: Colors.grey.shade700,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _messageCtrl,
                          maxLines: 3,
                          minLines: 1,
                          style: const TextStyle(fontSize: 12),
                          decoration: InputDecoration(
                            hintText: 'Type a message...',
                            hintStyle: TextStyle(
                              fontSize: 12,
                              color: Colors.grey.shade400,
                            ),
                            contentPadding: const EdgeInsets.symmetric(
                              vertical: 6,
                              horizontal: 10,
                            ),
                            isDense: true,
                            filled: true,
                            fillColor: const Color(0xFFF3F4F6),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(18),
                              borderSide: BorderSide.none,
                            ),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(18),
                              borderSide: BorderSide.none,
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(18),
                              borderSide: BorderSide.none,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 6),
                      SizedBox(
                        width: 34,
                        height: 34,
                        child: Material(
                          color: (_isSending || _groupManualLocked)
                              ? Colors.grey.shade300
                              : AppColors.primaryBlue,
                          shape: const CircleBorder(),
                          clipBehavior: Clip.antiAlias,
                          child: InkWell(
                            onTap: (_isSending || _groupManualLocked)
                                ? null
                                : _sendMessage,
                            child: Center(
                              child: _isSending
                                  ? const SizedBox(
                                      width: 14,
                                      height: 14,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : const Icon(
                                      Icons.send_rounded,
                                      size: 16,
                                      color: Colors.white,
                                    ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: _isScheduling
                              ? null
                              : _pickScheduleDateTime,
                          icon: const Icon(Icons.schedule_rounded, size: 16),
                          label: Text(
                            _scheduledAt == null
                                ? 'Pick Schedule Time'
                                : _formatScheduleTime(_scheduledAt!),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      ElevatedButton.icon(
                        onPressed: _isScheduling ? null : _scheduleMessage,
                        icon: _isScheduling
                            ? const SizedBox(
                                width: 12,
                                height: 12,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              )
                            : const Icon(Icons.alarm_add_rounded, size: 16),
                        label: const Text('Schedule'),
                      ),
                    ],
                  ),
                  if (_groupLockHint.isNotEmpty) ...[
                    const SizedBox(height: 6),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        _groupLockHint,
                        style: TextStyle(
                          fontSize: 11,
                          color: Colors.orange.shade800,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                  const SizedBox(height: 8),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _TypePill extends StatelessWidget {
  final String label;
  final Color color;

  const _TypePill({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 9,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }
}
