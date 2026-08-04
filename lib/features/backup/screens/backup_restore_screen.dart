import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/features/auth/google_auth/services/google_auth_service.dart';
import 'package:autoreply/features/backup/services/cloud_backup_service.dart';
import 'package:flutter/material.dart';

class BackupRestoreScreen extends StatefulWidget {
  const BackupRestoreScreen({super.key});

  @override
  State<BackupRestoreScreen> createState() => _BackupRestoreScreenState();
}

class _BackupRestoreScreenState extends State<BackupRestoreScreen> {
  final CloudBackupService _backupService = CloudBackupService.instance;
  final GoogleAuthService _authService = GoogleAuthService();

  Map<String, String> _userInfo = {};
  bool _autoBackupEnabled = true;
  bool _loading = false;
  String? _statusMessage;

  @override
  void initState() {
    super.initState();
    _loadInitialState();
  }

  Future<void> _loadInitialState() async {
    await _backupService.init();
    final info = await _authService.getUserInfo();
    final autoBackup = await _backupService.isAutoBackupEnabled();

    if (mounted) {
      setState(() {
        _userInfo = info;
        _autoBackupEnabled = autoBackup;
      });
    }
  }

  Future<void> _handleBackupNow() async {
    final email = _userInfo['email'];
    if (email == null || email.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please sign in with Email first to backup your data.'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    setState(() {
      _loading = true;
      _statusMessage = 'Exporting and uploading data to Cloud VPS...';
    });

    try {
      final success = await _backupService.performBackup();
      if (!mounted) return;

      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Cloud Backup Completed Successfully! ✅'),
            backgroundColor: Colors.green,
          ),
        );
        setState(() {
          _statusMessage = 'Backup completed at ${_formatTimestamp(DateTime.now())}';
        });
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Backup Failed: $e'),
          backgroundColor: Colors.red,
        ),
      );
      setState(() {
        _statusMessage = 'Backup failed: $e';
      });
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _handleRestoreNow() async {
    final email = _userInfo['email'];
    if (email == null || email.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please sign in with Email to restore your data.'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.cloud_download_rounded, color: AppColors.primaryBlue),
            SizedBox(width: 8),
            Text('Restore Cloud Data?'),
          ],
        ),
        content: Text(
          'This will download your latest cloud backup for "$email" and restore all Contacts, Chatbots, Templates & AutoReply rules into your app.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primaryBlue,
              foregroundColor: Colors.white,
            ),
            child: const Text('Restore Data'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() {
      _loading = true;
      _statusMessage = 'Fetching backup payload from server...';
    });

    try {
      final cloudData = await _backupService.fetchCloudBackup(userEmail: email);
      if (!mounted) return;

      if (cloudData == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('No Cloud Backup found for this email address.'),
            backgroundColor: Colors.orange,
          ),
        );
        setState(() => _statusMessage = 'No backup found on cloud server.');
        return;
      }

      setState(() => _statusMessage = 'Restoring Contacts, Templates & Chatbots...');
      final itemCount = await _backupService.restoreAllData(cloudData);
      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Restored $itemCount items successfully! 🎉'),
          backgroundColor: Colors.green,
        ),
      );
      setState(() {
        _statusMessage = 'Restored $itemCount items successfully.';
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Restore Failed: $e'),
          backgroundColor: Colors.red,
        ),
      );
      setState(() => _statusMessage = 'Restore failed: $e');
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  String _formatTimestamp(DateTime? dt) {
    if (dt == null) return 'Never';
    return '${dt.day}/${dt.month}/${dt.year} ${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    final email = _userInfo['email'] ?? '';
    final name = _userInfo['name'] ?? 'App User';

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: const Text(
          'Cloud Backup & Restore',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        elevation: 0.5,
      ),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          // ── User Account Card ──
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF004D40), Color(0xFF00796B)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.teal.withValues(alpha: 0.3),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 26,
                  backgroundColor: Colors.white24,
                  child: Text(
                    name.isNotEmpty ? name[0].toUpperCase() : 'U',
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 22,
                    ),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        name,
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        email.isNotEmpty ? email : 'Not Logged In',
                        style: const TextStyle(
                          color: Colors.white70,
                          fontSize: 13,
                        ),
                      ),
                    ],
                  ),
                ),
                const Icon(Icons.cloud_done_rounded, color: Colors.white, size: 28),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // ── Backup Status Banner ──
          ValueListenableBuilder<DateTime?>(
            valueListenable: _backupService.lastBackupNotifier,
            builder: (context, lastBackup, _) {
              final isSynced = lastBackup != null;
              return Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(
                    color: isSynced ? Colors.green.shade300 : Colors.orange.shade300,
                  ),
                ),
                child: Row(
                  children: [
                    Icon(
                      isSynced ? Icons.check_circle_rounded : Icons.info_outline_rounded,
                      color: isSynced ? Colors.green : Colors.orange,
                      size: 32,
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            isSynced ? 'Cloud Synced ✅' : 'Cloud Backup Pending ⚠️',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 15,
                              color: isSynced ? Colors.green.shade800 : Colors.orange.shade900,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Last Backup: ${_formatTimestamp(lastBackup)}',
                            style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
          const SizedBox(height: 24),

          // ── Actions Card ──
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.03),
                  blurRadius: 8,
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Backup & Restore Actions',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 6),
                Text(
                  'Includes Contacts, Chatbots, Templates, AutoReply Rules & OneShot Progress.',
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                ),
                const SizedBox(height: 20),

                // Backup Button
                ElevatedButton.icon(
                  onPressed: _loading ? null : _handleBackupNow,
                  icon: _loading
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.cloud_upload_rounded),
                  label: const Text('BACKUP NOW (Save to Cloud)'),
                  style: ElevatedButton.styleFrom(
                    minimumSize: const Size.fromHeight(48),
                    backgroundColor: const Color(0xFF004D40),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    elevation: 2,
                  ),
                ),
                const SizedBox(height: 12),

                // Restore Button
                OutlinedButton.icon(
                  onPressed: _loading ? null : _handleRestoreNow,
                  icon: const Icon(Icons.cloud_download_rounded, color: AppColors.primaryBlue),
                  label: const Text(
                    'RESTORE DATA (Download from Cloud)',
                    style: TextStyle(color: AppColors.primaryBlue, fontWeight: FontWeight.bold),
                  ),
                  style: OutlinedButton.styleFrom(
                    minimumSize: const Size.fromHeight(48),
                    side: const BorderSide(color: AppColors.primaryBlue, width: 1.5),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // ── Auto Backup Settings ──
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
            ),
            child: SwitchListTile(
              contentPadding: EdgeInsets.zero,
              activeColor: const Color(0xFF004D40),
              title: const Text(
                'Auto Backup on Changes',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
              ),
              subtitle: const Text(
                'Automatically sync data when adding contacts, chatbots, or templates',
                style: TextStyle(fontSize: 12, color: Colors.grey),
              ),
              value: _autoBackupEnabled,
              onChanged: (val) async {
                setState(() => _autoBackupEnabled = val);
                await _backupService.setAutoBackupEnabled(val);
              },
            ),
          ),

          if (_statusMessage != null) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: Colors.grey.shade300),
              ),
              child: Text(
                _statusMessage!,
                style: TextStyle(fontSize: 12, color: Colors.grey.shade800),
                textAlign: TextAlign.center,
              ),
            ),
          ],
        ],
      ),
    );
  }
}
