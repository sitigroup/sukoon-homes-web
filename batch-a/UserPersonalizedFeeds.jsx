import {
    getCategoriesApi,
    getFacilitiesApi,
    submitUserInterestsApi,
    getUserIntrestedDataApi,
    deleteUserIntrestedDataApi,
} from "@/api/apiRoutes";
import { useEffect, useState } from "react";
import { FaCircleCheck } from "react-icons/fa6";
import { Skeleton } from "@/components/ui/skeleton";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import toast from "react-hot-toast";
import { useTranslation } from "../context/TranslationContext";
import CustomLocationAutocomplete from "../location-search/CustomLocationAutocomplete";
import { useSelector } from "react-redux";
import { useQuery } from "@tanstack/react-query";
import { Slider } from "@/components/ui/slider";
import { extractAddressComponents, isRTL } from "@/utils/helperFunction";
import { Input } from "../ui/input";
import AreaListingFilter from "@/plugins/area-listing/AreaListingFilter";
import { extractAreaListingFromPlace } from "@/plugins/area-listing/areaListingUtils";

const emptyLocationState = () => ({
    city: "",
    state: "",
    country: "",
    latitude: "",
    longitude: "",
    formattedAddress: "",
    state_id: "",
    city_id: "",
    area_id: "",
    sub_area_id: "",
    area_name: "",
    sub_area_name: "",
    detected_area_name: "",
    detected_sub_area_name: "",
});

