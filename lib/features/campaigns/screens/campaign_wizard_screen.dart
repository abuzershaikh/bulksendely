import 'package:autoreply/core/theme/app_colors.dart';
import 'package:autoreply/data/local/contact_storage.dart';
import 'package:autoreply/data/local/template_storage.dart';
import 'package:autoreply/data/models/button_template_model.dart';
import 'package:autoreply/data/models/contact_group_model.dart';
import 'package:autoreply/features/campaigns/models/campaign_draft_model.dart';
import 'package:autoreply/features/campaigns/services/campaign_send_service.dart';
import 'package:autoreply/features/campaign_status/services/campaign_status_service.dart';
import 'package:autoreply/core/network/api_client.dart';
import 'package:autoreply/features/contacts/screens/contact_groups_screen.dart';
import 'package:autoreply/features/media/services/cloudflare_upload_service.dart';
import 'package:autoreply/features/templates/screens/create_template_screen.dart';
import 'package:autoreply/features/campaigns/screens/select_contacts_oneshot_screen.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

class CampaignWizardScreen extends StatefulWidget {
  const CampaignWizardScreen({super.key});

  @override
  State<CampaignWizardScreen> createState() => _CampaignWizardScreenState();
}

class _CampaignWizardScreenState extends State<CampaignWizardScreen> {
  final PageController _pageController = PageController();
  final CampaignDraftModel _draft = CampaignDraftModel();
  final CampaignSendService _campaignSendService = CampaignSendService();
  final CampaignStatusService _campaignStatusService = CampaignStatusService();
  
