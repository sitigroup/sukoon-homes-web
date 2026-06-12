#!/usr/bin/env node
/**
 * scripts/check-slug-consistency.js
 *
 * Reports AreaListing rows where stored slug !== name-derived slug.
 * Run from Next.js root: node scripts/check-slug-consistency.js
 */

const axios = require('axios');

try {
  require('dotenv').config();
} catch {
  // optional
}

const getApiBase = () => {
  const url = process.env.NEXT_PUBLIC_API_URL || '';
  const sub = process.env.NEXT_PUBLIC_END_POINT || '/api/';
  return `${url}${sub}`;
};

const slugify = (text) => {
  if (!text) return '';
  return String(text)
    .normalize('NFC')
    .toLowerCase()
    .trim()
    .replace(/[^\p{L}\p{N}\p{M}\s-]/gu, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-+|-+$/g, '');
};

const headers = { 'X-Active-Role': 'user' };

async function main() {
  const api = getApiBase();
  const mismatches = [];

  const areasRes = await axios.get(`${api}area-listing/areas`, { headers, timeout: 20000 });
  const areas = areasRes.data?.data || [];

  areas.forEach((area) => {
    const derived = slugify(area.name);
    if (area.slug && derived && area.slug !== derived) {
      mismatches.push({
        type: 'area',
        id: area.id,
        name: area.name,
        stored: area.slug,
        derived,
        city: area.city,
      });
    }
    (area.sub_areas || []).forEach((sub) => {
      const subDerived = slugify(sub.name);
      if (sub.slug && subDerived && sub.slug !== subDerived) {
        mismatches.push({
          type: 'sub_area',
          id: sub.id,
          area_id: area.id,
          name: sub.name,
          stored: sub.slug,
          derived: subDerived,
          city: area.city,
          area_name: area.name,
        });
      }
    });
  });

  const citiesRes = await axios.get(`${api}area-listing/cities`, { headers, timeout: 10000 });
  const cities = citiesRes.data?.data || [];
  cities.forEach((city) => {
    const derived = slugify(city.name);
    if (city.slug && derived && city.slug !== derived) {
      mismatches.push({
        type: 'city',
        id: city.id,
        name: city.name,
        stored: city.slug,
        derived,
      });
    }
  });

  console.log('=== AreaListing slug consistency report ===');
  console.log(`Areas checked: ${areas.length}`);
  console.log(`Cities checked: ${cities.length}`);
  console.log(`Mismatches (stored !== name-derived): ${mismatches.length}`);
  console.log('');

  if (mismatches.length === 0) {
    console.log('OK — all stored slugs match name-derived slugs.');
    return;
  }

  mismatches.forEach((row) => {
    console.log(
      `[${row.type}] id=${row.id} name="${row.name}" stored="${row.stored}" derived="${row.derived}"` +
        (row.city ? ` city="${row.city}"` : '') +
        (row.area_name ? ` area="${row.area_name}"` : '')
    );
  });
}

main().catch((err) => {
  console.error('check-slug-consistency failed:', err.message);
  process.exit(1);
});
