import 'package:autoreply/features/autoreply/keyword_reply/models/keyword_reply_model.dart';
import 'package:autoreply/features/autoreply/keyword_reply/screens/keyword_reply_editor_screen.dart';
import 'package:autoreply/features/autoreply/keyword_reply/storage/keyword_reply_storage.dart';
import 'package:autoreply/features/subscription/models/app_user_subscription.dart';
import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:flutter/material.dart';

class KeywordReplyLibraryScreen extends StatefulWidget {
  const KeywordReplyLibraryScreen({super.key});

  @override
  State<KeywordReplyLibraryScreen> createState() =>
      _KeywordReplyLibraryScreenState();
}

class _KeywordReplyLibraryScreenState extends State<KeywordReplyLibraryScreen> {
  final KeywordReplyStorage _storage = KeywordReplyStorage();
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

  Future<void> _openEditor([KeywordReplyFlow? flow]) async {
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
        builder: (context) => KeywordReplyEditorScreen(existingFlow: flow),
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
          'Keyword Reply',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openEditor(),
        backgroundColor: const Color(0xFF00A884), // WhatsApp-like green
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text(
          'New Flow',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
      ),
      body: flows.isEmpty
          ? Center(
              child: Text(
                'No keyword reply flows yet',
                style: TextStyle(color: Colors.grey.shade500, fontSize: 16),
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
                    onTap: () => _openEditor(flow),
                    title: Text(
                      flow.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
                    subtitle: Text(
                      '${flow.keywords.join(', ')} • ${flow.steps.length} reply step${flow.steps.length == 1 ? '' : 's'}',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    trailing: IconButton(
                      onPressed: () {
                        _storage.remove(flow.id);
                        setState(() {});
                      },
                      icon: const Icon(
                        Icons.delete_outline,
                        color: Colors.redAccent,
                      ),
                    ),
                  ),
                );
              },
            ),
    );
  }
}
