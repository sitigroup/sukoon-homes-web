import 'package:ebroker/data/model/trust_verification_models.dart';
import 'package:ebroker/exports/main_export.dart';
class TrustBadgesRow extends StatelessWidget {
  const TrustBadgesRow({
    required this.badges,
    this.wrap = false,
    this.max = 4,
    super.key,
  });

  final List<TrustVerificationBadge> badges;
  final bool wrap;
  final int max;

  @override
  Widget build(BuildContext context) {
    final visible = badges.take(max).toList();
    if (visible.isEmpty) return const SizedBox.shrink();

    final chips = visible
        .map(
          (badge) => Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: context.color.backgroundColor,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: context.color.borderColor),
            ),
            child: Text(
              badge.label ?? badge.slug ?? 'Verified',
              style: TextStyle(
                fontSize: context.font.sm,
                fontWeight: FontWeight.w500,
                color: context.color.textColorDark,
              ),
            ),
          ),
        )
        .toList();

    if (wrap) {
      return Wrap(spacing: 8, runSpacing: 8, children: chips);
    }
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          for (var i = 0; i < chips.length; i++) ...[
            if (i > 0) const SizedBox(width: 8),
            chips[i],
          ],
        ],
      ),
    );
  }
}
