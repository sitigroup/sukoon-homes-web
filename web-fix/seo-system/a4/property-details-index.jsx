import { GET_PROPETRES } from '@/api/apiEndpoints';
import MetaData from '@/components/meta/MetaData';
import PropertyDetailPage from '@/components/pagescomponents/PropertyDetailPage';
import axios from 'axios';
import {
  mergeStructuredData,
  propertyBreadcrumbList,
  realEstateListing,
} from '@/utils/jsonld';

const fetchPageData = async (slug) => {
  try {
    const response = await axios.get(
      `${process.env.NEXT_PUBLIC_API_URL}${process.env.NEXT_PUBLIC_END_POINT}${GET_PROPETRES}?slug_id=${slug}&with_seo=1`,
      {
        headers: { 'X-Active-Role': 'user' },
        timeout: 15000,
      }
    );
    return response.data;
  } catch (error) {
    console.error('Error fetching property page data:', error);
    return null;
  }
};

const PropertyDetailsRoute = ({ seoData, pageName, initialPropertyLoad, structuredData }) => {
  const property = seoData?.data?.[0];

  return (
    <div>
      <MetaData
        title={property?.meta_title}
        description={property?.meta_description}
        keywords={property?.meta_keywords}
        ogImage={property?.meta_image}
        pageName={pageName}
        structuredData={structuredData}
      />
      <PropertyDetailPage initialPropertyLoad={initialPropertyLoad} />
    </div>
  );
};

let serverSidePropsFunction = null;
if (process.env.NEXT_PUBLIC_SEO === 'true') {
  serverSidePropsFunction = async (context) => {
    const { query, params } = context;
    const slugValue = params?.slug;
    const lang = query?.lang || 'en';
    const pageName = `/property-details/${slugValue}/?lang=${lang}`;

    const pageData = await fetchPageData(slugValue);
    const property = pageData?.data?.[0] || null;

    if (!property?.slug_id) {
      return { notFound: true };
    }

    const initialPropertyLoad = property
      ? {
          property,
          similar_properties:
            pageData?.similar_properties || pageData?.similiar_properties || [],
        }
      : null;

    const structuredData = mergeStructuredData(
      property?.schema_markup,
      realEstateListing(property),
      propertyBreadcrumbList(property, lang)
    );

    return {
      props: {
        seoData: pageData,
        pageName,
        initialPropertyLoad,
        structuredData,
      },
    };
  };
}

export const getServerSideProps = serverSidePropsFunction;
export default PropertyDetailsRoute;
