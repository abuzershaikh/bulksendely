import 'package:autoreply/features/autoreply/menu_reply/models/menu_reply_model.dart';

class MenuReplyBuilderService {
  String newId() => DateTime.now().microsecondsSinceEpoch.toString();

  MenuReplyStep newTextStep() {
    return MenuReplyStep(id: newId(), type: MenuReplyStepType.text);
  }

  MenuReplyRow newReplyRow() {
    return MenuReplyRow(
      id: newId(),
      title: '',
      description: '',
      action: MenuReplyAction(
        type: MenuReplyActionType.sendReply,
        steps: [newTextStep()],
      ),
    );
  }

  MenuReplyNode newNode({String? name}) {
    return MenuReplyNode(
      id: newId(),
      name: name ?? 'Menu Node',
      title: '',
      body: '',
      footer: '',
      buttonText: 'Select',
      rows: [newReplyRow()],
    );
  }

  MenuReplyFlow newFlow() {
    final root = newNode(name: 'Root Menu');
    return MenuReplyFlow(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      name: '',
      isActive: true,
      keywords: const [],
      rootNodeId: root.id,
      nodes: [root],
      createdAt: DateTime.now().millisecondsSinceEpoch,
    );
  }
}
