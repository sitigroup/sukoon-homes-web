import { useState, useEffect, useRef, useCallback } from 'react';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Button } from '@/components/ui/button';
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import {
    Drawer,
    DrawerContent,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
} from "@/components/ui/drawer";
import { useTranslation } from '../context/TranslationContext';
import { getAdvancedFilterDataApi, getCategoriesApi } from '@/api/apiRoutes';
import { IoClose, IoFilterSharp } from 'react-icons/io5';
import searchIcon from '@/assets/searchIcon.svg';
import Image from 'next/image';
import CustomLocationAutocomplete from '../location-search/CustomLocationAutocomplete';
import { extractAddressComponents } from '@/utils/helperFunction';
import { useSelector } from 'react-redux';
import toast from 'react-hot-toast';
import AreaSubAreaSelector from '@/plugins/area-listing/AreaSubAreaSelector';
import { extractAreaListingFromPlace } from '@/plugins/area-listing/areaListingUtils';

const SearchBox = ({
    // Props for external state management - using flat structure (new)
    propertyType = 'All',
    selectedCategory = '',
    keywords = '',
    city = '',
    state = '',
    country = '',
    minPrice = '',
    maxPrice = '',
    postedSince = 'anytime',
    amenities = [],
    nearbyPlaces = [],
    areaLocation = {},
    showAdvancedFilters = false,

    // Handler functions for flat structure (new)
    onPropertyTypeChange,
    onCategoryChange,
    onKeywordsChange,
    onCityChange,
    onStateChange,
    onCountryChange,
    onMinPriceChange,
    onMaxPriceChange,
    onPostedSinceChange,
    onAmenitiesChange,
    onNearbyPlacesChange,
    onAreaLocationChange,
    onShowAdvancedFiltersChange,
    onApplyFilters,
    onClearFilters,

    // Legacy props for backward compatibility (old nested structure)
    locationInput = '',
    locationData = { formatted_address: '', city: '', state: '', country: '' },
    filters = { min_price: '', max_price: '', posted_since: 'anytime', amenities: [], nearbyPlaces: [] },
    onLocationInputChange,
    onLocationDataChange,
    onFiltersChange,

    // Optional props for customization
    showSearchButton = true,
    showFiltersButton = true,
    className = ''
}) => {
    const t = useTranslation();
    const limit = 10;

    // --- State for categories and facilities ---
    const [categories, setCategories] = useState([]);
    const [facilities, setFacilities] = useState([]);
    const [nearByPlaces, setNearByPlaces] = useState([]);
    const [offset, setOffset] = useState(0);
    const [hasMoreCategories, setHasMoreCategories] = useState(true);
    const [isLoadingMore, setIsLoadingMore] = useState(false);
    const [isMobileView, setIsMobileView] = useState(false);

    // Track data loading state
    const [isCategoriesLoaded, setIsCategoriesLoaded] = useState(false);
    const [isFacilitiesLoaded, setIsFacilitiesLoaded] = useState(false);
    const dataFetchedRef = useRef(false);

    // Google Maps integration - removed, using custom autocomplete
    // const [mapsLoaded, setMapsLoaded] = useState(false);
    // const [mapsLoadError, setMapsLoadError] = useState(null);
    // const [loadingMaps, setLoadingMaps] = useState(true);
    // const autocompleteRef = useRef(null);

    const language = useSelector((state) => state.LanguageSettings?.active_language);

    // Get web settings for distance symbol
    const webSettings = useSelector((state) => state.WebSetting?.data);
    const distanceSymbol = webSettings?.distance_option;
    const distanceSymbolMap = {
        km: t('enterDistanceInKm'),
        m: t('enterDistanceInMeters'),
        mi: t('enterDistanceInMiles'),
        yd: t('enterDistanceInYards')
    };
    const distancePlaceholder = distanceSymbolMap[distanceSymbol] || t('enterDistanceInkm');

    // Resolve values with backward compatibility - new flat structure takes precedence
    const resolvedCity = city || locationData?.city || '';
    const resolvedState = state || locationData?.state || '';
    const resolvedCountry = country || locationData?.country || '';
    const resolvedMinPrice = minPrice || filters?.min_price || '';
    const resolvedMaxPrice = maxPrice || filters?.max_price || '';
    const resolvedPostedSince = postedSince || filters?.posted_since || 'anytime';
    const resolvedAmenities = amenities?.length > 0 ? amenities : (filters?.amenities || []);
    const resolvedNearbyPlaces = nearbyPlaces?.length > 0 ? nearbyPlaces : (filters?.nearbyPlaces || []);
    const resolvedAreaLocation = areaLocation || {};
    const resolvedLocationInput = locationInput || '';

    // Resolve handlers with backward compatibility
    const resolvedOnCityChange = onCityChange || ((value) => onLocationDataChange?.({ ...locationData, city: value }));
    const resolvedOnStateChange = onStateChange || ((value) => onLocationDataChange?.({ ...locationData, state: value }));
    const resolvedOnCountryChange = onCountryChange || ((value) => onLocationDataChange?.({ ...locationData, country: value }));
    const resolvedOnMinPriceChange = onMinPriceChange || ((value) => onFiltersChange?.({ ...filters, min_price: value }));
    const resolvedOnMaxPriceChange = onMaxPriceChange || ((value) => onFiltersChange?.({ ...filters, max_price: value }));
    const resolvedOnPostedSinceChange = onPostedSinceChange || ((value) => onFiltersChange?.({ ...filters, posted_since: value }));
    const resolvedOnAmenitiesChange = onAmenitiesChange || ((value) => onFiltersChange?.({ ...filters, amenities: value }));
    const resolvedOnNearbyPlacesChange = onNearbyPlacesChange || ((value) => onFiltersChange?.({ ...filters, nearbyPlaces: value }));
    const resolvedOnAreaLocationChange = onAreaLocationChange || (() => { });
    const resolvedOnLocationInputChange = onLocationInputChange || (() => { });


    // Fetch categories function with pagination logic
    const fetchCategories = useCallback(async (currentOffset) => {
        // Skip if already loaded and not loading more
        if (isCategoriesLoaded && currentOffset === 0) return;

        if (currentOffset > 0) setIsLoadingMore(true);

        try {
            const response = await getCategoriesApi({
                limit: limit,
                offset: currentOffset
            });

            const newCategories = response?.data || [];
            const totalFromServer = response?.total;

            // Update categories state and calculate next state length simultaneously
            let nextCategoriesLength = 0;
            if (currentOffset > 0) {
                setCategories(prev => {
                    const updated = [...prev, ...newCategories];
                    nextCategoriesLength = updated.length;
                    return updated;
                });
            } else {
                setCategories(newCategories);
                nextCategoriesLength = newCategories.length;
                setIsCategoriesLoaded(true);
            }

            // Check if there are likely more categories based on total count
            if (typeof totalFromServer === 'number') {
                setHasMoreCategories(nextCategoriesLength < totalFromServer);
            } else {
                setHasMoreCategories(newCategories.length === limit);
            }

        } catch (error) {
            console.error("Error fetching categories:", error);
            setHasMoreCategories(false);
        } finally {
            if (currentOffset > 0) setIsLoadingMore(false);
        }
    }, [isCategoriesLoaded, limit]);

    const fetchAdvancedData = useCallback(async () => {
        // Skip if already loaded
        if (isFacilitiesLoaded) return;

        try {
            const response = await getAdvancedFilterDataApi();
            if (response?.data && !response?.error) {
                const facilities = response?.data?.parameters || [];
                const nearbyPlaces = response?.data?.nearby_facilities || [];

                setFacilities(facilities);
                setNearByPlaces(nearbyPlaces);
                setIsFacilitiesLoaded(true);
            }
        } catch (error) {
            console.error("Error fetching advanced filter data:", error);
        }
    }, [isFacilitiesLoaded]);

    // Initial fetch on component mount
    useEffect(() => {
        // Prevent duplicate API calls
        if (dataFetchedRef.current) return;
        dataFetchedRef.current = true;

        setOffset(0);
        fetchCategories(0);
        fetchAdvancedData();
    }, [fetchCategories, fetchAdvancedData, language]);


    useEffect(() => {
        const mediaQuery = window.matchMedia('(max-width: 767px)');
        const handleMediaChange = (event) => setIsMobileView(event.matches);

        setIsMobileView(mediaQuery.matches);
        mediaQuery.addEventListener('change', handleMediaChange);

        return () => mediaQuery.removeEventListener('change', handleMediaChange);
    }, []);

    // Handler for the 'Load More' button
    const handleLoadMoreCategories = async (event) => {
        // Prevent the select dropdown from closing when clicking the button
        event.stopPropagation();
        event.preventDefault();

        if (isLoadingMore || !hasMoreCategories) return;

        const nextOffset = offset + limit;
        setOffset(nextOffset);
        await fetchCategories(nextOffset);
    };

    // Handle place selection from custom autocomplete
    const handlePlaceSelect = (placeData, placeDetails) => {
        if (placeData) {
            const sourcePlace = placeDetails?.address_components ? placeDetails : placeData;
            const address = extractAddressComponents(sourcePlace);
            const areaData = extractAreaListingFromPlace(sourcePlace);
            const nextAreaLocation = {
                ...(resolvedAreaLocation || {}),
                city: address.city,
                state: address.state,
                country: address.country,
                city_id: "",
                state_id: "",
                area_id: "",
                area_name: "",
                sub_area_id: "",
                sub_area_name: "",
                ...areaData,
            };
            resolvedOnCityChange(address.city);
            resolvedOnStateChange(address.state);
            resolvedOnCountryChange(address.country);
            resolvedOnAreaLocationChange(nextAreaLocation);
            resolvedOnLocationInputChange(address.formattedAddress || placeData.formatted_address || address.city || "");
        }
    };

    // --- Options --- 
    const propertyTypeOptions = ['All', 'Sell', 'Rent'];
    const postedSinceOptions = ['anytime', 'yesterday', 'lastWeek', 'lastMonth', 'last3Months', 'last6Months'];

    // --- Handlers --- 
    const handleAmenityChange = (amenityId) => {
        const facility = facilities.find(f => f.id === amenityId);

        if (!facility) return; // safeguard if not found

        const facilityObj = {
            id: amenityId,
            value: facility.translated_name || facility.name,
        };

        const currentAmenities = resolvedAmenities || [];
        const exists = currentAmenities.some(item => {
            // Handle both object format {id, value} and simple ID format for backward compatibility
            return typeof item === 'object' ? item.id === amenityId : item === amenityId;
        });

        let newAmenities;
        if (exists) {
            // Remove if already exists
            newAmenities = currentAmenities.filter(item => {
                return typeof item === 'object' ? item.id !== amenityId : item !== amenityId;
            });
        } else {
            // Always add as objects with {id, value}
            newAmenities = [...currentAmenities, facilityObj];
        }

        resolvedOnAmenitiesChange(newAmenities);
    };

    const handleNearbyPlaceChange = (e) => {
        const { name, value, dataset } = e.target;
        const placeId = Number(name);
        const placeName = dataset.placename || "";

        const currentNearbyPlaces = resolvedNearbyPlaces || [];
        const withoutCurrent = currentNearbyPlaces.filter((p) => p.id !== placeId);

        if (!value) {
            resolvedOnNearbyPlacesChange(withoutCurrent);
            return;
        }

        const newNearbyPlaces = [...withoutCurrent, { id: placeId, distance: parseInt(value), value: placeName }];
        resolvedOnNearbyPlacesChange(newNearbyPlaces);
    };

    const handleClearFiltersInternal = () => {
        // Call external clear handler
        onClearFilters?.();
    };

    const handleApplyFiltersInternal = () => {
        // Call external apply handler
        if (Number(resolvedMinPrice) > Number(resolvedMaxPrice)) {
            toast.error(t("minPriceGreaterThanMaxPrice"));
            return;
        }
        onApplyFilters?.();
    };

    const renderAdvancedFiltersContent = ({ showActions = true } = {}) => (
        <>
            {/* Property Budget & Posted Since Row */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div className="lg:col-span-1">
                    <Label className="block text-sm font-medium text-gray-700 mb-1">{t('propertyBudget') || 'Property Budget'}</Label>
                    <div className="grid grid-cols-2 gap-2">
                        <Input
                            type="number"
                            placeholder={t('minPrice') || 'Min Price'}
                            value={resolvedMinPrice}
                            onChange={(e) => resolvedOnMinPriceChange(e.target.value)}
                            className="w-full text-sm md:text-base bg-gray-100 newBorder h-11 rounded-md focus:ring-0 focus:border-none focus-visible:ring-0"
                        />
                        <Input
                            type="number"
                            placeholder={t('maxPrice') || 'Max Price'}
                            value={resolvedMaxPrice}
                            onChange={(e) => resolvedOnMaxPriceChange(e.target.value)}
                            className=" w-full text-sm md:text-base bg-gray-100 newBorder h-11 rounded-md focus:ring-0 focus:border-none focus-visible:ring-0"
                        />
                    </div>
                </div>
                <div className="lg:col-span-1">
                    <Label htmlFor="postedSince" className="block text-sm font-medium text-gray-700 mb-1">{t('postedSince') || 'Posted Since'}</Label>
                    <Select
                        value={resolvedPostedSince}
                        onValueChange={resolvedOnPostedSinceChange}
                    >
                        <SelectTrigger
                            id="postedSince"
                            className="w-full text-sm md:text-base bg-gray-100 border-gray-200 rounded-md h-11 focus:ring-0 focus:border-none focus-visible:ring-0"
                        >
                            <SelectValue placeholder={t('anytime') || 'Anytime'} />
                        </SelectTrigger>
                        <SelectContent>
                            {postedSinceOptions.map(option => (
                                <SelectItem key={option} value={option}>{t(option) || option}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>


            {/* Amenities Section */}
            {facilities?.length > 0 &&
                <div className="mb-6">
                    <Label className="block text-sm font-medium text-gray-700 mb-2">{t('amenities')}</Label>
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-x-4 gap-y-3">
                        {facilities.map(amenity => {
                            // Check if amenity is selected (handle both object and simple ID formats)
                            const isChecked = resolvedAmenities?.some(item => {
                                return typeof item === 'object' ? item.id === amenity.id : item === amenity.id;
                            });


                            return (
                                <div key={amenity.id} className="flex items-center gap-2">
                                    <Checkbox
                                        id={amenity.id}
                                        checked={isChecked}
                                        onCheckedChange={() => handleAmenityChange(amenity.id)}
                                        className="w-6 h-6 data-[state=checked]:primaryBg"
                                    />
                                    <Label htmlFor={amenity.id} className="text-sm font-normal text-gray-600 cursor-pointer">{amenity.translated_name || amenity.name}</Label>
                                </div>
                            );
                        })}
                    </div>
                </div>}

            {/* Nearby Places Section */}
            {nearByPlaces?.length > 0 && (
                <div className="mb-6">
                    <Label className="block text-sm font-medium text-gray-700 mb-2">{t('nearbyPlaces')}</Label>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                        {nearByPlaces.map((place) => (
                            <div key={place.id} className="flex flex-col gap-1">
                                <Label className="text-xs text-gray-700 sm:text-sm">
                                    {place.name}
                                </Label>
                                <Input
                                    type="number"
                                    name={place?.id}
                                    id={place?.id}
                                    data-placename={place.name}
                                    className="w-full text-sm bg-gray-100 border-gray-200 rounded-md h-9 focus:ring-0 focus:border-none focus-visible:ring-0"
                                    placeholder={distancePlaceholder}
                                    onChange={handleNearbyPlaceChange}
                                    value={resolvedNearbyPlaces?.find(p => p.id === place.id)?.distance || ''}
                                />
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {showActions && renderFilterActions()}
        </>
    );

    const renderFilterActions = () => (
        <div className="flex justify-end gap-3">
            <Button variant="ghost" onClick={handleClearFiltersInternal} className="text-gray-700 hover:bg-gray-100">
                {t('clear')}
            </Button>
            <Button onClick={handleApplyFiltersInternal} className="bg-gray-900 text-white hover:bg-gray-700 px-5">
                {t('applyFilter')}
            </Button>
        </div>
    );

    const fieldCell = 'min-w-0 w-full';

    const renderBasicFilterFields = () => (
        <>
            {/* Property Type Select (Sell/Rent/All) */}
            <div className={`${fieldCell} md:col-span-1 lg:col-span-1 xl:col-span-1 2xl:col-span-1`}>
                <Label htmlFor="propertyType" className="block text-sm font-medium text-gray-700 mb-1">{t('sellOrRent')}</Label>
                <Select value={propertyType} onValueChange={onPropertyTypeChange}>
                    <SelectTrigger id="propertyType" className="!shadow-none w-full min-w-0 bg-gray-100 border-gray-200 rounded-md h-11 focus:ring-0 focus:border-none focus-visible:ring-0 text-sm md:text-base">
                        <SelectValue placeholder="Select Type" />
                    </SelectTrigger>
                    <SelectContent>
                        {propertyTypeOptions.map(option => (
                            <SelectItem key={option} value={option}>{t(option)}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            {/* Category Select */}
            <div className={`${fieldCell} md:col-span-1 lg:col-span-1 xl:col-span-1 2xl:col-span-1`}>
                <Label htmlFor="category" className="block text-sm font-medium text-gray-700 mb-1">{t('category')}</Label>
                <Select value={selectedCategory} onValueChange={onCategoryChange}>
                    <SelectTrigger id="category" className="!shadow-none w-full min-w-0 bg-gray-100 border-gray-200 rounded-md h-11 focus:ring-0 focus:border-none focus-visible:ring-0 text-sm md:text-base">
                        <SelectValue placeholder={t('selectCategory')} />
                    </SelectTrigger>
                    <SelectContent className='max-w-min'>
                        {/* Map existing categories */}
                        {categories?.length > 0 ? categories?.map(option => (
                            <SelectItem key={option?.id} value={option?.id}>{option?.translated_name || option?.category}</SelectItem>
                        )) : (
                            <SelectItem value="no-category-found" disabled>{t('noCategoriesFound')}</SelectItem>
                        )}
                        {/* 'Load More' button section */}
                        {hasMoreCategories && (
                            <div className="p-2 text-center border-t border-gray-200 mt-1">
                                <Button
                                    variant="link"
                                    onClick={handleLoadMoreCategories}
                                    disabled={isLoadingMore}
                                    className="text-sm h-auto p-0 disabled:opacity-50 disabled:cursor-not-allowed w-full justify-center"
                                >
                                    {isLoadingMore ? (t('loading') || 'Loading...') : (t('loadMore') || 'Load More')}
                                </Button>
                            </div>
                        )}
                    </SelectContent>
                </Select>
            </div>
            {/* Location Input - Custom Autocomplete */}
            <div className={`${fieldCell} md:col-span-2 lg:col-span-2 xl:col-span-2 2xl:col-span-1 relative z-[60]`}>
                <Label htmlFor="location" className="block text-sm font-medium text-gray-700 mb-1">{t('location') || 'Location'}</Label>
                <div className="relative z-[60] min-w-0">
                    <CustomLocationAutocomplete
                        value={resolvedLocationInput}
                        onChange={(e) => {
                            const value = e.target.value;
                            resolvedOnLocationInputChange(value);
                            resolvedOnCityChange(value);
                            resolvedOnAreaLocationChange({
                                ...(resolvedAreaLocation || {}),
                                city: value,
                                state: value ? resolvedState : "",
                                country: value ? resolvedCountry : "",
                                state_id: "",
                                city_id: "",
                                area_id: "",
                                area_name: "",
                                sub_area_id: "",
                                sub_area_name: "",
                                detected_area_name: "",
                                detected_sub_area_name: "",
                            });
                            if (!value) {
                                resolvedOnStateChange("");
                                resolvedOnCountryChange("");
                            }
                        }}
                        onPlaceSelect={handlePlaceSelect}
                        placeholder={t('enterLocation')}
                        className="!shadow-none w-full text-sm md:text-base bg-gray-100 newBorder rounded-md h-11 px-3 focus:outline-none"
                        debounceMs={1000}
                        maxResults={10}
                    />
                </div>
            </div>
            <div className={`${fieldCell} md:col-span-2 lg:col-span-2 xl:col-span-2 2xl:col-span-2 relative z-[60]`}>
                <AreaSubAreaSelector
                    compactSeparate
                    requiresCity
                    showStateCity={false}
                    selectedLocationAddress={{
                        ...resolvedAreaLocation,
                        city: resolvedAreaLocation?.city || resolvedCity,
                        state: resolvedAreaLocation?.state || resolvedState,
                        country: resolvedAreaLocation?.country || resolvedCountry,
                    }}
                    setSelectedLocationAddress={(value) => {
                        const nextValue = typeof value === "function"
                            ? value({
                                ...resolvedAreaLocation,
                                city: resolvedAreaLocation?.city || resolvedCity,
                                state: resolvedAreaLocation?.state || resolvedState,
                                country: resolvedAreaLocation?.country || resolvedCountry,
                            })
                            : value;
                        resolvedOnAreaLocationChange(nextValue || {});
                        if (nextValue?.city) resolvedOnCityChange(nextValue.city);
                        if (nextValue?.state) resolvedOnStateChange(nextValue.state);
                        if (nextValue?.country) resolvedOnCountryChange(nextValue.country);
                    }}
                />
            </div>
        </>
    );

    return (
        // Outer container relative for positioning the dropdown
        <div className="relative w-full container overflow-visible">
            {/* --- Top Row Filters Container --- */}
            <div
                className={`bg-white ${className} p-3 md:p-6 w-full relative z-30 overflow-visible transition-all duration-300 ease-in-out ${showAdvancedFilters ? 'border-b' : ''}`}
            >
                {/* Mobile compact search row */}
                <div className="flex md:hidden items-center gap-2">
                    <Input
                        id="mobileKeywords"
                        type="text"
                        placeholder={t('enterKeywords')}
                        className="!shadow-none min-w-0 flex-1 text-sm bg-gray-100 newBorder rounded-md h-11 focus:ring-0 focus:border-none focus-visible:ring-0"
                        value={keywords}
                        onChange={(e) => onKeywordsChange?.(e.target.value)}
                    />
                    {showFiltersButton && (
                        <Button
                            type="button"
                            variant="outline"
                            className="h-11 w-11 shrink-0 border-gray-300 text-gray-700 border-[1.5px] brandBorder p-0 hover:brandBg hover:text-white"
                            onClick={() => onShowAdvancedFiltersChange?.(!showAdvancedFilters)}
                            aria-label={t('smartFilters')}
                            aria-expanded={showAdvancedFilters}
                        >
                            <IoFilterSharp size={20} />
                        </Button>
                    )}
                    {showSearchButton && (
                        <button
                            type="button"
                            onClick={handleApplyFiltersInternal}
                            className="h-11 w-11 shrink-0 brandBg text-white flex items-center justify-center hover:primaryBg rounded-lg"
                            aria-label={t('search')}
                        >
                            <Image src={searchIcon} width={20} height={20} className='w-5 h-5' alt='searchButton' />
                        </button>
                    )}
                </div>
                {/* Tablet/desktop grid: 2 cols (md) → 4 (lg) → 6 (xl) → 9 (2xl single row) */}
                <div className="hidden md:grid md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 2xl:grid-cols-9 gap-3 lg:gap-4 items-end overflow-visible">
                    {renderBasicFilterFields()}
                    {/* Keywords Input */}
                    <div className={`${fieldCell} md:col-span-2 lg:col-span-2 xl:col-span-2 2xl:col-span-1`}>
                        <Label htmlFor="keywords" className="block text-sm font-medium text-gray-700 mb-1">{t('keywords') || 'Keywords'}</Label>
                        <Input
                            id="keywords"
                            type="text"
                            placeholder={t('enterKeywords')}
                            className="!shadow-none w-full min-w-0 text-sm md:text-base bg-gray-100 newBorder rounded-md h-11 focus:ring-0 focus:border-none focus-visible:ring-0"
                            value={keywords}
                            onChange={(e) => onKeywordsChange?.(e.target.value)}
                        />
                    </div>
                    {/* Conditional Filters/Cancel Button */}
                    {showFiltersButton && (
                        <div className={`${fieldCell} md:col-span-1 lg:col-span-2 xl:col-span-2 2xl:col-span-2`}>
                            <Button
                                variant="outline"
                                className="w-full h-11 border-gray-300 text-gray-700 border-[1.5px] brandBorder flex items-center justify-center gap-1.5 xl:gap-2 hover:brandBg hover:text-white text-sm md:text-base whitespace-nowrap px-2 xl:px-4"
                                onClick={() => onShowAdvancedFiltersChange?.(!showAdvancedFilters)}
                                aria-expanded={showAdvancedFilters}
                            >
                                {showAdvancedFilters ? (
                                    <><IoClose size={18} className="shrink-0" /> <span className="truncate">{t('cancel') || 'Cancel'}</span></>
                                ) : (
                                    <><IoFilterSharp size={18} className="shrink-0" /> <span className="truncate">{t('smartFilters')}</span></>
                                )}
                            </Button>
                        </div>
                    )}
                    {/* Search Button */}
                    {showSearchButton && (
                        <div className={`${fieldCell} md:col-span-1 lg:col-span-2 xl:col-span-2 2xl:col-span-1`}>
                            <button
                                onClick={handleApplyFiltersInternal}
                                className="w-full h-11 brandBg text-white flex items-center justify-center gap-2 hover:primaryBg text-sm md:text-base rtl:flex-row-reverse rounded-lg whitespace-nowrap px-3"
                            >
                                <Image src={searchIcon} width={20} height={20} className='w-5 h-5 shrink-0' alt='searchButton' />
                                <span>{t('search')}</span>
                            </button>
                        </div>
                    )}
                </div>
            </div>

            <Drawer open={isMobileView && showAdvancedFilters} onOpenChange={onShowAdvancedFiltersChange}>
                <DrawerContent className="h-[85vh] md:hidden">
                    <DrawerHeader className="shrink-0 border-b border-gray-100 pb-3">
                        <DrawerTitle>{t('smartFilters')}</DrawerTitle>
                    </DrawerHeader>
                    <div className="min-h-0 flex-1 overflow-y-auto px-2 py-4">
                        <div className="grid grid-cols-1 gap-4 mb-5">
                            {renderBasicFilterFields()}
                        </div>
                        {renderAdvancedFiltersContent({ showActions: false })}
                    </div>
                    <DrawerFooter className="shrink-0 border-t border-gray-200 bg-white px-4 py-3">
                        {renderFilterActions()}
                    </DrawerFooter>
                </DrawerContent>
            </Drawer>

            {/* --- Advanced Filters Dropdown Section --- */}
            {showAdvancedFilters && (
                // Absolute positioning below the top row
                <div className="absolute top-full left-0 right-0 hidden w-full bg-white shadow-lg border-0 border-gray-200 z-20 p-4 md:block md:p-6 overflow-visible">
                    {renderAdvancedFiltersContent()}
                </div>
            )}
        </div>
    );
};

export default SearchBox;
