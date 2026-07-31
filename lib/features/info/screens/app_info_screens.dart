import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

const String kSupportEmail = 'support@wealthmize.com';
const String kSupportEmailSubject = 'Support Request - Bulksendly';

class AboutUsScreen extends StatelessWidget {
  const AboutUsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const _InfoScreenScaffold(
      title: 'About Us',
      subtitle: 'Who we are and what we help you achieve',
      accentColor: Color(0xFF00F2FE),
      sections: [
        _InfoSection(
          heading: 'Welcome to Bulksendly',
          body:
              'Bulksendly is built to help businesses grow faster with simple WhatsApp marketing tools. We focus on making campaign sending, contact management, auto-replies, and bulk communication easy to use for every business owner.',
        ),
        _InfoSection(
          heading: 'Our mission',
          body:
              'Our mission is to give small and growing businesses a practical platform for reaching customers, saving time, and building stronger relationships through smart messaging workflows.',
        ),
        _InfoSection(
          heading: 'What you can do',
          body:
              'With the app, you can manage contacts, create message templates, track campaign activity, connect WhatsApp, and automate customer communication from one place.',
        ),
      ],
    );
  }
}

class PrivacyPolicyScreen extends StatelessWidget {
  const PrivacyPolicyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const _InfoScreenScaffold(
      title: 'Privacy Policy',
      subtitle: 'How we collect, use, and protect your information',
      accentColor: Color(0xFFFBC2EB),
      sections: [
        _InfoSection(
          heading: 'Information we collect',
          body:
              'We do not store personal chat content or private customer conversation data on our servers. Messaging works in a direct chat-to-chat flow through your connected WhatsApp session, and any contact/message content remains under your control.',
        ),
        _InfoSection(
          heading: 'Data policy',
          body:
              'Your data is used only for providing app features such as account access, campaign sending, contact management, WhatsApp connection, automation features, service support, and platform improvement. We do not ask for unnecessary personal data beyond what is needed for app functionality.',
        ),
        _InfoSection(
          heading: 'How we use information',
          body:
              'Your information may be used to maintain your account, process activity inside the app, improve reliability, troubleshoot errors, respond to support requests, and communicate service-related updates or important notices.',
        ),
        _InfoSection(
          heading: 'Contact and campaign data',
          body:
              'Any contact lists, phone numbers, customer records, templates, and campaign content uploaded by you remain your responsibility. You should only upload data that you are legally allowed to use and for which proper customer consent has been obtained where required.',
        ),
        _InfoSection(
          heading: 'Children policy',
          body:
              'This app is not intended for children under the age of 13. We do not knowingly collect personal information directly from children. If you believe that a child has submitted personal data through the app, please contact support at support@wealthmize.com so that appropriate review and removal steps can be taken.',
        ),
        _InfoSection(
          heading: 'Data sharing',
          body:
              'We do not sell your personal data. Information may only be shared when necessary to operate the service, comply with legal obligations, protect platform security, investigate misuse, or respond to valid government or legal requests.',
        ),
        _InfoSection(
          heading: 'Data retention',
          body:
              'We keep data only for as long as it is reasonably needed for service delivery, account maintenance, compliance, dispute resolution, security monitoring, or legitimate business operations. Some records may be retained longer when required by law or technical necessity.',
        ),
        _InfoSection(
          heading: 'Data protection',
          body:
              'We take reasonable administrative, technical, and operational steps to protect your data from unauthorized access, misuse, alteration, or disclosure. However, no digital platform can guarantee absolute security, so users should also protect their devices and account access.',
        ),
        _InfoSection(
          heading: 'Your responsibility',
          body:
              'You are responsible for ensuring that the contacts, templates, and messages you upload or send comply with applicable laws, WhatsApp policies, anti-spam rules, and customer consent requirements.',
        ),
        _InfoSection(
          heading: 'Policy updates',
          body:
              'This privacy policy may be updated from time to time to reflect feature changes, legal requirements, or operational improvements. Continued use of the app after updates means you accept the revised policy.',
        ),
        _InfoSection(
          heading: 'Contact for Privacy Concerns',
          body:
              'If you have any questions, concerns, or requests regarding this Privacy Policy or your data, please email us at support@wealthmize.com.',
        ),
      ],
    );
  }
}

class TermsAndConditionsScreen extends StatelessWidget {
  const TermsAndConditionsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const _InfoScreenScaffold(
      title: 'Terms & Conditions',
      subtitle: 'Rules for using the app responsibly',
      accentColor: Color(0xFFFFD700),
      sections: [
        _InfoSection(
          heading: 'Use of service',
          body:
              'By using Bulksendly, you agree to use the platform only for lawful business communication and in accordance with WhatsApp policies and applicable regulations.',
        ),
        _InfoSection(
          heading: 'Account responsibility',
          body:
              'You are responsible for the activity performed through your account, the content you send, and the contact lists you manage inside the app.',
        ),
        _InfoSection(
          heading: 'Prohibited activity',
          body:
              'Spam, abusive messaging, unauthorized promotions, misleading content, and any activity that violates third-party platform rules are strictly prohibited.',
        ),
        _InfoSection(
          heading: 'Service changes',
          body:
              'We may update, improve, suspend, or modify features when necessary to maintain quality, security, compliance, or business operations.',
        ),
        _InfoSection(
          heading: 'Contact Information',
          body:
              'For any questions or inquiries regarding these Terms & Conditions, please write to us at support@wealthmize.com.',
        ),
      ],
    );
  }
}

