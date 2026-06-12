import 'dart:async';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:ebroker/utils/admob/interstitial_ad_manager.dart';
import 'package:ebroker/utils/custom_appbar.dart';
import 'package:ebroker/utils/custom_text.dart';
import 'package:ebroker/utils/extensions/extensions.dart';
import 'package:flutter/material.dart';

class GalleryViewWidget extends StatefulWidget {
  const GalleryViewWidget({
    required this.images,
    required this.initalIndex,
    super.key,
  });
  final List<dynamic> images;
  final int initalIndex;

  @override
  State<GalleryViewWidget> createState() => _GalleryViewWidgetState();
}

class _GalleryViewWidgetState extends State<GalleryViewWidget> {
  List<dynamic> images = [];
  late PageController controller;
  late int page;
  InterstitialAdManager admanager = InterstitialAdManager();
  bool _showSwipeHint = true;
  Timer? _hintTimer;

  bool get _hasMultipleImages => images.length > 1;

  @override
  void initState() {
    images = widget.images
        .where(
          (element) => element != null && element.toString().trim().isNotEmpty,
        )
        .toList();
    final safeIndex =
        widget.initalIndex.clamp(0, images.isEmpty ? 0 : images.length - 1);
    controller = PageController(initialPage: safeIndex);
    page = safeIndex;
    unawaited(admanager.load());
    if (_hasMultipleImages) {
      _hintTimer = Timer(const Duration(seconds: 4), () {
        if (mounted) {
          setState(() => _showSwipeHint = false);
        }
      });
    } else {
      _showSwipeHint = false;
    }
    super.initState();
  }

  @override
  void dispose() {
    _hintTimer?.cancel();
    controller.dispose();
    super.dispose();
  }

  Future<void> _goToPage(int index) async {
    if (!_hasMultipleImages) return;
    final target = index.clamp(0, images.length - 1);
    if (target == page) return;
    await controller.animateToPage(
      target,
      duration: const Duration(milliseconds: 280),
      curve: Curves.easeOutCubic,
    );
  }

  void _goPrevious() {
    unawaited(_goToPage(page - 1));
  }

  void _goNext() {
    unawaited(_goToPage(page + 1));
  }

  @override
  Widget build(BuildContext context) {
    final canGoBack = page > 0;
    final canGoForward = page < images.length - 1;
    final screenWidth = MediaQuery.sizeOf(context).width;

    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: CustomAppBar(
        isTransparent: true,
        titleWidget: _hasMultipleImages
            ? CustomText(
                '${page + 1} / ${images.length}',
                color: Colors.white,
                fontWeight: FontWeight.w600,
              )
            : null,
      ),
      backgroundColor: Colors.black,
      body: images.isEmpty
          ? const SizedBox.shrink()
          : Stack(
              fit: StackFit.expand,
              children: [
                PageView.builder(
                  controller: controller,
                  physics: _hasMultipleImages
                      ? const PageScrollPhysics()
                      : const NeverScrollableScrollPhysics(),
                  onPageChanged: (value) async {
                    page = value;
                    if (page.isEven) {
                      await admanager.show();
                    }
                    if (_showSwipeHint) {
                      setState(() => _showSwipeHint = false);
                    } else {
                      setState(() {});
                    }
                  },
                  itemCount: images.length,
                  itemBuilder: (context, index) {
                    return InteractiveViewer(
                      maxScale: 5,
                      panEnabled: false,
                      child: Center(
                        child: CachedNetworkImage(
                          imageUrl: images[index].toString(),
                          fit: BoxFit.contain,
                        ),
                      ),
                    );
                  },
                ),
                if (_hasMultipleImages) ...[
                  Positioned(
                    left: 0,
                    top: 0,
                    bottom: 0,
                    width: screenWidth * 0.28,
                    child: GestureDetector(
                      behavior: HitTestBehavior.translucent,
                      onTap: canGoBack ? _goPrevious : null,
                    ),
                  ),
                  Positioned(
                    right: 0,
                    top: 0,
                    bottom: 0,
                    width: screenWidth * 0.28,
                    child: GestureDetector(
                      behavior: HitTestBehavior.translucent,
                      onTap: canGoForward ? _goNext : null,
                    ),
                  ),
                  Positioned(
                    left: 4,
                    top: 0,
                    bottom: 0,
                    child: Center(
                      child: _GalleryNavButton(
                        icon: Icons.chevron_left_rounded,
                        enabled: canGoBack,
                        onPressed: _goPrevious,
                      ),
                    ),
                  ),
                  Positioned(
                    right: 4,
                    top: 0,
                    bottom: 0,
                    child: Center(
                      child: _GalleryNavButton(
                        icon: Icons.chevron_right_rounded,
                        enabled: canGoForward,
                        onPressed: _goNext,
                      ),
                    ),
                  ),
                ],
                if (_hasMultipleImages && _showSwipeHint)
                  Positioned(
                    left: 24,
                    right: 24,
                    bottom: 32,
                    child: _SwipeHintBanner(
                      message: 'gallerySwipeHint'.translate(context),
                    ),
                  ),
                if (_hasMultipleImages)
                  Positioned(
                    left: 0,
                    right: 0,
                    bottom: _showSwipeHint ? 88 : 24,
                    child: Center(
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 14,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.black54,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: CustomText(
                          '${page + 1} / ${images.length}',
                          color: Colors.white,
                          fontSize: context.font.sm,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
    );
  }
}

class _GalleryNavButton extends StatelessWidget {
  const _GalleryNavButton({
    required this.icon,
    required this.enabled,
    required this.onPressed,
  });

  final IconData icon;
  final bool enabled;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.black.withValues(alpha: enabled ? 0.45 : 0.2),
      shape: const CircleBorder(),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: enabled ? onPressed : null,
        customBorder: const CircleBorder(),
        child: Padding(
          padding: const EdgeInsets.all(6),
          child: Icon(
            icon,
            color: Colors.white.withValues(alpha: enabled ? 1 : 0.35),
            size: 36,
          ),
        ),
      ),
    );
  }
}

class _SwipeHintBanner extends StatelessWidget {
  const _SwipeHintBanner({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.black.withValues(alpha: 0.65),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white24),
      ),
      child: Row(
        children: [
          const Icon(Icons.swipe_rounded, color: Colors.white70, size: 22),
          const SizedBox(width: 10),
          Expanded(
            child: CustomText(
              message,
              color: Colors.white,
              fontSize: context.font.sm,
              maxLines: 2,
            ),
          ),
        ],
      ),
    );
  }
}
