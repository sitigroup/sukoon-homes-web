/** Paths that must not be indexed (mirrors public/robots.txt Disallow rules + private app areas). */
const NOINDEX_PATH_PATTERNS = [
  /^\/login(\/|$)/,
  /^\/register(\/|$)/,
  /^\/user(\/|$)/,
  /\/dashboard(\/|$)/,
  /^\/owner-dashboard(\/|$)/,
  /^\/tenant-dashboard(\/|$)/,
  /^\/payment(\/|$)/,
  /^\/property-detail-preview(\/|$)/,
  /^\/compare-properties(\/|$)/,
  /^\/my-/,
  /^\/agent\/login(\/|$)/,
  /^\/agent\/my-/,
  /^\/agent(\/|$)/,
  /^\/all-personalized-feeds(\/|$)/,
  /^\/404(\/|$)/,
];

export function shouldNoIndexPath(pathname = '') {
  const path = String(pathname || '').split('?')[0];
  return NOINDEX_PATH_PATTERNS.some((pattern) => pattern.test(path));
}
