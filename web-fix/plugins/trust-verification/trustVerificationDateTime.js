/**
 * Trust Verification — display timestamps in Asia/Kolkata.
 */

export const TV_TIMEZONE = "Asia/Kolkata";
export const TV_PENDING_LABEL = "Pending";
export const TV_NOT_RECORDED_LABEL = "Not recorded";

/**
 * @param {string|number|Date|null|undefined} value
 * @returns {string|null}
 */
export function formatTrustVerificationDateTime(value) {
  if (value == null || value === "") return null;
  const date = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(date.getTime())) return null;

  const parts = new Intl.DateTimeFormat("en-GB", {
    timeZone: TV_TIMEZONE,
    day: "numeric",
    month: "short",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
    hour12: true,
  }).formatToParts(date);

  const pick = (type) => parts.find((p) => p.type === type)?.value ?? "";
  const day = pick("day");
  const month = pick("month");
  const year = pick("year");
  const hour = pick("hour");
  const minute = pick("minute");
  const dayPeriod = pick("dayPeriod").toUpperCase();

  if (!day || !month || !year) return null;

  return `${day} ${month} ${year}, ${hour}:${minute} ${dayPeriod}`;
}

/**
 * @param {object|null|undefined} order
 * @returns {object}
 */
export function getOrderTimestamps(order) {
  return order?.timestamps && typeof order.timestamps === "object" ? order.timestamps : {};
}

/**
 * @param {string|null|undefined} iso
 * @param {string} [fallback]
 */
export function formatTimestampOrFallback(iso, fallback = TV_NOT_RECORDED_LABEL) {
  return formatTrustVerificationDateTime(iso) || fallback;
}
