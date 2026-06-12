import GuidePageView from '@/plugins/seo-engine/GuidePageView';
import { buildGuidePath, fetchGuidePage } from '@/plugins/seo-engine/guidePageApi';
import {
  guideArticle,
  guideBreadcrumbList,
  guideFaqPage,
  mergeGuideStructuredData,
} from '@/plugins/seo-engine/jsonld-guide';

const CACHE_HEADER = 'public, s-maxage=21600, stale-while-revalidate=86400';

const GuideRoutePage = (props) => <GuidePageView {...props} />;

let serverSidePropsFunction = null;
if (process.env.NEXT_PUBLIC_SEO === 'true') {
  serverSidePropsFunction = async (context) => {
    const { params, query, res } = context;
    const lang = query?.lang || 'en';
    const category = params?.category;
    const slug = params?.slug;

    res.setHeader('Cache-Control', CACHE_HEADER);
    res.setHeader('X-Robots-Tag', 'index, follow');

    const payload = await fetchGuidePage(category, slug);
    if (!payload) {
      return { notFound: true };
    }

    const structuredData = mergeGuideStructuredData(
      guideBreadcrumbList(payload.breadcrumbs, lang),
      guideArticle(payload.page, payload.canonical),
      guideFaqPage(payload.page)
    );

    return {
      props: {
        payload,
        lang,
        structuredData,
        robots: 'index, follow',
      },
    };
  };
}

export const getServerSideProps = serverSidePropsFunction;
export default GuideRoutePage;
