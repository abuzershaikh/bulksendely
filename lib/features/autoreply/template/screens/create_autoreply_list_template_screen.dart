import 'package:autoreply/features/autoreply/template/storage/autoreply_template_storage.dart';
import 'package:autoreply/features/templates/screens/create_list_template_screen.dart';
import 'package:flutter/material.dart';
import 'package:autoreply/data/models/button_template_model.dart';

class CreateAutoReplyListTemplateScreen extends StatelessWidget {
  final ButtonTemplateModel? initialTemplate;

  const CreateAutoReplyListTemplateScreen({super.key, this.initialTemplate});

  @override
  Widget build(BuildContext context) {
    return CreateListTemplateScreen(
      screenTitle: initialTemplate != null ? 'Edit AutoReply List Template' : 'Create AutoReply List Template',
      initialTemplate: initialTemplate,
      onSaveTemplate: AutoReplyTemplateStorage().addTemplate,
    );
  }
}
