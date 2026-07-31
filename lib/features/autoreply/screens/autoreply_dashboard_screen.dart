import 'package:flutter/material.dart';
import 'package:autoreply/features/autoreply/keyword_reply/screens/keyword_reply_library_screen.dart';
import 'package:autoreply/features/autoreply/template/screens/autoreply_template_library_screen.dart';
import 'package:autoreply/features/autoreply/welcome_message/screens/welcome_message_library_screen.dart';

class AutoReplyDashboardScreen extends StatelessWidget {
  const AutoReplyDashboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final cards = [
      _AutoReplyCardData(
        title: 'Reply Template',
        subtitle: 'Reusable templates',
        icon: Icons.reply_all_rounded,
        colors: const [Color(0xFF43CEA2), Color(0xFF185A9D)],
      ),
      _AutoReplyCardData(
        title: 'Welcome Msg',
        subtitle: 'First message',
        icon: Icons.waving_hand_rounded,
        colors: const [Color(0xFFFFB75E), Color(0xFFED8F03)],
      ),
      _AutoReplyCardData(
        title: 'Keyword Reply',
        subtitle: 'Triggered replies',
        icon: Icons.key_rounded,
        colors: const [Color(0xFF7F7FD5), Color(0xFF86A8E7)],
      ),
    ];

    return Scaffold(
      backgroundColor: const Color(0xFFE8EEF5), // Premium subtle background
      appBar: AppBar(
        title: const Text(
          'Message Automation',
          style: TextStyle(fontWeight: FontWeight.w700, fontSize: 18),
        ),
        centerTitle: true,
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        elevation: 0,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(bottom: Radius.circular(20)),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Full width AI Agent Card
            _buildAiAgentCard(context),

            const SizedBox(height: 24),

            // Section Title
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 4),
              child: Text(
                'Automation Tools',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF1A1D26),
                  letterSpacing: 0.3,
                ),
              ),
            ),
            const SizedBox(height: 14),

            // Smaller Cards Grid
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
                childAspectRatio:
                    1.15, // Made smaller and squarer to reduce height size
              ),
              itemCount: cards.length,
              itemBuilder: (context, index) {
                final card = cards[index];
                return _AutoReplyCard(
                  data: card,
                  onTap: () {
                    if (index == 0) {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) =>
                              const AutoReplyTemplateLibraryScreen(),
                        ),
                      );
                      return;
                    }

                    if (index == 1) {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) =>
                              const WelcomeMessageLibraryScreen(),
                        ),
                      );
                      return;
                    }

                    if (index == 2) {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) =>
                              const KeywordReplyLibraryScreen(),
                        ),
                      );
                      return;
                    }

                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text(
                          '${card.title} screen will be added next',
                        ),
                        behavior: SnackBarBehavior.floating,
                      ),
                    );
                  },
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAiAgentCard(BuildContext context) {
    return GestureDetector(
      onTap: () {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('AI Agent configuration opening...'),
            behavior: SnackBarBehavior.floating,
          ),
        );
      },
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF667EEA), Color(0xFF764BA2)],
          ),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: Colors.white.withValues(alpha: 0.3),
            width: 1.5,
          ),
          boxShadow: [
            BoxShadow(
              color: const Color(0xFF667EEA).withValues(alpha: 0.4),
              blurRadius: 18,
              offset: const Offset(0, 6),
              spreadRadius: 1,
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              width: 50,
              height: 50,
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.2),
                borderRadius: BorderRadius.circular(14),
              ),
              child: const Icon(
                Icons.smart_toy_rounded,
                color: Colors.white,
                size: 28,
              ),
            ),
            const SizedBox(width: 16),
            const Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'AI Agent',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.5,
                    ),
                  ),
                  SizedBox(height: 4),
                  Text(
                    'Smart auto-replies powered by AI',
                    style: TextStyle(
                      color: Colors.white70,
                      fontSize: 12,
                      fontWeight: FontWeight.w400,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.2),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(
                Icons.arrow_forward_ios_rounded,
                size: 14,
                color: Colors.white,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _AutoReplyCardData {
  final String title;
  final String subtitle;
  final IconData icon;
  final List<Color> colors;

  const _AutoReplyCardData({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.colors,
  });
}

class _AutoReplyCard extends StatefulWidget {
  final _AutoReplyCardData data;
  final VoidCallback onTap;

  const _AutoReplyCard({required this.data, required this.onTap});

  @override
  State<_AutoReplyCard> createState() => _AutoReplyCardState();
}

class _AutoReplyCardState extends State<_AutoReplyCard>
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
      upperBound: 0.04,
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
              color: const Color(0xFF000000).withValues(alpha: 0.08),
              width: 1,
            ),
            boxShadow: [
              BoxShadow(
                color: _isPressed
                    ? widget.data.colors[0].withValues(alpha: 0.3)
                    : const Color(0xFF000000).withValues(alpha: 0.08),
                blurRadius: _isPressed ? 14 : 10,
                offset: const Offset(0, 4),
                spreadRadius: _isPressed ? 1 : 0,
              ),
            ],
          ),
          child: Padding(
            padding: const EdgeInsets.all(12), // Reduced padding
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      width: 40, // Reduced icon size
                      height: 40,
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                          colors: widget.data.colors,
                        ),
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: [
                          BoxShadow(
                            color: widget.data.colors[0].withValues(
                              alpha: 0.25,
                            ),
                            blurRadius: 8,
                            offset: const Offset(0, 3),
                          ),
                        ],
                      ),
                      child: Icon(
                        widget.data.icon,
                        color: Colors.white,
                        size: 20,
                      ),
                    ),
                    const Spacer(),
                    Container(
                      width: 24,
                      height: 24,
                      decoration: BoxDecoration(
                        color: widget.data.colors[0].withValues(alpha: 0.08),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Icon(
                        Icons.arrow_forward_ios_rounded,
                        size: 11,
                        color: widget.data.colors[0],
                      ),
                    ),
                  ],
                ),
                const Spacer(),
                Text(
                  widget.data.title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 13, // Reduced font size
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF1A1D26),
                    letterSpacing: 0.1,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  widget.data.subtitle,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 10, // Reduced font size
                    color: Colors.grey.shade500,
                    fontWeight: FontWeight.w400,
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
