/**
 * scripts/sitemap-generator.js — sitemap index + child sitemaps (SSR + CLI).
 *
 * Index: /sitemap.xml
 * Children: /sitemaps/static.xml, locations.xml, properties-{n}.xml, projects.xml, articles.xml, rent-pages.xml
 */

const axios = require('axios');

try {
  require('dotenv').config();
} catch {
  // dotenv optional in Next.js runtime
}

const PROPERTY_CHUNK_SIZE = 5000;

// ---------------------------------------------------------------------------
// Environment helpers
// ---------------------------------------------------------------------------

const getApiBase = () => {
  const url = process.env.NEXT_PUBLIC_API_URL || '';
  const sub = process.env.NEXT_PUBLIC_END_POINT || '/api/';
  return `${url}${sub}`;
};

const getWebUrl = () => (process.env.NEXT_PUBLIC_WEB_URL || '').replace(/\/$/, '');

const apiHeaders = () => ({ 'X-Active-Role': 'user' });

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

const validateEnvironment = () => {
  const required = ['NEXT_PUBLIC_WEB_URL', 'NEXT_PUBLIC_API_URL', 'NEXT_PUBLIC_END_POINT'];
  const missing = required.filter((v) => !process.env[v]);
  if (missing.length > 0) {
    throw new Error(`Missing required environment variables: ${missing.join(', ')}`);
  }
};

// ---------------------------------------------------------------------------
// Static routes
// ---------------------------------------------------------------------------

const staticRoutes = [
  { path: '/', priority: 1.0, changefreq: 'daily' },
  { path: '/properties/', priority: 0.9, changefreq: 'daily' },
  { path: '/projects/', priority: 0.9, changefreq: 'daily' },
  { path: '/search/', priority: 0.9, changefreq: 'daily' },
  { path: '/projects/featured-projects/', priority: 0.8, changefreq: 'weekly' },
  { path: '/properties-on-map/', priority: 0.8, changefreq: 'weekly' },
  { path: '/all/categories/', priority: 0.8, changefreq: 'weekly' },
  { path: '/all/agents/', priority: 0.8, changefreq: 'weekly' },
  { path: '/all/articles/', priority: 0.8, changefreq: 'weekly' },
  { path: '/about-us/', priority: 0.7, changefreq: 'monthly' },
  { path: '/contact-us/', priority: 0.7, changefreq: 'monthly' },
  { path: '/subscription-plan/', priority: 0.7, changefreq: 'monthly' },
  { path: '/faqs/', priority: 0.6, changefreq: 'monthly' },
  { path: '/privacy-policy/', priority: 0.5, changefreq: 'yearly' },
  { path: '/terms-and-conditions/', priority: 0.5, changefreq: 'yearly' },
];

// ---------------------------------------------------------------------------
// URL entry builder (hreflang preserved)
// ---------------------------------------------------------------------------

