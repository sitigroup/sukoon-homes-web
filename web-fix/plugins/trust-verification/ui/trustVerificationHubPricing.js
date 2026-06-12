/** Minimum package price for hub display (existing packages API — no backend change). */
export function minPackagePrice(packages = []) {
  if (!Array.isArray(packages) || packages.length === 0) {
    return null;
  }
  const prices = packages
    .map((p) => Number(p?.price))
    .filter((n) => Number.isFinite(n) && n > 0);
  if (prices.length === 0) {
    return null;
  }
  return Math.min(...prices);
}

export function formatInr(amount) {
  if (amount == null) {
    return null;
  }
  return `₹${Number(amount).toLocaleString("en-IN")}`;
}

export function startingPriceLabel(packages) {
  const min = minPackagePrice(packages);
  return min != null ? `Starting from ${formatInr(min)}` : "View packages & pricing";
}

/** Split label (graphite) + amount (gold) for hub service cards */
export function startingPriceParts(packages) {
  const min = minPackagePrice(packages);
  if (min == null) {
    return { label: "View packages & pricing", amount: null };
  }
  return { label: "Starting from", amount: formatInr(min) };
}
