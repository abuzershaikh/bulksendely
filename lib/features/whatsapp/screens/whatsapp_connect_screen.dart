import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:autoreply/features/whatsapp/screens/group_message_screen.dart';
import 'package:autoreply/features/whatsapp/services/whatsapp_api_service.dart';
import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

// ── Premium Blue Theme Colors ──
class _AppColors {
  static const Color primaryBlue = Color(0xFF1565C0);
  static const Color deepBlue = Color(0xFF0D47A1);
  static const Color accentBlue = Color(0xFF2196F3);
  static const Color lightBlue = Color(0xFF42A5F5);
  static const Color surfaceBlue = Color(0xFFE3F2FD);
  static const Color paleBlue = Color(0xFFF5F9FF);
  static const Color darkText = Color(0xFF1A2035);
  static const Color bodyText = Color(0xFF4A5568);
  static const Color mutedText = Color(0xFF94A3B8);
  static const Color border = Color(0xFFE2E8F0);
  static const Color cardBg = Colors.white;
  static const Color success = Color(0xFF25D366);
  static const Color warning = Color(0xFFFFA726);
  static const Color danger = Color(0xFFEF5350);
}

class WhatsappConnectScreen extends StatefulWidget {
  const WhatsappConnectScreen({super.key});

  @override
  State<WhatsappConnectScreen> createState() => _WhatsappConnectScreenState();
}

