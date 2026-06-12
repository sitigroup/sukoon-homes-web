import { useCallback, useEffect, useState, useMemo } from "react";
import NewBreadcrumb from "../breadcrumb/NewBreadCrumb";
import FilterTopBar from "../reusable-components/FilterTopBar";
import PropertySideFilter from "../pagescomponents/PropertySideFilter";
import { getAdBannerApi, getPropertyListApi } from "@/api/apiRoutes";
import { useRouter } from "next/router";
import PropertyVerticalCard from "../cards/PropertyVerticalCard";
import PropertyHorizontalCard from "../cards/PropertyHorizontalCard";
import VerticlePropertyCardSkeleton from "../skeletons/VerticlePropertyCardSkeleton";
import PropertyHorizontalCardSkeleton from "../skeletons/PropertyHorizontalCardSkeleton";
import { useTranslation } from "../context/TranslationContext";
// Import Shadcn UI components
import { Button } from "@/components/ui/button";
import { Sheet, SheetContent } from "@/components/ui/sheet";
import NoDataFound from "../no-data-found/NoDataFound";
import { isRTL, decodeBase64FilterUrl, getPostedSince } from "@/utils/helperFunction";
import {
  buildPropertySearchUrl,
  mergePathAndDecodedFilters,
  parseLocationPathFromRouter,
  resolveLocationSearchFilters,
  slugToDisplayName,
} from "@/utils/locationSearchUrl";
import { useQuery } from "@tanstack/react-query";
import ImageWithPlaceholder from "../image-with-placeholder/ImageWithPlaceholder";

const buildSearchLocationPayload = (filterParams = {}) => ({
  country: filterParams.country || "",
  state: filterParams.state || "",
  city: filterParams.city || "",
  state_id: filterParams.state_id || "",
  city_id: filterParams.city_id || "",
  area_id: filterParams.area_id || "",
  area_name: filterParams.area_name || "",
  detected_area_name: filterParams.detected_area_name || filterParams.area_name || "",
  sub_area_id: filterParams.sub_area_id || "",
  sub_area_name: filterParams.sub_area_name || "",
  detected_sub_area_name: filterParams.detected_sub_area_name || filterParams.sub_area_name || "",
  place_id: "",
  latitude: filterParams.latitude || undefined,
  longitude: filterParams.longitude || undefined,
  range: filterParams.range || undefined,
});

