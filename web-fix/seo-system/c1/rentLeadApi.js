const apiBase = () =>
  `${process.env.NEXT_PUBLIC_API_URL || ''}${process.env.NEXT_PUBLIC_END_POINT || '/api/'}`;

const headers = () => ({ 'X-Active-Role': 'user', 'Content-Type': 'application/json' });

export async function submitRentLead(payload) {
  const res = await fetch(`${apiBase()}seo-engine/leads`, {
    method: 'POST',
    headers: headers(),
    body: JSON.stringify(payload),
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(json?.message || `Lead submit failed (${res.status})`);
  }
  return json;
}

export async function fetchSeoPublicSettings() {
  const res = await fetch(`${apiBase()}seo-engine/settings`, {
    headers: { 'X-Active-Role': 'user' },
    next: { revalidate: 600 },
  });
  if (!res.ok) return null;
  const json = await res.json();
  return json?.data || null;
}
