class TrustTierInfo {
  const TrustTierInfo({
    required this.id,
    required this.label,
    required this.min,
    required this.max,
    required this.color,
  });

  final String id;
  final String label;
  final int min;
  final int max;
  final int color;
}

const List<TrustTierInfo> trustTiers = [
  TrustTierInfo(
    id: 'basic',
    label: 'Basic',
    min: 0,
    max: 30,
    color: 0xFF6B7280,
  ),
  TrustTierInfo(
    id: 'verified',
    label: 'Verified',
    min: 31,
    max: 60,
    color: 0xFF4B5563,
  ),
  TrustTierInfo(
    id: 'trusted',
    label: 'Trusted',
    min: 61,
    max: 85,
    color: 0xFF374151,
  ),
  TrustTierInfo(
    id: 'elite',
    label: 'Elite Trusted',
    min: 86,
    max: 100,
    color: 0xFF111827,
  ),
];

int clampTrustScore(int score) {
  if (score < 0) return 0;
  if (score > 100) return 100;
  return score;
}

TrustTierInfo trustTierForScore(int rawScore) {
  final score = clampTrustScore(rawScore);
  for (final tier in trustTiers) {
    if (score >= tier.min && score <= tier.max) {
      return tier;
    }
  }
  return trustTiers.first;
}
