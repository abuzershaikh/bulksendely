import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/features/autoreply/menu_reply/models/menu_reply_model.dart';
import 'package:autoreply/features/autoreply/menu_reply/screens/menu_reply_editor_screen.dart';
import 'package:autoreply/features/autoreply/menu_reply/storage/menu_reply_storage.dart';
import 'package:flutter/material.dart';

class MenuReplyLibraryScreen extends StatefulWidget {
  const MenuReplyLibraryScreen({super.key});

  @override
  State<MenuReplyLibraryScreen> createState() => _MenuReplyLibraryScreenState();
}

class _MenuReplyLibraryScreenState extends State<MenuReplyLibraryScreen> {
  final MenuReplyStorage _storage = MenuReplyStorage();

  @override
  void initState() {
    super.initState();
    _storage.load().then((_) {
      if (mounted) setState(() {});
    });
  }

  Future<void> _openEditor([MenuReplyFlow? flow]) async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => MenuReplyEditorScreen(existingFlow: flow),
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
          'Menu Reply',
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
          'New Menu',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
      ),
      body: flows.isEmpty
          ? Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      Icons.menu_book_rounded,
                      size: 76,
                      color: Colors.grey.shade300,
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'No menu reply flows yet',
                      style: TextStyle(color: Colors.grey.shade600, fontSize: 16),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Create a keyword-triggered WhatsApp menu with submenus, templates, and media replies.',
                      textAlign: TextAlign.center,
                      style: TextStyle(color: Colors.grey.shade500, fontSize: 13),
                    ),
                  ],
                ),
              ),
            )
          : ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: flows.length,
              itemBuilder: (context, index) {
                final flow = flows[index];
                final rootNode = flow.rootNode;
                final subtitleParts = [
                  if (flow.keywords.isNotEmpty) flow.keywords.join(', '),
                  '${flow.nodes.length} node${flow.nodes.length == 1 ? '' : 's'}',
                  '${rootNode?.rows.length ?? 0} root option${(rootNode?.rows.length ?? 0) == 1 ? '' : 's'}',
                ];

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
                  child: InkWell(
                    borderRadius: BorderRadius.circular(18),
                    onTap: () => _openEditor(flow),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 12,
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            flow.name,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            subtitleParts.join(' • '),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 10),
                          Wrap(
                            spacing: 6,
                            runSpacing: 6,
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 4,
                                ),
                                decoration: BoxDecoration(
                                  color: (flow.isActive ? Colors.green : Colors.orange)
                                      .withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(999),
                                ),
                                child: Text(
                                  flow.isActive ? 'Active' : 'Paused',
                                  style: TextStyle(
                                    color: flow.isActive ? Colors.green : Colors.orange,
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                              _CompactActionButton(
                                icon: Icons.copy_rounded,
                                onTap: () {
                                  _storage.duplicate(flow);
                                  setState(() {});
                                },
                              ),
                              _CompactActionButton(
                                icon: Icons.delete_outline,
                                color: Colors.redAccent,
                                onTap: () {
                                  _storage.remove(flow.id);
                                  setState(() {});
                                },
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
    );
  }
}

class _CompactActionButton extends StatelessWidget {
  final IconData icon;
  final Color? color;
  final VoidCallback onTap;

  const _CompactActionButton({
    required this.icon,
    required this.onTap,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(999),
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: (color ?? Colors.black).withValues(alpha: 0.05),
          borderRadius: BorderRadius.circular(999),
        ),
        child: Icon(icon, size: 18, color: color ?? Colors.black87),
      ),
    );
  }
}
