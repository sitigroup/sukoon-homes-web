import * as api from "@/api/apiRoutes";
import {
    AgentSectionSkeleton,
    ArticleSectionSkeleton,
    CircularItemsSkeleton,
    CitiesSkeleton,
    FaqsSkeleton,
    FeaturedProjectsSkeleton,
    FeaturedPropertiesSkeleton,
    MainSwiperSkeleton,
    MapSkeleton,
    MostLikedSkeleton,
    MostViewedSkeleton,
    NearbyPropertiesSkeleton,
    PremiumPropertiesSkeleton,
    ProjectsSkeleton,
    PropertySectionSkeleton,
    UserRecommendationSkeleton
} from '@/components/skeletons/home/index';
import { useAuthStatus } from '@/hooks/useAuthStatus';
import dynamic from 'next/dynamic';
import { useEffect, useState, useRef, useCallback } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import Swal from 'sweetalert2';
import { useTranslation } from '../context/TranslationContext';
import LocationSearchWithRadius from '../location-search/LocationSearchWithRadius';
import { useQuery } from "@tanstack/react-query";
import ImageWithPlaceholder from "../image-with-placeholder/ImageWithPlaceholder";
import { Skeleton } from '@/components/ui/skeleton';
import { useRouter } from "next/router";
import { getCurrentLocationData } from "@/utils/helperFunction";
import { setIsLocationBasedHomepageData } from "@/redux/slices/cacheSlice";
const MainSwiper = dynamic(() => import('../mainswiper/MainSwiper'), { ssr: false, loading: () => <MainSwiperSkeleton /> });
const Faqs = dynamic(() => import('../faqs/Faqs'), { ssr: false, loading: () => <FaqsSkeleton /> });

// Specific Dynamic Components with specific Skeletons to prevent CLS
const NearbyPropertiesSection = dynamic(() => import('../homepagesections/HomeNewSectionOne'), { ssr: false, loading: () => <NearbyPropertiesSkeleton /> });
const MostViewedSection = dynamic(() => import('../homepagesections/HomeNewSectionTwo'), { ssr: false, loading: () => <MostViewedSkeleton /> });
const MostLikedSection = dynamic(() => import('../homepagesections/HomeNewSectionTwo'), { ssr: false, loading: () => <MostLikedSkeleton /> });
const FeaturedPropertiesSection = dynamic(() => import('../homepagesections/HomeNewSectionFour'), { ssr: false, loading: () => <FeaturedPropertiesSkeleton /> });
const PremiumPropertiesSection = dynamic(() => import('../homepagesections/PremiumPropertiesSection'), { ssr: false, loading: () => <PremiumPropertiesSkeleton /> });
const ProjectsSection = dynamic(() => import('../homepagesections/HomeNewSectionTwo'), { ssr: false, loading: () => <ProjectsSkeleton /> });
const FeaturedProjectsSection = dynamic(() => import('../homepagesections/PremiumPropertiesSection'), { ssr: false, loading: () => <FeaturedProjectsSkeleton /> });
const CitiesSection = dynamic(() => import('../homepagesections/HomeNewSectionTwo'), { ssr: false, loading: () => <CitiesSkeleton /> });
const AgentsSection = dynamic(() => import('../homepagesections/AgentSwiperSection'), { ssr: false, loading: () => <AgentSectionSkeleton /> });
const ArticlesSection = dynamic(() => import('../homepagesections/HomeNewSectionOne'), { ssr: false, loading: () => <ArticleSectionSkeleton /> });
const CategoriesSection = dynamic(() => import('../homepagesections/HomeNewSectionOne'), { ssr: false, loading: () => <CircularItemsSkeleton /> });
const UserRecommendationsSection = dynamic(() => import('../homepagesections/HomeNewSectionOne'), { ssr: false, loading: () => <UserRecommendationSkeleton /> });
const MapSection = dynamic(() => import('../homepagesections/HomePropertiesOnMap'), { ssr: false, loading: () => <MapSkeleton /> });
const AllPropertiesSection = dynamic(() => import('../homepagesections/AllPropertiesSection'), { ssr: false, loading: () => <PropertySectionSkeleton /> });

// Mapping section types to API response keys
const SECTION_TYPE_TO_KEY_MAP = {
    // Property sections
    'nearby_properties_section': 'nearby_properties',
    'featured_properties_section': 'featured_properties',
    'most_viewed_properties_section': 'most_viewed_properties',
    'most_liked_properties_section': 'most_liked_properties',
    'premium_properties_section': 'premium_properties',
    // Project sections
    'projects_section': 'projects',
    'featured_projects_section': 'featured_projects',
    // Other sections
    'categories_section': 'categories',
    'agents_list_section': 'agents',
    'articles_section': 'articles',
    'user_recommendations_section': 'user_recommendations',
    'faqs_section': 'faqs',
    'slider_section': 'slider'
};

