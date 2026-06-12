import 'package:ebroker/data/model/trust_verification_models.dart';
import 'package:ebroker/data/repositories/trust_verification_repository.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/plugins/trust_verification/widgets/trust_score_card.dart';
import 'package:flutter/material.dart';

/// Public Sukoon trust card for property owners / agents (matches web SukoonPublicTrustProfile).
class TrustPublicTrustSection extends StatefulWidget {
  const TrustPublicTrustSection({
    required this.customerId,
    this.compact = true,
    super.key,
  });

  final int customerId;
  final bool compact;

  @override
  State<TrustPublicTrustSection> createState() =>
      _TrustPublicTrustSectionState();
}

class _TrustPublicTrustSectionState extends State<TrustPublicTrustSection> {
  final TrustVerificationRepository _repository = TrustVerificationRepository();
  bool _loading = true;
  TrustVerificationProfile? _profile;

  @override
  void initState() {
    super.initState();
    unawaited(_load());
  }

  Future<void> _load() async {
    try {
      final profile = await _repository.fetchPublicTrust(widget.customerId);
      if (!mounted) return;
      setState(() {
        _profile = profile;
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const SizedBox(
        height: 4,
        child: LinearProgressIndicator(minHeight: 2),
      );
    }
    final profile = _profile;
    if (profile == null || !profile.hasPublicScore) {
      return const SizedBox.shrink();
    }
    return TrustScoreCard(profile: profile, compact: widget.compact);
  }
}
