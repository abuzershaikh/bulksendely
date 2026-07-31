import 'package:flutter/material.dart';

class PremiumFeatureDialog {
  static Future<void> show(
    BuildContext context, {
    String message = 'This is a premium feature.',
  }) {
    return showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Premium Feature'),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }
}