class _WhatsappConnectScreenState extends State<WhatsappConnectScreen>
    with TickerProviderStateMixin {
  static const int _maxLinkedInstances = 10;
  static const List<String> _dialCodes = <String>[
    '+91',
    '+1',
    '+44',
    '+61',
    '+65',
    '+92',
    '+966',
    '+971',
  ];

  final _phoneCtrl = TextEditingController();
  final _api = WhatsappApiService();
  final SubscriptionService _subscriptionService = SubscriptionService.instance;
  bool _isLoading = false;
  bool _isManagingSession = false;
  bool _isCheckingStatus = false;
  bool _showPairingForm = true;
  WhatsappPairingSession? _pairingSession;
  List<ActiveWhatsappInstance> _linkedInstances = const [];
  ActiveWhatsappInstance? _linkedInstance;
  Timer? _statusTimer;
  int _statusPollCount = 0;
  String _selectedDialCode = '+91';

  String _displayLinkedNumber(ActiveWhatsappInstance instance) {
    return WhatsappApiService.formatLinkedNumber(instance.linkedNumber) ??
        instance.instanceId;
  }

  bool _isConnectedInstance(ActiveWhatsappInstance instance) {
    return instance.connected ||
        instance.healthy ||
        (instance.wsState ?? '').toUpperCase() == 'OPEN';
  }

  late AnimationController _pulseCtrl;
  late AnimationController _slideCtrl;
  late Animation<double> _pulseAnim;

  @override
  void initState() {
    super.initState();
    _pulseCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2000),
    )..repeat(reverse: true);
    _pulseAnim = Tween<double>(
      begin: 0.95,
      end: 1.05,
    ).animate(CurvedAnimation(parent: _pulseCtrl, curve: Curves.easeInOut));

    _slideCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );

    Future.delayed(const Duration(milliseconds: 200), () {
      if (mounted) _slideCtrl.forward();
    });
    _loadLinkedInstances();
  }

  @override
  void dispose() {
    _pulseCtrl.dispose();
    _slideCtrl.dispose();
    _statusTimer?.cancel();
    _phoneCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadLinkedInstances() async {
    try {
      final linked = await _api.getSavedOrActiveInstance();
      var instances = await _api.getInstances();
      if (instances.isEmpty && linked != null && linked.instanceId.isNotEmpty) {
        instances = [linked];
      }

      final connectedInstances = instances
          .where(_isConnectedInstance)
          .toList(growable: false);

      ActiveWhatsappInstance? selected = linked;
      if (selected != null && selected.instanceId.isNotEmpty) {
        final selectedId = selected.instanceId;
        try {
          selected = connectedInstances.firstWhere(
            (item) => item.instanceId == selectedId,
          );
        } catch (_) {
          selected = connectedInstances.isNotEmpty
              ? connectedInstances.first
              : null;
        }
      } else {
        selected = connectedInstances.isNotEmpty
            ? connectedInstances.first
            : null;
      }

      if (!mounted) return;
      setState(() {
        _linkedInstances = connectedInstances;
        _linkedInstance = selected;
        if (connectedInstances.isNotEmpty) {
          _showPairingForm = false;
        } else {
          _showPairingForm = true;
        }
      });
    } catch (_) {
      ActiveWhatsappInstance? fallback;
      try {
        fallback = await _api.getSavedOrActiveInstance();
      } catch (_) {
        fallback = null;
      }
      if (!mounted) return;
      setState(() {
        _linkedInstances =
            fallback != null &&
                fallback.instanceId.isNotEmpty &&
                _isConnectedInstance(fallback)
            ? [fallback]
            : const [];
        _linkedInstance = _linkedInstances.isNotEmpty
            ? _linkedInstances.first
            : null;
        _showPairingForm = _linkedInstances.isEmpty;
      });
    }
  }

  void _getPairingCode() async {
    if (_linkedInstances.length >= _maxLinkedInstances) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Maximum 10 WhatsApp numbers are already linked'),
        ),
      );
      return;
    }

    final phone = _phoneCtrl.text.trim();
    if (phone.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Row(
            children: [
              Icon(Icons.warning_amber_rounded, color: Colors.white, size: 18),
              SizedBox(width: 8),
              Text(
                'Please enter your WhatsApp number',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
            ],
          ),
          backgroundColor: _AppColors.danger,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          margin: const EdgeInsets.all(16),
        ),
      );
      return;
    }

    FocusScope.of(context).unfocus();

    setState(() {
      _isLoading = true;
      _pairingSession = null;
      _isCheckingStatus = false;
    });

    try {
      final session = await _api.requestPairingCode(
        phone,
        countryCode: _selectedDialCode,
      );
      if (mounted) {
        setState(() {
          _isLoading = false;
          _pairingSession = session;
          _isCheckingStatus = true;
        });
        _startStatusPolling();
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Failed to get code: $e'),
            backgroundColor: _AppColors.danger,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            margin: const EdgeInsets.all(16),
          ),
        );
      }
    }
  }

  void _startStatusPolling() {
    _statusTimer?.cancel();
    _statusPollCount = 0;
    _statusTimer = Timer.periodic(const Duration(seconds: 3), (timer) async {
      final session = _pairingSession;
      if (!mounted || session == null) {
        timer.cancel();
        return;
      }

      _statusPollCount++;
      WhatsappConnectionStatus status;
      try {
        status = await _api.getConnectionStatus(session.instanceId);
      } catch (_) {
        if (_statusPollCount < 60) {
          return;
        }
        status = const WhatsappConnectionStatus(connected: false);
      }
      if (!mounted) return;

      if (status.connected) {
        timer.cancel();
        setState(() {
          _isCheckingStatus = false;
        });
        await _api.saveLinkedInstance(
          instanceId: session.instanceId,
          linkedNumber: status.wid ?? session.phone,
          linkedName: status.name,
        );
        await _subscriptionService.updateWhatsappSession(
          sessionId: session.instanceId,
        );
        await _loadLinkedInstances();
        if (!mounted) return;
        _phoneCtrl.clear();
        setState(() {
          _pairingSession = null;
          _showPairingForm = false;
        });
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const Icon(
                  Icons.check_circle_rounded,
                  color: Colors.white,
                  size: 18,
                ),
                const SizedBox(width: 8),
                Text(
                  'WhatsApp connected${status.name != null ? ': ${status.name}' : ''}',
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
              ],
            ),
            backgroundColor: _AppColors.success,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            margin: const EdgeInsets.all(16),
          ),
        );
        return;
      }

      if (_statusPollCount >= 60) {
        timer.cancel();
        setState(() {
          _isCheckingStatus = false;
        });
      }
    });
  }

  void _copyCode() {
    if (_pairingSession != null && _pairingSession!.pairCode.isNotEmpty) {
      final code = _pairingSession!.pairCode
          .replaceAll(RegExp(r'[^a-zA-Z0-9]'), '')
          .toUpperCase();
      Clipboard.setData(ClipboardData(text: code));
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Row(
            children: [
              Icon(Icons.check_circle, color: Colors.white, size: 18),
              SizedBox(width: 8),
              Text(
                'Code copied!',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
            ],
          ),
          backgroundColor: _AppColors.primaryBlue,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          margin: const EdgeInsets.all(16),
          duration: const Duration(seconds: 1),
        ),
      );
    }
  }

  Future<void> _logoutSession([ActiveWhatsappInstance? instance]) async {
    final instanceId =
        instance?.instanceId ?? _linkedInstance?.instanceId ?? '';
    if (instanceId.isEmpty) return;

    setState(() => _isManagingSession = true);
    try {
      await _api.logoutInstance(instanceId);
      await _subscriptionService.clearWhatsappSession();
      _statusTimer?.cancel();
      if (!mounted) return;
      setState(() {
        _linkedInstances = _linkedInstances
            .where((item) => item.instanceId != instanceId)
            .toList(growable: false);
        _linkedInstance = null;
        _pairingSession = null;
        _isCheckingStatus = false;
        _showPairingForm = _linkedInstances.isEmpty;
      });
      await _loadLinkedInstances();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('WhatsApp logged out and local session cleared'),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Logout failed: $e')));
    } finally {
      if (mounted) {
        setState(() => _isManagingSession = false);
      }
    }
  }

  Future<void> _reconnectSession([ActiveWhatsappInstance? instance]) async {
    final instanceId =
        instance?.instanceId ?? _linkedInstance?.instanceId ?? '';
    if (instanceId.isEmpty) return;

    setState(() => _isManagingSession = true);
    try {
      await _api.reconnectInstance(instanceId);
      await Future<void>.delayed(const Duration(seconds: 2));
      await _loadLinkedInstances();
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Reconnect request sent')));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Reconnect failed: $e')));
    } finally {
      if (mounted) {
        setState(() => _isManagingSession = false);
      }
    }
  }

  Future<void> _resetSession([ActiveWhatsappInstance? instance]) async {
    final instanceId =
        instance?.instanceId ?? _linkedInstance?.instanceId ?? '';
    if (instanceId.isEmpty) return;

    setState(() {
      _isManagingSession = true;
      _pairingSession = null;
      _isCheckingStatus = false;
    });

    try {
      final resetSession = await _api.resetInstanceAndRequestPairing(
        instanceId,
      );

      // Best-effort cleanup only. Reset success should not fail because a
      // local persistence step or Firestore update throws.
      try {
        await _subscriptionService.clearWhatsappSession();
      } catch (_) {}

      try {
        await _loadLinkedInstances();
      } catch (_) {}

      if (!mounted) return;
      setState(() {
        _linkedInstance = null;
        _pairingSession = resetSession.instanceId.isNotEmpty
            ? resetSession
            : null;
        _showPairingForm = true;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Session cleared. Generate a new pairing code to relogin.',
          ),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Reset failed: $e')));
    } finally {
      if (mounted) {
        setState(() => _isManagingSession = false);
      }
    }
  }

  Future<void> _cleanupUnlinkedInstances() async {
    setState(() {
      _isManagingSession = true;
      _isLoading = false;
    });

    try {
      await _api.cleanupAllInstances();
      await _subscriptionService.clearWhatsappSession();
      _statusTimer?.cancel();
      if (!mounted) return;
      setState(() {
        _linkedInstances = const [];
        _linkedInstance = null;
        _pairingSession = null;
        _isCheckingStatus = false;
        _showPairingForm = true;
      });
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Clear successful')));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Refresh failed: $e')));
    } finally {
      if (mounted) {
        setState(() => _isManagingSession = false);
      }
    }
  }

  void _showLinkInfoDialog() {
    showDialog(
      context: context,
      builder: (context) => Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22)),
        backgroundColor: Colors.white,
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Header icon
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [Color(0xFF0D47A1), Color(0xFF1976D2)],
                  ),
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: _AppColors.primaryBlue.withValues(alpha: 0.25),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: const Icon(
                  Icons.link_rounded,
                  color: Colors.white,
                  size: 28,
                ),
              ),
              const SizedBox(height: 16),
              const Text(
                'How to Link WhatsApp',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                  color: _AppColors.darkText,
                  letterSpacing: -0.3,
                ),
              ),
              const SizedBox(height: 6),
              const Text(
                'Follow these steps to connect your WhatsApp account',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 12,
                  color: _AppColors.mutedText,
                  fontWeight: FontWeight.w400,
                ),
              ),
              const SizedBox(height: 20),

              // Steps
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: _AppColors.paleBlue,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: _AppColors.surfaceBlue),
                ),
                child: const Column(
                  children: [
                    _InfoStepRow(
                      number: '1',
                      title: 'Enter Phone Number',
                      desc:
                          'Type your WhatsApp number with country code (e.g. +91)',
                    ),
                    SizedBox(height: 12),
                    _InfoStepRow(
                      number: '2',
                      title: 'Get Pairing Code',
                      desc: 'Tap the button to generate a unique pairing code',
                    ),
                    SizedBox(height: 12),
                    _InfoStepRow(
                      number: '3',
                      title: 'Open WhatsApp',
                      desc: 'Go to Settings → Linked Devices → Link a Device',
                    ),
                    SizedBox(height: 12),
                    _InfoStepRow(
                      number: '4',
                      title: 'Enter Code',
                      desc: 'Use "Link with Phone Number" and enter the code',
                    ),
                    SizedBox(height: 12),
                    _InfoStepRow(
                      number: '5',
                      title: 'Done!',
                      desc: 'Your WhatsApp will be connected automatically',
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),

              // Note
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFFFF8E1),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFFFFE0B2)),
                ),
                child: const Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(
                      Icons.lightbulb_outline_rounded,
                      color: Color(0xFFFFA726),
                      size: 16,
                    ),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Your WhatsApp session stays active even when this app is closed. You can manage it anytime.',
                        style: TextStyle(
                          fontSize: 11,
                          color: Color(0xFF5D4037),
                          fontWeight: FontWeight.w500,
                          height: 1.4,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 18),

              // Close button
              SizedBox(
                width: double.infinity,
                height: 46,
                child: ElevatedButton(
                  onPressed: () => Navigator.pop(context),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _AppColors.primaryBlue,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    elevation: 0,
                  ),
                  child: const Text(
                    'Got it',
                    style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final hasPairCode = (_pairingSession?.pairCode ?? '').isNotEmpty;

    final statusBarHeight = MediaQuery.of(context).padding.top;

    return Scaffold(
      backgroundColor: _AppColors.paleBlue,
      floatingActionButton: _linkedInstances.length >= _maxLinkedInstances
          ? null
          : FloatingActionButton(
              onPressed: () {
                setState(() {
                  _showPairingForm = true;
                  _pairingSession = null;
                  _isCheckingStatus = false;
                });
              },
              backgroundColor: _AppColors.primaryBlue,
              foregroundColor: Colors.white,
              child: const Icon(Icons.add_rounded),
            ),
      body: Column(
        children: [
          // ── Premium Blue App Bar (extends behind status bar) ──
          Container(
            padding: EdgeInsets.fromLTRB(16, statusBarHeight + 12, 16, 16),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  Color(0xFF0D47A1),
                  Color(0xFF1565C0),
                  Color(0xFF1976D2),
                ],
              ),
              borderRadius: const BorderRadius.only(
                bottomLeft: Radius.circular(24),
                bottomRight: Radius.circular(24),
              ),
              boxShadow: [
                BoxShadow(
                  color: _AppColors.primaryBlue.withValues(alpha: 0.25),
                  blurRadius: 20,
                  offset: const Offset(0, 8),
                ),
              ],
            ),
            child: Row(
              children: [
                Material(
                  color: Colors.transparent,
                  child: InkWell(
                    onTap: () => Navigator.pop(context),
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.2),
                        ),
                      ),
                      child: const Icon(
                        Icons.arrow_back_ios_new_rounded,
                        color: Colors.white,
                        size: 16,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Connect WhatsApp',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 19,
                          fontWeight: FontWeight.w700,
                          letterSpacing: -0.3,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Link your number to start messaging',
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.7),
                          fontSize: 12,
                          fontWeight: FontWeight.w400,
                        ),
                      ),
                    ],
                  ),
                ),
                Material(
                  color: Colors.transparent,
                  child: InkWell(
                    onTap: _showLinkInfoDialog,
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.2),
                        ),
                      ),
                      child: const Icon(
                        Icons.info_outline_rounded,
                        color: Colors.white,
                        size: 17,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),

          // ── Content ──
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 18),
              child: SlideTransition(
                position:
                    Tween<Offset>(
                      begin: const Offset(0, 0.3),
                      end: Offset.zero,
                    ).animate(
                      CurvedAnimation(
                        parent: _slideCtrl,
                        curve: Curves.easeOutCubic,
                      ),
                    ),
                child: FadeTransition(
                  opacity: _slideCtrl,
                  child: Column(
                    children: [
                      const SizedBox(height: 22),

                      // ── WhatsApp Logo ──
                      AnimatedBuilder(
                        animation: _pulseAnim,
                        builder: (context, child) {
                          return Transform.scale(
                            scale: _pulseAnim.value,
                            child: child,
                          );
                        },
                        child: Container(
                          width: 80,
                          height: 80,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            gradient: const LinearGradient(
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                              colors: [Color(0xFF1565C0), Color(0xFF42A5F5)],
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: _AppColors.primaryBlue.withValues(
                                  alpha: 0.3,
                                ),
                                blurRadius: 30,
                                spreadRadius: 4,
                              ),
                            ],
                          ),
                          child: const Icon(
                            Icons.chat_rounded,
                            color: Colors.white,
                            size: 38,
                          ),
                        ),
                      ),

                      const SizedBox(height: 22),

                      // ── Current Session Card ──
                      _buildLinkedSummaryCard(),

                      if (_linkedInstances.isNotEmpty) ...[
                        const SizedBox(height: 16),
                        ..._linkedInstances.map(_buildSessionCard),
                        const SizedBox(height: 16),
                      ],

                      // ── Phone Number Input Card ──
                      if (_showPairingForm ||
                          _linkedInstances.isEmpty ||
                          _pairingSession != null)
                        _buildPhoneInputCard(),

                      // ── Pairing Code Result ──
                      if (_pairingSession != null) ...[
                        const SizedBox(height: 18),
                        _buildPairingCodeCard(hasPairCode),
                      ],

                      if (_linkedInstances.isEmpty) ...[
                        const SizedBox(height: 18),
                        _buildRefreshSessionButton(),
                      ],

                      const SizedBox(height: 40),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ── Current Session Card ──
  Widget _buildLinkedSummaryCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: _AppColors.border),
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: _AppColors.surfaceBlue,
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(
              Icons.device_hub_rounded,
              color: _AppColors.primaryBlue,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Linked Numbers: ${_linkedInstances.length}/$_maxLinkedInstances',
                  style: const TextStyle(
                    color: _AppColors.darkText,
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  _linkedInstances.isEmpty
                      ? 'Add your first WhatsApp number'
                      : 'All linked sessions stay active in the background',
                  style: const TextStyle(
                    color: _AppColors.mutedText,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSessionCard(ActiveWhatsappInstance instance) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: _AppColors.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 12,
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
                width: 32,
                height: 32,
                decoration: BoxDecoration(
                  color: _AppColors.surfaceBlue,
                  borderRadius: BorderRadius.circular(9),
                ),
                child: const Icon(
                  Icons.link_rounded,
                  color: _AppColors.primaryBlue,
                  size: 17,
                ),
              ),
              const SizedBox(width: 10),
              const Text(
                'Linked Session',
                style: TextStyle(
                  color: _AppColors.darkText,
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  letterSpacing: -0.2,
                ),
              ),
              const Spacer(),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: _AppColors.success.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(6),
                  border: Border.all(
                    color: _AppColors.success.withValues(alpha: 0.3),
                  ),
                ),
                child: const Text(
                  'LINKED',
                  style: TextStyle(
                    color: _AppColors.success,
                    fontSize: 9,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 1,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: _AppColors.paleBlue,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: _AppColors.border),
            ),
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [_AppColors.primaryBlue, _AppColors.accentBlue],
                    ),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.person_rounded,
                    color: Colors.white,
                    size: 18,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _displayLinkedNumber(instance),
                        style: const TextStyle(
                          color: _AppColors.darkText,
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          letterSpacing: 0.3,
                        ),
                      ),
                      if ((instance.linkedName ?? '').isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 2),
                          child: Text(
                            instance.linkedName!,
                            style: const TextStyle(
                              color: _AppColors.mutedText,
                              fontSize: 12,
                              fontWeight: FontWeight.w400,
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            child: _modernSessionButton(
              label: 'Open Groups',
              icon: Icons.groups_rounded,
              color: _AppColors.success,
              onTap: _isManagingSession
                  ? null
                  : () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) =>
                              GroupMessageScreen(instance: instance),
                        ),
                      );
                    },
            ),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: _modernSessionButton(
                  label: 'Reconnect',
                  icon: Icons.refresh_rounded,
                  color: _AppColors.primaryBlue,
                  onTap: _isManagingSession
                      ? null
                      : () => _reconnectSession(instance),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _modernSessionButton(
                  label: 'Logout',
                  icon: Icons.logout_rounded,
                  color: _AppColors.warning,
                  onTap: _isManagingSession
                      ? null
                      : () => _logoutSession(instance),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _modernSessionButton(
                  label: 'Reset',
                  icon: Icons.delete_sweep_rounded,
                  color: _AppColors.danger,
                  onTap: _isManagingSession
                      ? null
                      : () => _resetSession(instance),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _modernSessionButton({
    required String label,
    required IconData icon,
    required Color color,
    required VoidCallback? onTap,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: Container(
          height: 40,
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.08),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: color.withValues(alpha: 0.25)),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 14, color: color),
              const SizedBox(width: 4),
              Text(
                label,
                style: TextStyle(
                  color: color,
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // ── Phone Number Input Card ──
  Widget _buildPhoneInputCard() {
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: _AppColors.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 20,
            offset: const Offset(0, 6),
          ),
          BoxShadow(
            color: _AppColors.primaryBlue.withValues(alpha: 0.04),
            blurRadius: 40,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Card Header with blue accent
          Container(
            width: double.infinity,
            padding: const EdgeInsets.fromLTRB(18, 16, 18, 14),
            decoration: BoxDecoration(
              color: _AppColors.surfaceBlue.withValues(alpha: 0.5),
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(20),
                topRight: Radius.circular(20),
              ),
              border: Border(
                bottom: BorderSide(
                  color: _AppColors.border.withValues(alpha: 0.5),
                ),
              ),
            ),
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [_AppColors.deepBlue, _AppColors.primaryBlue],
                    ),
                    borderRadius: BorderRadius.circular(10),
                    boxShadow: [
                      BoxShadow(
                        color: _AppColors.primaryBlue.withValues(alpha: 0.3),
                        blurRadius: 8,
                        offset: const Offset(0, 3),
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.phone_android_rounded,
                    color: Colors.white,
                    size: 18,
                  ),
                ),
                const SizedBox(width: 12),
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Phone Number',
                        style: TextStyle(
                          color: _AppColors.darkText,
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                          letterSpacing: -0.2,
                        ),
                      ),
                      SizedBox(height: 1),
                      Text(
                        'Select country code, then enter the mobile number',
                        style: TextStyle(
                          color: _AppColors.mutedText,
                          fontSize: 11,
                          fontWeight: FontWeight.w400,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // Phone Input Field
          Padding(
            padding: const EdgeInsets.fromLTRB(18, 16, 18, 0),
            child: Row(
              children: [
                Expanded(
                  flex: 2,
                  child: Container(
                    height: 54,
                    padding: const EdgeInsets.symmetric(horizontal: 14),
                    decoration: BoxDecoration(
                      color: _AppColors.paleBlue,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: _AppColors.border, width: 1.5),
                    ),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<String>(
                        value: _selectedDialCode,
                        isExpanded: true,
                        dropdownColor: Colors.white,
                        icon: const Icon(
                          Icons.keyboard_arrow_down_rounded,
                          color: _AppColors.primaryBlue,
                        ),
                        style: const TextStyle(
                          color: _AppColors.darkText,
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                        ),
                        items: _dialCodes
                            .map(
                              (code) => DropdownMenuItem<String>(
                                value: code,
                                child: Text(code),
                              ),
                            )
                            .toList(growable: false),
                        onChanged: (_isLoading || _isManagingSession)
                            ? null
                            : (value) {
                                if (value == null) return;
                                setState(() {
                                  _selectedDialCode = value;
                                });
                              },
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  flex: 5,
                  child: Container(
                    height: 54,
                    padding: const EdgeInsets.symmetric(horizontal: 14),
                    decoration: BoxDecoration(
                      color: _AppColors.paleBlue,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: _AppColors.border, width: 1.5),
                    ),
                    alignment: Alignment.centerLeft,
                    child: TextField(
                      controller: _phoneCtrl,
                      keyboardType: TextInputType.phone,
                      style: const TextStyle(
                        color: _AppColors.darkText,
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        letterSpacing: 1.2,
                      ),
                      cursorColor: _AppColors.primaryBlue,
                      cursorWidth: 2,
                      decoration: const InputDecoration(
                        hintText: '98765 43210',
                        hintStyle: TextStyle(
                          color: _AppColors.mutedText,
                          fontSize: 15,
                          fontWeight: FontWeight.w400,
                          letterSpacing: 0.5,
                        ),
                        border: InputBorder.none,
                        contentPadding: EdgeInsets.zero,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),

          Padding(
            padding: const EdgeInsets.fromLTRB(18, 8, 18, 0),
            child: Text(
              'Select country code and enter only the mobile number.',
              style: TextStyle(
                color: _AppColors.mutedText.withValues(alpha: 0.95),
                fontSize: 11,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),

          const SizedBox(height: 16),

          // Submit Button — Premium Blue
          Padding(
            padding: const EdgeInsets.fromLTRB(18, 0, 18, 18),
            child: SizedBox(
              width: double.infinity,
              height: 52,
              child: ElevatedButton(
                onPressed: (_isLoading || _isManagingSession)
                    ? null
                    : _getPairingCode,
                style: ElevatedButton.styleFrom(
                  backgroundColor: _AppColors.primaryBlue,
                  foregroundColor: Colors.white,
                  disabledBackgroundColor: _AppColors.primaryBlue.withValues(
                    alpha: 0.4,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                  elevation: 0,
                  shadowColor: Colors.transparent,
                ),
                child: _isLoading
                    ? Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(
                              color: Colors.white.withValues(alpha: 0.9),
                              strokeWidth: 2,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Text(
                            'Connecting...',
                            style: TextStyle(
                              fontWeight: FontWeight.w600,
                              fontSize: 14,
                              color: Colors.white.withValues(alpha: 0.9),
                            ),
                          ),
                        ],
                      )
                    : const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.link_rounded, size: 20),
                          SizedBox(width: 10),
                          Text(
                            'Get Pairing Code',
                            style: TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: 15,
                              letterSpacing: 0.3,
                            ),
                          ),
                        ],
                      ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ── Pairing Code Card ──
  Widget _buildRefreshSessionButton() {
    final isDisabled = _isManagingSession || _isLoading;

    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: SizedBox(
        width: double.infinity,
        height: 50,
        child: OutlinedButton.icon(
          onPressed: isDisabled ? null : _cleanupUnlinkedInstances,
          style: OutlinedButton.styleFrom(
            foregroundColor: _AppColors.primaryBlue,
            side: BorderSide(
              color: _AppColors.primaryBlue.withValues(alpha: 0.28),
              width: 1.4,
            ),
            backgroundColor: Colors.white,
            disabledForegroundColor: _AppColors.primaryBlue.withValues(
              alpha: 0.45,
            ),
            disabledBackgroundColor: Colors.white,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
            ),
          ),
          icon: isDisabled
              ? SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: _AppColors.primaryBlue.withValues(alpha: 0.75),
                  ),
                )
              : const Icon(Icons.refresh_rounded, size: 20),
          label: const Text(
            'Refresh Session',
            style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700),
          ),
        ),
      ),
    );
  }

  Widget _buildPairingCodeCard(bool hasPairCode) {
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0.0, end: 1.0),
      duration: const Duration(milliseconds: 500),
      curve: Curves.easeOutBack,
      builder: (context, value, child) {
        return Transform.scale(
          scale: value,
          child: Opacity(opacity: value.clamp(0.0, 1.0), child: child),
        );
      },
      child: Container(
        width: double.infinity,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: _AppColors.border),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 20,
              offset: const Offset(0, 6),
            ),
            BoxShadow(
              color: _AppColors.primaryBlue.withValues(alpha: 0.04),
              blurRadius: 40,
              offset: const Offset(0, 12),
            ),
          ],
        ),
        child: Column(
          children: [
            // Status bar — checking connection
            if (_isCheckingStatus)
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(
                  vertical: 10,
                  horizontal: 18,
                ),
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    colors: [Color(0xFF0D47A1), Color(0xFF1976D2)],
                  ),
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20),
                  ),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    SizedBox(
                      width: 13,
                      height: 13,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white.withValues(alpha: 0.9),
                      ),
                    ),
                    const SizedBox(width: 10),
                    const Text(
                      'Waiting for confirmation...',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
                        letterSpacing: 0.2,
                      ),
                    ),
                  ],
                ),
              ),

            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
              child: Column(
                children: [
                  // Header row — icon + title inline
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        width: 28,
                        height: 28,
                        decoration: BoxDecoration(
                          color: _AppColors.surfaceBlue,
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.check_circle_rounded,
                          color: _AppColors.primaryBlue,
                          size: 16,
                        ),
                      ),
                      const SizedBox(width: 8),
                      const Text(
                        'Your Pairing Code',
                        style: const TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: _AppColors.darkText,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // ── Compact Pairing Code ──
                  if (hasPairCode)
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 20,
                        vertical: 10,
                      ),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                          colors: [Color(0xFF0D47A1), Color(0xFF1976D2)],
                        ),
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: [
                          BoxShadow(
                            color: _AppColors.primaryBlue.withValues(
                              alpha: 0.2,
                            ),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Text(
                        _pairingSession!.pairCode,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 20,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 1.4,
                          fontFamily: 'monospace',
                        ),
                      ),
                    ),

                  if (!hasPairCode)
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFFF8E1),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: const Color(0xFFFFE0B2)),
                      ),
                      child: const Row(
                        children: [
                          Icon(
                            Icons.hourglass_top_rounded,
                            color: _AppColors.warning,
                            size: 15,
                          ),
                          SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'Pair code not available yet. Please try again.',
                              style: TextStyle(
                                color: Color(0xFF5D4037),
                                fontSize: 11,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),

                  const SizedBox(height: 10),

                  // Instance ID + Copy inline
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: _AppColors.paleBlue,
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(color: _AppColors.border),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(
                              Icons.fingerprint_rounded,
                              size: 11,
                              color: _AppColors.mutedText,
                            ),
                            const SizedBox(width: 4),
                            ConstrainedBox(
                              constraints: const BoxConstraints(maxWidth: 120),
                              child: Text(
                                _pairingSession!.instanceId,
                                style: const TextStyle(
                                  fontSize: 9,
                                  color: _AppColors.mutedText,
                                  fontWeight: FontWeight.w600,
                                ),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      ),
                      if (hasPairCode) ...[
                        const SizedBox(width: 8),
                        Material(
                          color: Colors.transparent,
                          child: InkWell(
                            onTap: _copyCode,
                            borderRadius: BorderRadius.circular(6),
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 4,
                              ),
                              decoration: BoxDecoration(
                                color: _AppColors.surfaceBlue,
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(
                                  color: _AppColors.primaryBlue.withValues(
                                    alpha: 0.2,
                                  ),
                                ),
                              ),
                              child: const Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(
                                    Icons.copy_rounded,
                                    size: 11,
                                    color: _AppColors.primaryBlue,
                                  ),
                                  SizedBox(width: 4),
                                  Text(
                                    'Copy',
                                    style: TextStyle(
                                      fontWeight: FontWeight.w700,
                                      fontSize: 11,
                                      color: _AppColors.primaryBlue,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),

                  const SizedBox(height: 12),

                  // Compact Steps
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: _AppColors.paleBlue,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: _AppColors.surfaceBlue),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Row(
                          children: [
                            Icon(
                              Icons.format_list_numbered_rounded,
                              size: 13,
                              color: _AppColors.primaryBlue,
                            ),
                            SizedBox(width: 6),
                            Text(
                              'How to link',
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: _AppColors.primaryBlue,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        _ModernStepRow(
                          number: '1',
                          text: 'Open WhatsApp → Linked Devices',
                        ),
                        const SizedBox(height: 5),
                        _ModernStepRow(
                          number: '2',
                          text: 'Tap "Link with Phone Number"',
                        ),
                        const SizedBox(height: 5),
                        _ModernStepRow(
                          number: '3',
                          text: 'Enter the code above',
                        ),
                      ],
                    ),
                  ),

                  if (!_isCheckingStatus) ...[
                    const SizedBox(height: 10),
                    Material(
                      color: Colors.transparent,
                      child: InkWell(
                        onTap: _pairingSession == null
                            ? null
                            : _startStatusPolling,
                        borderRadius: BorderRadius.circular(8),
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 14,
                            vertical: 7,
                          ),
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: _AppColors.border),
                          ),
                          child: const Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                Icons.refresh_rounded,
                                size: 14,
                                color: _AppColors.primaryBlue,
                              ),
                              SizedBox(width: 6),
                              Text(
                                'Check Connection',
                                style: TextStyle(
                                  fontWeight: FontWeight.w600,
                                  fontSize: 11,
                                  color: _AppColors.primaryBlue,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ModernStepRow extends StatelessWidget {
  final String number;
  final String text;
  const _ModernStepRow({required this.number, required this.text});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 20,
          height: 20,
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [_AppColors.deepBlue, _AppColors.primaryBlue],
            ),
            borderRadius: BorderRadius.circular(6),
          ),
          child: Center(
            child: Text(
              number,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 10,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            text,
            style: const TextStyle(
              fontSize: 12,
              color: _AppColors.bodyText,
              fontWeight: FontWeight.w500,
              height: 1.3,
            ),
          ),
        ),
      ],
    );
  }
}

class _InfoStepRow extends StatelessWidget {
  final String number;
  final String title;
  final String desc;
  const _InfoStepRow({
    required this.number,
    required this.title,
    required this.desc,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 22,
          height: 22,
          margin: const EdgeInsets.only(top: 1),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Color(0xFF0D47A1), Color(0xFF1565C0)],
            ),
            borderRadius: BorderRadius.circular(7),
          ),
          child: Center(
            child: Text(
              number,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 11,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: _AppColors.darkText,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                desc,
                style: const TextStyle(
                  fontSize: 11,
                  color: _AppColors.mutedText,
                  fontWeight: FontWeight.w400,
                  height: 1.3,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
