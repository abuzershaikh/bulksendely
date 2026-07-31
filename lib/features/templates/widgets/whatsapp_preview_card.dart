import 'package:autoreply/data/models/button_template_model.dart';
import 'package:flutter/material.dart';

class WhatsappPreviewCard extends StatelessWidget {
  final String title;
  final String caption;
  final String footer;
  final String? imageUrl;
  final List<TemplateButton> buttons;
  final bool isListTemplate;
  final String listButtonText;
  final List<ListTemplateSection> sections;

  const WhatsappPreviewCard({
    super.key,
    required this.title,
    required this.caption,
    required this.footer,
    this.imageUrl,
    required this.buttons,
    this.isListTemplate = false,
    this.listButtonText = '',
    this.sections = const [],
  });

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        constraints: const BoxConstraints(maxWidth: 280),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: const BorderRadius.only(
            topLeft: Radius.circular(0),
            topRight: Radius.circular(10),
            bottomLeft: Radius.circular(10),
            bottomRight: Radius.circular(10),
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              blurRadius: 4,
              offset: const Offset(0, 1),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            // ── Header Image ──
            if (imageUrl != null && imageUrl!.isNotEmpty)
              ClipRRect(
                borderRadius: const BorderRadius.only(topRight: Radius.circular(10)),
                child: Image.network(
                  imageUrl!,
                  width: double.infinity,
                  height: 130,
                  fit: BoxFit.cover,
                  errorBuilder: (context, error, stackTrace) => Container(
                    width: double.infinity,
                    height: 130,
                    color: Colors.grey.shade100,
                    child: Icon(Icons.broken_image_outlined, color: Colors.grey.shade400, size: 28),
                  ),
                ),
              ),

            // ── Text Block ──
            Padding(
              padding: const EdgeInsets.fromLTRB(10, 8, 10, 6),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (title.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 3),
                      child: Text(
                        title,
                        style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          fontSize: 14,
                          color: Colors.black87,
                        ),
                      ),
                    ),
                  Text(
                    caption,
                    style: const TextStyle(fontSize: 13, color: Colors.black87, height: 1.35),
                  ),
                  if (footer.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      footer,
                      style: TextStyle(color: Colors.grey.shade500, fontSize: 12),
                    ),
                  ],
                  // Timestamp
                  const SizedBox(height: 2),
                  Align(
                    alignment: Alignment.bottomRight,
                    child: Text(
                      '10:42 AM',
                      style: TextStyle(color: Colors.grey.shade400, fontSize: 10),
                    ),
                  ),
                ],
              ),
            ),

            // ── Buttons ──
            if (isListTemplate && sections.isNotEmpty) ...[
              Divider(height: 1, thickness: 0.5, color: Colors.grey.shade200),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 10),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.list_alt_rounded, size: 14, color: Color(0xFF00A884)),
                    const SizedBox(width: 5),
                    Text(
                      listButtonText.isEmpty ? 'Select' : listButtonText,
                      style: const TextStyle(
                        color: Color(0xFF00A884),
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                    ),
                  ],
                ),
              ),
            ] else if (buttons.isNotEmpty) ...[
              Divider(height: 1, thickness: 0.5, color: Colors.grey.shade200),
              for (var i = 0; i < buttons.length; i++) ...[
                _WaButton(button: buttons[i]),
                if (i < buttons.length - 1)
                  Divider(height: 1, thickness: 0.5, color: Colors.grey.shade200),
              ],
            ],
          ],
        ),
      ),
    );
  }
}

class _WaButton extends StatelessWidget {
  final TemplateButton button;
  const _WaButton({required this.button});

  @override
  Widget build(BuildContext context) {
    IconData icon;
    switch (button.type) {
      case ButtonTemplateType.text:
        icon = Icons.reply_rounded;
      case ButtonTemplateType.link:
        icon = Icons.open_in_new_rounded;
      case ButtonTemplateType.call:
        icon = Icons.phone_rounded;
      case ButtonTemplateType.copy:
        icon = Icons.copy_rounded;
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 14, color: const Color(0xFF00A884)),
          const SizedBox(width: 5),
          Text(
            button.displayText.isEmpty ? 'Button' : button.displayText,
            style: const TextStyle(
              color: Color(0xFF00A884),
              fontWeight: FontWeight.w600,
              fontSize: 14,
            ),
          ),
        ],
      ),
    );
  }
}
