import 'package:flutter_test/flutter_test.dart';

import 'package:autoreply/main.dart';

void main() {
  testWidgets('app builds root widget', (WidgetTester tester) async {
    await tester.pumpWidget(const GroupSenderProApp());
    expect(find.byType(GroupSenderProApp), findsOneWidget);
  });
}
