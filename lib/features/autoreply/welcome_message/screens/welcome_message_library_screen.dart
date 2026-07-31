import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/features/autoreply/welcome_message/models/welcome_message_model.dart';
import 'package:autoreply/features/autoreply/welcome_message/screens/welcome_message_editor_screen.dart';
import 'package:autoreply/features/autoreply/welcome_message/storage/welcome_message_storage.dart';
import 'package:autoreply/features/subscription/models/app_user_subscription.dart';
import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:flutter/material.dart';

class WelcomeMessageLibraryScreen extends StatefulWidget {
  const WelcomeMessageLibraryScreen({super.key});

  @override
  State<WelcomeMessageLibraryScreen> createState() =>
      _WelcomeMessageLibraryScreenState();
}

class _WelcomeMessageLibraryScreenState
    extends State<WelcomeMessageLibraryScreen> {
  final WelcomeMessageStorage _storage = WelcomeMessageStorage();
  final SubscriptionService _subscriptionService = SubscriptionService.instance;

  bool get _isPremiumUser =>
      _subscriptionService.currentUserNotifier.value?.isPremium ?? false;

  @override
  void initState() {
    super.initState();
    _storage.load().then((_) {
      if (mounted) setState(() {});
    });
  }

  Future<void> _openEditor([WelcomeMessageFlow? flow]) async {
    final isCreating = flow == null;
    if (isCreating &&
        !_isPremiumUser &&
        _storage.flows.length >= AppUserSubscription.freeAutoReplyLimit) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Free users can create up to 5 auto-reply flows. Upgrade for unlimited.',
          ),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => WelcomeMessageEditorScreen(existingFlow: flow),
      ),
    );
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final flows = _storage.flows;

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: const Text(
          'Welcome Message',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openEditor(),
        backgroundColor: AppColors.primaryBlue,
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text(
          'New Flow',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
      ),
      body: flows.isEmpty
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.waving_hand_rounded,
                    size: 80,
                    color: Colors.grey.shade300,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'No welcome message flows yet',
                    style: TextStyle(color: Colors.grey.shade500, fontSize: 16),
                  ),
                ],
              ),
            )
          : ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: flows.length,
              itemBuilder: (context, index) {
                final flow = flows[index];
                return Container(
                  margin: const EdgeInsets.only(bottom: 14),
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
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 10,
                    ),
                    onTap: () => _openEditor(flow),
                    title: Text(
                      flow.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
                    subtitle: Text(
                      '${flow.steps.length} message step${flow.steps.length == 1 ? '' : 's'} • ${flow.isActive ? 'Active' : 'Paused'}',
                    ),
                    trailing: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color:
                                (flow.isActive ? Colors.green : Colors.orange)
                                    .withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            flow.isActive ? 'Active' : 'Paused',
                            style: TextStyle(
                              color: flow.isActive
                                  ? Colors.green
                                  : Colors.orange,
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                        IconButton(
                          onPressed: () {
                            _storage.remove(flow.id);
                            setState(() {});
                          },
                          icon: const Icon(
                            Icons.delete_outline,
                            color: Colors.redAccent,
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
    );
  }
}
