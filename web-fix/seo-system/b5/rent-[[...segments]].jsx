import RentPageView from '@/plugins/seo-engine/RentPageView';
import {
  buildRentPath,
  fetchPopularRentPaths,
  fetchRentPage,
  fetchRentRedirect,
  logRent404,
} from '@/plugins/seo-engine/rentPageApi';
import {
  mergeRentStructuredData,
  rentBreadcrumbList,
  rentFaqPage,
  rentItemList,
} from '@/plugins/seo-engine/jsonld-rent';

const CACHE_HEADER = 'public, s-maxage=21600, stale-while-revalidate=86400';

const RentRoutePage = (props) => <RentPageView {...props} />;

let serverSidePropsFunction = null;
if (process.env.NEXT_PUBLIC_SEO === 'true') {
  serverSidePropsFunction = async (context) => {
    const { params, query, req, res } = context;
    const lang = query?.lang || 'en';
    const segments = params?.segments || [];
    const path = buildRentPath(segments);

    res.setHeader('Cache-Control', CACHE_HEADER);

    let payload = await fetchRentPage(path);

    if (!payload) {
      const redirect = await fetchRentRedirect(path);
      if (redirect?.to_path) {
        const dest = redirect.to_path.startsWith('http')
          ? redirect.to_path
          : `${redirect.to_path}${redirect.to_path.includes('?') ? '&' : '?'}lang=${lang}`;
        return {
          redirect: {
            destination: dest,
            permanent: Number(redirect.status_code) !== 302,
          },
        };
      }
      await logRent404(path, req?.headers?.referer || '');
      return { notFound: true };
    }

    const page = payload.page;
    const robots = page.is_indexable ? 'index, follow' : 'noindex, nofollow';
    res.setHeader('X-Robots-Tag', robots);
    const structuredData = mergeRentStructuredData(
      rentBreadcrumbList(payload.breadcrumbs, lang),
      rentItemList(payload.listings, page.title, lang),
      rentFaqPage(page.faq_json)
    );
    const popularPaths = await fetchPopularRentPaths(10);

    return {
      props: {
        payload,
        popularPaths,
        lang,
        structuredData,
        robots,
      },
    };
  };
}

export const getServerSideProps = serverSidePropsFunction;
export default RentRoutePage;
