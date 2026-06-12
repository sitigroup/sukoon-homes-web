import { GET_SEO_SETTINGS } from '@/api/apiEndpoints';
import MetaData from '@/components/meta/MetaData';
import SearchPage from '@/components/pagescomponents/SearchPage';
import axios from 'axios';
import { slugToDisplayName } from '@/utils/locationSearchUrl';

const fetchDataFromSeo = async () => {
    try {
        const response = await axios.get(
            `${process.env.NEXT_PUBLIC_API_URL}${process.env.NEXT_PUBLIC_END_POINT}${GET_SEO_SETTINGS}?page=search`
        );
        return response.data;
    } catch (error) {
        console.error("Error fetching data:", error);
        return null;
    }
};

const LocationSearchPage = ({ seoData, pageName = "/search/", locationTitle = "" }) => {
    const titleSuffix = locationTitle
        ? ` — ${locationTitle}`
        : "";
    return (
        <div>
            <MetaData
                title={(seoData?.data?.[0]?.title || "Search properties") + titleSuffix}
                description={seoData?.data?.[0]?.description}
                keywords={seoData?.data?.[0]?.keywords}
                ogImage={seoData?.data?.[0]?.image}
                pageName={seoData?.data?.[0]?.page || pageName}
                structuredData={seoData?.data?.[0]?.schema_markup}
            />
            <SearchPage />
        </div>
    );
};

let serverSidePropsFunction = null;
if (process.env.NEXT_PUBLIC_SEO === "true") {
    serverSidePropsFunction = async (context) => {
        const { query } = context;
        const lang = query?.lang || 'en';
        const citySlug = query?.citySlug || '';
        const areaSlug = query?.areaSlug || '';
        const subAreaSlug = query?.subAreaSlug || '';
        const parts = [citySlug, areaSlug, subAreaSlug].filter(Boolean);
        const path = parts.length ? `/search/${parts.join('/')}/` : '/search/';
        const pageName = `${path}?lang=${lang}`;
        const locationTitle = [
            slugToDisplayName(citySlug),
            slugToDisplayName(areaSlug),
            slugToDisplayName(subAreaSlug),
        ].filter(Boolean).join(", ");
        const seoData = await fetchDataFromSeo();
        return {
            props: {
                seoData,
                pageName,
                locationTitle,
            },
        };
    };
}

export const getServerSideProps = serverSidePropsFunction;
export default LocationSearchPage;
