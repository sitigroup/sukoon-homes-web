import 'dart:async';
import 'dart:math' as math;
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';

class ThemeSwitcher extends StatefulWidget {
  const ThemeSwitcher({
    required this.child,
    super.key,
    this.duration = const Duration(milliseconds: 600),
  });

  final Widget child;
  final Duration duration;

  static ThemeSwitcherController of(BuildContext context) {
    final scope = context
        .dependOnInheritedWidgetOfExactType<_ThemeSwitcherScope>();
    assert(
      scope != null,
      'ThemeSwitcher.of called without ThemeSwitcher above',
    );
    return scope!.controller;
  }

  @override
  State<ThemeSwitcher> createState() => _ThemeSwitcherState();
}

class ThemeSwitcherController {
  ThemeSwitcherController._(this._reveal);

  final Future<void> Function(Offset center, FutureOr<void> Function() onSwitch)
  _reveal;

  Future<void> reveal(
    Offset center,
    FutureOr<void> Function() onSwitch,
  ) => _reveal(center, onSwitch);
}

class _ThemeSwitcherState extends State<ThemeSwitcher>
    with SingleTickerProviderStateMixin {
  final GlobalKey _captureKey = GlobalKey();
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: widget.duration,
  );
  late final CurvedAnimation _curved = CurvedAnimation(
    parent: _controller,
    curve: Curves.easeOut,
  );

  ui.Image? _snapshot;
  Offset _center = Offset.zero;
  double _maxRadius = 0;

  @override
  void dispose() {
    _curved.dispose();
    _controller.dispose();
    _snapshot?.dispose();
    super.dispose();
  }

  Future<void> _reveal(
    Offset center,
    FutureOr<void> Function() onSwitch,
  ) async {
    if (_controller.isAnimating) return;
    final boundary =
        _captureKey.currentContext?.findRenderObject()
            as RenderRepaintBoundary?;
    if (boundary == null) {
      await onSwitch();
      return;
    }
    final mq = MediaQuery.of(context);
    // Cap pixel ratio to avoid oversized images on high-DPI screens
    final pixelRatio = mq.devicePixelRatio.clamp(1.0, 2.0);
    ui.Image image;
    try {
      image = await boundary.toImage(pixelRatio: pixelRatio);
    } on Object {
      await onSwitch();
      return;
    }
    final size = mq.size;
    final dx = math.max(center.dx, size.width - center.dx);
    final dy = math.max(center.dy, size.height - center.dy);
    if (!mounted) {
      image.dispose();
      return;
    }
    setState(() {
      _snapshot?.dispose();
      _snapshot = image;
      _center = center;
      _maxRadius = math.sqrt(dx * dx + dy * dy);
    });
    await onSwitch();
    await WidgetsBinding.instance.endOfFrame;
    if (!mounted) return;
    await _controller.forward(from: 0);
    if (!mounted) return;
    setState(() {
      _snapshot?.dispose();
      _snapshot = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    return _ThemeSwitcherScope(
      controller: ThemeSwitcherController._(_reveal),
      child: Stack(
        children: [
          RepaintBoundary(key: _captureKey, child: widget.child),
          if (_snapshot != null)
            Positioned.fill(
              child: IgnorePointer(
                child: AnimatedBuilder(
                  animation: _curved,
                  builder: (context, _) => CustomPaint(
                    painter: _RevealPainter(
                      image: _snapshot!,
                      center: _center,
                      radius: _maxRadius * _curved.value,
                    ),
                    size: Size.infinite,
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _ThemeSwitcherScope extends InheritedWidget {
  const _ThemeSwitcherScope({required this.controller, required super.child});

  final ThemeSwitcherController controller;

  @override
  bool updateShouldNotify(_ThemeSwitcherScope oldWidget) => false;
}

class _RevealPainter extends CustomPainter {
  _RevealPainter({
    required this.image,
    required this.center,
    required this.radius,
  });

  final ui.Image image;
  final Offset center;
  final double radius;

  @override
  void paint(Canvas canvas, Size size) {
    canvas
      ..saveLayer(Offset.zero & size, Paint())
      ..drawImageRect(
        image,
        Rect.fromLTWH(0, 0, image.width.toDouble(), image.height.toDouble()),
        Offset.zero & size,
        Paint(),
      );
    if (radius > 0) {
      canvas.drawCircle(center, radius, Paint()..blendMode = BlendMode.clear);
    }
    canvas.restore();
  }

  @override
  bool shouldRepaint(_RevealPainter old) =>
      old.center != center || old.radius != radius || old.image != image;
}
