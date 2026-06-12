/**
 * pages/sitemaps/[name].js — child sitemaps (static.xml, locations.xml, properties-1.xml, …).
 */

const CACHE_HEADER = 'public, s-maxage=3600, stale-while-revalidate=86400';

const sendXml = (res, xml) => {
  res.statusCode = 200;
  res.setHeader('Content-Type', 'application/xml; charset=utf-8');
  res.setHeader('Cache-Control', CACHE_HEADER);
  res.end(xml);
};

export const getServerSideProps = async ({ params, res }) => {
  const rawName = String(params?.name || '');
  const name = rawName.replace(/\.xml$/i, '');

  try {
    const { generateChildSitemapXml, fetchSettings } = require('../../scripts/sitemap-generator');
    const settings = await fetchSettings();
    const xml = await generateChildSitemapXml(name, settings);
    sendXml(res, xml);
  } catch (error) {
    console.error(`[sitemaps/${rawName}] Failed:`, error.message);
    res.statusCode = 404;
    res.setHeader('Content-Type', 'text/plain; charset=utf-8');
    res.end('Sitemap not found');
  }

  return { props: {} };
};

const ChildSitemapPage = () => null;
export default ChildSitemapPage;
