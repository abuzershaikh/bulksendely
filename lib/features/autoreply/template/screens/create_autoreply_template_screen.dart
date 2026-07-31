import 'package:autoreply/features/autoreply/template/storage/autoreply_template_storage.dart';
import 'package:autoreply/features/templates/screens/create_template_screen.dart';
import 'package:flutter/material.dart';
import 'package:autoreply/data/models/button_template_model.dart';

class CreateAutoReplyTemplateScreen extends StatelessWidget {
  final ButtonTemplateModel? initialTemplate;

  const CreateAutoReplyTemplateScreen({super.key, this.initialTemplate});

  @override
  Widget build(BuildContext context) {
    return CreateTemplateScreen(
      screenTitle: initialTemplate != null ? 'Edit AutoReply Template' : 'Create AutoReply Template',
      initialTemplate: initialTemplate,
      onSaveTemplate: AutoReplyTemplateStorage().addTemplate,
    );
  }
}
