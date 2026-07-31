import 'package:autoreply/features/subscription/models/app_user_subscription.dart';
import 'package:autoreply/features/subscription/models/subscription_plan.dart';
import 'package:autoreply/features/subscription/screens/upgrade_plan_screen.dart';
import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:flutter/material.dart';

class SubscriptionProfileScreen extends StatefulWidget {
  const SubscriptionProfileScreen({super.key});

  @override
  State<SubscriptionProfileScreen> createState() =>
      _SubscriptionProfileScreenState();
}

class _SubscriptionProfileScreenState extends State<SubscriptionProfileScreen> {
  final SubscriptionService _subscriptionService = SubscriptionService.instance;
  bool _refreshing = false;
  String? _message;

  Future<void> _refreshPlan() async {
    setState(() {
      _refreshing = true;
      _message = null;
    });

    try {
      await _subscriptionService.syncPlanFromPanel();
      if (!mounted) {
        return;
      }
      setState(() {
        _message = 'Plan synced successfully.';
      });
    } catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _message = 'Plan sync failed: $error';
      });
    } finally {
      if (mounted) {
        setState(() {
          _refreshing = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<AppUserSubscription?>(
      valueListenable: _subscriptionService.currentUserNotifier,
      builder: (context, subscription, _) {
        final isPremium = subscription?.isPremium ?? false;
        final planLabel = subscription?.planLabel ?? 'Free';
        final trialOver = subscription?.trialOver ?? false;
        final trialEndsAt = subscription?.trialEndsAt;
        final trialStartedAt = subscription?.trialStartedAt;

        String trialStatus;
        if (isPremium) {
          trialStatus = 'Not applicable (Premium)';
        } else if (trialOver) {
          trialStatus = 'Over';
        } else if (trialEndsAt != null && trialEndsAt.isAfter(DateTime.now())) {
          final remaining = trialEndsAt.difference(DateTime.now());
          trialStatus =
              'Active (${remaining.inHours}h ${remaining.inMinutes % 60}m left)';
        } else {
          trialStatus = 'Active';
        }

        return Scaffold(
          backgroundColor: const Color(0xFFF5F7FA),
          appBar: AppBar(
            title: const Text(
              'My Plan',
              style: TextStyle(fontWeight: FontWeight.w700),
            ),
            backgroundColor: Colors.white,
            surfaceTintColor: Colors.white,
            actions: [
              IconButton(
                onPressed: _refreshing ? null : _refreshPlan,
                icon: _refreshing
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.refresh_rounded),
                tooltip: 'Sync Plan',
              ),
            ],
          ),
          body: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Container(
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: isPremium
                        ? const [Color(0xFF11998E), Color(0xFF38EF7D)]
                        : const [Color(0xFF1D3557), Color(0xFF457B9D)],
                  ),
                  borderRadius: BorderRadius.circular(22),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '$planLabel Plan',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      isPremium
                          ? 'Your premium features are active in the app.'
                          : 'You are on free plan. Upgrade from panel to unlock premium features.',
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.9),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              if (_message != null)
                Container(
                  margin: const EdgeInsets.only(bottom: 16),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: Colors.grey.shade300),
                  ),
                  child: Text(_message!),
                ),
              _infoCard(
                title: 'Profile',
                rows: [
                  _InfoRow('Name', subscription?.name ?? '-'),
                  _InfoRow('Email', subscription?.email ?? '-'),
                  _InfoRow('Status', isPremium ? 'Paid User' : 'Free User'),
                  _InfoRow('Plan', planLabel),
                ],
              ),
              const SizedBox(height: 16),
              _infoCard(
                title: 'Subscription',
                rows: [
                  _InfoRow(
                    'Expires At',
                    subscription?.subscriptionExpiresAt.isNotEmpty == true
                        ? subscription!.subscriptionExpiresAt
                        : 'No expiry / not set',
                  ),
                  _InfoRow(
                    'Free Messages Left',
                    '${subscription?.freeMessagesLeft ?? AppUserSubscription.freeMessageLimit}',
                  ),
                  _InfoRow(
                    'Messages Sent',
                    '${subscription?.totalMessagesSent ?? 0}',
                  ),
                  _InfoRow(
                    'WhatsApp Sessions',
                    '${subscription?.sessionCount ?? 0}',
                  ),
                ],
              ),
              const SizedBox(height: 16),
              _infoCard(
                title: 'Trial',
                rows: [
                  _InfoRow('Trial Status', trialStatus),
                  _InfoRow('Trial Started', _formatDateTime(trialStartedAt)),
                  _InfoRow('Trial Ends', _formatDateTime(trialEndsAt)),
                  _InfoRow(
                    'Message',
                    (subscription?.trialStatusMessage ?? '').isNotEmpty
                        ? (subscription?.trialStatusMessage ?? '')
                        : (trialOver
                              ? 'Your trial is over. Please upgrade to continue.'
                              : '1-day trial is active.'),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              if (subscription?.plan != SubscriptionPlan.lifetime)
                Padding(
                  padding: const EdgeInsets.only(bottom: 16),
                  child: ElevatedButton.icon(
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const UpgradePlanScreen(),
                        ),
                      );
                    },
                    icon: const Icon(Icons.star_rounded),
                    label: const Text('Upgrade to Premium'),
                    style: ElevatedButton.styleFrom(
                      minimumSize: const Size.fromHeight(56),
                      backgroundColor: const Color(0xFF6366F1),
                      foregroundColor: Colors.white,
                      elevation: 4,
                      shadowColor: const Color(0xFF6366F1).withOpacity(0.4),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                  ),
                ),
              OutlinedButton.icon(
                onPressed: _refreshing ? null : _refreshPlan,
                icon: const Icon(Icons.sync_rounded),
                label: Text(_refreshing ? 'Syncing...' : 'Sync Plan Status'),
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(52),
                  side: BorderSide(color: Colors.grey.shade300),
                  foregroundColor: Colors.grey.shade700,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _infoCard({
    required String title,
    required List<_InfoRow> rows,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
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
            title,
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 12),
          ...rows.map(
            (row) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SizedBox(
                    width: 130,
                    child: Text(
                      row.label,
                      style: TextStyle(color: Colors.grey.shade600),
                    ),
                  ),
                  Expanded(
                    child: Text(
                      row.value,
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _formatDateTime(DateTime? value) {
    if (value == null) {
      return '-';
    }
    final local = value.toLocal();
    final day = local.day.toString().padLeft(2, '0');
    final month = local.month.toString().padLeft(2, '0');
    final year = local.year.toString();
    final hour = local.hour.toString().padLeft(2, '0');
    final minute = local.minute.toString().padLeft(2, '0');
    return '$day/$month/$year $hour:$minute';
  }
}

class _InfoRow {
  final String label;
  final String value;

  const _InfoRow(this.label, this.value);
}