  int _currentStep = 0;

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  void _nextStep() {
    if (_currentStep == 0 && !_draft.isStep1Valid) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please fill all fields and select a contact list.')));
      return;
    }
    if (_currentStep == 1 && !_draft.isStep2Valid) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please select a template or write a message.')));
      return;
    }

    if (_currentStep < 2) {
      _pageController.nextPage(duration: const Duration(milliseconds: 300), curve: Curves.easeInOut);
      setState(() => _currentStep++);
    } else {
      _launchCampaign();
    }
  }

  void _prevStep() {
    if (_currentStep > 0) {
      _pageController.previousPage(duration: const Duration(milliseconds: 300), curve: Curves.easeInOut);
      setState(() => _currentStep--);
    } else {
      Navigator.pop(context);
    }
  }

  Future<void> _launchCampaign() async {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => const Center(
        child: Card(
          child: Padding(
            padding: EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                CircularProgressIndicator(),
                SizedBox(height: 16),
                Text('Launching Campaign...', style: TextStyle(fontWeight: FontWeight.bold)),
                SizedBox(height: 8),
                Text('Sending in Progress', style: TextStyle(fontSize: 12, color: Colors.grey)),
              ],
            ),
          ),
        ),
      ),
    );

    try {
      final group = _draft.selectedGroup;
      if (group == null) {
        throw Exception('Please select a contact group');
      }

      final result = _draft.useTemplate
          ? await _campaignSendService.sendTemplateMessage(
              campaignName: _draft.name.trim(),
              group: group,
              template: _draft.selectedTemplate!,
              countryCode: _draft.countryCode,
              delaySeconds: _draft.delaySeconds,
              scheduledAt: _draft.scheduledAt,
            )
          : _draft.messageMode == CampaignMessageMode.media
              ? await _campaignSendService.sendMediaMessage(
                  campaignName: _draft.name.trim(),
                  group: group,
                  mediaType: _draft.mediaType,
                  mediaUrl: _draft.mediaUrl.trim(),
                  caption: _draft.mediaCaption.trim(),
                  filename: _draft.mediaFilename.trim(),
                  countryCode: _draft.countryCode,
                  delaySeconds: _draft.delaySeconds,
                  scheduledAt: _draft.scheduledAt,
                )
              : await _campaignSendService.sendPlainMessage(
                  campaignName: _draft.name.trim(),
                  group: group,
                  message: _draft.plainMessage.trim(),
                  countryCode: _draft.countryCode,
                  delaySeconds: _draft.delaySeconds,
                  scheduledAt: _draft.scheduledAt,
                );

      if (!mounted) {
        return;
      }

      try {
        final accessToken = await ApiClient.requireWaziperAccessToken();
        String msgLabel = 'Plain Text';
        if (_draft.useTemplate) {
          msgLabel = 'Template: ${_draft.selectedTemplate?.name ?? ''}';
        } else if (_draft.messageMode == CampaignMessageMode.media) {
          msgLabel = 'Media: ${_draft.mediaType.name}';
        }

        await _campaignStatusService.saveCampaignStatus({
          'access_token': accessToken,
          'campaign_name': _draft.name.trim(),
          'target_name': group.name,
          'target_count': result.total,
          'sent_count': result.sent,
          'failed_count': result.failedNumbers.length,
          'message_mode': _draft.useTemplate ? 'template' : _draft.messageMode.name,
          'message_label': msgLabel,
          'delay_seconds': _draft.delaySeconds,
          'instance_id': result.instanceId,
          'schedule_at': _draft.scheduledAt != null ? (_draft.scheduledAt!.millisecondsSinceEpoch ~/ 1000) : 0,
          'items': result.items.map((i) => i.toJson()).toList(),
          if (result.campaignId.isNotEmpty) 'ids': result.campaignId,
          'status': result.queued ? 'queued' : 'completed',
        });
      } catch (e) {
        debugPrint('Failed to save campaign status: $e');
      }

      Navigator.pop(context);
      final failed = result.total - result.sent;
      final message = result.queued
          ? 'Campaign send in progress for ${result.total} contacts'
          : failed == 0
              ? 'Campaign sent to ${result.sent}/${result.total} contacts'
              : 'Campaign sent to ${result.sent}/${result.total}. Failed: $failed';

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(message),
          backgroundColor: failed == 0 ? Colors.green : Colors.orange,
        ),
      );

      if (result.queued) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Message sending continues in the background.',
            ),
            backgroundColor: Colors.blueGrey,
          ),
        );
      } else if (failed == 0) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Using instance ${result.instanceId}'),
            backgroundColor: Colors.blueGrey,
          ),
        );
      }

      Navigator.pop(context);
    } catch (e) {
      if (!mounted) {
        return;
      }

      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Campaign failed: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        title: const Text('Create Campaign', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        elevation: 0.5,
        leading: IconButton(
          icon: const Icon(Icons.close_rounded),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: Column(
        children: [
          // ── Stepper ──
          Container(
            color: Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 20),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                _buildStepIndicator(0, 'Setup'),
                _buildStepLine(0),
                _buildStepIndicator(1, 'Message'),
                _buildStepLine(1),
                _buildStepIndicator(2, 'Launch'),
              ],
            ),
          ),

          // ── Page Views ──
          Expanded(
            child: PageView(
              controller: _pageController,
              physics: const NeverScrollableScrollPhysics(), // Disable manual swipe to force validation
              children: [
                _Step1Setup(draft: _draft, onChanged: () => setState(() {})),
                _Step2Message(draft: _draft, onChanged: () => setState(() {})),
                _Step3Launch(draft: _draft, onChanged: () => setState(() {})),
              ],
            ),
          ),
        ],
      ),
      
      // ── Bottom Navigation ──
      bottomNavigationBar: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [
            BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, -4)),
          ],
        ),
        child: SafeArea(
          child: Row(
            children: [
              if (_currentStep > 0)
                Expanded(
                  child: OutlinedButton(
                    onPressed: _prevStep,
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      side: BorderSide(color: Colors.grey.shade300),
                    ),
                    child: const Text('Back', style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold)),
                  ),
                ),
              if (_currentStep > 0) const SizedBox(width: 16),
              Expanded(
                flex: 2,
                child: ElevatedButton(
                  onPressed: _nextStep,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primaryBlue,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    elevation: 0,
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(_currentStep == 2 ? 'Launch Campaign' : 'Next Step', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                      if (_currentStep < 2) ...[
                        const SizedBox(width: 8),
                        const Icon(Icons.arrow_forward_rounded, size: 18),
                      ]
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStepIndicator(int stepIndex, String title) {
    final isActive = _currentStep == stepIndex;
    final isCompleted = _currentStep > stepIndex;
    final color = (isActive || isCompleted) ? AppColors.primaryBlue : Colors.grey.shade300;
    
    return Column(
      children: [
        Container(
          width: 32, height: 32,
          decoration: BoxDecoration(
            color: isActive ? color : (isCompleted ? color : Colors.white),
            shape: BoxShape.circle,
            border: Border.all(color: color, width: 2),
            boxShadow: isActive ? [BoxShadow(color: color.withValues(alpha: 0.3), blurRadius: 8)] : null,
          ),
          child: Center(
            child: isCompleted 
                ? const Icon(Icons.check, size: 18, color: Colors.white)
                : Text('${stepIndex + 1}', style: TextStyle(
                    color: isActive ? Colors.white : Colors.grey.shade500, 
                    fontWeight: FontWeight.bold
                  )),
          ),
        ),
        const SizedBox(height: 6),
        Text(title, style: TextStyle(fontSize: 11, fontWeight: isActive ? FontWeight.bold : FontWeight.w500, color: (isActive || isCompleted) ? AppColors.primaryBlue : Colors.grey)),
      ],
    );
  }

  Widget _buildStepLine(int stepIndex) {
    final isCompleted = _currentStep > stepIndex;
    return Expanded(
      child: Container(
        height: 2,
        margin: const EdgeInsets.symmetric(horizontal: 8).copyWith(bottom: 20),
        color: isCompleted ? AppColors.primaryBlue : Colors.grey.shade200,
      ),
    );
  }
}

// ============================================================================
// STEP 1: SETUP
// ============================================================================
class _Step1Setup extends StatelessWidget {
  final CampaignDraftModel draft;
  final VoidCallback onChanged;

  const _Step1Setup({required this.draft, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    final groups = ContactStorage().groups;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Campaign Details', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 24),
          
          _Label('Campaign Name'),
          TextField(
            onChanged: (v) { draft.name = v; onChanged(); },
            decoration: _inputDeco('e.g. Summer Promo 2026'),
          ),
          const SizedBox(height: 20),

          _Label('Country Code'),
          TextField(
            onChanged: (v) { draft.countryCode = v; onChanged(); },
            controller: TextEditingController(text: draft.countryCode)..selection = TextSelection.collapsed(offset: draft.countryCode.length),
            decoration: _inputDeco('+91'),
            keyboardType: TextInputType.phone,
          ),
          const SizedBox(height: 24),

          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _Label('Select Contact Group'),
              TextButton(
                onPressed: () async {
                  await Navigator.push(context, MaterialPageRoute(builder: (ctx) => const ContactGroupsScreen()));
                  onChanged(); // Refresh if they created a new group
                },
                child: const Text('Add New +', style: TextStyle(fontSize: 12)),
              ),
            ],
          ),
          
          if (groups.isEmpty)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: Colors.orange.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)),
              child: const Text('No Contact Groups found. Please add a group first.', style: TextStyle(color: Colors.orange)),
            )
          else ...[
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.grey.shade300),
              ),
              child: DropdownButtonHideUnderline(
                child: DropdownButton<ContactGroupModel>(
                  isExpanded: true,
                  hint: const Text('Select a group'),
                  value: draft.selectedGroup,
                  items: groups.map((g) => DropdownMenuItem(
                    value: g,
                    child: Text('${g.name} (${g.contacts.length} numbers)'),
                  )).toList(),
                  onChanged: (v) { draft.selectedGroup = v; onChanged(); },
                ),
              ),
            ),
            const SizedBox(height: 12),

            // One Shot Range Selector Tile
            InkWell(
              onTap: () async {
                final selectedGroupResult = await Navigator.push<ContactGroupModel>(
                  context,
                  MaterialPageRoute(
                    builder: (ctx) => SelectContactsOneShotScreen(
                      initialGroup: draft.selectedGroup,
                    ),
                  ),
                );

                if (selectedGroupResult != null) {
                  draft.selectedGroup = selectedGroupResult;
                  onChanged();
                }
              },
              borderRadius: BorderRadius.circular(12),
              child: Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: const Color(0xFF004D40).withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFF004D40).withValues(alpha: 0.3)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.filter_list_rounded, color: Color(0xFF004D40)),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'One Shot Range Selection',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 14,
                              color: Color(0xFF004D40),
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            draft.selectedGroup != null
                                ? 'Series Range Mode (${draft.selectedGroup!.contacts.length} contacts selected)'
                                : 'Select series range, batch size & track sent history',
                            style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
                          ),
                        ],
                      ),
                    ),
                    const Icon(Icons.chevron_right_rounded, color: Color(0xFF004D40)),
                  ],
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

