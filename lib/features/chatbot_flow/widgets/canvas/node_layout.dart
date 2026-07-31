import 'package:flutter/material.dart';

/// Shared layout constants between NodeWidget and ConnectionPainter
/// IMPORTANT: If you change any dimension in NodeWidget, update here too!
class NodeLayout {
  static const double selectionBorder = 3.0;
  static const double nodeWidth = 300.0;
  static const double headerHeight = 32.0;
  static const double connectedFromBadgeHeight = 28.0;
  static const double contentHeight = 80.0; // Fixed content area
  static const double buttonRowHeight = 42.0;
  static const double portCircleSize = 26.0;

  /// Get the Y offset of a button port relative to node's top-left (including selection border)
  static double getButtonPortY(int btnIndex, {bool hasConnectedFrom = false}) {
    double y = selectionBorder; // selection wrapper border
    y += headerHeight;
    if (hasConnectedFrom) y += connectedFromBadgeHeight;
    y += contentHeight;
    y += (btnIndex * buttonRowHeight) + (buttonRowHeight / 2);
    return y;
  }

  /// Get the X offset of the port (right edge of node)
  static double getButtonPortX() {
    return selectionBorder + nodeWidth;
  }

  /// Get top-center input point of a node
  static Offset getInputPort({bool hasConnectedFrom = false}) {
    return Offset(selectionBorder + nodeWidth / 2, selectionBorder);
  }
}
