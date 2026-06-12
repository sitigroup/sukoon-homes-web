import 'package:ebroker/data/model/trust_verification_models.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/plugins/trust_verification/trust_tier_utils.dart';
import 'package:ebroker/ui/screens/plugins/trust_verification/widgets/trust_badges_row.dart';
import 'package:flutter/material.dart';

class TrustScoreCard extends StatelessWidget {
  const TrustScoreCard({
    required this.profile,
    this.compact = false,
    this.title = 'Sukoon Trust Score',
    super.key,
  });

  final TrustVerificationProfile profile;
  final bool compact;
  final String title;

  @override
  Widget build(BuildContext context) {
    if (!profile.hasPublicScore) return const SizedBox.shrink();

    final score = clampTrustScore(profile.trustScore);
    final tier = trustTierForScore(score);

    return Container(
      width: double.infinity,
      padding: EdgeInsets.all(compact ? 14 : 18),
      decoration: BoxDecoration(
        color: context.color.secondaryColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: context.color.borderColor.withValues(alpha: 0.85),
        ),
      ),
      child: compact
          ? _CompactLayout(
              title: title,
              score: score,
              tier: tier,
              badges: profile.badges,
            )
          : _FullLayout(
              title: title,
              score: score,
              tier: tier,
              badges: profile.badges,
            ),
    );
  }
}

class _CompactLayout extends StatelessWidget {
  const _CompactLayout({
    required this.title,
    required this.score,
    required this.tier,
    required this.badges,
  });

  final String title;
  final int score;
  final TrustTierInfo tier;
  final List<TrustVerificationBadge> badges;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            TrustScoreRing(score: score, size: 52),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontWeight: FontWeight.w600,
                      color: context.color.textColorDark,
                    ),
                  ),
                  const SizedBox(height: 4),
                  TrustTierPill(label: tier.label, color: Color(tier.color)),
                ],
              ),
            ),
          ],
        ),
        if (badges.isNotEmpty) ...[
          const SizedBox(height: 12),
          TrustBadgesRow(badges: badges),
        ],
      ],
    );
  }
}

class _FullLayout extends StatelessWidget {
  const _FullLayout({
    required this.title,
    required this.score,
    required this.tier,
    required this.badges,
  });

  final String title;
  final int score;
  final TrustTierInfo tier;
  final List<TrustVerificationBadge> badges;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            TrustScoreRing(score: score, size: 84),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontSize: context.font.lg,
                      fontWeight: FontWeight.w700,
                      color: context.color.textColorDark,
                    ),
                  ),
                  const SizedBox(height: 6),
                  TrustTierPill(label: tier.label, color: Color(tier.color)),
                  const SizedBox(height: 8),
                  Text(
                    'Trust level reflects completed verifications and earned badges.',
                    style: TextStyle(
                      fontSize: context.font.sm,
                      color: context.color.textLightColor,
                      height: 1.35,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        if (badges.isNotEmpty) ...[
          const SizedBox(height: 16),
          Text(
            'Verification badges',
            style: TextStyle(
              fontWeight: FontWeight.w600,
              color: context.color.textColorDark,
            ),
          ),
          const SizedBox(height: 10),
          TrustBadgesRow(badges: badges, wrap: true),
        ],
      ],
    );
  }
}

class TrustScoreRing extends StatelessWidget {
  const TrustScoreRing({
    required this.score,
    this.size = 72,
    super.key,
  });

  final int score;
  final double size;

  @override
  Widget build(BuildContext context) {
    final normalized = clampTrustScore(score) / 100;
    return SizedBox(
      width: size,
      height: size,
      child: Stack(
        alignment: Alignment.center,
        children: [
          SizedBox(
            width: size,
            height: size,
            child: CircularProgressIndicator(
              value: normalized,
              strokeWidth: size < 60 ? 5 : 7,
              backgroundColor: context.color.borderColor.withValues(alpha: 0.35),
              color: context.color.tertiaryColor,
            ),
          ),
          Text(
            '$score',
            style: TextStyle(
              fontSize: size < 60 ? 16 : 22,
              fontWeight: FontWeight.w700,
              color: context.color.textColorDark,
            ),
          ),
        ],
      ),
    );
  }
}

class TrustTierPill extends StatelessWidget {
  const TrustTierPill({
    required this.label,
    required this.color,
    super.key,
  });

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.35)),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: context.font.sm,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }
}
