import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/features/campaigns/models/one_shot_range_model.dart';
import 'package:autoreply/features/campaigns/services/one_shot_storage.dart';
import 'package:autoreply/features/campaigns/widgets/one_shot_history_dialog.dart';
import 'package:flutter/material.dart';

class OneShotSettingsDialog extends StatefulWidget {
  final String? selectedGroupId;
  final String? selectedGroupName;
  final VoidCallback onSettingsUpdated;
  final VoidCallback onProgressReset;

  const OneShotSettingsDialog({
    super.key,
    this.selectedGroupId,
    this.selectedGroupName,
    required this.onSettingsUpdated,
    required this.onProgressReset,
  });

  @override
  State<OneShotSettingsDialog> createState() => _OneShotSettingsDialogState();
}

class _OneShotSettingsDialogState extends State<OneShotSettingsDialog> {
  final OneShotStorage _storage = OneShotStorage.instance;
  late int _selectedSize;

  final List<int> _presetSizes = [10, 20, 50, 100, 200, 500];

  @override
  void initState() {
    super.initState();
    _selectedSize = _storage.settings.rangeSize;
  }

  Future<void> _saveSize(int size) async {
    setState(() => _selectedSize = size);
    await _storage.updateSettings(
      OneShotSettings(rangeSize: size),
    );
    widget.onSettingsUpdated();
  }

  Future<void> _resetProgress() async {
    if (widget.selectedGroupId == null) return;
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.warning_amber_rounded, color: Colors.orange),
            SizedBox(width: 8),
            Text('Reset Progress?'),
          ],
        ),
        content: Text(
          'Are you sure you want to reset the sent markings for "${widget.selectedGroupName ?? 'this group'}"? All numbers will be marked as pending again.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              foregroundColor: Colors.white,
            ),
            child: const Text('Reset'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await _storage.resetGroupProgress(widget.selectedGroupId!);
      widget.onProgressReset();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Sending progress reset successfully.'),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.pop(context);
      }
    }
  }

  void _openHistory() {
    Navigator.pop(context);
    showDialog(
      context: context,
      builder: (ctx) => const OneShotHistoryDialog(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Header ──
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: AppColors.primaryBlue.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.settings_rounded, color: AppColors.primaryBlue),
                ),
                const SizedBox(width: 12),
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'One Shot Settings',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                      ),
                      Text(
                        'Configure range size & history',
                        style: TextStyle(fontSize: 12, color: Colors.grey),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close_rounded),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const Divider(height: 24),

            // ── Batch Size Selector ──
            const Text(
              'One Shot Range Size (Contacts per Batch)',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
            ),
            const SizedBox(height: 6),
            Text(
              'Choose how many contacts to send per series range:',
              style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
            ),
            const SizedBox(height: 12),

            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: _presetSizes.map((size) {
                final selected = _selectedSize == size;
                return ChoiceChip(
                  label: Text('$size Contacts'),
                  selected: selected,
                  selectedColor: AppColors.primaryBlue,
                  labelStyle: TextStyle(
                    color: selected ? Colors.white : Colors.black87,
                    fontWeight: selected ? FontWeight.bold : FontWeight.normal,
                  ),
                  onSelected: (val) {
                    if (val) _saveSize(size);
                  },
                );
              }).toList(),
            ),
            const SizedBox(height: 20),

            // ── Action Buttons ──
            if (widget.selectedGroupId != null) ...[
              OutlinedButton.icon(
                onPressed: _resetProgress,
                icon: const Icon(Icons.restart_alt_rounded, color: Colors.red),
                label: Text(
                  'Reset Sent Markings (${widget.selectedGroupName ?? 'Group'})',
                  style: const TextStyle(color: Colors.red),
                ),
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(44),
                  side: BorderSide(color: Colors.red.shade300),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 10),
            ],

            ElevatedButton.icon(
              onPressed: _openHistory,
              icon: const Icon(Icons.history_rounded),
              label: const Text('View Sent Range History'),
              style: ElevatedButton.styleFrom(
                minimumSize: const Size.fromHeight(44),
                backgroundColor: AppColors.primaryBlue,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