// ============================================================================
// STEP 2: MESSAGE
// ============================================================================
class _Step2Message extends StatefulWidget {
  final CampaignDraftModel draft;
  final VoidCallback onChanged;

  const _Step2Message({required this.draft, required this.onChanged});

  @override
  State<_Step2Message> createState() => _Step2MessageState();
}

class _Step2MessageState extends State<_Step2Message> {
  final CloudflareUploadService _uploadService = CloudflareUploadService();
  bool _uploading = false;

  Future<void> _pickAndUploadMedia() async {
    setState(() => _uploading = true);
    try {
      final uploaded = await _uploadService.pickAndUpload(
        folder: widget.draft.mediaType.name,
        type: FileType.any,
        allowedExtensions: _allowedExtensions(widget.draft.mediaType),
      );

      if (uploaded == null || !mounted) {
        return;
      }

      widget.draft.mediaUrl = uploaded.url;
      if (widget.draft.mediaFilename.trim().isEmpty) {
        widget.draft.mediaFilename = uploaded.filename;
      }
      widget.onChanged();

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('File ready: ${uploaded.filename}'),
          backgroundColor: Colors.green,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Upload failed: $e'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _uploading = false);
      }
    }
  }

  List<String> _allowedExtensions(CampaignMediaType type) {
    switch (type) {
      case CampaignMediaType.image:
        return ['jpg', 'jpeg', 'png', 'webp'];
      case CampaignMediaType.video:
        return ['mp4', 'mov', 'mkv', 'webm'];
      case CampaignMediaType.audio:
        return ['mp3', 'wav', 'aac', 'ogg', 'm4a'];
      case CampaignMediaType.document:
        return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    }
  }

  @override
  Widget build(BuildContext context) {
    final draft = widget.draft;
    final templates = TemplateStorage().templates;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Message Content', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 24),

          // Toggle Type
          Row(
            children: [
              Expanded(
                child: _TypeToggle(
                  title: 'Use Template',
                  icon: Icons.dashboard_customize_rounded,
                  isSelected: draft.messageMode == CampaignMessageMode.template,
                  onTap: () {
                    draft.messageMode = CampaignMessageMode.template;
                    widget.onChanged();
                  },
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: _TypeToggle(
                  title: 'Plain Text',
                  icon: Icons.text_snippet_rounded,
                  isSelected: draft.messageMode == CampaignMessageMode.text,
                  onTap: () {
                    draft.messageMode = CampaignMessageMode.text;
                    widget.onChanged();
                  },
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: _TypeToggle(
                  title: 'Media',
                  icon: Icons.perm_media_rounded,
                  isSelected: draft.messageMode == CampaignMessageMode.media,
                  onTap: () {
                    draft.messageMode = CampaignMessageMode.media;
                    widget.onChanged();
                  },
                ),
              )
            ],
          ),
          const SizedBox(height: 32),

          // Dynamic Content
          if (draft.messageMode == CampaignMessageMode.template) ...[
             Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _Label('Select Template'),
                TextButton(
                  onPressed: () async {
                    await Navigator.push(context, MaterialPageRoute(builder: (ctx) => const CreateTemplateScreen()));
                    widget.onChanged(); // Refresh
                  },
                  child: const Text('Create New +', style: TextStyle(fontSize: 12)),
                ),
              ],
            ),
            Container(
              width: double.infinity,
              margin: const EdgeInsets.only(bottom: 14),
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFFF6F9FD),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.blueGrey.withValues(alpha: 0.12)),
              ),
              child: const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Template types',
                    style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
                  ),
                  SizedBox(height: 6),
                  Text(
                    'Button message: user ko 1 tap me 2-3 quick action buttons dikhte hain.',
                    style: TextStyle(fontSize: 12, color: Color(0xFF5F6678)),
                  ),
                  SizedBox(height: 4),
                  Text(
                    'List message: user ko menu style me multiple options rows ke form me dikhte hain.',
                    style: TextStyle(fontSize: 12, color: Color(0xFF5F6678)),
                  ),
                ],
              ),
            ),
            if (templates.isEmpty)
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(color: Colors.orange.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)),
                child: const Text('No templates found. Please create one.', style: TextStyle(color: Colors.orange)),
              )
            else
              ...templates.map((t) {
                final isSelected = draft.selectedTemplate?.id == t.id;
                return GestureDetector(
                  onTap: () { draft.selectedTemplate = t; widget.onChanged(); },
                  child: Container(
                    margin: const EdgeInsets.only(bottom: 12),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: isSelected ? AppColors.primaryBlue.withValues(alpha: 0.05) : Colors.white,
                      border: Border.all(color: isSelected ? AppColors.primaryBlue : Colors.grey.shade300, width: isSelected ? 2 : 1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.check_circle_rounded, color: isSelected ? AppColors.primaryBlue : Colors.grey.shade300),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(t.name, style: const TextStyle(fontWeight: FontWeight.bold)),
                              const SizedBox(height: 4),
                              Text(
                                _templateTypeTitle(t.templateType),
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                  color: t.templateType == TemplateLibraryType.list
                                      ? Colors.green.shade700
                                      : AppColors.primaryBlue,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                _templateTypeDescription(t),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                              ),
                            ],
                          ),
                        ),
                        Container(
                          margin: const EdgeInsets.only(left: 10),
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: (t.templateType == TemplateLibraryType.list ? Colors.green : AppColors.primaryBlue)
                                .withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            t.templateType == TemplateLibraryType.list ? 'List Menu' : 'Button Msg',
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                              color: t.templateType == TemplateLibraryType.list ? Colors.green : AppColors.primaryBlue,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              }),
          ] else if (draft.messageMode == CampaignMessageMode.text) ...[
            _Label('Message Text'),
            TextField(
              maxLines: 6,
              onChanged: (v) { draft.plainMessage = v; widget.onChanged(); },
              controller: TextEditingController(text: draft.plainMessage)..selection = TextSelection.collapsed(offset: draft.plainMessage.length),
              decoration: _inputDeco('Type your message here...'),
            ),
          ] else ...[
            _Label('Media Type'),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.grey.shade300),
              ),
              child: DropdownButtonHideUnderline(
                child: DropdownButton<CampaignMediaType>(
                  isExpanded: true,
                  value: draft.mediaType,
                  items: CampaignMediaType.values
                      .map(
                        (type) => DropdownMenuItem(
                          value: type,
                          child: Text(_mediaTypeLabel(type)),
                        ),
                      )
                      .toList(),
                  onChanged: (value) {
                    if (value == null) return;
                    draft.mediaType = value;
                    widget.onChanged();
                  },
                ),
              ),
            ),
            const SizedBox(height: 20),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _uploading ? null : _pickAndUploadMedia,
                    icon: _uploading
                        ? const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.upload_file_rounded),
                    label: Text(_uploading ? 'Processing...' : 'Choose File'),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                  ),
                ),
              ],
            ),
            if (draft.mediaFilename.trim().isNotEmpty) ...[
              const SizedBox(height: 12),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.grey.shade300),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.insert_drive_file_rounded, size: 18),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        draft.mediaFilename,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                    ),
                  ],
                ),
              ),
            ],
            const SizedBox(height: 20),
            _Label('Caption / Message'),
            TextField(
              maxLines: 4,
              onChanged: (v) {
                draft.mediaCaption = v;
                widget.onChanged();
              },
              controller: TextEditingController(text: draft.mediaCaption)
                ..selection = TextSelection.collapsed(offset: draft.mediaCaption.length),
              decoration: _inputDeco('Optional caption for media'),
            ),
          ]
        ],
      ),
    );
  }
}

