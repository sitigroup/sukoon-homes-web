/**
 * pages/sitemap.xml.js — sitemap index (SSR).
 */

const CACHE_HEADER = 'public, s-maxage=3600, stale-while-revalidate=86400';

const sendXml = (res, xml, cacheHeader = CACHE_HEADER) => {
  res.statusCode = 200;
  res.setHeader('Content-Type', 'application/xml; charset=utf-8');
  res.setHeader('Cache-Control', cacheHeader);
  res.end(xml);
};

export const getServerSideProps = async ({ res }) => {
  try {
    const {
      generateSitemapIndexXml,
      buildSitemapIndexChildren,
    } = require('../scripts/sitemap-generator');

    const children = await buildSitemapIndexChildren();
    const indexXml = generateSitemapIndexXml(children);
    sendXml(res, indexXml);
  } catch (error) {
    console.error('[sitemap.xml] Index generation failed:', error);
    const webUrl = (process.env.NEXT_PUBLIC_WEB_URL || '').replace(/\/$/, '');
    const fallback = [
      '<?xml version="1.0" encoding="UTF-8"?>',
      '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
      `  <sitemap><loc>${webUrl}/sitemaps/static.xml</loc></sitemap>`,
      '</sitemapindex>',
    ].join('\n');
    sendXml(res, fallback, 'public, s-maxage=600, stale-while-revalidate=3600');
  }

  return { props: {} };
};

const SitemapIndexPage = () => null;
export default SitemapIndexPage;
