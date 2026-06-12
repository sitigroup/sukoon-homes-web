"use client";
import { useState, useEffect } from 'react';
import { useTranslation } from '@/components/context/TranslationContext';
import { Skeleton } from "@/components/ui/skeleton";
import { generateAIPropertyDescriptionApi, generateAIPropertyMetaDataApi, getCategoriesApi, getFacilitiesApi, postPropertyApi, getPackagesApi, getPaymentSettingsApi } from '@/api/apiRoutes';
import ImageWithPlaceholder from '@/components/image-with-placeholder/ImageWithPlaceholder';
import toast from 'react-hot-toast';
import { generateSlug } from '@/utils/helperFunction';
import { useSelector } from 'react-redux';
import { useRouter } from 'next/router';
import FacilitiesComponent from '@/components/reusable-components/add-property/FacilitiesComponent';
import OutdoorFacilitiesComponent from '@/components/reusable-components/add-property/OutdoorFacilitiesComponent';
import LocationComponent from '@/components/reusable-components/add-property/LocationComponent';
import { buildAreaListingSaveFields } from '@/plugins/area-listing/areaListingPermissions';
import ImagesVideoTab from '@/components/reusable-components/add-property/ImagesVideoTab';
import SEODetailsTab from '@/components/reusable-components/add-property/SEODetailsTab';
import PropertyDetailsTab from '@/components/reusable-components/add-property/PropertyDetailsTab';
import React from 'react';
import Swal from 'sweetalert2';
import successMark from '@/assets/SuccessTick.gif';
import { checkPackageAvailable } from '@/utils/checkPackages/checkPackage';
import { PackageTypes } from '@/utils/checkPackages/packageTypes';
import PayAsYouGoModal from '@/components/modal/PayAsYouGoModal';
import PaymentSelectionModalWrapper from '@/components/modal/PaymentSelectionModalWrapper';
import PropertyFormShell from '@/components/agent/property/PropertyFormShell';
import { setPaymentReturnUrl } from '@/utils/paymentReturn';

