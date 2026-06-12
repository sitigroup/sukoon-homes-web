const apiBase = () =>
  `${process.env.NEXT_PUBLIC_API_URL || ''}${process.env.NEXT_PUBLIC_END_POINT || '/api/'}`;

const headers = () => ({ 'X-Active-Role': 'user' });

export async function fetchRentPage(path) {
  const url = `${apiBase()}seo-engine/page?path=${encodeURIComponent(path)}`;
  const res = await fetch(url, { headers: headers(), cache: 'no-store' });
  if (res.status === 404) return null;
  if (!res.ok) throw new Error(`page API ${res.status}`);
  const json = await res.json();
  return json?.data || null;
}

export async function fetchRentRedirect(fromPath) {
  const url = `${apiBase()}seo-engine/redirect?from=${encodeURIComponent(fromPath)}`;
  const res = await fetch(url, { headers: headers(), cache: 'no-store' });
  if (!res.ok) return null;
  const json = await res.json();
  return json?.data || null;
}

export async function logRent404(path, referrer = '') {
  try {
    await fetch(`${apiBase()}seo-engine/404-log`, {
      method: 'POST',
      headers: { ...headers(), 'Content-Type': 'application/json' },
      body: JSON.stringify({ path, referrer }),
    });
  } catch {
    // non-blocking
  }
}

export async function fetchPopularRentPaths(limit = 10) {
  const url = `${apiBase()}seo-engine/paths?indexable=1&per_page=${limit}`;
  const res = await fetch(url, { headers: headers(), cache: 'no-store' });
  if (!res.ok) return [];
  const json = await res.json();
  return json?.data || [];
}

export async function fetchBotFile(kind) {
  const url = `${apiBase()}seo-engine/${kind}-txt`;
  const res = await fetch(url, { headers: headers(), next: { revalidate: 600 } });
  if (!res.ok) return '';
  const json = await res.json();
  return json?.data?.body || '';
}

export function buildRentPath(segments) {
  const parts = Array.isArray(segments) ? segments.filter(Boolean) : [];
  if (!parts.length) return '/rent/';
  return `/rent/${parts.join('/')}/`;
}
