import 'package:flutter/material.dart';
import 'package:autoreply/features/templates/screens/create_template_screen.dart';
import 'package:autoreply/data/models/button_template_model.dart';
import '../storage/chatbot_template_storage.dart';

class CreateChatbotTemplateScreen extends StatelessWidget {
  final String screenTitle;
  final ButtonTemplateModel? initialTemplate;

  const CreateChatbotTemplateScreen({
    super.key,
    this.screenTitle = 'Create Chatbot Template',
    this.initialTemplate,
  });

  @override
  Widget build(BuildContext context) {
    return CreateTemplateScreen(
      screenTitle: screenTitle,
      initialTemplate: initialTemplate,
      onSaveTemplate: ChatbotTemplateStorage().addTemplate,
    );
  }
}
