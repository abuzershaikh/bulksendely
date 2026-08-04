import 'package:autoreply/features/templates/screens/template_library_screen.dart';
import 'package:autoreply/features/contacts/screens/contact_groups_screen.dart';
import 'package:autoreply/features/whatsapp/screens/whatsapp_connect_screen.dart';
import 'package:autoreply/features/whatsapp/screens/parent_group_list_screen.dart';
import 'package:autoreply/features/group_sender_status/screens/group_sender_status_screen.dart';
import 'dart:async';
import 'package:autoreply/features/autoreply/screens/autoreply_dashboard_screen.dart';
import 'package:autoreply/features/whatsapp/services/whatsapp_api_service.dart';
import 'package:autoreply/features/campaigns/screens/campaign_wizard_screen.dart';
import 'package:autoreply/features/campaign_status/screens/campaign_status_screen.dart';
import 'package:autoreply/features/auth/google_auth/services/google_auth_service.dart';
import 'package:autoreply/features/auth/google_auth/screens/google_sign_in_screen.dart';
import 'package:autoreply/features/subscription/models/app_user_subscription.dart';
import 'package:autoreply/features/subscription/screens/upgrade_plan_screen.dart';
import 'package:autoreply/features/subscription/screens/subscription_profile_screen.dart';
import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:autoreply/features/info/screens/app_info_screens.dart';
import 'package:autoreply/features/chatbot_flow/screens/flow_manager_screen.dart';
import 'package:autoreply/features/backup/screens/backup_restore_screen.dart';
import 'dart:ui';
import 'package:flutter/material.dart';

// ─────────────────────────────────────────────
// HOME SCREEN with BulkSendly Dashboard
// ─────────────────────────────────────────────
class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> with TickerProviderStateMixin {
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();
  final WhatsappApiService _whatsappApiService = WhatsappApiService();
  final GoogleAuthService _authService = GoogleAuthService();
  final SubscriptionService _subscriptionService = SubscriptionService.instance;
  late AnimationController _cardAnimController;
  late AnimationController _fabController;
  late AnimationController _drawerIconController;
  late PageController _bannerPageController;
  Timer? _premiumBannerHideTimer;
  bool _isLoadingLinkedAccount = true;
  bool _hidePremiumHeroBanner = false;
  ActiveWhatsappInstance? _activeInstance;
  Map<String, String> _userInfo = {};
  int _currentBannerPage = 0;
  final int _currentNavIndex = 0;

