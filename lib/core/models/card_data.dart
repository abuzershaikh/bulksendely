import 'package:flutter/material.dart';

class CardData {
  final String title;
  final String subtitle;
  final IconData icon;
  final List<Color> gradientColors;
  final Color iconBgColor;
  final int delay;

  CardData({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.gradientColors,
    required this.iconBgColor,
    required this.delay,
  });
}
