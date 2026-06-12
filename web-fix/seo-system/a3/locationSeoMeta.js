import axios from 'axios';

const getApiBase = () =>
  `${process.env.NEXT_PUBLIC_API_URL || ''}${process.env.NEXT_PUBLIC_END_POINT || '/api/'}`;

const apiHeaders = { 'X-Active-Role': 'user' };

/**
 * Resolve Area Wise seo_title / seo_description for a /search/ path (SSR).
 * Sub-area meta wins over area meta when both exist.
 */
export async function fetchAreaWiseSeoMeta({ citySlug = '', areaSlug = '', subAreaSlug = '' } = {}) {
  if (!areaSlug) {
    return null;
  }

  const api = getApiBase();

  try {
    const areasRes = await axios.get(`${api}area-listing/areas`, {
      headers: apiHeaders,
      timeout: 12000,
    });
    const areas = areasRes.data?.data || [];

    let cityName = '';
    if (citySlug) {
      const citiesRes = await axios.get(`${api}area-listing/cities`, {
        headers: apiHeaders,
        timeout: 10000,
      });
      const cityRow = (citiesRes.data?.data || []).find((c) => c.slug === citySlug);
      cityName = (cityRow?.name || '').toLowerCase();
    }

    const area = areas.find((row) => {
      if (row.slug !== areaSlug) return false;
      if (!cityName) return true;
      return String(row.city || '').toLowerCase() === cityName;
    });

    if (!area) return null;

    if (subAreaSlug) {
      const sub = (area.sub_areas || []).find((s) => s.slug === subAreaSlug);
      if (sub && (sub.seo_title || sub.seo_description)) {
        return {
          seo_title: sub.seo_title || '',
          seo_description: sub.seo_description || '',
        };
      }
    }

    if (area.seo_title || area.seo_description) {
      return {
        seo_title: area.seo_title || '',
        seo_description: area.seo_description || '',
      };
    }
  } catch (error) {
    console.error('[fetchAreaWiseSeoMeta]', error.message);
  }

  return null;
}
