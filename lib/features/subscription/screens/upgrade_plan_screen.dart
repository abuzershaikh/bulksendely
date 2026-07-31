import 'dart:convert';

import 'package:autoreply/features/subscription/models/subscription_plan.dart';
import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:razorpay_flutter/razorpay_flutter.dart';

class UpgradePlanScreen extends StatefulWidget {
  const UpgradePlanScreen({super.key});

  @override
  State<UpgradePlanScreen> createState() => _UpgradePlanScreenState();
}

class _UpgradePlanScreenState extends State<UpgradePlanScreen>
    with SingleTickerProviderStateMixin {
  late Razorpay _razorpay;
  late AnimationController _shimmerCtrl;
  String _selectedPlanId = 'lifetime';
  bool _isLoading = false;

  String _userEmail = '';
  String _userName = '';
  String _userPhone = '';

  static const String _workerBaseUrl =
      'https://razorpay-worker.zestbizar.workers.dev';
  static const String _razorpayKeyId = 'rzp_live_RTIlARYCEbxgfS';

  int _amountForSelectedPlan() {
    switch (_selectedPlanId) {
      case 'monthly':
        return 499;
      case 'yearly':
        return 999;
      case 'lifetime':
      default:
        return 1999;
    }
  }

  String _paymentDescriptionForSelectedPlan() {
    switch (_selectedPlanId) {
      case 'monthly':
        return '1 Month Premium';
      case 'yearly':
        return '1 Year Premium';
      case 'lifetime':
      default:
        return 'Lifetime Access';
    }
  }

  SubscriptionPlan _subscriptionPlanForSelectedPlan() {
    switch (_selectedPlanId) {
      case 'monthly':
        return SubscriptionPlan.monthly;
      case 'yearly':
        return SubscriptionPlan.yearly;
      case 'lifetime':
      default:
        return SubscriptionPlan.lifetime;
    }
  }

  @override
  void initState() {
    super.initState();
    _shimmerCtrl = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    )..repeat();
    _razorpay = Razorpay();
    _razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, _handlePaymentSuccess);
    _razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, _handlePaymentError);
    _razorpay.on(Razorpay.EVENT_EXTERNAL_WALLET, _handleExternalWallet);
    _fetchUserDetails();
  }

  void _fetchUserDetails() {
    final user = FirebaseAuth.instance.currentUser;
    if (user != null) {
      setState(() {
        _userEmail = user.email ?? '';
        _userName = user.displayName ?? '';
        _userPhone = user.phoneNumber ?? '';
      });
    }
    final sub = SubscriptionService.instance.currentUser;
    if (sub != null) {
      if (_userEmail.isEmpty) _userEmail = sub.email;
      if (_userName.isEmpty) _userName = sub.name;
    }
  }

  @override
  void dispose() {
    _shimmerCtrl.dispose();
    _razorpay.clear();
    super.dispose();
  }

  Future<void> _startPayment() async {
    if (_userEmail.isEmpty) {
      _showSnack('Gmail not found. Please sign in again.', isError: true);
      return;
    }
    setState(() => _isLoading = true);

    final amount = _amountForSelectedPlan();

    try {
      final res = await http.post(
        Uri.parse('$_workerBaseUrl/create-order'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'amount': amount,
          'currency': 'INR',
          'receipt': 'rcpt_${DateTime.now().millisecondsSinceEpoch}',
          'plan_id': _selectedPlanId,
          'email': _userEmail,
        }),
      );

      if (res.statusCode != 200) throw Exception('Order failed: ${res.body}');
      final data = jsonDecode(res.body);
      final orderId = data['order']?['id'] ?? data['id'];
      if (orderId == null) throw Exception('No order ID received');

      _razorpay.open({
        'key': _razorpayKeyId,
        'amount': amount * 100,
        'name': 'Bulksendly',
        'order_id': orderId,
        'description': _paymentDescriptionForSelectedPlan(),
        'timeout': 300,
        'prefill': {
          'email': _userEmail,
          'contact': _userPhone,
          'name': _userName,
        },
        'theme': {'color': '#6C3AE1'},
      });
    } catch (e) {
      _showSnack('$e', isError: true);
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _handlePaymentSuccess(PaymentSuccessResponse response) async {
    _showSnack('Payment successful! Verifying...');
    setState(() => _isLoading = true);

    try {
      final res = await http.post(
        Uri.parse('$_workerBaseUrl/verify-payment'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'razorpay_order_id': response.orderId,
          'razorpay_payment_id': response.paymentId,
          'razorpay_signature': response.signature,
        }),
      );

      if (res.statusCode != 200) throw Exception('Verification failed');

      final plan = _subscriptionPlanForSelectedPlan();
      String? expiresAt;

      if (plan == SubscriptionPlan.lifetime) {
        expiresAt = 'Lifetime';
      } else {
        final validityMonths = plan == SubscriptionPlan.monthly ? 1 : 12;
        final now = DateTime.now();
        expiresAt = DateTime(
          now.year,
          now.month + validityMonths,
          now.day,
        ).toIso8601String();
      }

      await SubscriptionService.instance.updatePlan(
        plan: plan,
        status: SubscriptionStatus.active,
        expiresAt: expiresAt,
      );

      if (mounted) {
        _showSuccessDialog();
      }
    } catch (e) {
      _showSnack('Verification error: $e', isError: true);
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _handlePaymentError(PaymentFailureResponse response) {
    _showSnack('Payment cancelled or failed.', isError: true);
    setState(() => _isLoading = false);
  }

  void _handleExternalWallet(ExternalWalletResponse response) {
    _showSnack('Wallet: ${response.walletName}');
  }

  void _showSnack(String msg, {bool isError = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor: isError
            ? Colors.red.shade700
            : const Color(0xFF6C3AE1),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    );
  }

  void _showSuccessDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: const BoxDecoration(
                shape: BoxShape.circle,
                gradient: LinearGradient(
                  colors: [Color(0xFF6C3AE1), Color(0xFFA855F7)],
                ),
              ),
              child: const Icon(
                Icons.check_rounded,
                color: Colors.white,
                size: 48,
              ),
            ),
            const SizedBox(height: 20),
            const Text(
              'Upgrade Successful!',
              style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900),
            ),
            const SizedBox(height: 8),
            Text(
              'Welcome to Premium',
              style: TextStyle(color: Colors.grey.shade600, fontSize: 15),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () {
                  Navigator.pop(context);
                  Navigator.pop(context);
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF6C3AE1),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
                child: const Text(
                  'Continue',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0F0A1E),
      body: Stack(
        children: [
          Positioned(
            top: -80,
            right: -60,
            child: _gradientOrb(200, const Color(0xFF6C3AE1)),
          ),
          Positioned(
            bottom: 120,
            left: -80,
            child: _gradientOrb(180, const Color(0xFFA855F7)),
          ),
          SafeArea(
            child: Column(
              children: [
                _buildAppBar(),
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.fromLTRB(20, 8, 20, 120),
                    child: Column(
                      children: [
                        _buildHeader(),
                        const SizedBox(height: 24),
                        _buildUserCard(),
                        const SizedBox(height: 24),
                        _buildPlanCard(
                          id: 'monthly',
                          title: '1 Month',
                          subtitle: '30 days premium access',
                          price: '\u20B9499',
                          perMonth: '\u20B9499/mo',
                          icon: Icons.schedule_rounded,
                        ),
                        const SizedBox(height: 14),
                        _buildPlanCard(
                          id: 'yearly',
                          title: '1 Year',
                          subtitle: '365 days premium access',
                          price: '\u20B9999',
                          perMonth: '\u20B983/mo',
                          icon: Icons.calendar_month_rounded,
                        ),
                        const SizedBox(height: 14),
                        _buildPlanCard(
                          id: 'lifetime',
                          title: 'Lifetime',
                          subtitle: 'Pay once, use forever',
                          price: '\u20B91,999',
                          badge: 'BEST VALUE',
                          icon: Icons.all_inclusive_rounded,
                        ),
                        const SizedBox(height: 28),
                        _buildFeatures(),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
          Positioned(left: 0, right: 0, bottom: 0, child: _buildBottomCTA()),
          if (_isLoading)
            Container(
              color: Colors.black54,
              child: const Center(
                child: CircularProgressIndicator(color: Color(0xFFA855F7)),
              ),
            ),
        ],
      ),
    );
  }

  Widget _gradientOrb(double size, Color color) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: RadialGradient(
          colors: [color.withOpacity(0.3), color.withOpacity(0)],
        ),
      ),
    );
  }

  Widget _buildAppBar() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      child: Row(
        children: [
          IconButton(
            onPressed: () => Navigator.pop(context),
            icon: const Icon(
              Icons.arrow_back_ios_new_rounded,
              color: Colors.white70,
              size: 20,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Column(
      children: [
        ShaderMask(
          shaderCallback: (bounds) => const LinearGradient(
            colors: [Color(0xFFA855F7), Color(0xFFE879F9)],
          ).createShader(bounds),
          child: const Icon(
            Icons.workspace_premium_rounded,
            size: 56,
            color: Colors.white,
          ),
        ),
        const SizedBox(height: 12),
        const Text(
          'Go Premium',
          style: TextStyle(
            fontSize: 32,
            fontWeight: FontWeight.w900,
            color: Colors.white,
            letterSpacing: -0.5,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          'Unlock unlimited messaging power',
          style: TextStyle(fontSize: 15, color: Colors.white.withOpacity(0.55)),
        ),
      ],
    );
  }

  Widget _buildUserCard() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.06),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withOpacity(0.08)),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 20,
            backgroundColor: const Color(0xFF6C3AE1),
            child: Text(
              _userName.isNotEmpty ? _userName[0].toUpperCase() : 'U',
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w800,
                fontSize: 18,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _userName.isNotEmpty ? _userName : 'User',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  _userEmail.isNotEmpty ? _userEmail : 'No email found',
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.5),
                    fontSize: 13,
                  ),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: Colors.orange.withOpacity(0.15),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Text(
              'FREE',
              style: TextStyle(
                color: Colors.orange,
                fontSize: 11,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPlanCard({
    required String id,
    required String title,
    required String subtitle,
    required String price,
    required IconData icon,
    String? perMonth,
    String? badge,
  }) {
    final selected = _selectedPlanId == id;

    return GestureDetector(
      onTap: () => setState(() => _selectedPlanId = id),
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          AnimatedContainer(
            duration: const Duration(milliseconds: 250),
            curve: Curves.easeOutCubic,
            padding: EdgeInsets.fromLTRB(18, badge != null ? 28 : 18, 18, 18),
            decoration: BoxDecoration(
              gradient: selected
                  ? const LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [Color(0xFF6C3AE1), Color(0xFF4F1DAB)],
                    )
                  : null,
              color: selected ? null : Colors.white.withOpacity(0.05),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                color: selected
                    ? const Color(0xFFA855F7)
                    : Colors.white.withOpacity(0.08),
                width: selected ? 2 : 1,
              ),
              boxShadow: selected
                  ? [
                      BoxShadow(
                        color: const Color(0xFF6C3AE1).withOpacity(0.35),
                        blurRadius: 24,
                        offset: const Offset(0, 8),
                      ),
                    ]
                  : null,
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: selected
                        ? Colors.white.withOpacity(0.15)
                        : Colors.white.withOpacity(0.06),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Icon(
                    icon,
                    color: selected ? Colors.white : const Color(0xFFA855F7),
                    size: 26,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w800,
                          color: selected
                              ? Colors.white
                              : Colors.white.withOpacity(0.85),
                        ),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        subtitle,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 13,
                          color: selected ? Colors.white60 : Colors.white38,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    FittedBox(
                      fit: BoxFit.scaleDown,
                      child: Text(
                        price,
                        style: TextStyle(
                          fontSize: 22,
                          fontWeight: FontWeight.w900,
                          color: selected
                              ? Colors.white
                              : const Color(0xFFA855F7),
                        ),
                      ),
                    ),
                    if (perMonth != null)
                      Text(
                        perMonth,
                        style: TextStyle(
                          fontSize: 12,
                          color: selected ? Colors.white54 : Colors.white30,
                        ),
                      ),
                  ],
                ),
                const SizedBox(width: 8),
                Icon(
                  selected ? Icons.check_circle_rounded : Icons.circle_outlined,
                  color: selected ? Colors.white : Colors.white24,
                  size: 24,
                ),
              ],
            ),
          ),
          if (badge != null)
            Positioned(
              top: -10,
              right: 14,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 5,
                ),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFFFBBF24), Color(0xFFF59E0B)],
                  ),
                  borderRadius: BorderRadius.circular(999),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFFF59E0B).withOpacity(0.28),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Text(
                  badge,
                  style: const TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w900,
                    color: Colors.black,
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildFeatures() {
    const features = [
      {
        'icon': Icons.all_inclusive_rounded,
        'text': 'Unlimited WhatsApp Messages',
      },
      {'icon': Icons.people_alt_rounded, 'text': 'Built-in CRM Access'},
      {'icon': Icons.quickreply_rounded, 'text': 'Smart AutoReply Flows'},
      {'icon': Icons.campaign_rounded, 'text': 'Advanced Campaign Reports'},
      {'icon': Icons.contacts_rounded, 'text': 'Bulk Import (CSV/VCF)'},
      {'icon': Icons.auto_awesome_rounded, 'text': 'Custom Message Templates'},
      {'icon': Icons.support_agent_rounded, 'text': 'Priority 24/7 Support'},
      {'icon': Icons.block_rounded, 'text': 'Completely Ad-Free'},
    ];

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.04),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white.withOpacity(0.06)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Everything included',
            style: TextStyle(
              color: Colors.white,
              fontSize: 16,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 14),
          ...features.map(
            (f) => Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: const Color(0xFF6C3AE1).withOpacity(0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(
                      f['icon'] as IconData,
                      color: const Color(0xFFA855F7),
                      size: 16,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Text(
                    f['text'] as String,
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.7),
                      fontSize: 14,
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

  Widget _buildBottomCTA() {
    final price = switch (_selectedPlanId) {
      'monthly' => '\u20B9499',
      'yearly' => '\u20B9999',
      _ => '\u20B91,999',
    };
    final label = switch (_selectedPlanId) {
      'monthly' => '1 Month Premium',
      'yearly' => '1 Year Premium',
      _ => 'Lifetime Access',
    };

    return Container(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 28),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            const Color(0xFF0F0A1E).withOpacity(0),
            const Color(0xFF0F0A1E),
          ],
        ),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                price,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 24,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(width: 8),
              Text(
                '- $label',
                style: TextStyle(
                  color: Colors.white.withOpacity(0.5),
                  fontSize: 14,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            height: 58,
            child: ElevatedButton(
              onPressed: _isLoading ? null : _startPayment,
              style: ElevatedButton.styleFrom(
                padding: EdgeInsets.zero,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                elevation: 0,
                backgroundColor: Colors.transparent,
              ),
              child: Ink(
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF6C3AE1), Color(0xFFA855F7)],
                  ),
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF6C3AE1).withOpacity(0.45),
                      blurRadius: 20,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: Container(
                  alignment: Alignment.center,
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(
                        Icons.bolt_rounded,
                        color: Colors.white,
                        size: 22,
                      ),
                      const SizedBox(width: 8),
                      Text(
                        _isLoading ? 'Processing...' : 'Upgrade Now',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 18,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 0.3,
                        ),
                      ),
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
}