const UserPersonalizedFeeds = () => {
    const t = useTranslation();
    const isRtl = isRTL();

    const systemSettings = useSelector((state) => state?.WebSetting?.data);
    const language = useSelector((state) => state.LanguageSettings?.active_language);
    const currency = systemSettings?.currency_symbol || "";
    const defaultMaxPrice = Number(systemSettings?.max_price) || 0;
    const defaultMinPrice = Number(systemSettings?.min_price) || 0;

    const [selectedInterests, setSelectedInterests] = useState([]);
    const [selectedPlaces, setSelectedPlaces] = useState([]);
    const [priceRange, setPriceRange] = useState([defaultMinPrice, defaultMaxPrice]);
    const [minPrice, setMinPrice] = useState(defaultMinPrice);
    const [maxPrice, setMaxPrice] = useState(defaultMaxPrice);
    const [location, setLocation] = useState(emptyLocationState());
    const [listingType, setListingType] = useState("all");

    const [loadCategories, setLoadCategories] = useState(true);
    const [categories, setCategories] = useState([]);

    const [loadFacilities, setLoadFacilities] = useState(true);
    const [facilities, setFacilities] = useState([]);

    const [loading, setLoading] = useState(false);

    const userInterestsQuery = useQuery({
        queryKey: ["userPersonalizedInterests", language],
        queryFn: async () => {
            const response = await getUserIntrestedDataApi();
            return response || {};
        },
        staleTime: 10 * 60 * 1000,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
        refetchOnMount: false,
    });

    const categoriesQuery = useQuery({
        queryKey: ["userPersonalizedCategories", language],
        queryFn: async () => {
            const response = await getCategoriesApi({
                limit: 100,
                offset: 0,
                passHasProperty: false,
            });

            return response || {};
        },
        staleTime: 10 * 60 * 1000,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
        refetchOnMount: false,
    });

    const facilitiesQuery = useQuery({
        queryKey: ["userPersonalizedFacilities", language],
        queryFn: async () => {
            const response = await getFacilitiesApi();
            return response || {};
        },
        staleTime: 10 * 60 * 1000,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
        refetchOnMount: false,
    });

    useEffect(() => {
        const userData = userInterestsQuery.data?.data;

        if (!userData) return;

        if (userData.category_ids?.length > 0) {
            setSelectedInterests(userData.category_ids.map((id) => Number(id)));
        } else {
            setSelectedInterests([]);
        }

        if (userData.outdoor_facilitiy_ids?.length > 0) {
            setSelectedPlaces(userData.outdoor_facilitiy_ids.map((id) => Number(id)));
        } else {
            setSelectedPlaces([]);
        }

        if (userData.price_range?.length >= 2) {
            const minValue = Number(userData.price_range[0]) || 0;
            const maxValue = Number(userData.price_range[1]) || 0;

            setMinPrice(minValue);
            setMaxPrice(maxValue);
            setPriceRange([minValue, maxValue]);
        } else {
            setMinPrice(defaultMinPrice);
            setMaxPrice(defaultMaxPrice);
            setPriceRange([defaultMinPrice, defaultMaxPrice]);
        }

        if (userData.property_type !== undefined) {
            let listingTypeValue = "all";
            if (userData.property_type[0] === "0") {
                listingTypeValue = "sell";
            } else if (userData.property_type[0] === "1") {
                listingTypeValue = "rent";
            }
            setListingType(listingTypeValue);
        } else {
            setListingType("all");
        }

        if (userData.city || userData.area_id || userData.sub_area_id) {
            setLocation({
                ...emptyLocationState(),
                city: userData.city || "",
                formattedAddress: userData.city || "",
                state_id: userData.state_id || "",
                city_id: userData.city_id || "",
                area_id: userData.area_id || "",
                sub_area_id: userData.sub_area_id || "",
                area_name: userData.area_name || "",
                sub_area_name: userData.sub_area_name || "",
            });
        } else {
            setLocation(emptyLocationState());
        }
    }, [userInterestsQuery.data, defaultMaxPrice, defaultMinPrice]);

    useEffect(() => {
        setCategories(categoriesQuery.data?.data || []);
        setLoadCategories(categoriesQuery.isLoading);
    }, [categoriesQuery.data, categoriesQuery.isLoading]);

    useEffect(() => {
        setFacilities(facilitiesQuery.data?.data || []);
        setLoadFacilities(facilitiesQuery.isLoading);
    }, [facilitiesQuery.data, facilitiesQuery.isLoading]);

    const getStringFromOnChange = (valueOrEvent) => {
        if (typeof valueOrEvent === "string") return valueOrEvent;
        return valueOrEvent?.target?.value ?? "";
    };

    const toggleInterest = (id) => {
        setSelectedInterests((prev) =>
            prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id],
        );
    };

    const togglePlace = (id) => {
        setSelectedPlaces((prev) =>
            prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id],
        );
    };

    const handleMinPriceChange = (event) => {
        const value = event.target.value;

        if (value === "" || /^\d+$/.test(value)) {
            const numValue = value === "" ? 0 : parseInt(value, 10);
            setMinPrice(numValue);
            setPriceRange([numValue, priceRange[1]]);
        }
    };

    const handleMaxPriceChange = (event) => {
        const value = event.target.value;

        if (value === "" || /^\d+$/.test(value)) {
            const numValue = value === "" ? 0 : parseInt(value, 10);
            setMaxPrice(numValue);
            setPriceRange([priceRange[0], numValue]);
        }
    };

    const handleRangeChange = (values) => {
        const [min, max] = values;
        setPriceRange([min, max]);
        setMinPrice(min);
        setMaxPrice(max);
    };

    const handleSubmit = async () => {
        if (minPrice > maxPrice) {
            toast.error(t("minPriceGreaterThanMaxPrice"));
            return;
        }

        setLoading(true);

        try {
            const propertyTypeValue =
                listingType === "sell" ? "0" : listingType === "rent" ? "1" : "";

            const formData = {
                category_ids: selectedInterests.join(","),
                outdoor_facilitiy_ids: selectedPlaces.join(","),
                price_range: `${minPrice},${maxPrice}`,
                property_type: propertyTypeValue,
                city: location.city || "",
                state_id: location.state_id || "",
                city_id: location.city_id || "",
                area_id: location.area_id || "",
                sub_area_id: location.sub_area_id || "",
                area_name: location.area_name || location.detected_area_name || "",
                sub_area_name: location.sub_area_name || location.detected_sub_area_name || "",
            };

            const response = await submitUserInterestsApi(formData);

            if (response.error === false) {
                toast.success(t(response?.message));
            } else {
                toast.error(t(response?.message));
            }
        } catch (error) {
            console.error("Error submitting interests:", error);
            toast.error(t(error?.message));
        } finally {
            setLoading(false);
        }
    };

    const handleReset = async () => {
        setLoading(true);

        try {
            const response = await deleteUserIntrestedDataApi();

            if (response.error === false) {
                setSelectedInterests([]);
                setSelectedPlaces([]);
                setPriceRange([defaultMinPrice, defaultMaxPrice]);
                setMinPrice(defaultMinPrice);
                setMaxPrice(defaultMaxPrice);
                setLocation(emptyLocationState());
                setListingType("all");

                toast.success(t(response?.message));

                userInterestsQuery.refetch();
            } else {
                toast.error(t(response?.message));
            }
        } catch (error) {
            console.error("Error resetting interests:", error);
            toast.error(t("interestsResetError"));
        } finally {
            setLoading(false);
        }
    };

    const handleLocationSelect = (placeData, placeDetails) => {
        if (!placeData) return;

        const formattedAddressRaw =
            placeData?.formattedAddress ||
            placeData?.formatted_address ||
            placeDetails?.formatted_address ||
            "";

        const { city, state, country, formattedAddress } = extractAddressComponents({
            formatted_address: formattedAddressRaw,
            address_components:
                placeDetails?.address_components || placeData?.address_components || [],
        });

        const latitudeValue = Number(placeData?.latitude);
        const longitudeValue = Number(placeData?.longitude);

        const areaHints = extractAreaListingFromPlace(placeDetails || placeData || {});

        setLocation((prev) => ({
            ...prev,
            city: city || placeData?.city || "",
            state: state || placeData?.state || "",
            country: country || placeData?.country || "",
            latitude: Number.isFinite(latitudeValue) ? latitudeValue : "",
            longitude: Number.isFinite(longitudeValue) ? longitudeValue : "",
            formattedAddress: formattedAddress || formattedAddressRaw || "",
            detected_area_name: areaHints.detected_area_name || prev.detected_area_name || "",
            detected_sub_area_name: areaHints.detected_sub_area_name || prev.detected_sub_area_name || "",
            area_id: "",
            sub_area_id: "",
            area_name: "",
            sub_area_name: "",
        }));
    };

    const listingTypeOptions = [
        { value: "all", label: t("all") },
        { value: "sell", label: t("sell") },
        { value: "rent", label: t("rent") },
    ];

    const sectionTitleClass = "text-lg font-semibold text-gray-900 sm:text-xl";
    const pillBaseClass =
        "inline-flex items-center rounded-full border px-4 py-2 text-sm font-medium transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20";
    const fieldClass =
        "h-11 w-full rounded-lg border newBorderColor primaryBackgroundBg px-4 text-sm text-gray-900 shadow-none transition-all duration-200 placeholder:text-gray-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/15 sm:h-12";

    return (
        <div className="w-full">
            <div className="overflow-hidden rounded-2xl border newBorder bg-white shadow-sm">
                <div className="border-b newBorderColor px-4 py-4 sm:px-6">
                    <h2 className="text-base font-semibold text-gray-900 md:text-xl">
                        {t("personalizeFeed")}
                    </h2>
                </div>

                <div className="space-y-8 px-4 py-6 sm:px-6 sm:py-7">
                    <section className="space-y-4">
                        <h3 className={sectionTitleClass}>{t("chooseYourInterest")}</h3>
                        <div className="flex flex-wrap gap-3">
                            {loadCategories
                                ? Array.from({ length: 8 }).map((_, index) => (
                                    <Skeleton
                                        key={index}
                                        className="h-10 w-20 rounded-full sm:w-24"
                                    />
                                ))
                                : categories?.length > 0
                                    ? categories.map((category) => {
                                        const isSelected = selectedInterests.includes(category.id);

                                        return (
                                            <button
                                                key={category.id}
                                                type="button"
                                                aria-pressed={isSelected}
                                                className={`${pillBaseClass} ${isSelected
                                                    ? "primaryBg primaryBorderColor text-white"
                                                    : "border-gray-200 bg-gray-50 text-gray-700 hover:bg-white hover:border-gray-300"
                                                    }`}
                                                onClick={() => toggleInterest(category.id)}
                                            >
                                                {isSelected && (
                                                    <FaCircleCheck size={16} className="mr-2 rtl:ml-2" />
                                                )}
                                                <span>{category.translated_name || category.category}</span>
                                            </button>
                                        );
                                    })
                                    : (
                                        <p className="text-sm text-gray-500">{t("noCategoriesFound")}</p>
                                    )}
                        </div>
                    </section>

                    <section className="space-y-4">
                        <div className="space-y-1">
                            <h3 className={sectionTitleClass}>{t("chooseNearbyPlaces")}</h3>
                            <p className="text-sm leading-6 text-gray-500">
                                {t("getRecommendationForPropertiesThatAreCloseToTheseLocations")}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-3">
                            {loadFacilities
                                ? Array.from({ length: 8 }).map((_, index) => (
                                    <Skeleton
                                        key={index}
                                        className="h-10 w-20 rounded-full sm:w-24"
                                    />
                                ))
                                : facilities?.length > 0
                                    ? facilities.map((place) => {
                                        const isSelected = selectedPlaces.includes(place.id);

                                        return (
                                            <button
                                                key={place.id}
                                                type="button"
                                                aria-pressed={isSelected}
                                                className={`${pillBaseClass} ${isSelected
                                                    ? "primaryBg primaryBorderColor text-white"
                                                    : "border-gray-200 bg-gray-50 text-gray-700 hover:bg-white hover:border-gray-300"
                                                    }`}
                                                onClick={() => togglePlace(place.id)}
                                            >
                                                {isSelected && (
                                                    <FaCircleCheck size={16} className="mr-2 rtl:ml-2" />
                                                )}
                                                <span>{place.translated_name || place.name}</span>
                                            </button>
                                        );
                                    })
                                    : (
                                        <p className="text-sm text-gray-500">{t("noFacilitiesFound")}</p>
                                    )}
                        </div>
                    </section>

                    <section className="space-y-4">
                        <h3 className={sectionTitleClass}>
                            {t("chooseYourPreferredLocationAndPropertyType")}
                        </h3>
                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div className="space-y-2 lg:col-span-2">
                                <label className="block text-sm font-medium text-gray-700">
                                    {t("selectLocation")}
                                </label>
                                <CustomLocationAutocomplete
                                    value={location.formattedAddress || location.city || ""}
                                    onChange={(valueOrEvent) => {
                                        const nextValue = getStringFromOnChange(valueOrEvent);
                                        setLocation((prev) => ({
                                            ...prev,
                                            city: nextValue,
                                            formattedAddress: nextValue,
                                            area_id: "",
                                            sub_area_id: "",
                                            area_name: "",
                                            sub_area_name: "",
                                        }));
                                    }}
                                    onPlaceSelect={handleLocationSelect}
                                    placeholder={t("searchLocation")}
                                    className={fieldClass}
                                />
                            </div>

                            <div className="space-y-2 lg:col-span-2">
                                <label className="block text-sm font-medium text-gray-700">
                                    {t("area")} / {t("subArea")}
                                </label>
                                <AreaListingFilter
                                    stacked
                                    locationInput={location}
                                    setLocationInput={setLocation}
                                />
                            </div>

                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-700">
                                    {t("listingType")}
                                </label>
                                <Select
                                    value={listingType}
                                    onValueChange={setListingType}
                                    dir={isRtl ? "rtl" : "ltr"}
                                >
                                    <SelectTrigger className={fieldClass}>
                                        <SelectValue placeholder={t("listingType")} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {listingTypeOptions.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </section>

                    <section className="space-y-4">
                        <h3 className={sectionTitleClass}>{t("chooseTheBudgetForTheProperty")}</h3>
                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-700">
                                    {t("minPrice")}
                                </label>
                                <Input
                                    type="text"
                                    placeholder={`${currency} ${defaultMinPrice}`}
                                    className={fieldClass}
                                    value={minPrice}
                                    onChange={handleMinPriceChange}
                                />
                            </div>
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-700">
                                    {t("maxPrice")}
                                </label>
                                <Input
                                    type="text"
                                    placeholder={`${currency} ${defaultMaxPrice}`}
                                    className={fieldClass}
                                    value={maxPrice}
                                    onChange={handleMaxPriceChange}
                                />
                            </div>
                        </div>

                        <div className="space-y-3 pt-1">
                            <div className="text-sm font-semibold text-gray-900">
                                {t("priceRange")} {currency} {priceRange[0]} {t("to")} {currency} {priceRange[1]}
                            </div>
                            <div className="py-1">
                                <Slider
                                    isTwoThumb={true}
                                    defaultValue={[priceRange[0], priceRange[1]]}
                                    value={[priceRange[0], priceRange[1]]}
                                    max={defaultMaxPrice}
                                    min={0}
                                    step={1000}
                                    onValueChange={handleRangeChange}
                                    className="my-4"
                                />
                            </div>
                        </div>
                    </section>
                </div>

                <div className="flex flex-col gap-3 border-t newBorderColor px-4 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button
                        type="button"
                        className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                        onClick={handleReset}
                        disabled={loading}
                    >
                        {loading ? t("processing") : t("clear")}
                    </button>
                    <button
                        type="button"
                        className="primaryBg rounded-md px-4 py-2 text-sm font-semibold text-white transition-colors hover:opacity-95 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                        onClick={handleSubmit}
                        disabled={loading}
                    >
                        {loading ? t("processing") : t("submit")}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default UserPersonalizedFeeds;
