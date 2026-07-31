import 'dart:async';
import 'dart:math';
import 'package:flutter/material.dart';
import 'package:autoreply/features/onboarding/screens/feature_onboarding2_screen.dart';

// ─────────────────────────────────────────────
// FEATURE ONBOARDING - WhatsApp Button Message
// ─────────────────────────────────────────────

class FeatureOnboardingScreen extends StatefulWidget {
  const FeatureOnboardingScreen({super.key});

  @override
  State<FeatureOnboardingScreen> createState() =>
      _FeatureOnboardingScreenState();
}

class _FeatureOnboardingScreenState extends State<FeatureOnboardingScreen>
    with TickerProviderStateMixin {
  // Controllers
  late AnimationController _bgGlowController;
  late AnimationController _titleController;
  late AnimationController _messageSlideController;
  late AnimationController _buttonsPulseController;
  late AnimationController _descFadeController;
  late AnimationController _nextBtnController;
  late AnimationController _floatingBubbleController;

  // Animations
  late Animation<double> _titleFade;
  late Animation<Offset> _titleSlide;
  late Animation<double> _messageFade;
  late Animation<Offset> _messageSlide;
  late Animation<double> _descFade;
  late Animation<Offset> _descSlide;
  late Animation<double> _nextBtnScale;

  bool _showTyping = false;
  bool _showMessage = false;
  bool _showButtons = false;
  int _typingDotCount = 0;
  Timer? _typingDotTimer;
  int _countdownSeconds = 5;
  Timer? _countdownTimer;

  @override
  void initState() {
    super.initState();
    _initAnimations();
    _startSequence();
    _startCountdown();
  }

  void _initAnimations() {
    // Background glow pulsing
    _bgGlowController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 4),
    )..repeat(reverse: true);

    // Title entrance
    _titleController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    );
    _titleFade = CurvedAnimation(
      parent: _titleController,
      curve: Curves.easeOutCubic,
    );
    _titleSlide = Tween<Offset>(
      begin: const Offset(0, -0.3),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _titleController,
      curve: Curves.easeOutCubic,
    ));

    // Message slide-in
    _messageSlideController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );
    _messageFade = CurvedAnimation(
      parent: _messageSlideController,
      curve: Curves.easeOutBack,
    );
    _messageSlide = Tween<Offset>(
      begin: const Offset(-0.4, 0.1),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _messageSlideController,
      curve: Curves.easeOutBack,
    ));

    // Buttons pulse
    _buttonsPulseController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    )..repeat(reverse: true);

    // Description fade
    _descFadeController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );
    _descFade = CurvedAnimation(
      parent: _descFadeController,
      curve: Curves.easeOut,
    );
    _descSlide = Tween<Offset>(
      begin: const Offset(0, 0.2),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _descFadeController,
      curve: Curves.easeOut,
    ));

    // Next button
    _nextBtnController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );
    _nextBtnScale = CurvedAnimation(
      parent: _nextBtnController,
      curve: Curves.elasticOut,
    );

    // Floating bubbles
    _floatingBubbleController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 6),
    )..repeat();
  }

  void _startSequence() {
    // Step 1: Show title
    Future.delayed(const Duration(milliseconds: 300), () {
      if (!mounted) return;
      _titleController.forward();
    });

    // Step 2: Show typing indicator
    Future.delayed(const Duration(milliseconds: 1200), () {
      if (!mounted) return;
      setState(() => _showTyping = true);
      _startTypingDots();
    });

    // Step 3: Hide typing, show message
    Future.delayed(const Duration(milliseconds: 3000), () {
      if (!mounted) return;
      _typingDotTimer?.cancel();
      setState(() {
        _showTyping = false;
        _showMessage = true;
      });
      _messageSlideController.forward();
    });

    // Step 4: Show interactive buttons with stagger
    Future.delayed(const Duration(milliseconds: 3800), () {
      if (!mounted) return;
      setState(() => _showButtons = true);
    });

    // Step 5: Show description text
    Future.delayed(const Duration(milliseconds: 4600), () {
      if (!mounted) return;
      _descFadeController.forward();
    });

    // Step 6: Show next button
    Future.delayed(const Duration(milliseconds: 5200), () {
      if (!mounted) return;
      _nextBtnController.forward();
    });
  }

  void _startTypingDots() {
    _typingDotTimer = Timer.periodic(const Duration(milliseconds: 400), (t) {
      if (!mounted) {
        t.cancel();
        return;
      }
      setState(() => _typingDotCount = (_typingDotCount + 1) % 4);
    });
  }

  @override
  void dispose() {
    _bgGlowController.dispose();
    _titleController.dispose();
    _messageSlideController.dispose();
    _buttonsPulseController.dispose();
    _descFadeController.dispose();
    _nextBtnController.dispose();
    _floatingBubbleController.dispose();
    _typingDotTimer?.cancel();
    _countdownTimer?.cancel();
    super.dispose();
  }

  void _navigateToHome() {
    Navigator.pushReplacement(
      context,
      PageRouteBuilder(
        pageBuilder: (a, b, c) => const FeatureOnboarding2Screen(),
        transitionsBuilder: (ctx, anim, secAnim, child) {
          return FadeTransition(
            opacity: anim,
            child: SlideTransition(
              position: Tween<Offset>(
                begin: const Offset(0.3, 0),
                end: Offset.zero,
              ).animate(CurvedAnimation(parent: anim, curve: Curves.easeOut)),
              child: child,
            ),
          );
        },
        transitionDuration: const Duration(milliseconds: 600),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          // Animated gradient background
          _buildAnimatedBackground(),
          // Floating decorative bubbles
          ..._buildFloatingBubbles(),
          // Main content
          SafeArea(
            child: Column(
              children: [
                const SizedBox(height: 12),
                // Timer at top
                _buildCountdownTimer(),
                const SizedBox(height: 12),
                // Title section
                _buildTitleSection(),
                const SizedBox(height: 14),
                // Feature chips + description
                _buildChipsSection(),
                const SizedBox(height: 12),
                // WhatsApp button message
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.symmetric(horizontal: 28),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Typing indicator
                        if (_showTyping) _buildTypingIndicator(),
                        // The WhatsApp message (compact)
                        if (_showMessage) _buildWhatsAppButtonMessage(),
                        const SizedBox(height: 20),
                      ],
                    ),
                  ),
                ),
                // Next button at BOTTOM
                _buildNextButton(),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ─── Countdown Timer ───
  void _startCountdown() {
    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) {
        timer.cancel();
        return;
      }
      setState(() {
        _countdownSeconds--;
      });
      if (_countdownSeconds <= 0) {
        timer.cancel();
      }
    });
  }

  Widget _buildCountdownTimer() {
    if (_countdownSeconds <= 0) return const SizedBox.shrink();
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.12),
          width: 0.5,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          SizedBox(
            width: 14,
            height: 14,
            child: CircularProgressIndicator(
              value: _countdownSeconds / 5,
              strokeWidth: 2,
              color: const Color(0xFF4FACFE),
              backgroundColor: Colors.white.withValues(alpha: 0.15),
            ),
          ),
          const SizedBox(width: 8),
          Text(
            'Skip in ${_countdownSeconds}s',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.7),
              fontSize: 12,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  // ─── Background ───
  Widget _buildAnimatedBackground() {
    return AnimatedBuilder(
      animation: _bgGlowController,
      builder: (context, child) {
        return Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                Color.lerp(
                  const Color(0xFF0D1B2A),
                  const Color(0xFF1B2838),
                  _bgGlowController.value,
                )!,
                Color.lerp(
                  const Color(0xFF1B3A4B),
                  const Color(0xFF0A2342),
                  _bgGlowController.value,
                )!,
                Color.lerp(
                  const Color(0xFF162447),
                  const Color(0xFF1F4068),
                  _bgGlowController.value,
                )!,
              ],
            ),
          ),
          child: Stack(
            children: [
              // Glow orb top-right
              Positioned(
                right: -60,
                top: -40,
                child: Container(
                  width: 220,
                  height: 220,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: RadialGradient(
                      colors: [
                        const Color(0xFF25D366)
                            .withValues(alpha: 0.15 + _bgGlowController.value * 0.1),
                        Colors.transparent,
                      ],
                    ),
                  ),
                ),
              ),
              // Glow orb bottom-left
              Positioned(
                left: -80,
                bottom: 60,
                child: Container(
                  width: 280,
                  height: 280,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: RadialGradient(
                      colors: [
                        const Color(0xFF128C7E)
                            .withValues(alpha: 0.12 + _bgGlowController.value * 0.08),
                        Colors.transparent,
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  List<Widget> _buildFloatingBubbles() {
    return List.generate(5, (i) {
      final random = Random(i * 42);
      final startX = random.nextDouble() * 350;
      final size = 6.0 + random.nextDouble() * 14;
      return AnimatedBuilder(
        animation: _floatingBubbleController,
        builder: (context, child) {
          final progress =
              (_floatingBubbleController.value + i * 0.2) % 1.0;
          final yPos = MediaQuery.of(context).size.height * (1.0 - progress);
          return Positioned(
            left: startX + sin(progress * 3.14 * 2) * 20,
            top: yPos,
            child: Container(
              width: size,
              height: size,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: const Color(0xFF25D366)
                    .withValues(alpha: 0.06 + (1 - progress) * 0.08),
              ),
            ),
          );
        },
      );
    });
  }

  // ─── Title (smaller) ───
  Widget _buildTitleSection() {
    return SlideTransition(
      position: _titleSlide,
      child: FadeTransition(
        opacity: _titleFade,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24),
          child: Column(
            children: [
              // Feature badge
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                decoration: BoxDecoration(
                  color: const Color(0xFF25D366).withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(30),
                  border: Border.all(
                    color: const Color(0xFF25D366).withValues(alpha: 0.3),
                    width: 1,
                  ),
                ),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.auto_awesome,
                        color: Color(0xFF25D366), size: 13),
                    SizedBox(width: 5),
                    Text(
                      'FEATURE HIGHLIGHT',
                      style: TextStyle(
                        color: Color(0xFF25D366),
                        fontSize: 9.5,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 1.2,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 10),
              ShaderMask(
                shaderCallback: (bounds) => const LinearGradient(
                  colors: [Color(0xFF25D366), Color(0xFF4FFFB0)],
                ).createShader(bounds),
                child: const Text(
                  'Interactive Button Messages',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    color: Colors.white,
                    height: 1.2,
                    letterSpacing: -0.3,
                  ),
                ),
              ),
              const SizedBox(height: 5),
              Text(
                'Send WhatsApp messages with clickable buttons',
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.white.withValues(alpha: 0.55),
                  fontWeight: FontWeight.w400,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // ─── Typing Indicator ───
  Widget _buildTypingIndicator() {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFDCF8C6),
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: List.generate(3, (i) {
          final isActive = i <= _typingDotCount;
          return AnimatedContainer(
            duration: const Duration(milliseconds: 300),
            margin: const EdgeInsets.symmetric(horizontal: 3),
            width: 9,
            height: 9,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: isActive
                  ? const Color(0xFF25D366)
                  : const Color(0xFF25D366).withValues(alpha: 0.3),
            ),
          );
        }),
      ),
    );
  }

  // ─── WhatsApp Button Message (compact / medium size) ───
  Widget _buildWhatsAppButtonMessage() {
    return SlideTransition(
      position: _messageSlide,
      child: FadeTransition(
        opacity: _messageFade,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Main message bubble (compact) ──
            Container(
              width: double.infinity,
              decoration: BoxDecoration(
                color: const Color(0xFFDCF8C6),
                borderRadius: BorderRadius.circular(14),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.1),
                    blurRadius: 10,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Message text
                  Padding(
                    padding: const EdgeInsets.fromLTRB(12, 10, 12, 6),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Hi Customer,',
                          style: TextStyle(
                            color: Color(0xFF1B1B1B),
                            fontSize: 13,
                            fontWeight: FontWeight.w400,
                            height: 1.35,
                          ),
                        ),
                        const SizedBox(height: 1),
                        const Text(
                          'your order is expected to arrive today.\nTrack your package to see where it is.',
                          style: TextStyle(
                            color: Color(0xFF1B1B1B),
                            fontSize: 13,
                            fontWeight: FontWeight.w400,
                            height: 1.35,
                          ),
                        ),
                        const SizedBox(height: 6),
                        // Sender name + timestamp row
                        Row(
                          children: [
                            Text(
                              'eShop',
                              style: TextStyle(
                                color: Colors.grey.shade500,
                                fontSize: 10.5,
                                fontStyle: FontStyle.italic,
                              ),
                            ),
                            const Spacer(),
                            Text(
                              '1:25 PM',
                              style: TextStyle(
                                color: Colors.grey.shade500,
                                fontSize: 10,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  // ── Footer / Offer section ──
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(
                        horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFD4F4BE),
                      borderRadius: const BorderRadius.only(
                        bottomLeft: Radius.circular(14),
                        bottomRight: Radius.circular(14),
                      ),
                      border: Border(
                        top: BorderSide(
                          color: const Color(0xFF25D366).withValues(alpha: 0.2),
                          width: 0.5,
                        ),
                      ),
                    ),
                    child: Column(
                      children: [
                        RichText(
                          textAlign: TextAlign.center,
                          text: const TextSpan(
                            style: TextStyle(
                              fontSize: 12.5,
                              color: Color(0xFF1B1B1B),
                              height: 1.35,
                            ),
                            children: [
                              TextSpan(text: 'Enjoy '),
                              TextSpan(
                                text: '₹200 off',
                                style: TextStyle(
                                  color: Color(0xFF128C7E),
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              TextSpan(text: ' your next purchase!\n'),
                              TextSpan(text: 'Use code '),
                              TextSpan(
                                text: 'SPRING200',
                                style: TextStyle(
                                  fontWeight: FontWeight.w800,
                                  color: Color(0xFF1B1B1B),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'eShop',
                          style: TextStyle(
                            color: Colors.grey.shade500,
                            fontSize: 10.5,
                            fontStyle: FontStyle.italic,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 8),

            // ── Interactive Buttons (compact) ──
            if (_showButtons) ..._buildInteractiveButtons(),
          ],
        ),
      ),
    );
  }

  List<Widget> _buildInteractiveButtons() {
    final buttons = [
      {'icon': Icons.phone_rounded, 'text': 'Contact Us'},
      {'icon': Icons.open_in_new_rounded, 'text': 'Track Package'},
      {'icon': Icons.campaign_rounded, 'text': 'View Offers'},
    ];

    return List.generate(buttons.length, (i) {
      return TweenAnimationBuilder<double>(
        tween: Tween(begin: 0.0, end: 1.0),
        duration: Duration(milliseconds: 400 + i * 150),
        curve: Curves.easeOutBack,
        builder: (context, value, child) {
          return Transform.translate(
            offset: Offset(0, 12 * (1 - value)),
            child: Opacity(
              opacity: value.clamp(0.0, 1.0),
              child: child,
            ),
          );
        },
        child: AnimatedBuilder(
          animation: _buttonsPulseController,
          builder: (context, child) {
            final pulseValue =
                sin(_buttonsPulseController.value * 3.14 * 2 + i * 0.8) *
                        0.015 +
                    1.0;
            return Transform.scale(
              scale: pulseValue,
              child: Container(
                margin: const EdgeInsets.only(bottom: 6),
                width: double.infinity,
                child: Material(
                  color: Colors.transparent,
                  child: InkWell(
                    borderRadius: BorderRadius.circular(12),
                    onTap: () {},
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          vertical: 10, horizontal: 16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: const Color(0xFF25D366).withValues(alpha: 0.35),
                          width: 1,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFF25D366).withValues(alpha: 0.08),
                            blurRadius: 6,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            buttons[i]['icon'] as IconData,
                            color: const Color(0xFF128C7E),
                            size: 17,
                          ),
                          const SizedBox(width: 8),
                          Text(
                            buttons[i]['text'] as String,
                            style: const TextStyle(
                              color: Color(0xFF128C7E),
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            );
          },
        ),
      );
    });
  }

  // ─── Chips section (top) ───
  Widget _buildChipsSection() {
    return SlideTransition(
      position: _descSlide,
      child: FadeTransition(
        opacity: _descFade,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 28),
          child: Column(
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  _buildFeatureChip(
                      Icons.touch_app_rounded, 'Clickable'),
                  const SizedBox(width: 10),
                  _buildFeatureChip(
                      Icons.speed_rounded, 'Instant'),
                  const SizedBox(width: 10),
                  _buildFeatureChip(
                      Icons.trending_up_rounded, 'Engaging'),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                'Create & send interactive button messages\nto boost customer engagement!',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.55),
                  fontSize: 12.5,
                  height: 1.4,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // ─── Next button (bottom) ───
  Widget _buildNextButton() {
    return ScaleTransition(
      scale: _nextBtnScale,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 28),
        child: SizedBox(
          width: double.infinity,
          height: 52,
          child: DecoratedBox(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(14),
              gradient: const LinearGradient(
                colors: [Color(0xFF4A90D9), Color(0xFF357ABD)],
              ),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFF4A90D9).withValues(alpha: 0.4),
                  blurRadius: 14,
                  offset: const Offset(0, 5),
                ),
              ],
            ),
            child: ElevatedButton(
              onPressed: _navigateToHome,
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.transparent,
                shadowColor: Colors.transparent,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
              ),
              child: const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    'Next',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.5,
                    ),
                  ),
                  SizedBox(width: 8),
                  Icon(Icons.arrow_forward_rounded,
                      color: Colors.white, size: 20),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildFeatureChip(IconData icon, String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.1),
          width: 0.5,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: const Color(0xFF4FFFB0), size: 14),
          const SizedBox(width: 5),
          Text(
            label,
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.7),
              fontSize: 11,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }
}
