import 'package:ebroker/exports/main_export.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:webview_flutter_android/webview_flutter_android.dart';

/// Opens Sukoon web Trust Verification flows (hub, wizard, orders).
class TrustVerificationWebScreen extends StatefulWidget {
  const TrustVerificationWebScreen({
    required this.path,
    this.title = 'Sukoon Verification',
    super.key,
  });

  final String path;
  final String title;

  static String webUrl(String path) {
    final normalized = path.startsWith('/') ? path : '/$path';
    return 'https://${AppSettings.shareNavigationWebUrl}$normalized';
  }

  @override
  State<TrustVerificationWebScreen> createState() =>
      _TrustVerificationWebScreenState();
}

class _TrustVerificationWebScreenState extends State<TrustVerificationWebScreen> {
  late final WebViewController _controller;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageFinished: (_) {
            if (mounted) setState(() => _loading = false);
          },
        ),
      );
    unawaited(_initPlatform());
    unawaited(_controller.loadRequest(Uri.parse(TrustVerificationWebScreen.webUrl(widget.path))));
  }

  Future<void> _initPlatform() async {
    if (_controller.platform is AndroidWebViewController) {
      await AndroidWebViewController.enableDebugging(kDebugMode);
      await (_controller.platform as AndroidWebViewController)
          .setMediaPlaybackRequiresUserGesture(false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: context.color.backgroundColor,
      appBar: CustomAppBar(title: widget.title),
      body: Stack(
        children: [
          WebViewWidget(controller: _controller),
          if (_loading)
            Center(
              child: UiUtils.progress(),
            ),
        ],
      ),
    );
  }
}
