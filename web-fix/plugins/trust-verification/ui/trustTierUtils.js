/** Score → tier label and palette (public frontend: B/W only). */

export const TRUST_TIERS = [
  { id: "basic", label: "Basic", min: 0, max: 30, color: "#6b7280", ringTrack: "#e5e7eb", surface: "#f3f4f6" },
  { id: "verified", label: "Verified", min: 31, max: 60, color: "#4b5563", ringTrack: "#e5e7eb", surface: "#f9fafb" },
  { id: "trusted", label: "Trusted", min: 61, max: 85, color: "#374151", ringTrack: "#e5e7eb", surface: "#f3f4f6" },
  { id: "elite", label: "Elite Trusted", min: 86, max: 100, color: "#111827", ringTrack: "#e5e7eb", surface: "#ffffff" },
];

export function clampTrustScore(score) {
  return Math.max(0, Math.min(100, Number(score) || 0));
}

export function getTrustTier(score) {
  const n = clampTrustScore(score);
  const tier = TRUST_TIERS.find((t) => n >= t.min && n <= t.max) || TRUST_TIERS[0];
  return { ...tier, score: n };
}

export function getTrustTierClassName(tierId) {
  const map = {
    basic: "trustTierBasic",
    verified: "trustTierVerified",
    trusted: "trustTierTrusted",
    elite: "trustTierElite",
  };
  return map[tierId] || map.basic;
}