const generateUrlEntry = (
  path,
  priority = 0.5,
  changefreq = 'weekly',
  lastmod = null,
  languages = [],
  defaultLangCode = 'en'
) => {
  const normalizedPath = path ? path.replace(/^\/+/, '') : '';
  const baseWebUrl = getWebUrl();
  const baseUrl = normalizedPath ? `${baseWebUrl}/${normalizedPath}` : baseWebUrl;

  const encodeUrl = (url) => {
    try {
      const urlObj = new URL(url);
      urlObj.pathname = urlObj.pathname
        .split('/')
        .map((seg) => encodeURIComponent(decodeURIComponent(seg)))
        .join('/');
      return urlObj.toString();
    } catch {
      return encodeURI(url);
    }
  };

  const escapeXml = (url) =>
    url
      .replace(/&/g, '&amp;')
      .replace(/'/g, '&apos;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');

  const canonicalUrl = `${baseUrl}?lang=${defaultLangCode}`;
  const escapedCanonical = escapeXml(encodeUrl(canonicalUrl));
  const lastModified = lastmod ? lastmod.toISOString() : new Date().toISOString();
  const validPriority = Math.max(0.0, Math.min(1.0, priority)).toFixed(1);

  const uniqueLangs = languages
    .filter((l) => l.code && l.code.trim() !== '')
    .filter((l, i, arr) => arr.findIndex((x) => x.code === l.code) === i);

  const hreflangs = uniqueLangs
    .map((lang) => {
      const langUrl = escapeXml(encodeUrl(`${baseUrl}?lang=${lang.code}`));
      return `    <xhtml:link rel="alternate" hreflang="${lang.code}" href="${langUrl}" />`;
    })
    .join('\n');

  const xDefaultUrl = escapeXml(encodeUrl(`${baseUrl}?lang=${defaultLangCode}`));

  return `  <url>
    <loc>${escapedCanonical}</loc>
    <lastmod>${lastModified}</lastmod>
    <changefreq>${changefreq}</changefreq>
    <priority>${validPriority}</priority>
${hreflangs}
    <xhtml:link rel="alternate" hreflang="x-default" href="${xDefaultUrl}" />
  </url>`;
};

// ---------------------------------------------------------------------------
// API helpers
// ---------------------------------------------------------------------------

const fetchAllPages = async (endpoint, params = {}, limit = 100) => {
  const allItems = [];
  let offset = 0;

  while (true) {
    try {
      const res = await axios.get(endpoint, {
        params: { ...params, limit, offset },
        timeout: 15000,
        headers: apiHeaders(),
      });
      const data = res.data?.data ?? res.data;
      if (!Array.isArray(data) || data.length === 0) break;
      allItems.push(...data);
      if (data.length < limit) break;
      offset += limit;
    } catch (err) {
      console.error(`Error fetching ${endpoint} at offset ${offset}:`, err.message);
      break;
    }
  }

  return allItems;
};

const fetchSettings = async () => {
  try {
    const res = await axios.get(`${getApiBase()}web-settings`, { timeout: 10000 });
    const data = res.data?.data ?? {};
    return {
      webFavicon: data.web_favicon || '',
      webColor: data.system_color || '',
      defaultLangCode: data.default_language || 'en',
      languages: data.languages || [],
    };
  } catch (err) {
    console.error('Error fetching web-settings:', err.message);
    return { webFavicon: '', webColor: '', defaultLangCode: 'en', languages: [] };
  }
};

const buildPropertyFilters = () => {
  const filtersObject = {
    property_type: '',
    category_id: '',
    category_slug_id: '',
    parameters: [],
    nearby_places: [],
    location: {},
    price: {},
    posted_since: 0,
    search: '',
    flags: {},
  };
  return Buffer.from(JSON.stringify(filtersObject)).toString('base64');
};

const getDynamicRouteConfigs = () => [
  {
    name: 'Properties',
    type: 'properties',
    route: '/property-details/[slug]/',
    apiEndpoint: `${getApiBase()}get-property-list`,
    extraParams: { filters: buildPropertyFilters() },
    pathExtractor: (item) => item?.slug_id,
    priority: 0.8,
    changefreq: 'weekly',
  },
  {
    name: 'Projects',
    type: 'projects',
    route: '/project-details/[slug]/',
    apiEndpoint: `${getApiBase()}get-projects`,
    extraParams: {},
    pathExtractor: (item) => item?.slug_id,
    priority: 0.8,
    changefreq: 'weekly',
  },
  {
    name: 'Articles',
    type: 'articles',
    route: '/article-details/[slug]/',
    apiEndpoint: `${getApiBase()}get_articles`,
    extraParams: {},
    pathExtractor: (item) => item?.slug_id,
    priority: 0.7,
    changefreq: 'monthly',
  },
  {
    name: 'Agents',
    type: 'agents',
    route: '/agent-details/[slug]/',
    apiEndpoint: `${getApiBase()}agent-list`,
    extraParams: {},
    pathExtractor: (item) => item?.slug_id || item?.unique_name,
    priority: 0.7,
    changefreq: 'monthly',
  },
];

const itemsToRoutes = (items, config) => {
  const routes = [];
  items.forEach((item) => {
    const slug = config.pathExtractor(item);
    if (!slug) return;
    const fullPath = config.route.replace('[slug]', slug);
    const lastModified = item.updated_at
      ? new Date(item.updated_at)
      : item.created_at
        ? new Date(item.created_at)
        : new Date();
    routes.push({
      path: fullPath,
      priority: config.priority,
      changefreq: config.changefreq,
      lastmod: lastModified,
    });
  });
  return routes;
};

const fetchRoutesForType = async (type) => {
  const config = getDynamicRouteConfigs().find((c) => c.type === type);
  if (!config) return [];
  try {
    const items = await fetchAllPages(config.apiEndpoint, config.extraParams);
    return itemsToRoutes(items, config);
  } catch (err) {
    console.error(`Error fetching ${type}:`, err.message);
    return [];
  }
};

const fetchAllDynamicRoutes = async () => {
  const allRoutes = [];
  for (const config of getDynamicRouteConfigs()) {
    try {
      const items = await fetchAllPages(config.apiEndpoint, config.extraParams);
      if (items.length === 0) {
        console.warn(`⚠️  No items found for ${config.name}`);
        continue;
      }
      allRoutes.push(...itemsToRoutes(items, config));
      console.log(`✓ Added ${items.length} ${config.name}`);
    } catch (err) {
      console.error(`❌ Error fetching ${config.name}:`, err.message);
    }
  }
  return allRoutes;
};

/**
 * Area Wise /search/ URLs from AreaListing API (stored slugs only).
 */
const fetchLocationRoutes = async () => {
  const routes = [];
  const api = getApiBase();

  let cities = [];
  try {
    const res = await axios.get(`${api}area-listing/cities`, {
      timeout: 10000,
      headers: apiHeaders(),
    });
    cities = res.data?.data || [];
  } catch (err) {
    console.error('Error fetching area-listing cities:', err.message);
    return routes;
  }

  const citySlugByName = new Map();
  cities.forEach((city) => {
    if (!city.slug) return;
    const lastmod = city.updated_at ? new Date(city.updated_at) : new Date();
    routes.push({
      path: `/search/${city.slug}/`,
      priority: 0.85,
      changefreq: 'weekly',
      lastmod,
    });
    if (city.name) citySlugByName.set(city.name.toLowerCase(), city.slug);
    citySlugByName.set(city.slug, city.slug);
  });

  let areas = [];
  try {
    const res = await axios.get(`${api}area-listing/areas`, {
      timeout: 20000,
      headers: apiHeaders(),
    });
    areas = res.data?.data || [];
  } catch (err) {
    console.error('Error fetching area-listing areas:', err.message);
    return routes;
  }

  areas.forEach((area) => {
    if (!area.slug) return;
    const citySlug =
      citySlugByName.get((area.city || '').toLowerCase()) || slugify(area.city);
    if (!citySlug) return;
    const areaLastmod = area.updated_at ? new Date(area.updated_at) : new Date();
    routes.push({
      path: `/search/${citySlug}/${area.slug}/`,
      priority: 0.8,
      changefreq: 'weekly',
      lastmod: areaLastmod,
    });
    (area.sub_areas || []).forEach((sub) => {
      if (!sub.slug) return;
      routes.push({
        path: `/search/${citySlug}/${area.slug}/${sub.slug}/`,
        priority: 0.75,
        changefreq: 'weekly',
        lastmod: areaLastmod,
      });
    });
  });

  return routes;
};

// ---------------------------------------------------------------------------
// XML wrappers
// ---------------------------------------------------------------------------

const wrapUrlset = (urlEntries, { webFavicon = '', webColor = '' } = {}) => {
  const faviconPI = webFavicon ? `<?web-favicon ${webFavicon}?>` : '';
  const colorPI = webColor ? `<?web-color ${webColor}?>` : '';
  const lines = [
    '<?xml version="1.0" encoding="UTF-8"?>',
    '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>',
    faviconPI,
    colorPI,
    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"',
    '        xmlns:xhtml="http://www.w3.org/1999/xhtml"',
    '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"',
    '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9',
    '        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">',
    ...urlEntries,
    '</urlset>',
  ];
  return lines.filter(Boolean).join('\n');
};

const routesToEntries = (routes, languages, defaultLangCode) =>
  routes.map(({ path, priority, changefreq, lastmod }) =>
    generateUrlEntry(
      path === '/' ? '' : path.replace(/^\/+/, ''),
      priority,
      changefreq,
      lastmod,
      languages,
      defaultLangCode
    )
  );

const generateSitemapIndexXml = (childSitemaps = []) => {
  const escapeXml = (s) =>
    String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');

  const entries = childSitemaps.map(({ loc, lastmod }) => {
    const lm = (lastmod || new Date()).toISOString();
    return `  <sitemap>
    <loc>${escapeXml(loc)}</loc>
    <lastmod>${lm}</lastmod>
  </sitemap>`;
  });

  return [
    '<?xml version="1.0" encoding="UTF-8"?>',
    '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
    ...entries,
    '</sitemapindex>',
  ].join('\n');
};

/**
 * Indexable /rent/ pages from SeoEngine API (TASK B3).
 */
const fetchRentPageRoutes = async () => {
  try {
    const apiBase = getApiBase().replace(/\/$/, '');
    const res = await axios.get(`${apiBase}/seo-engine/rent-sitemap`, {
      headers: apiHeaders(),
      timeout: 20000,
    });
    const rows = res.data?.data || [];
    return rows.map((row) => ({
      path: row.path,
      priority: 0.75,
      changefreq: 'weekly',
      lastmod: row.lastmod ? new Date(row.lastmod) : new Date(),
    }));
  } catch (err) {
    console.warn('[sitemap] rent-pages fetch failed:', err.message);
    return [];
  }
};

/**
 * Build index child list (loc URLs only).
 */
const buildSitemapIndexChildren = async () => {
  const webUrl = getWebUrl();
  const now = new Date();
  const children = [
    { loc: `${webUrl}/sitemaps/static.xml`, lastmod: now },
    { loc: `${webUrl}/sitemaps/locations.xml`, lastmod: now },
  ];

  const propertyRoutes = await fetchRoutesForType('properties');
  const propertyChunks = Math.max(1, Math.ceil(propertyRoutes.length / PROPERTY_CHUNK_SIZE));
  if (propertyRoutes.length > 0) {
    for (let i = 1; i <= propertyChunks; i += 1) {
      children.push({ loc: `${webUrl}/sitemaps/properties-${i}.xml`, lastmod: now });
    }
  }

  const projectRoutes = await fetchRoutesForType('projects');
  if (projectRoutes.length > 0) {
    children.push({ loc: `${webUrl}/sitemaps/projects.xml`, lastmod: now });
  }

  const articleRoutes = await fetchRoutesForType('articles');
  if (articleRoutes.length > 0) {
    children.push({ loc: `${webUrl}/sitemaps/articles.xml`, lastmod: now });
  }

  const agentRoutes = await fetchRoutesForType('agents');
  if (agentRoutes.length > 0) {
    children.push({ loc: `${webUrl}/sitemaps/agents.xml`, lastmod: now });
  }

  const rentRoutes = await fetchRentPageRoutes();
  if (rentRoutes.length > 0) {
    children.push({ loc: `${webUrl}/sitemaps/rent-pages.xml`, lastmod: now });
  }

  return children;
};

/**
 * Generate one child sitemap by name (static, locations, properties-1, projects, articles, agents).
 */
const generateChildSitemapXml = async (name, options = {}) => {
  const {
    webFavicon = '',
    webColor = '',
    defaultLangCode = 'en',
    languages = [],
  } = options;

  let routes = [];

  if (name === 'static') {
    routes = staticRoutes.map(({ path, priority, changefreq }) => ({
      path,
      priority,
      changefreq,
      lastmod: new Date(),
    }));
  } else if (name === 'locations') {
    routes = await fetchLocationRoutes();
  } else if (name === 'projects') {
    routes = await fetchRoutesForType('projects');
  } else if (name === 'articles') {
    routes = await fetchRoutesForType('articles');
  } else if (name === 'agents') {
    routes = await fetchRoutesForType('agents');
  } else if (name.startsWith('properties-')) {
    const chunkNum = parseInt(name.replace('properties-', ''), 10);
    if (!Number.isFinite(chunkNum) || chunkNum < 1) {
      throw new Error(`Invalid properties chunk: ${name}`);
    }
    const all = await fetchRoutesForType('properties');
    const start = (chunkNum - 1) * PROPERTY_CHUNK_SIZE;
    routes = all.slice(start, start + PROPERTY_CHUNK_SIZE);
  } else if (name === 'rent-pages') {
    routes = await fetchRentPageRoutes();
  } else {
    throw new Error(`Unknown sitemap child: ${name}`);
  }

  const entries = routesToEntries(routes, languages, defaultLangCode);
  return wrapUrlset(entries, { webFavicon, webColor });
};

/**
 * Legacy single-file sitemap (all URLs in one urlset) — kept for CLI fallback.
 */
const generateSitemapXml = async ({
  webFavicon = '',
  webColor = '',
  defaultLangCode = 'en',
  languages = [],
} = {}) => {
  const staticEntries = staticRoutes.map(({ path, priority, changefreq }) => ({
    path,
    priority,
    changefreq,
    lastmod: new Date(),
  }));
  const dynamicRoutes = await fetchAllDynamicRoutes();
  const locationRoutes = await fetchLocationRoutes();
  const allRoutes = [...staticEntries, ...locationRoutes, ...dynamicRoutes];
  const entries = routesToEntries(allRoutes, languages, defaultLangCode);
  return wrapUrlset(entries, { webFavicon, webColor });
};

// ---------------------------------------------------------------------------
// CLI
// ---------------------------------------------------------------------------

const runCli = async () => {
  const fs = require('fs');
  const path = require('path');

  console.log('=================================');
  console.log('Starting Sitemap Index Generation');
  console.log('=================================\n');

  validateEnvironment();
  const settings = await fetchSettings();
  const children = await buildSitemapIndexChildren();
  const indexXml = generateSitemapIndexXml(children);

  const publicDir = 'public';
  const sitemapsDir = path.join(publicDir, 'sitemaps');
  if (!fs.existsSync(sitemapsDir)) fs.mkdirSync(sitemapsDir, { recursive: true });

  fs.writeFileSync(path.join(publicDir, 'sitemap.xml'), indexXml);

  const childNames = children.map((c) => {
    const m = c.loc.match(/\/sitemaps\/(.+)$/);
    return m ? m[1] : null;
  }).filter(Boolean);

  for (const childName of childNames) {
    const xml = await generateChildSitemapXml(childName, settings);
    fs.writeFileSync(path.join(sitemapsDir, childName), xml);
    const count = (xml.match(/<url>/g) || []).length;
    console.log(`✓ ${childName}: ${count} URLs`);
  }

  console.log(`\n✓ Index lists ${children.length} child sitemaps`);
};

if (require.main === module) {
  runCli().catch((err) => {
    console.error('❌ Sitemap generation failed:', err.message);
    process.exit(1);
  });
}

module.exports = {
  generateSitemapXml,
  generateSitemapIndexXml,
  generateChildSitemapXml,
  buildSitemapIndexChildren,
  fetchSettings,
  fetchLocationRoutes,
  fetchRoutesForType,
  slugify,
  PROPERTY_CHUNK_SIZE,
};
