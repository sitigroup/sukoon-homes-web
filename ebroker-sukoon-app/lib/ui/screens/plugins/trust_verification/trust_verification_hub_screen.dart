import 'package:ebroker/data/model/trust_verification_models.dart';
import 'package:ebroker/data/repositories/trust_verification_repository.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/plugins/trust_verification/trust_verification_order_detail_screen.dart';
import 'package:ebroker/ui/screens/plugins/trust_verification/trust_verification_wizard_screen.dart';
import 'package:ebroker/ui/screens/plugins/trust_verification/widgets/trust_score_card.dart';
import 'package:flutter/material.dart';

/// Native Sukoon Trust & Verification hub (profile entry).
class TrustVerificationHubScreen extends StatefulWidget {
  const TrustVerificationHubScreen({super.key});

  static Route<dynamic> route(RouteSettings settings) {
    return CupertinoPageRoute(
      builder: (_) => const TrustVerificationHubScreen(),
    );
  }

  @override
  State<TrustVerificationHubScreen> createState() =>
      _TrustVerificationHubScreenState();
}

class _TrustVerificationHubScreenState extends State<TrustVerificationHubScreen> {
  final TrustVerificationRepository _repository = TrustVerificationRepository();
  bool _loading = true;
  String? _error;
  TrustVerificationProfile? _profile;
  List<TrustVerificationOrderSummary> _orders = [];
  List<TrustVerificationIssuedBadge> _issuedBadges = [];

  @override
  void initState() {
    super.initState();
    unawaited(_load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final results = await Future.wait([
        _repository.fetchMyTrustProfile(),
        _repository.fetchMyOrders(),
        _repository.fetchMyVerificationBadges(),
      ]);
      if (!mounted) return;
      setState(() {
        _profile = results[0] as TrustVerificationProfile;
        _orders = results[1] as List<TrustVerificationOrderSummary>;
        _issuedBadges = results[2] as List<TrustVerificationIssuedBadge>;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _openWizard() async {
    await Navigator.push<void>(
      context,
      TrustVerificationWizardScreen.route(
        const RouteSettings(name: '/trustVerificationWizard'),
      ),
    );
    await _load();
  }

  Future<void> _openOrder(int orderId) async {
    await Navigator.push<void>(
      context,
      TrustVerificationOrderDetailScreen.route(orderId: orderId),
    );
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: context.color.backgroundColor,
      appBar: CustomAppBar(title: 'Sukoon Trust & Verification'),
      body: _loading
          ? Center(child: UiUtils.progress())
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          'Could not load trust profile.',
                          textAlign: TextAlign.center,
                          style: TextStyle(color: context.color.textColorDark),
                        ),
                        const SizedBox(height: 12),
                        UiUtils.buildButton(
                          context,
                          onPressed: _load,
                          height: 44,
                          buttonTitle: 'Retry',
                        ),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      if (_profile != null)
                        TrustScoreCard(profile: _profile!),
                      const SizedBox(height: 16),
                      UiUtils.buildButton(
                        context,
                        onPressed: _openWizard,
                        height: 48,
                        buttonTitle: 'Start verification',
                      ),
                      if (_issuedBadges.isNotEmpty) ...[
                        const SizedBox(height: 20),
                        _SectionTitle(title: 'Your verification badges'),
                        const SizedBox(height: 8),
                        ..._issuedBadges.map(_issuedBadgeTile),
                      ],
                      const SizedBox(height: 20),
                      _SectionTitle(title: 'Your verification orders'),
                      const SizedBox(height: 8),
                      if (_orders.isEmpty)
                        Text(
                          'No orders yet. Tap Start verification to begin.',
                          style: TextStyle(
                            color: context.color.textLightColor,
                            height: 1.4,
                          ),
                        )
                      else
                        ..._orders.map(_orderTile),
                      const SizedBox(height: 24),
                    ],
                  ),
                ),
    );
  }

  Widget _issuedBadgeTile(TrustVerificationIssuedBadge badge) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: context.color.secondaryColor,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: context.color.borderColor),
      ),
      child: Text(
        badge.label ?? badge.badgeType ?? 'Verification badge',
        style: TextStyle(
          fontWeight: FontWeight.w600,
          color: context.color.textColorDark,
        ),
      ),
    );
  }

  Widget _orderTile(TrustVerificationOrderSummary order) {
    final id = order.id;
    return InkWell(
      onTap: id != null ? () => _openOrder(id) : null,
      borderRadius: BorderRadius.circular(8),
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: context.color.secondaryColor,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: context.color.borderColor),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              order.packageName ?? 'Verification order #${order.id}',
              style: TextStyle(
                fontWeight: FontWeight.w600,
                color: context.color.textColorDark,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              [
                if (order.orderType != null) order.orderType,
                if (order.status != null) order.status,
                if (order.paymentStatus != null) order.paymentStatus,
              ].whereType<String>().join(' · '),
              style: TextStyle(
                fontSize: context.font.sm,
                color: context.color.textLightColor,
              ),
            ),
            if (id != null) ...[
              const SizedBox(height: 8),
              Text(
                'View details',
                style: TextStyle(
                  color: context.color.tertiaryColor,
                  fontWeight: FontWeight.w600,
                  fontSize: context.font.sm,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.title});

  final String title;

  @override
  Widget build(BuildContext context) {
    return Text(
      title,
      style: TextStyle(
        fontSize: context.font.lg,
        fontWeight: FontWeight.w700,
        color: context.color.textColorDark,
      ),
    );
  }
}