const Search = () => {
  const router = useRouter();
  const t = useTranslation();
  const lang = router?.query?.lang;
  const isRtl = isRTL();

  // Set initial loading to true to show skeletons while router is hydrating.
  const [loading, setLoading] = useState(true);
  const [properties, setProperties] = useState([]);
  const [totalItems, setTotalItems] = useState(0);
  const [viewType, setViewType] = useState("grid");
  const [sortBy, setSortBy] = useState("newest");
  const [loadingMore, setLoadingMore] = useState(false);
  const [hasMore, setHasMore] = useState(false);
  const [offset, setOffset] = useState(0);
  const limit = 12;
  const [isFilterSheetOpen, setIsFilterSheetOpen] = useState(false);
  const [resolvedFilterParams, setResolvedFilterParams] = useState(null);

  const normalizeId = (value) =>
    value !== undefined && value !== null && value !== "" ? String(value) : "";

  // 1. DERIVE FILTERS FROM URL: The URL is the single source of truth.
  // useMemo prevents re-calculating on every render, only when router.query changes.
  const rawFilterParams = useMemo(() => {
    // router.isReady ensures we have the query parameters from the URL.
    if (!router.isReady) {
      return null;
    }
    const query = router.query;
    const pathLoc = parseLocationPathFromRouter(router);

    const fromDecoded = (decodedFilters) => ({
      property_type: decodedFilters.property_type === 0 ? "Sell" : decodedFilters.property_type === 1 ? "Rent" : "",
      category_id: decodedFilters.category_id || "",
      category_slug_id: decodedFilters.category_slug_id || "",
      city: decodedFilters.location?.city || "",
      state_id: normalizeId(decodedFilters.location?.state_id),
      city_id: normalizeId(decodedFilters.location?.city_id),
      area_id: normalizeId(decodedFilters.location?.area_id),
      area_name: decodedFilters.location?.area_name || "",
      detected_area_name: decodedFilters.location?.detected_area_name || "",
      sub_area_id: normalizeId(decodedFilters.location?.sub_area_id),
      sub_area_name: decodedFilters.location?.sub_area_name || "",
      detected_sub_area_name: decodedFilters.location?.detected_sub_area_name || "",
      state: decodedFilters.location?.state || "",
      country: decodedFilters.location?.country || "",
      min_price: decodedFilters.price?.min_price || "",
      max_price: decodedFilters.price?.max_price || "",
      posted_since: decodedFilters.posted_since || "",
      promoted: decodedFilters.flags?.promoted === 1,
      keywords: decodedFilters.search || "",
      amenities: decodedFilters.parameters || [],
      is_premium: decodedFilters.flags?.get_all_premium_properties === 1,
      latitude: decodedFilters.location?.latitude || undefined,
      longitude: decodedFilters.location?.longitude || undefined,
      range: decodedFilters.location?.range || undefined,
      nearbyPlaces: decodedFilters.nearby_places || [],
    });

    const fromPathOnly = () => ({
      property_type: "",
      category_id: "",
      category_slug_id: "",
      city: slugToDisplayName(pathLoc.citySlug),
      state: "",
      country: "",
      state_id: "",
      city_id: "",
      area_id: "",
      area_name: pathLoc.areaSlug ? slugToDisplayName(pathLoc.areaSlug) : "",
      detected_area_name: pathLoc.areaSlug ? slugToDisplayName(pathLoc.areaSlug) : "",
      sub_area_id: "",
      sub_area_name: pathLoc.subAreaSlug ? slugToDisplayName(pathLoc.subAreaSlug) : "",
      detected_sub_area_name: pathLoc.subAreaSlug ? slugToDisplayName(pathLoc.subAreaSlug) : "",
      min_price: "",
      max_price: "",
      posted_since: "",
      promoted: false,
      keywords: "",
      amenities: [],
      is_premium: false,
      latitude: undefined,
      longitude: undefined,
      range: undefined,
      nearbyPlaces: [],
    });

    // Check if we have a base64 encoded filters parameter (new format)
    if (query.filters) {
      try {
        const decodedFilters = decodeBase64FilterUrl(decodeURIComponent(query.filters));
        const decoded = fromDecoded(decodedFilters);
        if (pathLoc?.citySlug) {
          return mergePathAndDecodedFilters(decoded, fromPathOnly());
        }
        return decoded;
      } catch (error) {
        console.error("Error decoding filters from URL:", error);
        // Fall through to legacy format handling
      }
    }

    if (pathLoc?.citySlug) {
      return fromPathOnly();
    }

    // Legacy format: Handle individual query parameters for backward compatibility
    // Helper function to process amenities properly
    const processAmenities = (amenitiesParam) => {
      if (!amenitiesParam) return [];

      // If it's already an array, process each item
      if (Array.isArray(amenitiesParam)) {
        return amenitiesParam.map(id => typeof id === "string" ? parseInt(id) : id).filter(id => !isNaN(id));
      }

      // If it's a string, split and convert to integers
      if (typeof amenitiesParam === "string") {
        return amenitiesParam.split(",").map(id => parseInt(id.trim())).filter(id => !isNaN(id));
      }

      return [];
    };

    return {
      property_type: query.property_type?.charAt(0).toUpperCase() + query.property_type?.slice(1) || "",
      category_id: query.category_id || "",
      category_slug_id: query.category_slug_id || "",
      city: query.city || "",
      state_id: normalizeId(query.state_id),
      city_id: normalizeId(query.city_id),
      area_id: normalizeId(query.area_id),
      area_name: query.area_name || "",
      detected_area_name: query.detected_area_name || "",
      sub_area_id: normalizeId(query.sub_area_id),
      sub_area_name: query.sub_area_name || "",
      detected_sub_area_name: query.detected_sub_area_name || "",
      state: query.state || "",
      country: query.country || "",
      min_price: query.min_price || "",
      max_price: query.max_price || "",
      posted_since: query.posted_since || "",
      promoted: query.promoted === "true" || query.promoted === "1",
      keywords: query.keywords || "",
      amenities: processAmenities(query.amenities),
      is_premium: query.is_premium === "true" || query.is_premium === "1",
      latitude: undefined,
      longitude: undefined,
      range: undefined,
      nearbyPlaces: []
    };
  }, [router.isReady, router.query, router.asPath]);

  useEffect(() => {
    if (!rawFilterParams) {
      setResolvedFilterParams(null);
      return undefined;
    }

    let cancelled = false;
    const pathLoc = parseLocationPathFromRouter(router);

    resolveLocationSearchFilters({
      citySlug: pathLoc?.citySlug,
      areaSlug: pathLoc?.areaSlug,
      subAreaSlug: pathLoc?.subAreaSlug,
      partial: rawFilterParams,
    })
      .then((locationFields) => {
        if (!cancelled) {
          setResolvedFilterParams(mergePathAndDecodedFilters(rawFilterParams, locationFields));
        }
      })
      .catch(() => {
        if (!cancelled) {
          setResolvedFilterParams(rawFilterParams);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [rawFilterParams, router.asPath]);

  const filterParams = resolvedFilterParams ?? rawFilterParams;

  // 2. FETCH DATA ON FILTER CHANGE: This effect runs for initial load and when filters change.
  useEffect(() => {
    // Do not fetch until the router is ready and filterParams are available.
    if (!filterParams) {
      return;
    }

    const pathLoc = parseLocationPathFromRouter(router);
    if (pathLoc?.citySlug && resolvedFilterParams === null) {
      return;
    }

    setLoading(true);
    setProperties([]); // Clear old results immediately for better UX

    // Build parameters array from amenities
    const parameters = filterParams?.amenities?.map((amenity) => ({
      id: parseInt(amenity.id || amenity),
      value: amenity.value || amenity.name || "" // Use name if available, otherwise empty
    })) || [];

    // Build nearby_places array from nearbyPlaces
    const nearby_places = filterParams?.nearbyPlaces?.map((place) => ({
      id: parseInt(place.id),
      value: parseInt(place.distance || place.value)
    })) || [];

    // Build location object
    const location = buildSearchLocationPayload(filterParams);

    // Build price object
    const price = {
      min_price: filterParams.min_price ? parseInt(filterParams.min_price) : 0,
      max_price: filterParams.max_price ? parseInt(filterParams.max_price) : 0
    };

    // Build flags object
    const flags = {
      promoted: filterParams.promoted ? 1 : 0,
      get_all_premium_properties: filterParams.is_premium ? 1 : 0,
      most_views: sortBy === "most_viewed" ? 1 : 0,
      most_liked: sortBy === "most_liked" ? 1 : 0
    };

    const apiParams = {
      property_type: filterParams.property_type === "Sell" ? 0 : filterParams.property_type === "Rent" ? 1 : "",
      category_id: filterParams.category_id ? parseInt(filterParams.category_id) : "",
      category_slug_id: filterParams.category_slug_id || "",
      parameters: parameters,
      nearby_places: nearby_places,
      location: location,
      price: price,
      posted_since: parseInt(getPostedSince(filterParams.posted_since)) || 0,
      search: filterParams.keywords || "",
      flags: flags,
      limit: limit.toString(),
      offset: "0", // Always start from the beginning for a new filter search
    };

    getPropertyListApi(apiParams)
      .then((res) => {
        if (!res?.error) {
          setProperties(res.data);
          setTotalItems(res.total);
          const newOffset = res.data.length;
          setOffset(newOffset);
          setHasMore(res.total > newOffset);
        } else {
          setProperties([]);
          setTotalItems(0);
          setOffset(0);
          setHasMore(false);
        }
      })
      .catch((error) => {
        console.error("Error fetching search data:", error);
        setProperties([]);
        setTotalItems(0);
        setOffset(0);
        setHasMore(false);
      })
      .finally(() => {
        setLoading(false);
      });
    // Use JSON.stringify to ensure the effect re-runs only when filter values change.
  }, [JSON.stringify(filterParams), sortBy, resolvedFilterParams]);

  // 3. LOAD MORE FUNCTIONALITY
  const loadMore = useCallback(() => {
    if (loadingMore || !hasMore || !filterParams) return;

    setLoadingMore(true);

    // Build parameters array from amenities
    const parameters = filterParams?.amenities?.map((amenity) => ({
      id: parseInt(amenity.id || amenity),
      value: amenity.value || amenity.name || "" // Use name if available, otherwise empty
    })) || [];

    // Build nearby_places array from nearbyPlaces
    const nearby_places = filterParams?.nearbyPlaces?.map((place) => ({
      id: parseInt(place.id),
      value: parseInt(place.distance || place.value)
    })) || [];

    const location = buildSearchLocationPayload(filterParams);

    // Build price object
    const price = {
      min_price: filterParams.min_price ? parseInt(filterParams.min_price) : 0,
      max_price: filterParams.max_price ? parseInt(filterParams.max_price) : 0
    };

    // Build flags object
    const flags = {
      promoted: filterParams.promoted ? 1 : 0,
      get_all_premium_properties: filterParams.is_premium ? 1 : 0,
      most_views: sortBy === "most_viewed" ? 1 : 0,
      most_liked: sortBy === "most_liked" ? 1 : 0
    };

    const apiParams = {
      property_type: filterParams.property_type === "Sell" ? 0 : filterParams.property_type === "Rent" ? 1 : "",
      category_id: filterParams.category_id ? parseInt(filterParams.category_id) : "",
      category_slug_id: filterParams.category_slug_id || "",
      parameters: parameters,
      nearby_places: nearby_places,
      location: location,
      price: price,
      posted_since: parseInt(getPostedSince(filterParams.posted_since)) || 0,
      search: filterParams.keywords || "",
      flags: flags,
      limit: limit.toString(),
      offset: offset.toString(), // Use the current offset for pagination
    };

    getPropertyListApi(apiParams)
      .then(res => {
        if (!res?.error) {
          setProperties(prev => [...prev, ...res.data]);
          const newOffset = offset + res.data.length;
          setOffset(newOffset);
          setHasMore(res.total > newOffset);
        }
      })
      .catch(error => console.error("Error loading more data:", error))
      .finally(() => setLoadingMore(false));

  }, [loadingMore, hasMore, filterParams, offset, limit, sortBy]);

  // 4. FILTER HANDLERS: These functions now only update the URL.
  const handleFilterApply = useCallback((newFilters) => {
    // Close filter sheet if open
    if (isFilterSheetOpen) {
      setIsFilterSheetOpen(false);
    }

    try {
      router?.push(buildPropertySearchUrl(newFilters, { lang, sortBy }));
    } catch (error) {
      console.error("Error applying filters:", error);
    }
  }, [router, lang, isFilterSheetOpen, sortBy]);

  const handleClearFilter = useCallback(() => {
    // Close filter sheet if open
    if (isFilterSheetOpen) {
      setIsFilterSheetOpen(false);
    }

    try {
      router.push(`/search?lang=${lang}`, undefined, { shallow: true });
    } catch (error) {
      console.error("Error clearing filters:", error);
    }
  }, [router, lang, isFilterSheetOpen]);

  const fetchSearchAdBanners = async () => {
    try {
      const response = await getAdBannerApi({
        page: "property_listing",
        platform: "web"
      })
      return response?.data || []
    } catch (error) {
      console.error("Error fetching search ad banners:", error);
      return []
    }
  }

  const searchAdBannersQuery = useQuery({
    queryKey: ['searchAdBanners'],
    queryFn: fetchSearchAdBanners,
    staleTime: 0,
  })

  const belowBreadcrumbAdBanner = searchAdBannersQuery?.data?.find(banner => banner?.placement === 'below_breadcrumb');
  const aboveFooterAdBanner = searchAdBannersQuery?.data?.find(banner => banner?.placement === 'above_footer');
  const belowSidebarFilterAdBanner = searchAdBannersQuery?.data?.find(banner => banner?.placement === 'sidebar_below_filters');

  return (
    <div>
      <NewBreadcrumb
        items={[{ label: t("search"), href: "/search" }]}
        title={t("propertySearchListing")}
        subtitle={`${t("thereAreCurrently")} ${totalItems} ${t("properties")}.`}
      />
      <div className="container mx-auto px-4 py-12 md:px-6">
        {belowBreadcrumbAdBanner && (
          <div className="mb-12"
            onClick={() => {
              if (belowBreadcrumbAdBanner?.external_link_url) {
                window.open(belowBreadcrumbAdBanner?.external_link_url, '_blank');
              } else if (belowBreadcrumbAdBanner?.property?.slug_id) {
                router.push(`/property-details/${belowBreadcrumbAdBanner?.property?.slug_id}/?lang=${lang}`);
              }
            }}
          >
            <ImageWithPlaceholder
              src={belowBreadcrumbAdBanner?.image}
              alt="Ad Below Breadcrumb"
              width={1920}
              height={350}
              className={`w-full h-full aspect-[1920/350] object-cover rounded-2xl ${belowBreadcrumbAdBanner?.external_link_url || belowBreadcrumbAdBanner?.property?.slug_id ? 'cursor-pointer' : ''}`}

            />
          </div>
        )}
        <FilterTopBar
          itemCount={properties.length} // Use properties.length directly
          totalItems={totalItems}
          viewType={viewType}
          sortBy={sortBy}
          setViewType={setViewType}
          setSortBy={setSortBy}
          onOpenFilters={() => setIsFilterSheetOpen(true)}
          showSortBy={false}
          showFilterButton={true}
        />
        <div className="mt-4 grid grid-cols-12 gap-4">
          {/* Mobile Filter Sheet */}
          <Sheet open={isFilterSheetOpen} onOpenChange={setIsFilterSheetOpen}>
            <SheetContent
              side={isRtl ? "left " : "right"}
              className="flex h-full w-full flex-col place-content-center !p-0 [&>button]:hidden"
            >
              <div className="overflow-y-auto no-scrollbar h-full p-2 ">
                <PropertySideFilter
                  showBorder={false}
                  onFilterApply={handleFilterApply}
                  handleClearFilter={handleClearFilter}
                  currentFilters={filterParams || {}} // Pass derived filters
                  hideFilter={false}
                  hideFilterType={"search"}
                  isMobileSheet={isFilterSheetOpen}
                  setIsFilterSheetOpen={setIsFilterSheetOpen}
                />
              </div>
            </SheetContent>
          </Sheet>

          {/* Side Filter - Hidden on mobile */}
          <div className="hidden xl:block sticky col-span-12 xl:top-[15vh] xl:col-span-3 xl:self-start">
            <PropertySideFilter
              onFilterApply={handleFilterApply}
              handleClearFilter={handleClearFilter}
              currentFilters={filterParams || {}} // Pass derived filters
              hideFilter={false}
              hideFilterType={"search"}
            />
            {belowSidebarFilterAdBanner && (
              <div className="mt-6"
                onClick={() => {
                  if (belowSidebarFilterAdBanner?.external_link_url) {
                    window.open(belowSidebarFilterAdBanner?.external_link_url, '_blank');
                  } else if (belowSidebarFilterAdBanner?.property?.slug_id) {
                    router.push(`/property-details/${belowSidebarFilterAdBanner?.property?.slug_id}/?lang=${lang}`);
                  }
                }}
              >
                <ImageWithPlaceholder
                  src={belowSidebarFilterAdBanner?.image}
                  alt="Ad Below Sidebar Filter"
                  width={387}
                  height={587}
                  className={`w-full h-full aspect-[387/587] object-cover rounded-2xl ${belowSidebarFilterAdBanner?.external_link_url || belowSidebarFilterAdBanner?.property?.slug_id ? 'cursor-pointer' : ''}`}
                />
              </div>
            )}
          </div>
          <div className="col-span-12 xl:col-span-9">
            {/* Initial loading skeletons */}
            {loading && viewType === "grid" && (
              <div className="grid grid-cols-1 gap-4 place-items-center md:grid-cols-2 lg:grid-cols-3">
                {[...Array(12)].map((_, index) => (
                  <VerticlePropertyCardSkeleton key={index} />
                ))}
              </div>
            )}
            {loading && viewType !== "grid" && (
              <div className="flex flex-col gap-4">
                {[...Array(6)].map((_, index) => (
                  <PropertyHorizontalCardSkeleton key={index} />
                ))}
              </div>
            )}

            {/* Property list */}
            {!loading && (
              viewType === "grid" ? (
                <div className="grid grid-cols-1 w-full sm:grid-cols-2 place-items-center gap-4 md:grid-cols-2 lg:grid-cols-3">
                  {properties.map((property) => (
                    <PropertyVerticalCard key={property.id} property={property} />
                  ))}
                </div>
              ) : (
                <div className="flex flex-col gap-4">
                  {properties.map((property) => (
                    <PropertyHorizontalCard key={property.id} property={property} />
                  ))}
                </div>
              )
            )}

            {!loading && properties.length === 0 && (
              <NoDataFound />
            )}

            {/* Skeletons for "Load More" */}
            {loadingMore && viewType === "grid" && (
              <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                {[...Array(3)].map((_, index) => (
                  <VerticlePropertyCardSkeleton key={`load-more-${index}`} />
                ))}
              </div>
            )}
            {loadingMore && viewType !== "grid" && (
              <div className="mt-4 flex flex-col gap-4">
                {[...Array(2)].map((_, index) => (
                  <PropertyHorizontalCardSkeleton key={`load-more-${index}`} />
                ))}
              </div>
            )}

            {/* Load More Button */}
            {hasMore && !loading && !loadingMore && (
              <div className="mt-8 flex justify-center">
                <Button
                  onClick={loadMore}
                  className="border font-medium text-base brandBorder bg-transparent brandColor hover:primaryBg hover:text-white hover:border-none"
                >
                  {t("loadMore")} {t("listing")}
                </Button>
              </div>
            )}
          </div>
        </div>
        {aboveFooterAdBanner && (
          <div className="mt-12"
            onClick={() => {
              if (aboveFooterAdBanner?.external_link_url) {
                window.open(aboveFooterAdBanner?.external_link_url, '_blank');
              } else if (aboveFooterAdBanner?.property?.slug_id) {
                router.push(`/property-details/${aboveFooterAdBanner?.property?.slug_id}/?lang=${lang}`);
              }
            }}
          >
            <ImageWithPlaceholder
              src={aboveFooterAdBanner?.image}
              alt="Ad Above Footer"
              width={1920}
              height={350}
              className={`w-full h-full aspect-[1920/350] object-cover rounded-2xl ${aboveFooterAdBanner?.external_link_url || aboveFooterAdBanner?.property?.slug_id ? 'cursor-pointer' : ''}`}
            />
          </div>
        )}
      </div>
    </div>
  );
};

export default Search;