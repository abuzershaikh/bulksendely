import 'dart:async';
import 'dart:math';
import 'package:flutter/material.dart';
import 'package:autoreply/features/home/screens/home_screen.dart';

// ─────────────────────────────────────────────
// FEATURE ONBOARDING 2 - AutoReply + Button Msg
// ─────────────────────────────────────────────

class FeatureOnboarding2Screen extends StatefulWidget {
  const FeatureOnboarding2Screen({super.key});

  @override
  State<FeatureOnboarding2Screen> createState() =>
      _FeatureOnboarding2ScreenState();
}

class _FeatureOnboarding2ScreenState extends State<FeatureOnboarding2Screen>
    with TickerProviderStateMixin {
  late AnimationController _bgGlowController;
  late AnimationController _titleController;
  late AnimationController _messageSlideController;
  late AnimationController _buttonsPulseController;
  late AnimationController _descFadeController;
  late AnimationController _nextBtnController;
  late AnimationController _floatingBubbleController;
  late AnimationController _replySlideController;

  late Animation<double> _titleFade;
  late Animation<Offset> _titleSlide;
  late Animation<double> _messageFade;
  late Animation<Offset> _messageSlide;
  late Animation<double> _descFade;
  late Animation<Offset> _descSlide;
  late Animation<double> _nextBtnScale;
  late Animation<double> _replyFade;
  late Animation<Offset> _replySlide;

  bool _showTyping = false;
  bool _showMessage = false;
  bool _showButtons = false;
  bool _showAutoReply = false;
  int _typingDotCount = 0;
  Timer? _typingDotTimer;
  Timer? _replyTypingTimer;
  int _countdownSeconds = 5;
  Timer? _countdownTimer;
  bool _showReplyTyping = false;
  int _replyTypingDotCount = 0;

  @override
  void initState() {
    super.initState();
    _initAnimations();
    _startSequence();
    _startCountdown();
  }

  void _initAnimations() {
    _bgGlowController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 4),
    )..repeat(reverse: true);

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

    _messageSlideController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );
    _messageFade = CurvedAnimation(
      parent: _messageSlideController,
      curve: Curves.easeOutBack,
    );
    _messageSlide = Tween<Offset>(
      begin: const Offset(0.4, 0.1),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _messageSlideController,
      curve: Curves.easeOutBack,
    ));

    _buttonsPulseController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    )..repeat(reverse: true);

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

    _nextBtnController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );
    _nextBtnScale = CurvedAnimation(
      parent: _nextBtnController,
      curve: Curves.elasticOut,
    );

    _floatingBubbleController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 6),
    )..repeat();

    // AutoReply message slide
    _replySlideController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );
    _replyFade = CurvedAnimation(
      parent: _replySlideController,
      curve: Curves.easeOutBack,
    );
    _replySlide = Tween<Offset>(
      begin: const Offset(-0.4, 0.1),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _replySlideController,
      curve: Curves.easeOutBack,
    ));
  }

  void _startSequence() {
    // Step 1: Show title
    Future.delayed(const Duration(milliseconds: 300), () {
      if (!mounted) return;
      _titleController.forward();
    });

    // Step 2: Description chips
    Future.delayed(const Duration(milliseconds: 800), () {
      if (!mounted) return;
      _descFadeController.forward();
    });

    // Step 3: Show user message (right side — user sends hi)
    Future.delayed(const Duration(milliseconds: 1500), () {
      if (!mounted) return;
      setState(() => _showMessage = true);
      _messageSlideController.forward();
    });

    // Step 4: Show typing indicator (bot replying)
    Future.delayed(const Duration(milliseconds: 2500), () {
      if (!mounted) return;
      setState(() => _showReplyTyping = true);
      _startReplyTypingDots();
    });

    // Step 5: Show auto-reply message with buttons
    Future.delayed(const Duration(milliseconds: 4200), () {
      if (!mounted) return;
      _replyTypingTimer?.cancel();
      setState(() {
        _showReplyTyping = false;
        _showAutoReply = true;
      });
      _replySlideController.forward();
    });

    // Step 6: Show buttons
    Future.delayed(const Duration(milliseconds: 5000), () {
      if (!mounted) return;
      setState(() => _showButtons = true);
    });

    // Step 7: Show next button
    Future.delayed(const Duration(milliseconds: 5600), () {
      if (!mounted) return;
      _nextBtnController.forward();
    });
  }

  void _startReplyTypingDots() {
    _replyTypingTimer = Timer.periodic(const Duration(milliseconds: 400), (t) {
      if (!mounted) {
        t.cancel();
        return;
      }
      setState(() => _replyTypingDotCount = (_replyTypingDotCount + 1) % 4);
    });
  }

  void _startCountdown() {
    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) {
        timer.cancel();
        return;
      }
      setState(() => _countdownSeconds--);
      if (_countdownSeconds <= 0) timer.cancel();
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
    _replySlideController.dispose();
    _typingDotTimer?.cancel();
    _replyTypingTimer?.cancel();
    _countdownTimer?.cancel();
    super.dispose();
  }

  void _navigateToHome() {
    Navigator.pushReplacement(
      context,
      PageRouteBuilder(
        pageBuilder: (a, b, c) => const HomeScreen(),
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
          _buildAnimatedBackground(),
          ..._buildFloatingBubbles(),
          SafeArea(
            child: Column(
              children: [
                const SizedBox(height: 12),
                _buildCountdownTimer(),
                const SizedBox(height: 12),
                _buildTitleSection(),
                const SizedBox(height: 14),
                _buildChipsSection(),
                const SizedBox(height: 12),
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.symmetric(horizontal: 24),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        // User sends "Hi" (right side)
                        if (_showMessage) _buildUserMessage(),
                        const SizedBox(height: 10),
                        // Bot typing indicator
                        if (_showReplyTyping)
                          Align(
                            alignment: Alignment.centerLeft,
                            child: _buildTypingIndicator(),
                          ),
                        // AutoReply with button message (left side)
                        if (_showAutoReply)
                          Align(
                            alignment: Alignment.centerLeft,
                            child: _buildAutoReplyMessage(),
                          ),
                        const SizedBox(height: 20),
                      ],
                    ),
                  ),
                ),
                _buildNextButton(),
                const SizedBox(height: 20),
              ],
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
                  const Color(0xFF1A0A2E),
                  const Color(0xFF2D1B69),
                  _bgGlowController.value,
                )!,
                Color.lerp(
                  const Color(0xFF16213E),
                  const Color(0xFF0F3460),
                  _bgGlowController.value,
                )!,
                Color.lerp(
                  const Color(0xFF1A1A2E),
                  const Color(0xFF16213E),
                  _bgGlowController.value,
                )!,
              ],
            ),
          ),
          child: Stack(
            children: [
              Positioned(
                right: -50,
                top: -30,
                child: Container(
                  width: 200,
                  height: 200,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: RadialGradient(
                      colors: [
                        const Color(0xFF7B68EE)
                            .withValues(alpha: 0.18 + _bgGlowController.value * 0.1),
                        Colors.transparent,
                      ],
                    ),
                  ),
                ),
              ),
              Positioned(
                left: -70,
                bottom: 80,
                child: Container(
                  width: 260,
                  height: 260,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: RadialGradient(
                      colors: [
                        const Color(0xFF43CEA2)
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
      final random = Random(i * 37);
      final startX = random.nextDouble() * 350;
      final size = 5.0 + random.nextDouble() * 12;
      return AnimatedBuilder(
        animation: _floatingBubbleController,
        builder: (context, child) {
          final progress = (_floatingBubbleController.value + i * 0.2) % 1.0;
          final yPos = MediaQuery.of(context).size.height * (1.0 - progress);
          return Positioned(
            left: startX + sin(progress * 3.14 * 2) * 18,
            top: yPos,
            child: Container(
              width: size,
              height: size,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: const Color(0xFF7B68EE)
                    .withValues(alpha: 0.05 + (1 - progress) * 0.07),
              ),
            ),
          );
        },
      );
    });
  }

  // ─── Timer ───
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
              color: const Color(0xFF7B68EE),
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

  // ─── Title ───
  Widget _buildTitleSection() {
    return SlideTransition(
      position: _titleSlide,
      child: FadeTransition(
        opacity: _titleFade,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24),
          child: Column(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                decoration: BoxDecoration(
                  color: const Color(0xFF7B68EE).withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(30),
                  border: Border.all(
                    color: const Color(0xFF7B68EE).withValues(alpha: 0.3),
                    width: 1,
                  ),
                ),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.auto_awesome,
                        color: Color(0xFF7B68EE), size: 13),
                    SizedBox(width: 5),
                    Text(
                      'SMART AUTOMATION',
                      style: TextStyle(
                        color: Color(0xFF7B68EE),
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
                  colors: [Color(0xFF7B68EE), Color(0xFF43CEA2)],
                ).createShader(bounds),
                child: const Text(
                  'AutoReply with Buttons',
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
                'Auto respond with interactive button messages',
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

  // ─── Chips ───
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
                  _buildFeatureChip(Icons.replay_rounded, 'AutoReply'),
                  const SizedBox(width: 10),
                  _buildFeatureChip(Icons.smart_toy_rounded, 'Smart Bot'),
                  const SizedBox(width: 10),
                  _buildFeatureChip(Icons.bolt_rounded, '24/7'),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                'Set automated replies with button options\nfor customers even when you\'re offline!',
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

  // ─── User Message (right side — "Hi") ───
  Widget _buildUserMessage() {
    return SlideTransition(
      position: _messageSlide,
      child: FadeTransition(
        opacity: _messageFade,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          decoration: BoxDecoration(
            color: const Color(0xFFDCF8C6),
            borderRadius: const BorderRadius.only(
              topLeft: Radius.circular(14),
              topRight: Radius.circular(14),
              bottomLeft: Radius.circular(14),
              bottomRight: Radius.circular(4),
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.1),
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              const Text(
                'Hi 👋',
                style: TextStyle(
                  color: Color(0xFF1B1B1B),
                  fontSize: 14,
                  fontWeight: FontWeight.w400,
                ),
              ),
              const SizedBox(height: 3),
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    '9:30 PM',
                    style: TextStyle(
                      color: Colors.grey.shade500,
                      fontSize: 10,
                    ),
                  ),
                  const SizedBox(width: 3),
                  const Icon(Icons.done_all, size: 14, color: Color(0xFF53BDEB)),
                ],
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
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: List.generate(3, (i) {
          final isActive = i <= _replyTypingDotCount;
          return AnimatedContainer(
            duration: const Duration(milliseconds: 300),
            margin: const EdgeInsets.symmetric(horizontal: 3),
            width: 8,
            height: 8,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: isActive
                  ? const Color(0xFF7B68EE)
                  : const Color(0xFF7B68EE).withValues(alpha: 0.25),
            ),
          );
        }),
      ),
    );
  }

  // ─── AutoReply Message with Buttons ───
  Widget _buildAutoReplyMessage() {
    return SlideTransition(
      position: _replySlide,
      child: FadeTransition(
        opacity: _replyFade,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Bot indicator
            Padding(
              padding: const EdgeInsets.only(left: 4, bottom: 4),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    padding: const EdgeInsets.all(3),
                    decoration: BoxDecoration(
                      color: const Color(0xFF7B68EE).withValues(alpha: 0.2),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.smart_toy_rounded,
                        color: Color(0xFF7B68EE), size: 10),
                  ),
                  const SizedBox(width: 5),
                  Text(
                    'AutoReply Bot',
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.5),
                      fontSize: 10,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
            // Main message bubble
            Container(
              width: MediaQuery.of(context).size.width * 0.78,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(4),
                  topRight: Radius.circular(14),
                  bottomLeft: Radius.circular(14),
                  bottomRight: Radius.circular(14),
                ),
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
                  Padding(
                    padding: const EdgeInsets.fromLTRB(12, 10, 12, 6),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          '👋 Hello! Welcome to our store.',
                          style: TextStyle(
                            color: Color(0xFF1B1B1B),
                            fontSize: 13,
                            fontWeight: FontWeight.w400,
                            height: 1.35,
                          ),
                        ),
                        const SizedBox(height: 2),
                        const Text(
                          'How can we help you today?\nPlease choose an option below:',
                          style: TextStyle(
                            color: Color(0xFF1B1B1B),
                            fontSize: 13,
                            fontWeight: FontWeight.w400,
                            height: 1.35,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: const Color(0xFF7B68EE)
                                    .withValues(alpha: 0.1),
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: const Text(
                                '⚡ Auto',
                                style: TextStyle(
                                  color: Color(0xFF7B68EE),
                                  fontSize: 9,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                            const Spacer(),
                            Text(
                              '9:30 PM',
                              style: TextStyle(
                                color: Colors.grey.shade400,
                                fontSize: 10,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 6),
            // Interactive buttons
            if (_showButtons)
              SizedBox(
                width: MediaQuery.of(context).size.width * 0.78,
                child: Column(
                  children: _buildInteractiveButtons(),
                ),
              ),
          ],
        ),
      ),
    );
  }

  List<Widget> _buildInteractiveButtons() {
    final buttons = [
      {'icon': Icons.shopping_bag_rounded, 'text': '🛍️ Browse Products'},
      {'icon': Icons.local_offer_rounded, 'text': '🏷️ Today\'s Deals'},
      {'icon': Icons.help_outline_rounded, 'text': '❓ FAQ & Help'},
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
                        0.012 +
                    1.0;
            return Transform.scale(
              scale: pulseValue,
              child: Container(
                margin: const EdgeInsets.only(bottom: 5),
                width: double.infinity,
                child: Material(
                  color: Colors.transparent,
                  child: InkWell(
                    borderRadius: BorderRadius.circular(10),
                    onTap: () {},
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          vertical: 9, horizontal: 14),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                          color: const Color(0xFF7B68EE).withValues(alpha: 0.3),
                          width: 1,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFF7B68EE).withValues(alpha: 0.06),
                            blurRadius: 5,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            buttons[i]['text'] as String,
                            style: const TextStyle(
                              color: Color(0xFF5B4FCF),
                              fontSize: 12.5,
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

  // ─── Next Button (bottom) ───
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
                    'Get Started',
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
          Icon(icon, color: const Color(0xFF9B8FFF), size: 14),
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