class ContactUsScreen extends StatelessWidget {
  const ContactUsScreen({super.key});

  Future<void> _launchExternalApp(
    BuildContext context,
    Uri uri, {
    required String errorMessage,
  }) async {
    final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!context.mounted || launched) {
      return;
    }

    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(errorMessage)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF6F8FF),
      appBar: AppBar(
        title: const Text('Contact Us'),
        elevation: 0,
        backgroundColor: Colors.transparent,
        foregroundColor: const Color(0xFF1E2A4A),
      ),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFFF6F8FF), Color(0xFFE9EEFF)],
          ),
        ),
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(24),
                gradient: const LinearGradient(
                  colors: [Color(0xFF667EEA), Color(0xFF764BA2)],
                ),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF667EEA).withValues(alpha: 0.18),
                    blurRadius: 24,
                    offset: const Offset(0, 12),
                  ),
                ],
              ),
              child: const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.mark_email_read_rounded, color: Colors.white, size: 34),
                  SizedBox(height: 14),
                  Text(
                    'We are here to help',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 24,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  SizedBox(height: 8),
                  Text(
                    'Send an email to our support team for any queries, assistance, or account inquiries.',
                    style: TextStyle(
                      color: Colors.white70,
                      fontSize: 14,
                      height: 1.45,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            const _ContactInfoCard(
              icon: Icons.email_rounded,
              title: 'Support Email',
              value: kSupportEmail,
              description: 'Official support channel for assistance and inquiries.',
            ),
            const SizedBox(height: 20),
            ElevatedButton.icon(
              onPressed: () => _launchExternalApp(
                context,
                Uri.parse(
                  'mailto:$kSupportEmail?subject=${Uri.encodeComponent(kSupportEmailSubject)}',
                ),
                errorMessage: 'Email client open nahi ho paya. Please email support@wealthmize.com',
              ),
              icon: const Icon(Icons.send_rounded),
              label: const Text('Send Email'),
              style: ElevatedButton.styleFrom(
                minimumSize: const Size.fromHeight(54),
                backgroundColor: const Color(0xFF667EEA),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(18),
                ),
                textStyle: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
            const SizedBox(height: 20),
            const _InfoSectionCard(
              heading: 'Support note',
              body:
                  'Please mention your registered account email and issue details when writing to support@wealthmize.com so we can help you faster.',
            ),
          ],
        ),
      ),
    );
  }
}

class _InfoScreenScaffold extends StatelessWidget {
  final String title;
  final String subtitle;
  final Color accentColor;
  final List<_InfoSection> sections;

  const _InfoScreenScaffold({
    required this.title,
    required this.subtitle,
    required this.accentColor,
    required this.sections,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF6F8FF),
      appBar: AppBar(
        title: Text(title),
        elevation: 0,
        backgroundColor: Colors.transparent,
        foregroundColor: const Color(0xFF1E2A4A),
      ),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFFF6F8FF), Color(0xFFE9EEFF)],
          ),
        ),
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(24),
                gradient: LinearGradient(
                  colors: [accentColor, const Color(0xFF667EEA)],
                ),
                boxShadow: [
                  BoxShadow(
                    color: accentColor.withValues(alpha: 0.18),
                    blurRadius: 24,
                    offset: const Offset(0, 12),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 26,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    subtitle,
                    style: const TextStyle(
                      color: Colors.white70,
                      fontSize: 14,
                      height: 1.45,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            for (final section in sections) ...[
              _InfoSectionCard(
                heading: section.heading,
                body: section.body,
              ),
              const SizedBox(height: 14),
            ],
          ],
        ),
      ),
    );
  }
}

class _InfoSectionCard extends StatelessWidget {
  final String heading;
  final String body;

  const _InfoSectionCard({required this.heading, required this.body});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0F172A).withValues(alpha: 0.06),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            heading,
            style: const TextStyle(
              color: Color(0xFF1E2A4A),
              fontSize: 17,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            body,
            style: const TextStyle(
              color: Color(0xFF475569),
              fontSize: 14,
              height: 1.6,
            ),
          ),
        ],
      ),
    );
  }
}

class _ContactInfoCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final String value;
  final String description;

  const _ContactInfoCard({
    required this.icon,
    required this.title,
    required this.value,
    required this.description,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0F172A).withValues(alpha: 0.06),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              color: const Color(0xFF667EEA).withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Icon(icon, color: const Color(0xFF667EEA)),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    color: Color(0xFF1E2A4A),
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: const TextStyle(
                    color: Color(0xFF334155),
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  description,
                  style: const TextStyle(
                    color: Color(0xFF64748B),
                    fontSize: 13,
                    height: 1.45,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoSection {
  final String heading;
  final String body;

  const _InfoSection({required this.heading, required this.body});
}