const AddProperty = () => {
    const router = useRouter();
    const { lang } = router?.query;
    const websettings = useSelector(state => state.WebSetting?.data);
    const userData = useSelector(state => state.User?.data);
    const languages = useSelector(state => state.LanguageSettings?.languages) || [];
    const activeLanguage = useSelector(state => state.LanguageSettings?.active_language);
    const t = useTranslation();
    const [activeTab, setActiveTab] = useState("categories");
    const [showPayAsYouGoModal, setShowPayAsYouGoModal] = useState(false);
    const [isCheckingPackage, setIsCheckingPackage] = useState(false);
    const [payAsYouGoData, setPayAsYouGoData] = useState(null);
    const [isPayAsYouGoProcessing, setIsPayAsYouGoProcessing] = useState(false);
    const [hasPendingPayAsYouGoSubmission, setHasPendingPayAsYouGoSubmission] = useState(false);

    // Payment modal state
    const [paymentSettingsData, setPaymentSettingsData] = useState([]);
    const [showPaymentSelection, setShowPaymentSelection] = useState(false);
    const [paymentLoading, setPaymentLoading] = useState(false);
    const [isCategoriesLoading, setIsCategoriesLoading] = useState(true);
    const [isFetchingMore, setIsFetchingMore] = useState(false);
    const [categories, setCategories] = useState([]);
    const [hasMoreCategories, setHasMoreCategories] = useState(false);
    const limit = 12;
    const [offset, setOffset] = useState(0);
    const [facilities, setFacilities] = useState([]);
    const [showLoader, setShowLoader] = useState(false);
    const [isDescriptionGenerating, setIsDescriptionGenerating] = useState(false);
    const [isMetaDataGenerating, setIsMetaDataGenerating] = useState(false);
    const currentLocation = useSelector((state) => state.location);
    const defaultLanguage = useSelector(state => state.LanguageSettings?.default_language)

    const isCustomVideoUpload = websettings?.show_direct_video_upload === "1";

    // Find the active language object from the languages array
    const activeLanguageObj = React.useMemo(() => {
        if (!activeLanguage || !languages.length) return null;
        return languages.find(lang => lang.code === activeLanguage) || null;
    }, [activeLanguage, languages]);

    const [selectedLanguage, setSelectedLanguage] = useState(activeLanguageObj || activeLanguage || "English");
    const [translations, setTranslations] = useState([]);

    const isUserRoute = router?.asPath?.includes("/user/");
    const addPropertyReturnPath = () =>
        isUserRoute ? `/user/add-property?lang=${lang}` : `/agent/add-property?lang=${lang}`;

    // Tab components
    const tabItems = [
        { id: "categories", label: t("categories") },
        { id: "propertyDetails", label: t("propertyDetails") },
        { id: "imagesVideo", label: t("imagesVideo") },
        { id: "facilities", label: t("facilities") },
        { id: "outdoorFacilities", label: t("outdoorFacilities") },
        { id: "location", label: t("location") },
        { id: "seoSettings", label: t("seoSettings") },
    ];

    // Facility Distances Form State
    const [facilityDistances, setFacilityDistances] = useState({});

    // Category parameters form state
    const [parameterFormData, setParameterFormData] = useState({});
    // Property Details Form State
    const [selectedCategory, setSelectedCategory] = useState("");

    // Property Details Form State
    const [propertyFormData, setPropertyFormData] = useState({
        propertyType: "Sell",
        propertyTitle: "",
        propertySlug: "",
        propertyPrice: "",
        propertyDescription: "",
        isPremiumProperty: false,
        rentDuration: ""
    });

    // SEO Settings Form State
    const [seoFormData, setSeoFormData] = useState({
        metaTitle: "",
        metaKeywords: "",
        metaDescription: "",
        ogImage: null
    });

    // Combined Media Form State
    const [mediaFormData, setMediaFormData] = useState({
        titleImages: [],
        documents: [],
        images3D: [],
        galleryImages: [],
        videoLink: '',
        videoType: '',
        customVideo: null
    });

    // Location Form State
    const [selectedLocationAddress, setSelectedLocationAddress] = useState({
        city: currentLocation?.city || "",
        state: currentLocation?.state || "",
        country: currentLocation?.country || "",
        formattedAddress: currentLocation?.formatted_address || "",
        manualAddress: "",
        clientAddress: "",
        latitude: currentLocation?.latitude || "",
        longitude: currentLocation?.longitude || "",
        area_id: "",
        sub_area_id: "",
        area_name: "",
        sub_area_name: "",
        detected_area_name: "",
        detected_sub_area_name: "",
        area_listing_source: "google",
        location_is_verified: false,
        state_id: "",
        city_id: ""
    });

    // Handle removing any type of media
    const handleRemoveMedia = (type, index, id = null) => {
        setMediaFormData(prev => {
            const updatedMedia = [...prev[type]];
            updatedMedia.splice(index, 1);
            return {
                ...prev,
                [type]: updatedMedia
            };
        });
    };

    // For backward compatibility with editing mode
    const handleRemoveGalleryImages = (index, id) => {
        // If the ID exists, you might want to track it for backend deletion
        // For now, just use the generic removal function
        handleRemoveMedia('galleryImages', index);
    };

    const handleRemoveDocuments = (index, id) => {
        // If the ID exists, you might want to track it for backend deletion
        // For now, just use the generic removal function
        handleRemoveMedia('documents', index);
    };

    // Handle multilingual data changes
    const handleMultiLangChange = (language, field, value) => {
        setTranslations(prev => {
            // Find if we already have a translation for this language
            const existingIndex = prev.findIndex(item =>
                item.language_id === language.id ||
                item.language_id === language
            );

            // Create a new array to avoid mutating the previous state
            const newTranslations = [...prev];

            if (existingIndex !== -1) {
                // Update existing translation
                newTranslations[existingIndex] = {
                    ...newTranslations[existingIndex],
                    [field]: value
                };
            } else {
                // Add new translation
                newTranslations.push({
                    language_id: language.id || language,
                    [field]: value
                });
            }

            return newTranslations;
        });
    };

    // Update property form field handler
    const handleUpdatePropertyForm = (e, customName, customValue) => {
        // For radio inputs and custom events where e.target might not have name/value
        if (customName && customValue !== undefined) {
            setPropertyFormData(prev => ({
                ...prev,
                [customName]: customValue
            }));
            return;
        }

        // For select inputs (from PropertyRentDuration) 
        if (typeof e === 'string' && customName) {
            setPropertyFormData(prev => ({
                ...prev,
                [customName]: e
            }));
            return;
        }

        // For standard form events (input, textarea)
        if (e && e.target) {
            const { name, value, type, checked } = e.target;

            // Handle title input for English language
            if (name === "title") {
                setPropertyFormData(prev => ({
                    ...prev,
                    propertyTitle: value,
                    propertySlug: generateSlug(value)
                }));
                return;
            }

            // Handle description input for English language
            if (name === "description") {
                setPropertyFormData(prev => ({
                    ...prev,
                    propertyDescription: value
                }));
                return;
            }

            if (name === "propertyTitle") {
                setPropertyFormData(prev => ({
                    ...prev,
                    [name]: value,
                    propertySlug: generateSlug(value)
                }));
                return;
            }

            // Handle checkbox/switch inputs
            if (type === 'checkbox') {
                setPropertyFormData(prev => ({
                    ...prev,
                    [name]: checked
                }));
                return;
            }

            // Handle regular inputs
            setPropertyFormData(prev => ({
                ...prev,
                [name]: value
            }));
        }
    };

    const handleFillSeoFormData = (e, customName, customValue) => {
        // If we have direct customName and customValue (from TagInput)
        if (customName && customValue !== undefined) {
            setSeoFormData(prev => ({
                ...prev,
                [customName]: customValue
            }));
            return;
        }

        // For standard form events (input, textarea)
        if (e && e.target) {
            const { name, value } = e.target;
            setSeoFormData(prev => ({
                ...prev,
                [name]: value
            }));
        }
    };

    const handleCheckRequiredFields = (currentTab, nextTab) => {
        let missingFields = false;

        switch (currentTab) {
            case "categories":
                if (!selectedCategory) {
                    toast.error(t("pleaseSelectCategory"));
                    missingFields = true;
                }
                break;

            case "propertyDetails":
                if (!propertyFormData.propertyTitle) {
                    toast.error(t("propertyTitleRequired"));
                    missingFields = true;
                    break;
                }

                if (!propertyFormData.propertyPrice) {
                    toast.error(t("propertyPriceRequired"));
                    missingFields = true;
                    break;
                }

                if (!propertyFormData.propertyDescription) {
                    toast.error(t("propertyDescriptionRequired"));
                    missingFields = true;
                    break;
                }

                if (propertyFormData.propertyType === "Rent" && !propertyFormData.rentDuration) {
                    toast.error(t("rentDurationRequired"));
                    missingFields = true;
                    break;
                }
                break;

            case "facilities":
                if (selectedCategory && categories.length > 0) {
                    const category = categories.find(cat => cat.id === selectedCategory?.id);
                    if (category && category.parameter_types) {
                        let requiredParameters = [];
                        if (typeof category.parameter_types === "object") {
                            requiredParameters = Object.values(category.parameter_types).filter(param => param.is_required === 1);
                        } else {
                            requiredParameters = category.parameter_types.filter(param => param.is_required === 1);
                        }
                        for (const param of requiredParameters) {
                            const value = parameterFormData[param.id];
                            if (value === undefined || value === '' || value === null) {
                                toast.error(`${t("pleaseFillRequiredField")} ${param.name}`);
                                missingFields = true;
                                break;
                            }
                        }
                    }
                }
                break;

            case "outdoorFacilities":
                if (facilities.length > 0) {
                    const requiredFacilities = facilities.filter(facility => facility.is_required === 1);

                    for (const facility of requiredFacilities) {
                        const distance = facilityDistances[facility.id];
                        if (distance === undefined || distance === '' || distance === null) {
                            toast.error(`${t("distanceFor")} ${facility.name} ${t("isRequired")}`);
                            missingFields = true;
                            break;
                        }
                    }
                }
                break;

            case "location":
                if (!selectedLocationAddress.city) {
                    toast.error(t("cityIsRequired"));
                    missingFields = true;
                    break;
                }
                if (!selectedLocationAddress.state) {
                    toast.error(t("stateIsRequired"));
                    missingFields = true;
                    break;
                }
                if (!selectedLocationAddress.country) {
                    toast.error(t("countryIsRequired"));
                    missingFields = true;
                    break;
                }
                if (!selectedLocationAddress.formattedAddress) {
                    toast.error(t("addressIsRequired"));
                    missingFields = true;
                    break;
                }
                break;

            case "imagesVideo":
                if (mediaFormData.titleImages.length === 0) {
                    toast.error(t("titleImageRequired"));
                    missingFields = true;
                    break;
                }


                break;
            case "seoSettings":
                // If we're on the seoSettings tab and no errors were found, and no nextTab is specified,
                // check package availability before submitting
                if (!missingFields && nextTab === null) {
                    handleSubmitWithPackageCheck();
                    return;
                }
        }

        if (!missingFields && nextTab) {
            setActiveTab(nextTab);
        }
    };

    const handleFetchCategories = async (currentOffset = 0, isInitial = false) => {
        if (isInitial) {
            setIsCategoriesLoading(true);
            setOffset(0); // Reset offset when initializing
        } else {
            setIsFetchingMore(true);
        }

        try {
            const res = await getCategoriesApi({ limit, offset: currentOffset, passHasProperty: false });

            if (isInitial) {
                setCategories(res.data);
            } else {
                setCategories(prev => [...prev, ...res.data]);
            }

            setHasMoreCategories(res.data.length >= limit);
        } catch (error) {
            console.error("Failed to fetch categories:", error);
        } finally {
            setIsCategoriesLoading(false);
            setIsFetchingMore(false);
        }
    };

    const handleFetchFacilities = async () => {
        try {
            const res = await getFacilitiesApi();
            setFacilities(res.data);
        } catch (error) {
            console.error("Failed to fetch facilities:", error);
        }
    };

    // Only fetch categories on initial component mount, not on tab changes
    useEffect(() => {
        if (activeTab === "categories" && categories.length === 0) {
            handleFetchCategories(0, true);
        } else if (activeTab === "categories" && facilities.length === 0) {
            handleFetchFacilities();
        }
    }, [activeTab, categories.length, facilities.length]);

    useEffect(() => {
        handleFetchCategories(0, true);
        handleFetchFacilities();
    }, [activeLanguage]);

    // Fetch payment settings
    useEffect(() => {
        const fetchPaymentSettings = async () => {
            try {
                const res = await getPaymentSettingsApi();
                if (!res?.error) {
                    setPaymentSettingsData(res?.data || []);
                }
            } catch (err) {
                console.error("Error fetching payment settings:", err);
            }
        };
        fetchPaymentSettings();
    }, []);

    const handleLoadMoreCategories = async () => {
        const newOffset = offset + limit;
        setOffset(newOffset);
        await handleFetchCategories(newOffset, false);
    };

    const handleTabChange = (value) => {
        const tabOrder = ["categories", "propertyDetails", "imagesVideo", "facilities", "outdoorFacilities", "location", "seoSettings"];
        const currentIndex = tabOrder.indexOf(activeTab);
        const targetIndex = tabOrder.indexOf(value);

        // If navigating backwards or to the same tab, allow without validation
        if (targetIndex <= currentIndex) {
            setActiveTab(value);
            setSelectedLanguage(activeLanguageObj || activeLanguage || "English");
            return;
        }

        // Only validate when moving forward
        handleCheckRequiredFields(activeTab, value);
        // Reset language selection to default/active language when switching tabs
        setSelectedLanguage(activeLanguageObj || activeLanguage || "English");
    };

    // Select Category
    const handleSelectCategory = (category) => {
        setSelectedCategory(category);
        setActiveTab("propertyDetails");
    };
    const handleRemoveCategory = () => {
        setSelectedCategory("");
        setActiveTab("categories");
    };

    //  Outdoor Facilities Distance Change
    const handleDistanceChange = (facilityId, value) => {
        // Ensure that the input value is a positive number
        const parsedValue = parseFloat(value);
        const newValue = isNaN(parsedValue) || parsedValue < 0 ? 0 : parsedValue;

        setFacilityDistances(prev => ({
            ...prev,
            [facilityId]: newValue
        }));
    };

    const handleLocationSelect = (address) => {
        setSelectedLocationAddress(prev => ({
            ...prev,
            ...address,
            formattedAddress: address.formattedAddress || prev.formattedAddress,
            latitude: address.latitude ?? address.lat ?? prev.latitude,
            longitude: address.longitude ?? address.lng ?? prev.longitude,
            lat: address.lat ?? address.latitude ?? prev.lat,
            lng: address.lng ?? address.longitude ?? prev.lng,
            clientAddress: address.clientAddress ?? prev.clientAddress,
            manualAddress: address.manualAddress ?? address.clientAddress ?? prev.manualAddress,
        }));
    };

    // CategorySkeleton component
    const CategorySkeleton = () => (
        <div className="flex items-center justify-center p-4 rounded-lg primaryBackgroundBg gap-3">
            <Skeleton className="h-12 w-12 rounded-full bg-gray-200" />
            <Skeleton className="h-6 w-24 bg-gray-200" />
        </div>
    );

    // Check package before submitting
    const handleSubmitWithPackageCheck = async () => {
        if (isCheckingPackage) return; // Prevent double submit
        if (hasPendingPayAsYouGoSubmission) {
            setShowPayAsYouGoModal(false);
            setShowPaymentSelection(true);
            return;
        }
        setIsCheckingPackage(true);
        try {
            const isAvailable = await checkPackageAvailable(PackageTypes.PROPERTY_LIST);
            if (!isAvailable) {
                // Fetch packages to get pay_as_you_go data
                let payAsYouGoList = [];
                let propertyPayAsYouGo = [];
                try {
                    const packagesRes = await getPackagesApi();
                    payAsYouGoList = packagesRes?.pay_as_you_go || [];
                    propertyPayAsYouGo = payAsYouGoList.find(
                        (item) => item.type === "property" && item.status === 1
                    );
                    setPayAsYouGoData(propertyPayAsYouGo || []);
                } catch (err) {
                    console.error("Error fetching pay as you go data:", err);
                    setPayAsYouGoData([]);
                }

                const submitted = await handlePostProperty(null, { showSwal: false });
                if (!submitted) {
                    return;
                }
                if (payAsYouGoList?.length !== 0) {
                    setHasPendingPayAsYouGoSubmission(true);
                    setShowPayAsYouGoModal(true);
                } else {
                    setHasPendingPayAsYouGoSubmission(false);
                    setShowPayAsYouGoModal(false);
                    setPaymentReturnUrl(addPropertyReturnPath());
                    router?.push(isUserRoute ? `/user/subscription-plan?lang=${lang}` : `/agent/packages?lang=${lang}`);
                }
                return;
            }
            // Package available — proceed to submit
            handlePostProperty();
        } catch (error) {
            console.error("Error checking package:", error);
            toast.error(t("somethingWentWrong"));
        } finally {
            setIsCheckingPackage(false);
        }
    };

    const handlePendingPayAsYouGoDismiss = () => {
        if (!hasPendingPayAsYouGoSubmission) {
            return;
        }

        setHasPendingPayAsYouGoSubmission(false);
        toast.success(t("propertyDraftPaymentReminder"));
        router.push(isUserRoute ? `/user/listings?tab=properties&lang=${lang}` : `/agent/dashboard?lang=${lang}`);
    };

    // Handle post property submission
    const handlePostProperty = async (e, options = {}) => {
        // If called from a button click, prevent default form submission
        if (e) e.preventDefault();
        const { showSwal = true } = options;
        setShowLoader(true);
        try {
            // Format translations for API
            const formattedTranslations = translations.map((translation, index) => ({
                translation_id: index,
                language_id: translation.language_id,
                title: translation.title || "",
                description: translation.description || ""
            }));

            const data = {
                userid: userData?.id,
                title: propertyFormData.propertyTitle,
                description: propertyFormData.propertyDescription,
                city: selectedLocationAddress.city,
                state: selectedLocationAddress.state,
                country: selectedLocationAddress.country,
                latitude: selectedLocationAddress.latitude,
                longitude: selectedLocationAddress.longitude,
                ...buildAreaListingSaveFields(selectedLocationAddress, {
                    isUserPortal: Boolean(router?.asPath?.includes('/user/')),
                }),
                address: selectedLocationAddress.formattedAddress,
                manual_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                customer_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                client_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                                price: propertyFormData.propertyPrice,
                category_id: selectedCategory.id,
                property_type: propertyFormData.propertyType?.toLowerCase() === "sell" ? "0" : "1", // 0 for Sell, 1 for Rent
                video_link: mediaFormData.videoLink,
                custom_video: mediaFormData.customVideo,
                ...(mediaFormData.videoLink || mediaFormData.customVideo
                    ? { video_type: mediaFormData.videoType }
                    : {}),
                parameters: parameterFormData,
                facilities: facilityDistances,
                title_image: mediaFormData.titleImages[0],
                three_d_image: mediaFormData.images3D[0],
                gallery_images: mediaFormData.galleryImages,
                meta_title: seoFormData.metaTitle,
                meta_description: seoFormData.metaDescription,
                meta_keywords: seoFormData.metaKeywords,
                meta_image: seoFormData.ogImage,
                rentduration: propertyFormData.rentDuration,
                is_premium: propertyFormData.isPremiumProperty,
                slug_id: propertyFormData.propertySlug,
                documents: mediaFormData.documents,
                translations: formattedTranslations
            };
            // Call the API with FormData
            const response = await postPropertyApi(data);

            if (response && !response.error) {
                if (showSwal) {
                    await Swal.fire({
                        imageUrl: successMark.src,
                        imageWidth: 160,
                        imageHeight: 160,
                        title: t("propertySubmittedSuccess"),
                        text: !websettings?.auto_approve
                            ? websettings?.text_property_submission
                            : "",
                        allowOutsideClick: false,
                        showCancelButton: false,
                        customClass: {
                            confirmButton: "Swal-confirm-buttons",
                            cancelButton: "Swal-cancel-buttons",
                        },
                        confirmButtonText: t("viewProperties"),
                    });
                    router.push(`${isUserRoute ? `/user/listings?tab=properties&lang=${lang}` : `/agent/properties?lang=${lang}`}`);
                }
                return true;
            } else {
                toast.error(t(response?.message) || t("failedToAddProperty"));
                return false;
            }

        } catch (error) {
            toast.error(t(error?.message) || t("somethingWentWrong"));
            console.error("Error adding property:", error);
            return false;
        } finally {
            setShowLoader(false);
        }
    };


    // Handler to generate AI description 
    const handleGenerateAIDescription = async () => {
        try {
            setIsDescriptionGenerating(true);
            const response = await generateAIPropertyDescriptionApi({
                entity_type: "property",
                title: translations.find(tr => tr.language_id === selectedLanguage?.id)?.title || propertyFormData.propertyTitle || "",
                category_id: selectedCategory?.id,
                language_id: selectedLanguage?.id,
                property_type: propertyFormData.propertyType?.toLowerCase(),
                city: selectedLocationAddress.city,
                state: selectedLocationAddress.state,
                country: selectedLocationAddress.country
            })
            if (selectedLanguage?.code === defaultLanguage) {
                setPropertyFormData((prev) => ({
                    ...prev,
                    propertyDescription: response?.data?.description || "",
                }));
            } else {
                handleMultiLangChange(selectedLanguage, "description", response?.data?.description || "");
            }
        } catch (error) {
            toast.error(error?.message)
            console.error("Failed to generate AI description:", error);
        } finally {
            setIsDescriptionGenerating(false);
        }
    }

    // Handler to generate AI meta data
    const handleGenerateAIMetaData = async () => {
        try {
            setIsMetaDataGenerating(true);
            const response = await generateAIPropertyMetaDataApi({
                entity_type: "property",
                title: propertyFormData.propertyTitle || "",
                category_id: selectedCategory?.id,
                language_id: selectedLanguage?.id,
                property_type: propertyFormData.propertyType?.toLowerCase(),
                city: selectedLocationAddress.city,
                state: selectedLocationAddress.state,
                country: selectedLocationAddress.country
            })
            const data = response?.data;
            setSeoFormData((prev) => ({
                ...prev,
                metaTitle: data?.meta_title || "",
                metaKeywords: data?.meta_keywords || "",
                metaDescription: data?.meta_description || "",
            }));
        } catch (error) {
            toast.error(error?.message)
            console.error("Failed to generate AI meta data:", error);
        } finally {
            setIsMetaDataGenerating(false);
        }
    }

    const handlePaymentSelectionClose = () => {
        setShowPaymentSelection(false);
        setShowPayAsYouGoModal(false);
        handlePendingPayAsYouGoDismiss();
    };

    return (
        <>
            <PropertyFormShell
                title={t("addProperty")}
                tabItems={tabItems}
                activeTab={activeTab}
                onTabChange={handleTabChange}
                showRoundedBorders={router?.asPath?.includes("/user")}
            >
                {activeTab === "categories" ? (
                    <>
                        <div className="flex flex-wrap items-center gap-4">
                            {isCategoriesLoading ? (
                                // Show skeletons when initially loading
                                Array.from({ length: 5 }).map((_, index) => (
                                    <CategorySkeleton key={index} />
                                ))
                            ) : (
                                // Show categories when loaded
                                categories.map((category) => (
                                    <div
                                        key={category.id}
                                        className={`flex items-center justify-center py-2 px-3 md:p-4 rounded-lg cursor-pointer transition-colors duration-500 primaryBackgroundBg gap-3 hover:primaryBg hover:text-white ${selectedCategory && selectedCategory?.id === category.id ? "!primaryBg text-white" : ""}`}
                                        onClick={() => handleSelectCategory(category)}
                                    >
                                        <div className="w-10 h-10 flex justify-center items-center bg-white rounded-full">
                                            <ImageWithPlaceholder src={category.image} alt={category.translated_name || category.category} className="w-7 h-7" />
                                        </div>
                                        <span className="text-lg font-semibold">{category?.translated_name || category?.category}</span>
                                    </div>
                                ))
                            )}
                        </div>
                        {/* Show loading indicators when fetching more */}
                        {isFetchingMore && (
                            <div className="flex flex-wrap items-center gap-4 mt-4">
                                {Array.from({ length: 3 }).map((_, index) => (
                                    <CategorySkeleton key={`more-${index}`} />
                                ))}
                            </div>
                        )}

                        {/* Load More Button and Loading Indicator */}
                        {!isCategoriesLoading && hasMoreCategories && (
                            <div className="flex justify-center mt-6">
                                <button
                                    onClick={handleLoadMoreCategories}
                                    className="border primaryColor primaryBorderColor px-4 py-2 rounded-md disabled:opacity-70"
                                    disabled={isFetchingMore}
                                >
                                    {isFetchingMore ? t("loading") : t("loadMore")}
                                </button>
                            </div>
                        )}


                    </>
                ) : (
                    <>
                        {activeTab === "propertyDetails" && (
                            <div className="flex flex-col gap-6 md:gap-14">
                                <PropertyDetailsTab
                                    selectedCategory={selectedCategory}
                                    handleRemoveCategory={handleRemoveCategory}
                                    handleUpdatePropertyForm={handleUpdatePropertyForm}
                                    propertyFormData={propertyFormData}
                                    handleCheckRequiredFields={handleCheckRequiredFields}
                                    selectedLanguage={selectedLanguage}
                                    setSelectedLanguage={setSelectedLanguage}
                                    languages={languages}
                                    translations={translations}
                                    handleMultiLangChange={handleMultiLangChange}
                                    handleGenerateAIDescription={handleGenerateAIDescription}
                                    isDescriptionGenerating={isDescriptionGenerating}
                                />
                            </div>
                        )}

                        {activeTab === "seoSettings" && (
                            <SEODetailsTab
                                showLoader={showLoader || isCheckingPackage}
                                handleSubmit={() => handleCheckRequiredFields("seoSettings", null)}
                                handleTabChange={handleTabChange}
                                seoFormData={seoFormData}
                                handleFillSeoFormData={handleFillSeoFormData}
                                handleGenerateAIMetaData={handleGenerateAIMetaData}
                                isMetaDataGenerating={isMetaDataGenerating}
                            />
                        )}

                        {activeTab === "facilities" && (
                            <FacilitiesComponent
                                formData={parameterFormData}
                                setFormData={setParameterFormData}
                                selectedCategory={selectedCategory}
                                categories={categories}
                                handleTabChange={handleTabChange}
                                handleCheckRequiredFields={handleCheckRequiredFields}
                            />
                        )}

                        {activeTab === "outdoorFacilities" && (
                            <OutdoorFacilitiesComponent
                                facilities={facilities}
                                handleTabChange={handleTabChange}
                                facilityDistances={facilityDistances}
                                handleDistanceChange={handleDistanceChange}
                            />
                        )}

                        {activeTab === "location" && (
                            <LocationComponent
                                selectedLocationAddress={selectedLocationAddress}
                                setSelectedLocationAddress={setSelectedLocationAddress}
                                handleLocationSelect={handleLocationSelect}
                                handleCheckRequiredFields={handleCheckRequiredFields}
                            />
                        )}

                        {activeTab === "imagesVideo" && (
                            <ImagesVideoTab
                                showLoader={showLoader}
                                handleCheckRequiredFields={handleCheckRequiredFields}
                                mediaFormData={mediaFormData}
                                setMediaFormData={setMediaFormData}
                                handleRemoveMedia={handleRemoveMedia}
                                handleRemoveGalleryImages={handleRemoveGalleryImages}
                                handleRemoveDocuments={handleRemoveDocuments}
                                isProperty={true}
                                handleTabChange={handleTabChange}
                                isCustomVideoUpload={isCustomVideoUpload}
                            />
                        )}
                    </>
                )}
            </PropertyFormShell>

            {/* Pay As You Go Modal */}
            <PayAsYouGoModal
                isOpen={showPayAsYouGoModal}
                onClose={handlePaymentSelectionClose}
                payAsYouGoData={payAsYouGoData}
                isProcessing={isPayAsYouGoProcessing}
                onContinuePayment={async () => {
                    if (isPayAsYouGoProcessing) return;
                    setIsPayAsYouGoProcessing(true);
                    setShowPayAsYouGoModal(false);
                    setPaymentReturnUrl(addPropertyReturnPath());
                    setShowPaymentSelection(true);
                    setIsPayAsYouGoProcessing(false);
                }}
                onViewMorePlans={async () => {
                    if (isPayAsYouGoProcessing) return;
                    setIsPayAsYouGoProcessing(true);
                    setShowPayAsYouGoModal(false);
                    setPaymentReturnUrl(addPropertyReturnPath());
                    router.push(isUserRoute ? `/user/subscription-plan?lang=${lang}` : `/agent/packages?lang=${lang}`);
                    setIsPayAsYouGoProcessing(false);
                }}
            />

            {/* Payment Selection Modal */}
            <PaymentSelectionModalWrapper
                payAsYouGoData={payAsYouGoData}
                paymentSettingsData={paymentSettingsData}
                showPaymentSelection={showPaymentSelection}
                setShowPaymentSelection={setShowPaymentSelection}
                paymentLoading={paymentLoading}
                setPaymentLoading={setPaymentLoading}
                router={router}
                websettings={websettings}
                onPaymentSelectionClose={handlePaymentSelectionClose}
                onPaymentSuccess={async () => {
                    setShowPaymentSelection(false);
                    setHasPendingPayAsYouGoSubmission(false);
                    toast.success(t("paymentSuccessfull"));
                    try {
                        const isAvailable = await checkPackageAvailable(PackageTypes.PROPERTY_LIST);
                        if (isAvailable) {
                            await handlePostProperty(null, { showSwal: true });
                        }
                    } catch (error) {
                        console.error("Error after payment:", error);
                    }
                }}
                onPaymentFailed={() => {
                    setShowPaymentSelection(false);
                    setHasPendingPayAsYouGoSubmission(false);
                }}
            />
        </>
    );
};

export default AddProperty;