  @override
  void initState() {
    super.initState();
    _cardAnimController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );
    _fabController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );
    _drawerIconController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 350),
    );
    _bannerPageController = PageController();
    // Start animations after build
    Future.delayed(const Duration(milliseconds: 200), () {
      if (mounted) {
        _cardAnimController.forward();
        _fabController.forward();
      }
    });
    _subscriptionService.currentUserNotifier.addListener(
      _handleSubscriptionChanged,
    );
    _loadLinkedWhatsapp();
    _loadUserInfo();
    _syncPlanOnOpen();
  }

  Future<void> _syncPlanOnOpen() async {
    await _subscriptionService.syncPlanFromPanel();
  }

  Future<void> _loadUserInfo() async {
    final info = await _authService.getUserInfo();
    setState(() {
      _userInfo = info;
    });
  }

  Future<void> _handleSignOut() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Sign Out'),
        content: const Text('Are you sure you want to sign out?'),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
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
            child: const Text('Sign Out'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await _authService.signOut();
      if (mounted) {
        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(builder: (context) => const GoogleSignInScreen()),
          (route) => false,
        );
      }
    }
  }

  @override
  void dispose() {
    _subscriptionService.currentUserNotifier.removeListener(
      _handleSubscriptionChanged,
    );
    _premiumBannerHideTimer?.cancel();
    _cardAnimController.dispose();
    _fabController.dispose();
    _drawerIconController.dispose();
    _bannerPageController.dispose();
    super.dispose();
  }

  void _handleSubscriptionChanged() {
    _schedulePremiumBannerVisibility(
      _subscriptionService.currentUserNotifier.value,
    );
  }

  void _schedulePremiumBannerVisibility(AppUserSubscription? subscription) {
    _premiumBannerHideTimer?.cancel();

    if (subscription == null || !subscription.isPremium) {
      if (_hidePremiumHeroBanner && mounted) {
        setState(() {
          _hidePremiumHeroBanner = false;
        });
      }
      return;
    }

    final activationTime = subscription.premiumActivatedAt;
    if (activationTime == null) {
      if (_hidePremiumHeroBanner && mounted) {
        setState(() {
          _hidePremiumHeroBanner = false;
        });
      }
      return;
    }

    final hideAt = activationTime.add(const Duration(minutes: 5));
    final remaining = hideAt.difference(DateTime.now());

    if (remaining <= Duration.zero) {
      if (!_hidePremiumHeroBanner && mounted) {
        setState(() {
          _hidePremiumHeroBanner = true;
        });
      }
      return;
    }

    if (_hidePremiumHeroBanner && mounted) {
      setState(() {
        _hidePremiumHeroBanner = false;
      });
    }

    _premiumBannerHideTimer = Timer(remaining, () {
      if (!mounted) {
        return;
      }
      setState(() {
        _hidePremiumHeroBanner = true;
      });
    });
  }

  Future<void> _loadLinkedWhatsapp() async {
    setState(() {
      _isLoadingLinkedAccount = true;
    });

    try {
      final activeInstance = await _whatsappApiService
          .getSavedOrActiveInstance();
      if (activeInstance != null && activeInstance.instanceId.isNotEmpty) {
        await _subscriptionService.updateWhatsappSession(
          sessionId: activeInstance.instanceId,
        );
      }
      if (!mounted) return;
      setState(() {
        _activeInstance = activeInstance;
        _isLoadingLinkedAccount = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _activeInstance = null;
        _isLoadingLinkedAccount = false;
      });
    }
  }


  // ─── Quick Action Tap Handler ───
  void _handleQuickActionTap(int index) {
    switch (index) {
      case 0: // Send Message
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => const CampaignWizardScreen(),
          ),
        );
        break;
      case 1: // Auto Reply
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => const AutoReplyDashboardScreen(),
          ),
        );
        break;
      case 2: // Chatbot
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => const FlowManagerScreen(),
          ),
        );
        break;
      case 3: // Welcome Msg
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => const AutoReplyDashboardScreen(),
          ),
        );
        break;
      case 4: // Group Send
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => const ParentGroupListScreen(),
          ),
        );
        break;
      case 5: // Templates
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => const TemplateLibraryScreen(),
          ),
        );
        break;
      case 6: // Contacts
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => const ContactGroupsScreen(),
          ),
        );
        break;
    }
  }

  // ─── Bottom Nav Tap Handler ───
  void _handleBottomNavTap(int index) {
    if (index == 0) {
      // Already on Dashboard
      return;
    }
    if (index == 1) {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => const CampaignStatusScreen(),
        ),
      );
    } else if (index == 2) {
      // Center FAB → New Campaign
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => const CampaignWizardScreen(),
        ),
      );
    } else if (index == 3) {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => const GroupSenderStatusScreen(),
        ),
      );
    }
  }

  // ─── WhatsApp Connect Handler ───
  Future<void> _handleWhatsAppConnect() async {
    final connected = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        builder: (context) => const WhatsappConnectScreen(),
      ),
    );

    await _loadLinkedWhatsapp();

    if (connected == true && mounted) {
      final linkedNumber = WhatsappApiService.formatLinkedNumber(
        _activeInstance?.linkedNumber,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            linkedNumber != null
                ? 'WhatsApp connected: $linkedNumber'
                : 'WhatsApp connected successfully',
          ),
          backgroundColor: const Color(0xFF25D366),
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  // ═══════════════════════════════════════════
  //  BUILD METHOD
  // ═══════════════════════════════════════════
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      backgroundColor: const Color(0xFFF2F4F7),
      drawer: AppDrawer(userInfo: _userInfo, onSignOut: _handleSignOut),
      onDrawerChanged: (isOpen) {
        if (isOpen) {
          _drawerIconController.forward();
        } else {
          _drawerIconController.reverse();
        }
      },
      body: SafeArea(
        child: Column(
          children: [
            _buildAppBar(),
            Expanded(
              child: _buildDashboardBody(),
            ),
          ],
        ),
      ),
      bottomNavigationBar: _buildBottomNavBar(),
    );
  }

  // ─── App Bar ───
  Widget _buildAppBar() {
    final userName = _userInfo['name'] ?? 'User';
    final firstLetter = userName.isNotEmpty ? userName[0].toUpperCase() : 'U';

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          // Hamburger Menu
          GestureDetector(
            onTap: () => _scaffoldKey.currentState?.openDrawer(),
            child: Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: const Color(0xFFF2F4F7),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(
                Icons.menu_rounded,
                color: Color(0xFF1A1D26),
                size: 22,
              ),
            ),
          ),
          const Spacer(),
          // BulkSendly Title
          const Text(
            'BulkSendly',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w700,
              color: Color(0xFF1A1D26),
              letterSpacing: 0.3,
            ),
          ),
          const Spacer(),
          // Notification Bell
          GestureDetector(
            onTap: () {
              // Notification action
            },
            child: Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: const Color(0xFFF2F4F7),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(
                Icons.notifications_outlined,
                color: Color(0xFF1A1D26),
                size: 22,
              ),
            ),
          ),
          const SizedBox(width: 10),
          // Profile Avatar
          GestureDetector(
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => const SubscriptionProfileScreen(),
                ),
              );
            },
            child: _userInfo['photo']?.isNotEmpty == true
                ? Container(
                    width: 38,
                    height: 38,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: const Color(0xFF25D366),
                        width: 2,
                      ),
                    ),
                    child: ClipOval(
                      child: Image.network(
                        _userInfo['photo']!,
                        width: 34,
                        height: 34,
                        fit: BoxFit.cover,
                      ),
                    ),
                  )
                : Container(
                    width: 38,
                    height: 38,
                    decoration: const BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: [Color(0xFF25D366), Color(0xFF128C7E)],
                      ),
                    ),
                    child: Center(
                      child: Text(
                        firstLetter,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  // ─── Dashboard Body ───
  Widget _buildDashboardBody() {
    return SingleChildScrollView(
      physics: const BouncingScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(0, 16, 0, 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Banner Carousel
          _buildBannerCarousel(),
          const SizedBox(height: 12),
          // Banner Dots
          _buildBannerDots(),
          const SizedBox(height: 24),
          // Quick Actions Section
          _buildQuickActionsSection(),
          const SizedBox(height: 24),
          // Management Section
          _buildManagementSection(),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  // ─── Banner Carousel ───
  Widget _buildBannerCarousel() {
    final linkedNumber = WhatsappApiService.formatLinkedNumber(
      _activeInstance?.linkedNumber,
    );
    final isLinked = _activeInstance != null;
    final bannerTitle =
        isLinked ? 'WhatsApp Connected' : 'Connect WhatsApp';
    final bannerSubtitle = _isLoadingLinkedAccount
        ? 'Checking linked WhatsApp...'
        : isLinked
            ? (linkedNumber?.isNotEmpty ?? false)
                ? 'Linked: $linkedNumber'
                : 'Linked successfully'
            : 'Link your WhatsApp account\nto get started';

    return SizedBox(
      height: 100,
      child: PageView(
        controller: _bannerPageController,
        onPageChanged: (index) {
          setState(() {
            _currentBannerPage = index;
          });
        },
        children: [
          // Banner 1: Connect WhatsApp
          _buildWhatsAppBannerCard(
            title: bannerTitle,
            subtitle: bannerSubtitle,
            buttonText: isLinked ? 'Manage →' : 'Connect Now →',
            onTap: _handleWhatsAppConnect,
            isLoading: _isLoadingLinkedAccount,
          ),
          // Banner 2: AutoReply Promo
          _buildPromoBannerCard(
            title: 'Smart AutoReply',
            subtitle: 'Set up automated replies,\nwelcome messages & more',
            buttonText: 'Setup Now →',
            icon: Icons.auto_awesome_rounded,
            gradientColors: const [Color(0xFF667EEA), Color(0xFF764BA2)],
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => const AutoReplyDashboardScreen(),
                ),
              );
            },
          ),
          // Banner 3: Upgrade
          _buildPromoBannerCard(
            title: 'Go Premium',
            subtitle: 'Unlock unlimited messages\n& advanced features',
            buttonText: 'Upgrade →',
            icon: Icons.workspace_premium_rounded,
            gradientColors: const [Color(0xFFE65100), Color(0xFFFF9800)],
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => const UpgradePlanScreen(),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  // ─── WhatsApp Banner Card ───
  Widget _buildWhatsAppBannerCard({
    required String title,
    required String subtitle,
    required String buttonText,
    required VoidCallback onTap,
    bool isLoading = false,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              begin: Alignment.centerLeft,
              end: Alignment.centerRight,
              colors: [Color(0xFF0D4F3C), Color(0xFF1B7A53), Color(0xFF25D366)],
            ),
            borderRadius: BorderRadius.circular(20),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF25D366).withValues(alpha: 0.3),
                blurRadius: 20,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Stack(
            children: [
              // Decorative circles in background
              Positioned(
                right: -20,
                top: -20,
                child: Container(
                  width: 120,
                  height: 120,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white.withValues(alpha: 0.06),
                  ),
                ),
              ),
              Positioned(
                right: 20,
                bottom: -30,
                child: Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white.withValues(alpha: 0.04),
                  ),
                ),
              ),
              // Content
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                child: Row(
                  children: [
                    // Text Content
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            title,
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w700,
                              color: Colors.white,
                              letterSpacing: 0.2,
                            ),
                          ),
                          const SizedBox(height: 3),
                          Text(
                            subtitle,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.white.withValues(alpha: 0.85),
                              fontWeight: FontWeight.w400,
                              height: 1.3,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              if (isLoading)
                                const SizedBox(
                                  width: 14,
                                  height: 14,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 1.5,
                                    color: Colors.white,
                                  ),
                                )
                              else
                                Text(
                                  buttonText,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    // WhatsApp Icon
                    Container(
                      width: 48,
                      height: 48,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: Colors.white.withValues(alpha: 0.15),
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.2),
                          width: 1.5,
                        ),
                      ),
                      child: const Icon(
                        Icons.chat_rounded,
                        color: Colors.white,
                        size: 22,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // ─── Promo Banner Card ───
  Widget _buildPromoBannerCard({
    required String title,
    required String subtitle,
    required String buttonText,
    required IconData icon,
    required List<Color> gradientColors,
    required VoidCallback onTap,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.centerLeft,
              end: Alignment.centerRight,
              colors: gradientColors,
            ),
            borderRadius: BorderRadius.circular(20),
            boxShadow: [
              BoxShadow(
                color: gradientColors[0].withValues(alpha: 0.3),
                blurRadius: 20,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Stack(
            children: [
              // Decorative circles
              Positioned(
                right: -20,
                top: -20,
                child: Container(
                  width: 120,
                  height: 120,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white.withValues(alpha: 0.06),
                  ),
                ),
              ),
              // Content
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                child: Row(
                  children: [
                    // Text Content
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            title,
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w700,
                              color: Colors.white,
                              letterSpacing: 0.2,
                            ),
                          ),
                          const SizedBox(height: 3),
                          Text(
                            subtitle,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.white.withValues(alpha: 0.85),
                              fontWeight: FontWeight.w400,
                              height: 1.3,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            buttonText,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                    // Icon
                    Container(
                      width: 48,
                      height: 48,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: Colors.white.withValues(alpha: 0.15),
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.2),
                          width: 1.5,
                        ),
                      ),
                      child: Icon(icon, color: Colors.white, size: 22),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // ─── Banner Dots ───
  Widget _buildBannerDots() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(3, (index) {
        final isActive = index == _currentBannerPage;
        return AnimatedContainer(
          duration: const Duration(milliseconds: 300),
          margin: const EdgeInsets.symmetric(horizontal: 4),
          width: isActive ? 24 : 8,
          height: 8,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(4),
            color: isActive
                ? const Color(0xFF25D366)
                : const Color(0xFFD0D5DD),
          ),
        );
      }),
    );
  }

  // ─── Quick Actions Section ───
  Widget _buildQuickActionsSection() {
    final quickActions = [
      _QuickActionItem(
        title: 'Send Message',
        subtitle: 'Start Campaign',
        icon: Icons.send_rounded,
        color: const Color(0xFF4A6CF7),
      ),
      _QuickActionItem(
        title: 'Auto Reply',
        subtitle: 'Manage Replies',
        icon: Icons.quickreply_rounded,
        color: const Color(0xFF25D366),
      ),
      _QuickActionItem(
        title: 'Chatbot',
        subtitle: 'Create Flow',
        icon: Icons.smart_toy_rounded,
        color: const Color(0xFF00BFA5),
      ),
      _QuickActionItem(
        title: 'Welcome Msg',
        subtitle: 'Setup Welcome',
        icon: Icons.waving_hand_rounded,
        color: const Color(0xFF26A69A),
      ),

      _QuickActionItem(
        title: 'Group Send',
        subtitle: 'Send to Groups',
        icon: Icons.groups_rounded,
        color: const Color(0xFFFF9800),
      ),
    ];

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        children: [
          // Section Header
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Quick Actions',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF1A1D26),
                  letterSpacing: 0.2,
                ),
              ),
              GestureDetector(
                onTap: () {
                  _scaffoldKey.currentState?.openDrawer();
                },
                child: Row(
                  children: [
                    Text(
                      'View All',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w500,
                        color: Colors.grey.shade500,
                      ),
                    ),
                    const SizedBox(width: 4),
                    Icon(
                      Icons.arrow_forward_ios_rounded,
                      size: 12,
                      color: Colors.grey.shade500,
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          // 3×3 Grid
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 3,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
              childAspectRatio: 1.15,
            ),
            itemCount: quickActions.length,
            itemBuilder: (context, index) {
              return AnimatedCardWrapper(
                controller: _cardAnimController,
                delay: (index * 60).clamp(0, 600),
                child: _QuickActionCard(
                  item: quickActions[index],
                  onTap: () => _handleQuickActionTap(index),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  // ─── Management Section ───
  Widget _buildManagementSection() {
    final managementActions = [
      _QuickActionItem(
        title: 'Templates',
        subtitle: 'Message Templates',
        icon: Icons.description_rounded,
        color: const Color(0xFFE91E63),
      ),
      _QuickActionItem(
        title: 'Contacts',
        subtitle: 'Manage Contacts',
        icon: Icons.contacts_rounded,
        color: const Color(0xFF2196F3),
      ),
    ];

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        children: [
          // Section Header
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Management',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF1A1D26),
                  letterSpacing: 0.2,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          // Grid
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 3,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
              childAspectRatio: 1.15,
            ),
            itemCount: managementActions.length,
            itemBuilder: (context, index) {
              return AnimatedCardWrapper(
                controller: _cardAnimController,
                delay: ((index + 5) * 60).clamp(0, 600),
                child: _QuickActionCard(
                  item: managementActions[index],
                  onTap: () => _handleQuickActionTap(index + 5),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  // ─── Bottom Navigation Bar ───
  Widget _buildBottomNavBar() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 16,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: SizedBox(
          height: 68,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              // Dashboard
              _buildNavItem(
                icon: Icons.home_rounded,
                label: 'Dashboard',
                index: 0,
              ),
              // Campaigns
              _buildNavItem(
                icon: Icons.rocket_launch_rounded,
                label: 'Campaigns',
                index: 1,
              ),
              // Center FAB
              _buildCenterFab(),
              // Reports
              _buildNavItem(
                icon: Icons.bar_chart_rounded,
                label: 'Reports',
                index: 3,
              ),
              // Dummy empty space to keep FAB in center
              const SizedBox(width: 64),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildNavItem({
    required IconData icon,
    required String label,
    required int index,
  }) {
    final isActive = _currentNavIndex == index;
    return GestureDetector(
      onTap: () => _handleBottomNavTap(index),
      behavior: HitTestBehavior.opaque,
      child: SizedBox(
        width: 64,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              size: 24,
              color: isActive
                  ? const Color(0xFF25D366)
                  : const Color(0xFF8492A6),
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 10,
                fontWeight: isActive ? FontWeight.w600 : FontWeight.w400,
                color: isActive
                    ? const Color(0xFF25D366)
                    : const Color(0xFF8492A6),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCenterFab() {
    return GestureDetector(
      onTap: () => _handleBottomNavTap(2),
      child: Container(
        width: 52,
        height: 52,
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF25D366), Color(0xFF128C7E)],
          ),
          shape: BoxShape.circle,
          boxShadow: [
            BoxShadow(
              color: const Color(0xFF25D366).withValues(alpha: 0.4),
              blurRadius: 16,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: const Icon(
          Icons.add_rounded,
          color: Colors.white,
          size: 28,
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────
// QUICK ACTION DATA MODEL
// ─────────────────────────────────────────────
class _QuickActionItem {
  final String title;
  final String subtitle;
  final IconData icon;
  final Color color;

  const _QuickActionItem({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.color,
  });
}

// ─────────────────────────────────────────────
// QUICK ACTION CARD WIDGET
// ─────────────────────────────────────────────
class _QuickActionCard extends StatefulWidget {
  final _QuickActionItem item;
  final VoidCallback onTap;

  const _QuickActionCard({
    required this.item,
    required this.onTap,
  });

  @override
  State<_QuickActionCard> createState() => _QuickActionCardState();
}

class _QuickActionCardState extends State<_QuickActionCard>
    with SingleTickerProviderStateMixin {
  late AnimationController _scaleController;
  bool _isPressed = false;

  @override
  void initState() {
    super.initState();
    _scaleController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 150),
      lowerBound: 0.0,
      upperBound: 0.05,
    );
  }

  @override
  void dispose() {
    _scaleController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _scaleController,
      builder: (context, child) {
        return Transform.scale(
          scale: 1.0 - _scaleController.value,
          child: child,
        );
      },
      child: GestureDetector(
        onTapDown: (_) {
          _scaleController.forward();
          setState(() => _isPressed = true);
        },
        onTapUp: (_) {
          _scaleController.reverse();
          setState(() => _isPressed = false);
          widget.onTap();
        },
        onTapCancel: () {
          _scaleController.reverse();
          setState(() => _isPressed = false);
        },
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: _isPressed
                  ? widget.item.color.withValues(alpha: 0.3)
                  : const Color(0xFFE8ECF0),
              width: 1,
            ),
            boxShadow: [
              BoxShadow(
                color: _isPressed
                    ? widget.item.color.withValues(alpha: 0.15)
                    : Colors.black.withValues(alpha: 0.04),
                blurRadius: _isPressed ? 16 : 8,
                offset: const Offset(0, 3),
                spreadRadius: _isPressed ? 1 : 0,
              ),
            ],
          ),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // Icon Container
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: widget.item.color.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(
                    widget.item.icon,
                    color: widget.item.color,
                    size: 18,
                  ),
                ),
                const SizedBox(height: 6),
                // Title
                Text(
                  widget.item.title,
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 10.5,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF1A1D26),
                    letterSpacing: 0.1,
                    height: 1.1,
                  ),
                ),
                const SizedBox(height: 2),
                // Subtitle
                Text(
                  widget.item.subtitle,
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 8.5,
                    color: Colors.grey.shade500,
                    fontWeight: FontWeight.w400,
                    height: 1.2,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}



// ─────────────────────────────────────────────
// PREMIUM DRAWER
// ─────────────────────────────────────────────
class AppDrawer extends StatelessWidget {
  final Map<String, String> userInfo;
  final VoidCallback onSignOut;

  const AppDrawer({super.key, required this.userInfo, required this.onSignOut});

  void _openScreen(BuildContext context, Widget screen) {
    Navigator.pop(context);
    Navigator.push(context, MaterialPageRoute(builder: (context) => screen));
  }

  @override
  Widget build(BuildContext context) {
    return Drawer(
      backgroundColor: Colors.transparent,
      elevation: 0,
      child: ClipRRect(
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
          child: Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  const Color(0xFF667EEA).withValues(alpha: 0.92),
                  const Color(0xFF764BA2).withValues(alpha: 0.95),
                  const Color(0xFF4A1A7A).withValues(alpha: 0.98),
                ],
              ),
            ),
            child: SafeArea(
              child: Column(
                children: [
                  const SizedBox(height: 16),
                  // Profile section
                  _buildProfileSection(context),
                  const SizedBox(height: 8),
                  // Divider
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 24),
                    child: Container(
                      height: 0.5,
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [
                            Colors.white.withValues(alpha: 0.0),
                            Colors.white.withValues(alpha: 0.3),
                            Colors.white.withValues(alpha: 0.0),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  // Menu items
                  Expanded(
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Column(
                        children: [
                          _DrawerMenuItem(
                            icon: Icons.dashboard_rounded,
                            title: 'Dashboard',
                            subtitle: 'Home screen',
                            accentColor: const Color(0xFF4FACFE),
                            onTap: () => Navigator.pop(context),
                          ),
                          _DrawerMenuItem(
                            icon: Icons.campaign_rounded,
                            title: 'Campaign Status',
                            subtitle: 'View all campaign history',
                            accentColor: const Color(0xFF38EF7D),
                            onTap: () {
                              Navigator.pop(context);
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (context) =>
                                      const CampaignStatusScreen(),
                                ),
                              );
                            },
                          _DrawerMenuItem(
                            icon: Icons.hub_rounded,
                            title: 'Group Sender Status',
                            subtitle: 'View group sender history',
                            accentColor: const Color(0xFFF093FB),
                            onTap: () {
                              Navigator.pop(context);
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (context) =>
                                      const GroupSenderStatusScreen(),
                                ),
                              );
                            },
                          ),
                          const SizedBox(height: 6),
                          _buildDrawerDivider(),
                          const SizedBox(height: 6),
                          _DrawerMenuItem(
                            icon: Icons.cloud_sync_rounded,
                            title: 'Cloud Backup & Restore',
                            subtitle: 'Backup contacts, chatbots & rules',
                            accentColor: const Color(0xFF004D40),
                            onTap: () => _openScreen(
                              context,
                              const BackupRestoreScreen(),
                            ),
                          ),
                          _DrawerMenuItem(
                            icon: Icons.privacy_tip_rounded,
                            title: 'Privacy Policy',
                            subtitle: 'View policy',
                            accentColor: const Color(0xFFFBC2EB),
                            onTap: () => _openScreen(
                              context,
                              const PrivacyPolicyScreen(),
                            ),
                          ),
                          _DrawerMenuItem(
                            icon: Icons.info_rounded,
                            title: 'About Us',
                            subtitle: 'Know more',
                            accentColor: const Color(0xFF00F2FE),
                            onTap: () =>
                                _openScreen(context, const AboutUsScreen()),
                          ),
                          _DrawerMenuItem(
                            icon: Icons.mail_rounded,
                            title: 'Contact Us',
                            subtitle: 'Get in touch',
                            accentColor: const Color(0xFFFA709A),
                            onTap: () =>
                                _openScreen(context, const ContactUsScreen()),
                          ),
                          _DrawerMenuItem(
                            icon: Icons.gavel_rounded,
                            title: 'Terms & Conditions',
                            subtitle: 'Read usage terms',
                            accentColor: const Color(0xFFFFD700),
                            onTap: () => _openScreen(
                              context,
                              const TermsAndConditionsScreen(),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  // Sign out + Version
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                    child: Column(
                      children: [
                        _buildDrawerDivider(),
                        const SizedBox(height: 8),
                        _DrawerMenuItem(
                          icon: Icons.logout_rounded,
                          title: 'Sign Out',
                          subtitle: 'See you later',
                          accentColor: const Color(0xFFFF6B6B),
                          onTap: () {
                            Navigator.pop(context);
                            onSignOut();
                          },
                        ),
                        const SizedBox(height: 6),
                        Text(
                          'BulkSendly v1.0.0',
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.4),
                            fontSize: 10,
                            fontWeight: FontWeight.w400,
                          ),
                        ),
                        const SizedBox(height: 8),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildProfileSection(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: Colors.white.withValues(alpha: 0.15),
            width: 0.5,
          ),
        ),
        child: Row(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(
                  color: Colors.white.withValues(alpha: 0.5),
                  width: 2,
                ),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF764BA2).withValues(alpha: 0.4),
                    blurRadius: 12,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: userInfo['photo']?.isNotEmpty == true
                  ? CircleAvatar(
                      radius: 22,
                      backgroundImage: NetworkImage(userInfo['photo']!),
                    )
                  : const CircleAvatar(
                      radius: 22,
                      backgroundColor: Color(0xFF9B7ADB),
                      child: Icon(Icons.person, color: Colors.white, size: 24),
                    ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    userInfo['name'] ?? 'User',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      letterSpacing: 0.2,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    userInfo['email'] ?? 'user@email.com',
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.6),
                      fontSize: 11,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: const Color(0xFF38EF7D).withValues(alpha: 0.2),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(
                  color: const Color(0xFF38EF7D).withValues(alpha: 0.4),
                  width: 0.5,
                ),
              ),
              child: const Text(
                'PRO',
                style: TextStyle(
                  color: Color(0xFF38EF7D),
                  fontSize: 9,
                  fontWeight: FontWeight.w700,
                  letterSpacing: 1,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDrawerDivider() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24),
      child: Container(
        height: 0.5,
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [
              Colors.white.withValues(alpha: 0.0),
              Colors.white.withValues(alpha: 0.2),
              Colors.white.withValues(alpha: 0.0),
            ],
          ),
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────
// DRAWER MENU ITEM
// ─────────────────────────────────────────────
class _DrawerMenuItem extends StatefulWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final Color accentColor;
  final VoidCallback onTap;

  const _DrawerMenuItem({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.accentColor,
    required this.onTap,
  });

  @override
  State<_DrawerMenuItem> createState() => _DrawerMenuItemState();
}

class _DrawerMenuItemState extends State<_DrawerMenuItem> {
  bool _isHovered = false;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTapDown: (_) => setState(() => _isHovered = true),
      onTapUp: (_) {
        setState(() => _isHovered = false);
        widget.onTap();
      },
      onTapCancel: () => setState(() => _isHovered = false),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        margin: const EdgeInsets.symmetric(horizontal: 14, vertical: 2),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: _isHovered
              ? Colors.white.withValues(alpha: 0.15)
              : Colors.transparent,
          borderRadius: BorderRadius.circular(14),
        ),
        child: Row(
          children: [
            Container(
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                color: widget.accentColor.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(widget.icon, color: widget.accentColor, size: 17),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    widget.title,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 13,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  Text(
                    widget.subtitle,
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.5),
                      fontSize: 10,
                    ),
                  ),
                ],
              ),
            ),
            Icon(
              Icons.chevron_right_rounded,
              color: Colors.white.withValues(alpha: 0.3),
              size: 18,
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────
// ANIMATED CARD WRAPPER
// ─────────────────────────────────────────────
class AnimatedCardWrapper extends StatelessWidget {
  final AnimationController controller;
  final int delay;
  final Widget child;

  const AnimatedCardWrapper({
    super.key,
    required this.controller,
    required this.delay,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    final delayedAnimation = CurvedAnimation(
      parent: controller,
      curve: Interval(
        delay / 1000,
        (delay + 500) / 1000,
        curve: Curves.easeOutCubic,
      ),
    );

    return AnimatedBuilder(
      animation: delayedAnimation,
      builder: (context, child) {
        return Transform.translate(
          offset: Offset(0, 40 * (1 - delayedAnimation.value)),
          child: Opacity(opacity: delayedAnimation.value, child: child),
        );
      },
      child: child,
    );
  }
}
