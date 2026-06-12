import { fetchBotFile } from '@/plugins/seo-engine/rentPageApi';

const CACHE_HEADER = 'public, s-maxage=600, stale-while-revalidate=3600';

export const getServerSideProps = async ({ res }) => {
  try {
    const body = await fetchBotFile('llms');
    res.statusCode = 200;
    res.setHeader('Content-Type', 'text/plain; charset=utf-8');
    res.setHeader('Cache-Control', CACHE_HEADER);
    res.end(body || '# Sukoon Homes\n');
  } catch (e) {
    console.error('[llms.txt]', e);
    res.statusCode = 200;
    res.setHeader('Content-Type', 'text/plain; charset=utf-8');
    res.end('# Sukoon Homes\n');
  }
  return { props: {} };
};

const LlmsTxtPage = () => null;
export default LlmsTxtPage;