// Property section types for mapping
const PROPERTY_SECTION_TYPES = [
    'nearby_properties_section',
    'featured_properties_section',
    'most_viewed_properties_section',
    'most_liked_properties_section',
    'premium_properties_section'
];

// Project section types for mapping
const PROJECT_SECTION_TYPES = [
    'projects_section',
    'featured_projects_section'
];

// Other section types for mapping
const OTHER_SECTION_TYPES = [
    'categories_section',
    'agents_list_section',
    'articles_section',
    'user_recommendations_section',
    'faqs_section',
    'slider_section'
];


const HOMEPAGE_LOCATION_PROMPT_KEY = "homepageLocationPromptDismissed";

/** Read persisted location before Redux rehydration finishes (avoids false popup on refresh). */
const readPersistedLocationIsSet = () => {
    if (typeof window === "undefined") return false;
    try {
        const raw = localStorage.getItem("persist:root");
        if (!raw) return false;
        const root = JSON.parse(raw);
        const location = JSON.parse(root.location || "{}");
        return location.isLocationSet === true;
    } catch {
        return false;
    }
};

const Home = () => {
    const t = useTranslation();
    const router = useRouter();
    const dispatch = useDispatch()

    // --- Use the custom hook to get reactive login status ---
    const isUserLoggedIn = useAuthStatus();
    const userSelectedLocation = useSelector(state => state.location);
    const persistRehydrated = useSelector(state => state._persist?.rehydrated === true);
    const languageCode = useSelector(state => state.LanguageSettings?.current_language?.code);
    const [isLocationPopupOpen, setIsLocationPopupOpen] = useState(false);
    const settings = useSelector(state => state.WebSetting?.data);
    const isHomePageLocationAlertEnabled = settings?.homepage_location_alert_status === "1";
    const [isHomepageDataAvailable, setIsHomepageDataAvailable] = useState(false);
    const activeLanguage = useSelector(state => state.LanguageSettings?.current_language?.code);
    const locationAlertShownRef = useRef(false);
    const homepagePromptCheckedRef = useRef(false);

    const dismissLocationPrompt = useCallback(() => {
        if (typeof window !== "undefined") {
            localStorage.setItem(HOMEPAGE_LOCATION_PROMPT_KEY, "1");
        }
        setIsLocationPopupOpen(false);
    }, []);

    const hasUserLocationSet =
        userSelectedLocation?.isLocationSet === true ||
        readPersistedLocationIsSet();

    useEffect(() => {
        if (!hasUserLocationSet) return;
        if (typeof window !== "undefined") {
            localStorage.setItem(HOMEPAGE_LOCATION_PROMPT_KEY, "1");
        }
        setIsLocationPopupOpen(false);
    }, [hasUserLocationSet]);

    useEffect(() => {
        if (!router.isReady || router.pathname !== "/" || !isHomePageLocationAlertEnabled) return;
        if (!persistRehydrated) return;
        if (typeof window === "undefined") return;
        if (homepagePromptCheckedRef.current) return;
        homepagePromptCheckedRef.current = true;

        if (hasUserLocationSet) return;
        if (localStorage.getItem(HOMEPAGE_LOCATION_PROMPT_KEY)) return;

        setIsLocationPopupOpen(true);
    }, [
        router.isReady,
        router.pathname,
        isHomePageLocationAlertEnabled,
        hasUserLocationSet,
        persistRehydrated,
    ]);

    // Location params for API calls
    const locationParams = {
        latitude: userSelectedLocation?.latitude || "",
        longitude: userSelectedLocation?.longitude || "",
        radius: userSelectedLocation?.radius || ""
    };

    // 1. Fetch Homepage Sections (order/structure)
    const sectionsQuery = useQuery({
        queryKey: ['homepageSections', activeLanguage],
        queryFn: async () => {
            const response = await api.getHomepageSectionsApi({});
            return response?.data || [];
        },
        staleTime: 10 * 60 * 1000,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
        refetchOnMount: false,
    });

    // 2. Fetch Property Sections (nearby, featured, most_viewed, most_liked, premium)
    const propertySectionsQuery = useQuery({
        queryKey: ['homepagePropertySections', locationParams.latitude, locationParams.longitude, locationParams.radius, isUserLoggedIn, activeLanguage],
        queryFn: async () => {
            const response = await api.getHomepagePropertiesSectionApi(locationParams);
            // Check location data availability - only show alert if user has set a location
            const hasUserLocation = locationParams.latitude && locationParams.longitude;
            if (response?.data?.location_based_data === false && isHomePageLocationAlertEnabled && hasUserLocation) {
                handleLocationAlert();
            }
            setIsHomepageDataAvailable(response?.data?.location_based_data);
            dispatch(setIsLocationBasedHomepageData(response?.data?.location_based_data));
            return response?.data || {};
        },
        staleTime: 10 * 60 * 1000,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
        refetchOnMount: false,
    });


    // 3. Fetch Project Sections (projects, featured_projects)
    const projectSectionsQuery = useQuery({
        queryKey: ['homepageProjectSections', locationParams.latitude, locationParams.longitude, locationParams.radius, activeLanguage],
        queryFn: async () => {
            const response = await api.getHomepageProjectsSectionApi(locationParams);
            return response?.data || {};
        },
        staleTime: 10 * 60 * 1000,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
        refetchOnMount: false,
    });

    // 4. Fetch Other Sections (categories, agents, articles, user_recommendations, faqs, slider)
    const otherSectionsQuery = useQuery({
        queryKey: ['homepageOtherSections', locationParams.latitude, locationParams.longitude, locationParams.radius, isUserLoggedIn, activeLanguage],
        queryFn: async () => {
            const response = await api.getHomepageOtherSectionsApi(locationParams);
            return response?.data || {};
        },
        staleTime: 10 * 60 * 1000,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
        refetchOnMount: false,
    });

    // 5. Fetch Map Section Data
    const mapQuery = useQuery({
        queryKey: ['homePageMap', locationParams.latitude, locationParams.longitude, locationParams.radius, activeLanguage],
        queryFn: async () => {
            const response = await api.getHomepagePropertiesOnMapSectionApi({});
            return response?.data?.data ?? [];
        },
        staleTime: 10 * 60 * 1000,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
        refetchOnMount: false,
    });

    // 6. Fetch Cities Section Data
    const citiesQuery = useQuery({
        queryKey: ['homePageCities', activeLanguage],
        queryFn: async () => {
            const response = await api.getHomepagePropertyByCitiesSectionApi();
            return {
                data: response?.data?.data,
                with_image: response?.data?.with_image
            } ?? [];
        },
        staleTime: 10 * 60 * 1000,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
        refetchOnMount: false,
    });

    // 7. Fetch Ad Banners
    const addBannersQuery = useQuery({
        queryKey: ['homeAdBanners'],
        queryFn: async () => {
            const response = await api.getAdBannerApi({
                page: "homepage",
                platform: "web"
            });
            return response?.data || [];
        },
        staleTime: 10 * 60 * 1000,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
        refetchOnMount: false,
    });

    // Handle location alert (no data for selected location) — show at most once per visit
    const handleLocationAlert = () => {
        if (locationAlertShownRef.current) return;
        if (typeof window !== "undefined" && localStorage.getItem(HOMEPAGE_LOCATION_PROMPT_KEY)) return;
        locationAlertShownRef.current = true;

        Swal.fire({
            title: t("locationDataNotAvailable"),
            text: t("pleaseChangeLocationOrContinue"),
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: t("changeLocation"),
            cancelButtonText: t("continue"),
            customClass: {
                confirmButton: "Swal-confirm-buttons",
                cancelButton: "Swal-cancel-buttons",
            },
        }).then((result) => {
            if (result.isConfirmed) {
                setIsLocationPopupOpen(true);
            }
        });
    };


    // Get slider data from other sections
    const sliderData = otherSectionsQuery.data?.slider?.data || [];

    const exploreAllCategoriesLink = "/all/categories" + (isHomepageDataAvailable ? "?latitude=" + locationParams.latitude + "&longitude=" + locationParams.longitude + "&range=" + locationParams.radius + "&lang=" + activeLanguage : "?lang=" + activeLanguage);

    const getSectionInfo = (type) => {
        switch (type) {
            case "nearby_properties_section":
                return { Component: NearbyPropertiesSection, style: null, buttonText: "exploreMoreListings" };
            case "most_viewed_properties_section":
                return { Component: MostViewedSection, style: null, label: "checkOutMostViewed" };
            case "most_liked_properties_section":
                return { Component: MostLikedSection, style: null, label: "seeMostLiked" };
            case "properties_by_cities_section":
                return { Component: CitiesSection, style: "style_2", label: "exploreCities" };
            case "featured_projects_section":
                return { Component: FeaturedProjectsSection, style: "style_1", buttonLink: `/projects/featured-projects?filters=${encodeURIComponent(btoa(JSON.stringify({ flags: { promoted: 1 }, location: { latitude: locationParams.latitude, longitude: locationParams.longitude, range: locationParams.radius } })))}&lang=${activeLanguage}`, buttonText: "browseFeaturedProjects" };
            case "user_recommendations_section":
                return { Component: UserRecommendationsSection, style: "style_1", buttonText: "exploreMoreListings" };
            case "categories_section":
                return { Component: CategoriesSection, style: "style_1", buttonLink: exploreAllCategoriesLink, buttonText: "exploreCategories" };
            case "agents_list_section":
                return { Component: AgentsSection, style: null };
            case "articles_section":
                return { Component: ArticlesSection, style: "style_3", buttonLink: "/all/articles", buttonText: "readMoreInsights" };
            case "projects_section":
                return { Component: ProjectsSection, style: "style_4", label: "exploreProjects" };
            case "faqs_section":
                return { Component: Faqs, style: null };
            case "featured_properties_section":
                return { Component: FeaturedPropertiesSection, style: null, buttonText: "seeFeaturedProperties" };
            case "premium_properties_section":
                return { Component: PremiumPropertiesSection, style: null, buttonText: "seeAllPremiumProperties" };
            case "properties_on_map_section":
                return { Component: MapSection, style: null, label: "exploreOnMap" };
            default:
                console.warn("Unknown section type:", type);
                return { Component: null, style: null };
        }
    };

    // --- Helper to get the correct skeleton component based on type ---
    const getSkeletonForType = (type) => {
        switch (type) {
            case "nearby_properties_section":
                return NearbyPropertiesSkeleton;
            case "most_viewed_properties_section":
                return MostViewedSkeleton;
            case "most_liked_properties_section":
                return MostLikedSkeleton;
            case "featured_properties_section":
                return FeaturedPropertiesSkeleton;
            case "user_recommendations_section":
                return UserRecommendationSkeleton;
            case "properties_by_cities_section":
                return CitiesSkeleton;
            case "categories_section":
                return CircularItemsSkeleton;
            case "agents_list_section":
                return AgentSectionSkeleton;
            case "articles_section":
                return ArticleSectionSkeleton;
            case "projects_section":
                return ProjectsSkeleton;
            case "premium_properties_section":
                return PremiumPropertiesSkeleton;
            case "featured_projects_section":
                return FeaturedProjectsSkeleton;
            case "properties_on_map_section":
                return MapSkeleton;
            // FaqsSkeleton is handled separately at the end
            default:
                return PropertySectionSkeleton; // Fallback to a default skeleton
        }
    };


    const getSectionQueryState = (sectionType) => {
        const dataKey = SECTION_TYPE_TO_KEY_MAP[sectionType];

        if (PROPERTY_SECTION_TYPES.includes(sectionType)) {
            return {
                isLoading: propertySectionsQuery.isLoading,
                data: propertySectionsQuery.data?.[dataKey]?.data
            };
        }

        if (PROJECT_SECTION_TYPES.includes(sectionType)) {
            return {
                isLoading: projectSectionsQuery.isLoading,
                data: projectSectionsQuery.data?.[dataKey]?.data
            };
        }

        if (OTHER_SECTION_TYPES.includes(sectionType)) {
            return {
                isLoading: otherSectionsQuery.isLoading,
                data: otherSectionsQuery.data?.[dataKey]?.data
            };
        }

        if (sectionType === 'properties_on_map_section') {
            return {
                isLoading: mapQuery.isLoading,
                data: mapQuery.data
            };
        }

        if (sectionType === 'properties_by_cities_section') {
            return {
                isLoading: citiesQuery.isLoading,
                data: citiesQuery.data?.data
            };
        }

        return { isLoading: false, data: null };
    };



    const belowSliderAdBanner = addBannersQuery?.data?.find((banner) => banner.placement === "below_slider");
    const aboveFooterAdBanner = addBannersQuery?.data?.find((banner) => banner.placement === "above_footer");

    return (
        <section className="">
            {/* SEO */}
            <h1 className="sr-only hidden">
                {process.env.NEXT_PUBLIC_APPLICATION_NAME}
            </h1>

            {/* ===================== */}
            {/* MAIN SWIPER */}
            {/* ===================== */}
            {otherSectionsQuery?.isLoading ? (
                <MainSwiperSkeleton />
            ) : (
                <MainSwiper slides={sliderData} />
            )}

            {/* ===================== */}
            {/* BELOW SLIDER AD SLOT */}
            {/* ===================== */}
            {addBannersQuery.isLoading ? (
                <div className="container mx-auto my-4 px-2 lg:px-0">
                    <Skeleton className="w-full h-[350px] rounded-2xl" />
                </div>
            ) : belowSliderAdBanner ? (
                <div
                    className="container mx-auto my-4 px-2 lg:px-0 max-h-[350px]"
                    onClick={() => {
                        if (belowSliderAdBanner?.external_link_url) {
                            window.open(belowSliderAdBanner.external_link_url, "_blank");
                        } else if (belowSliderAdBanner?.property?.slug_id) {
                            router.push(
                                `/property-details/${belowSliderAdBanner.property.slug_id}/?lang=${languageCode}`
                            );
                        }
                    }}
                >
                    <ImageWithPlaceholder
                        src={belowSliderAdBanner.image}
                        alt="Ad Below Slider"
                        width={1920}
                        height={350}
                        className="w-full aspect-[1920/350] cursor-pointer rounded-lg lg:rounded-2xl object-cover"
                        loading="lazy"
                    />
                </div>
            ) : null}

            {/* ===================== */}
            {/* HOMEPAGE SECTIONS */}
            {/* ===================== */}
            <div className="flex flex-col">
                {sectionsQuery.data?.map((section, index) => {
                    const { Component, label, buttonLink, buttonText } =
                        getSectionInfo(section.type);

                    if (!Component) return null;

                    const SkeletonComponent = getSkeletonForType(section.type);
                    const { isLoading, data } = getSectionQueryState(section.type);

                    // 1️⃣ Section skeleton (only for THIS section)
                    if (isLoading) {
                        return <SkeletonComponent key={index} />;
                    }

                    // 2️⃣ No data → skip rendering
                    if (!data || (Array.isArray(data) && data.length === 0)) {
                        return null;
                    }

                    // 3️⃣ Special cases
                    if (section.type === "faqs_section") {
                        return (
                            <Component
                                key={index}
                                translated_title={section.translated_title}
                                faqs={data}
                            />
                        );
                    }

                    if (section.type === "properties_by_cities_section") {
                        return (
                            <Component
                                key={index}
                                translated_title={section.translated_title}
                                title={section.title}
                                data={data}
                                index={index}
                                name={section.type}
                                label={label}
                                type={section.type}
                                with_image={citiesQuery.data?.with_image}
                            />
                        );
                    }

                    // 4️⃣ Default section render
                    return (
                        <Component
                            key={index}
                            translated_title={section.translated_title}
                            title={section.title}
                            data={data}
                            index={index}
                            name={section.type}
                            label={label}
                            type={section.type}
                            buttonLink={buttonLink}
                            buttonText={buttonText}
                            passLocationFilter={isHomepageDataAvailable}
                        />
                    );
                })}

                {/* ===================== */}
                {/* ALL PROPERTIES */}
                {/* ===================== */}
                <AllPropertiesSection />
            </div>

            {/* ===================== */}
            {/* ABOVE FOOTER AD */}
            {/* ===================== */}
            {aboveFooterAdBanner && (
                <div
                    className="container mx-auto mb-4 px-2 lg:px-0 lg:mb-[60px]"
                    onClick={() => {
                        if (aboveFooterAdBanner?.external_link_url) {
                            window.open(aboveFooterAdBanner.external_link_url, "_blank");
                        } else if (aboveFooterAdBanner?.property?.slug_id) {
                            router.push(
                                `/property-details/${aboveFooterAdBanner.property.slug_id}/?lang=${languageCode}`
                            );
                        }
                    }}
                >
                    <ImageWithPlaceholder
                        src={aboveFooterAdBanner.image}
                        alt="Ad Above Footer"
                        width={1920}
                        height={350}
                        className="w-full aspect-[1920/350] cursor-pointer rounded-lg lg:rounded-2xl object-cover"
                        loading="lazy"
                    />
                </div>
            )}

            {/* ===================== */}
            {/* LOCATION POPUP */}
            {/* ===================== */}
            {isLocationPopupOpen && (
                <LocationSearchWithRadius
                    isOpen={isLocationPopupOpen}
                    onClose={dismissLocationPrompt}
                />
            )}
        </section>
    );

};

export default Home;