class _TypeToggle extends StatelessWidget {
  final String title;
  final IconData icon;
  final bool isSelected;
  final VoidCallback onTap;

  const _TypeToggle({required this.title, required this.icon, required this.isSelected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 16),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primaryBlue.withValues(alpha: 0.1) : Colors.white,
          border: Border.all(color: isSelected ? AppColors.primaryBlue : Colors.grey.shade300, width: isSelected ? 2 : 1),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          children: [
            Icon(icon, color: isSelected ? AppColors.primaryBlue : Colors.grey.shade500),
            const SizedBox(height: 8),
            Text(title, style: TextStyle(fontWeight: FontWeight.bold, color: isSelected ? AppColors.primaryBlue : Colors.grey.shade600)),
          ],
        ),
      ),
    );
  }
}

// ============================================================================
// STEP 3: LAUNCH SETTINGS
// ============================================================================
class _Step3Launch extends StatelessWidget {
  final CampaignDraftModel draft;
  final VoidCallback onChanged;

  const _Step3Launch({required this.draft, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    String formatDateTime(DateTime dt) {
      final date = '${dt.year}-${dt.month.toString().padLeft(2, '0')}-${dt.day.toString().padLeft(2, '0')}';
      final time = TimeOfDay.fromDateTime(dt).format(context);
      return '$date $time';
    }

    final contactCount = draft.selectedGroup?.contacts.length ?? 0;
    final totalTimeSeconds = contactCount * draft.delaySeconds;
    final totalTimeFormatted = totalTimeSeconds > 60 
        ? '${(totalTimeSeconds / 60).toStringAsFixed(1)} minutes'
        : '$totalTimeSeconds seconds';

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Launch Settings', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 24),

          _Label('Delay Between Messages (Seconds)'),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 20),
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border.all(color: Colors.grey.shade300),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Column(
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('${draft.delaySeconds}s', style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: AppColors.primaryBlue)),
                    const Icon(Icons.timer_rounded, color: Colors.grey),
                  ],
                ),
                Slider(
                  value: draft.delaySeconds.toDouble(),
                  min: 0,
                  max: 30,
                  divisions: 30,
                  activeColor: AppColors.primaryBlue,
                  label: '${draft.delaySeconds}s',
                  onChanged: (v) { draft.delaySeconds = v.toInt(); onChanged(); },
                ),
                Text('0s sends continuously; increase delay if you want slower pacing.', style: TextStyle(fontSize: 11, color: Colors.grey.shade500)),
              ],
            ),
          ),
          
          const SizedBox(height: 32),
          const SizedBox(height: 32),
          _Label('Delivery Timing'),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: Colors.grey.shade200,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                Expanded(
                  child: InkWell(
                    onTap: () {
                      draft.scheduledAt = null;
                      onChanged();
                    },
                    borderRadius: BorderRadius.circular(8),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      decoration: BoxDecoration(
                        color: draft.scheduledAt == null ? Colors.white : Colors.transparent,
                        borderRadius: BorderRadius.circular(8),
                        boxShadow: draft.scheduledAt == null
                            ? [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 4, offset: const Offset(0, 2))]
                            : null,
                      ),
                      alignment: Alignment.center,
                      child: Text(
                        'Instant',
                        style: TextStyle(
                          fontWeight: draft.scheduledAt == null ? FontWeight.bold : FontWeight.normal,
                          color: draft.scheduledAt == null ? AppColors.primaryBlue : Colors.grey.shade700,
                        ),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: InkWell(
                    onTap: () async {
                      final date = await showDatePicker(
                        context: context,
                        initialDate: draft.scheduledAt ?? DateTime.now(),
                        firstDate: DateTime.now(),
                        lastDate: DateTime.now().add(const Duration(days: 365)),
                      );
                      if (date != null) {
                        if (!context.mounted) return;
                        final time = await showTimePicker(
                          context: context,
                          initialTime: TimeOfDay.fromDateTime(draft.scheduledAt ?? DateTime.now()),
                        );
                        if (time != null) {
                          draft.scheduledAt = DateTime(date.year, date.month, date.day, time.hour, time.minute);
                          onChanged();
                        }
                      }
                    },
                    borderRadius: BorderRadius.circular(8),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      decoration: BoxDecoration(
                        color: draft.scheduledAt != null ? Colors.white : Colors.transparent,
                        borderRadius: BorderRadius.circular(8),
                        boxShadow: draft.scheduledAt != null
                            ? [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 4, offset: const Offset(0, 2))]
                            : null,
                      ),
                      alignment: Alignment.center,
                      child: Text(
                        draft.scheduledAt != null
                            ? formatDateTime(draft.scheduledAt!.toLocal())
                            : 'Schedule',
                        style: TextStyle(
                          fontWeight: draft.scheduledAt != null ? FontWeight.bold : FontWeight.normal,
                          color: draft.scheduledAt != null ? AppColors.primaryBlue : Colors.grey.shade700,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          
          const SizedBox(height: 32),
          const Text('Campaign Summary', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          const SizedBox(height: 16),
          
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: const Color(0xFFF8F9FB),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.grey.shade200),
            ),
            child: Column(
              children: [
                _SummaryRow('Name', draft.name),
                const Divider(height: 24),
                _SummaryRow('Target', '${draft.selectedGroup?.name} ($contactCount numbers)'),
                const Divider(height: 24),
                _SummaryRow('Message', _campaignSummaryType(draft)),
                const Divider(height: 24),
                _SummaryRow('Schedule', draft.scheduledAt != null ? formatDateTime(draft.scheduledAt!.toLocal()) : 'Now'),
                const Divider(height: 24),
                _SummaryRow('Est. Time', totalTimeFormatted),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SummaryRow extends StatelessWidget {
  final String label;
  final String value;
  const _SummaryRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 80,
          child: Text(label, style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
        ),
        Expanded(
          child: Text(value, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13), textAlign: TextAlign.right),
        ),
      ],
    );
  }
}

String _mediaTypeLabel(CampaignMediaType type) {
  switch (type) {
    case CampaignMediaType.image:
      return 'Image';
    case CampaignMediaType.video:
      return 'Video';
    case CampaignMediaType.audio:
      return 'Audio';
    case CampaignMediaType.document:
      return 'Document';
  }
}

String _campaignSummaryType(CampaignDraftModel draft) {
  switch (draft.messageMode) {
    case CampaignMessageMode.template:
      return 'Template: ${draft.selectedTemplate?.name ?? 'Not selected'}';
    case CampaignMessageMode.text:
      return 'Plain Text';
    case CampaignMessageMode.media:
      return 'Media: ${_mediaTypeLabel(draft.mediaType)}';
  }
}

String _templateTypeTitle(TemplateLibraryType type) {
  switch (type) {
    case TemplateLibraryType.button:
      return 'Button message';
    case TemplateLibraryType.list:
      return 'List message';
  }
}

String _templateTypeDescription(ButtonTemplateModel template) {
  if (template.templateType == TemplateLibraryType.list) {
    final totalRows = template.sections.fold<int>(
      0,
      (sum, section) => sum + section.rows.length,
    );
    return totalRows > 0
        ? 'Menu style template with $totalRows selectable options.'
        : 'Menu style template with multiple selectable options.';
  }

  final buttonCount = template.buttons.length;
  return buttonCount > 0
      ? 'Quick reply style template with $buttonCount tap buttons.'
      : 'Quick reply style template with tap buttons.';
}

Widget _Label(String text) => Padding(
  padding: const EdgeInsets.only(bottom: 8, left: 4),
  child: Text(text, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Color(0xFF555A6E))),
);

InputDecoration _inputDeco(String hint) => InputDecoration(
  hintText: hint,
  filled: true,
  fillColor: Colors.white,
  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
  focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.primaryBlue, width: 2)),
  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
);
