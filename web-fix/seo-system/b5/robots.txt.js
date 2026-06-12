import { fetchBotFile } from '@/plugins/seo-engine/rentPageApi';

const CACHE_HEADER = 'public, s-maxage=600, stale-while-revalidate=3600';

const sendPlain = (res, body, cacheHeader = CACHE_HEADER) => {
  res.statusCode = 200;
  res.setHeader('Content-Type', 'text/plain; charset=utf-8');
  res.setHeader('Cache-Control', cacheHeader);
  res.end(body);
};

export const getServerSideProps = async ({ res }) => {
  try {
    const body = await fetchBotFile('robots');
    sendPlain(res, body || 'User-agent: *\nAllow: /\n');
  } catch (e) {
    console.error('[robots.txt]', e);
    sendPlain(res, 'User-agent: *\nAllow: /\nSitemap: https://homes.sukoon.group/sitemap.xml\n', 'public, s-maxage=300');
  }
  return { props: {} };
};

const RobotsTxtPage = () => null;
export default RobotsTxtPage;
