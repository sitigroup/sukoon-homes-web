const apiBase = () =>
  (process.env.NEXT_PUBLIC_API_URL || 'https://admin-homes.sukoon.group/api/').replace(/\/?$/, '/');

export async function fetchGuidePage(category, slug) {
  const params = new URLSearchParams({ category, slug });
  const res = await fetch(`${apiBase()}seo-engine/qa-page?${params}`, {
    headers: { Accept: 'application/json' },
  });
  if (res.status === 404) return null;
  if (!res.ok) throw new Error(`Guide API ${res.status}`);
  const json = await res.json();
  return json?.data ?? null;
}

export function buildGuidePath(category, slug) {
  return `/guides/${category}/${slug}/`;
}
