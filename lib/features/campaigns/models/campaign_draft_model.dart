import 'package:autoreply/data/models/button_template_model.dart';
import 'package:autoreply/data/models/contact_group_model.dart';

enum CampaignMessageMode { template, text, media }

enum CampaignMediaType { image, video, audio, document }

class CampaignDraftModel {
  String name;
  String countryCode;
  ContactGroupModel? selectedGroup;
  CampaignMessageMode messageMode;
  ButtonTemplateModel? selectedTemplate;
  String plainMessage;
  CampaignMediaType mediaType;
  String mediaUrl;
  String mediaCaption;
  String mediaFilename;
  int delaySeconds;
  DateTime? scheduledAt;

  CampaignDraftModel({
    this.name = '',
    this.countryCode = '+91',
    this.selectedGroup,
    this.messageMode = CampaignMessageMode.template,
    this.selectedTemplate,
    this.plainMessage = '',
    this.mediaType = CampaignMediaType.image,
    this.mediaUrl = '',
    this.mediaCaption = '',
    this.mediaFilename = '',
    this.delaySeconds = 0,
    this.scheduledAt,
  });

  bool get useTemplate => messageMode == CampaignMessageMode.template;

  bool get isStep1Valid =>
      name.trim().isNotEmpty &&
      countryCode.trim().isNotEmpty &&
      selectedGroup != null;

  bool get isStep2Valid {
    switch (messageMode) {
      case CampaignMessageMode.template:
        return selectedTemplate != null;
      case CampaignMessageMode.text:
        return plainMessage.trim().isNotEmpty;
      case CampaignMessageMode.media:
        return mediaUrl.trim().isNotEmpty;
    }
  }
}
