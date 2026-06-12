import { NextResponse } from 'next/server';

const API_BASE = (process.env.NEXT_PUBLIC_API_URL || 'https://admin-homes.sukoon.group').replace(/\/$/, '');
const ENDPOINT = (process.env.NEXT_PUBLIC_END_POINT || '/api').replace(/\/$/, '');

const CONTENT_PREFIXES = [
  '/property-details/',
  '/project-details/',
  '/article-details/',
  '/rent/',
];

const redirectCache = new Map();
const CACHE_MS = 10 * 60 * 1000;

function shouldCheckRedirect(pathname) {
  return CONTENT_PREFIXES.some((prefix) => pathname.startsWith(prefix));
}

async function fetchRedirect(pathname) {
  const cacheKey = pathname;
  const cached = redirectCache.get(cacheKey);
  if (cached && Date.now() - cached.at < CACHE_MS) {
    return cached.data;
  }

  const apiUrl = `${API_BASE}${ENDPOINT}/seo-engine/redirect?from=${encodeURIComponent(pathname)}`;
  try {
    const res = await fetch(apiUrl, {
      headers: { 'X-Active-Role': 'user' },
      cache: 'no-store',
    });
    if (!res.ok) return null;
    const json = await res.json();
    const data = json?.data || null;
    redirectCache.set(cacheKey, { at: Date.now(), data });
    return data;
  } catch {
    return null;
  }
}

export async function middleware(request) {
  const { pathname } = request.nextUrl;
  if (!shouldCheckRedirect(pathname)) {
    return NextResponse.next();
  }

  const normalized = pathname.endsWith('/') ? pathname : `${pathname}/`;
  const redirect = await fetchRedirect(normalized);
  if (!redirect?.to_path) {
    return NextResponse.next();
  }

  const target = redirect.to_path.startsWith('http')
    ? redirect.to_path
    : new URL(redirect.to_path, request.url).toString();

  const status = Number(redirect.status_code) === 302 ? 302 : 301;
  return NextResponse.redirect(target, status);
}

export const config = {
  matcher: [
    '/property-details/:path*',
    '/project-details/:path*',
    '/article-details/:path*',
    '/rent/:path*',
  ],
};
