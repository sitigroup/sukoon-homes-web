"use client"
import { useState, useEffect } from 'react'
import { useTranslation } from '@/components/context/TranslationContext'
import { Skeleton } from "@/components/ui/skeleton"
import { generateAIPropertyDescriptionApi, generateAIPropertyMetaDataApi, getCategoriesApi, postProjectApi, getPackagesApi, getPaymentSettingsApi } from '@/api/apiRoutes'
import ImageWithPlaceholder from '@/components/image-with-placeholder/ImageWithPlaceholder'
import toast from 'react-hot-toast'
import { generateSlug } from '@/utils/helperFunction'
import { useSelector } from 'react-redux'
import { useRouter } from 'next/router'
import LocationComponent from '@/components/reusable-components/add-property/LocationComponent'
import ImagesVideoTab from '@/components/reusable-components/add-project/ImagesVideoTab'
import SEODetailsTab from '@/components/reusable-components/add-property/SEODetailsTab'
import ProjectDetailsTab from '@/components/reusable-components/add-project/ProjectDetailsTab'
import FloorDetails from '@/components/reusable-components/add-project/FloorDetails'
import React from 'react'
import Swal from 'sweetalert2'
import successMark from '@/assets/SuccessTick.gif'
import { checkPackageAvailable } from '@/utils/checkPackages/checkPackage'
import { PackageTypes } from '@/utils/checkPackages/packageTypes'
import PayAsYouGoModal from '@/components/modal/PayAsYouGoModal'
import PaymentSelectionModalWrapper from '@/components/modal/PaymentSelectionModalWrapper'
import PropertyFormShell from '@/components/agent/property/PropertyFormShell'
import { setPaymentReturnUrl } from '@/utils/paymentReturn'

const AddProject = () => {
    const router = useRouter()
    const { lang } = router?.query

    const userData = useSelector(state => state.User?.data)
    const languages = useSelector(state => state.LanguageSettings?.languages) || []
    const activeLanguage = useSelector(state => state.LanguageSettings?.active_language)
    const defaultLanguage = useSelector(state => state.LanguageSettings?.default_language)
    const websettings = useSelector(state => state.WebSetting?.data)
    const currentLocation = useSelector((state) => state.location);

    const t = useTranslation()
    const [activeTab, setActiveTab] = useState("categories")
    const [showPayAsYouGoModal, setShowPayAsYouGoModal] = useState(false);
    const [isCheckingPackage, setIsCheckingPackage] = useState(false);
    const [payAsYouGoData, setPayAsYouGoData] = useState(null);
    const [isPayAsYouGoProcessing, setIsPayAsYouGoProcessing] = useState(false);
    const [hasPendingPayAsYouGoSubmission, setHasPendingPayAsYouGoSubmission] = useState(false);

    // Payment modal state
    const [paymentSettingsData, setPaymentSettingsData] = useState([]);
    const [showPaymentSelection, setShowPaymentSelection] = useState(false);
    const [paymentLoading, setPaymentLoading] = useState(false);

    const [isCategoriesLoading, setIsCategoriesLoading] = useState(true)
    const [isFetchingMore, setIsFetchingMore] = useState(false)
    const [categories, setCategories] = useState([])
    const [hasMoreCategories, setHasMoreCategories] = useState(false)
    const limit = 12
    const [offset, setOffset] = useState(0)
    const [showLoader, setShowLoader] = useState(false);
    const [isDescriptionGenerating, setIsDescriptionGenerating] = useState(false);
    const [isMetaDataGenerating, setIsMetaDataGenerating] = useState(false);

    const isCustomVideoUpload = websettings?.show_direct_video_upload === "1";

    // Find the active language object from the languages array
    const activeLanguageObj = React.useMemo(() => {
        if (!activeLanguage || !languages.length) return null;
        return languages.find(lang => lang.code === activeLanguage) || null;
    }, [activeLanguage, languages]);

    const [selectedLanguage, setSelectedLanguage] = useState(activeLanguageObj || activeLanguage || "English");
    const [translations, setTranslations] = useState([]);

    const isUserRoute = router?.asPath?.includes("/user/");
    const addProjectReturnPath = () =>
        isUserRoute ? `/user/add-project?lang=${lang}` : `/agent/add-project?lang=${lang}`;

    // Tab components
    const tabItems = [
        { id: "categories", label: t("categories") },
        { id: "projectDetails", label: t("projectDetails") },
        { id: "imagesVideo", label: t("imagesVideo") },
        { id: "location", label: t("location") },
        { id: "floorDetails", label: t("floorDetails") },
        { id: "seoSettings", label: t("seoSettings") },
    ]

    // Property Details Form State
    const [selectedCategory, setSelectedCategory] = useState("")

    // Project Details Form State
    const [projectFormData, setProjectFormData] = useState({
        projectType: "upcoming",
        projectTitle: "",
        projectSlug: "",
        projectDescription: "",
        isPremiumProject: false
    })

    // SEO Settings Form State
    const [seoFormData, setSeoFormData] = useState({
        metaTitle: "",
        metaKeywords: "",
        metaDescription: "",
        ogImage: null
    })

    // Floor Details Form State
    const [floorFormData, setFloorFormData] = useState([
        { floorTitle: '', floorImage: null }
    ]);

    // Combined Media Form State
    const [mediaFormData, setMediaFormData] = useState({
        titleImage: null,
        documents: [],
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
    const handleUpdateProjectForm = (e, customName, customValue) => {
        // For radio inputs and custom events where e.target might not have name/value
        if (customName && customValue !== undefined) {
            setProjectFormData(prev => ({
                ...prev,
                [customName]: customValue
            }));
            return;
        }

        // For standard form events (input, textarea)
        if (e && e.target) {
            const { name, value, type, checked } = e.target;

            // Handle title input for English language
            if (name === "title") {
                setProjectFormData(prev => ({
                    ...prev,
                    projectTitle: value,
                    projectSlug: generateSlug(value)
                }));
                return;
            }

            // Handle description input for English language
            if (name === "description") {
                setProjectFormData(prev => ({
                    ...prev,
                    projectDescription: value
                }));
                return;
            }

            if (name === "projectTitle") {
                setProjectFormData(prev => ({
                    ...prev,
                    [name]: value,
                    projectSlug: generateSlug(value)
                }));
                return;
            }
            // Handle checkbox/switch inputs
            if (type === 'checkbox') {
                setProjectFormData(prev => ({
                    ...prev,
                    [name]: checked
                }));
                return;
            }

            // Handle regular inputs
            setProjectFormData(prev => ({
                ...prev,
                [name]: value
            }));
        }
    }

    const handleFillSeoFormData = (e, customName, customValue) => {
        // If we have direct customName and customValue (from TagInput)
        if (customName && customValue !== undefined) {
            setSeoFormData(prev => ({
                ...prev,
                [customName]: customValue
            }))
            return
        }

        // For standard form events (input, textarea)
        if (e && e.target) {
            const { name, value } = e.target
            setSeoFormData(prev => ({
                ...prev,
                [name]: value
            }))
        }
    }

    const handleCheckRequiredFields = (currentTab, nextTab) => {
        let missingFields = false;
        switch (currentTab) {
            case "categories":
                if (!selectedCategory) {
                    toast.error(t("pleaseSelectCategory"));
                    missingFields = true;
                }
                break;

            case "projectDetails":
                if (!projectFormData.projectTitle) {
                    toast.error(t("projectTitleIsRequired"));
                    missingFields = true;
                    break;
                }

                if (!projectFormData.projectDescription) {
                    toast.error(t("projectDescriptionIsRequired"));
                    missingFields = true;
                    break;
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
                if (!mediaFormData.titleImage) {
                    toast.error(t("projectTitleImageIsRequired"));
                    missingFields = true;
                    break;
                }
                break;

            case "seoSettings":
                // If we're on the seoSettings tab and no errors were found, and nextTab is "submit",
                // check package availability before submitting
                if (!missingFields && nextTab === "submit") {
                    handleSubmitWithPackageCheck();
                    return;
                }
                break;

            default:
                break;
        }

        if (!missingFields) {
            if (nextTab === "submit") {
                handleSubmitWithPackageCheck();
            } else {
                setActiveTab(nextTab);
            }
        }
    };

    const handleFetchCategories = async (currentOffset = 0, isInitial = false) => {
        if (isInitial) {
            setIsCategoriesLoading(true)
            setOffset(0) // Reset offset when initializing
        } else {
            setIsFetchingMore(true)
        }

        try {
            const res = await getCategoriesApi({ limit, offset: currentOffset, passHasProperty: false })

            if (isInitial) {
                setCategories(res.data)
            } else {
                setCategories(prev => [...prev, ...res.data])
            }

            setHasMoreCategories(res.data.length >= limit)
        } catch (error) {
            console.error("Failed to fetch categories:", error)
        } finally {
            setIsCategoriesLoading(false)
            setIsFetchingMore(false)
        }
    }

    const handleLoadMoreCategories = async () => {
        if (!isFetchingMore && hasMoreCategories) {
            const newOffset = offset + limit
            setOffset(newOffset)
            await handleFetchCategories(newOffset, false)
        }
    };

    const handleTabChange = (value) => {
        const tabOrder = ["categories", "projectDetails", "imagesVideo", "location", "floorDetails", "seoSettings"];
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

    const handleSelectCategory = (category) => {
        // Set selected category and move to next tab directly.
        // Avoid immediate validation which can read stale state before setState commits.
        setSelectedCategory(category);
        setActiveTab("projectDetails");
        // Keep language selection consistent with tab changes
        setSelectedLanguage(activeLanguageObj || activeLanguage || "English");
    };

    const handleRemoveCategory = () => {
        setSelectedCategory(null);
        setActiveTab("categories");
    };

    const handleLocationSelect = (address) => {
        // Update the form field with the selected address from the Map component
        setSelectedLocationAddress(prev => ({
            ...prev,
            city: address.city || prev.city,
            state: address.state || prev.state,
            country: address.country || prev.country,
            formattedAddress: address.formattedAddress || prev.formattedAddress,
            latitude: address.latitude || address.lat || prev.latitude,
            longitude: address.longitude || address.lng || prev.longitude
        }));
    };

    // Only fetch categories on initial component mount
    useEffect(() => {
        if (activeTab === "categories" && categories.length === 0) {
            handleFetchCategories(0, true)
        } else if (categories?.length > 0) {
            handleFetchCategories(0, true)
        }
    }, [activeTab, activeLanguage]);

    useEffect(() => {
    }, [activeLanguage])

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

    // Category selection skeleton loader
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
            const isAvailable = await checkPackageAvailable(PackageTypes.PROJECT_LIST);
            if (!isAvailable) {
                // Fetch packages to get pay_as_you_go data
                let payAsYouGoList = [];
                let projectPayAsYouGo = [];
                try {
                    const packagesRes = await getPackagesApi();
                    payAsYouGoList = packagesRes?.pay_as_you_go || [];
                    projectPayAsYouGo = payAsYouGoList.find(
                        (item) => item.type === "project" && item.status === 1
                    );
                    setPayAsYouGoData(projectPayAsYouGo || []);
                } catch (err) {
                    console.error("Error fetching pay as you go data:", err);
                    setPayAsYouGoData([]);
                }

                const submitted = await handlePostProject({ showSwal: false });
                if (!submitted) {
                    return;
                }

                if (projectPayAsYouGo.length !== 0) {
                    setHasPendingPayAsYouGoSubmission(true);
                    setShowPayAsYouGoModal(true);
                } else {
                    setHasPendingPayAsYouGoSubmission(false);
                    setShowPayAsYouGoModal(false);
                    setPaymentReturnUrl(addProjectReturnPath());
                    router?.push(isUserRoute ? `/user/subscription-plan?lang=${lang}` : `/agent/packages?lang=${lang}`);
                }
                return;
            }
            // Package available — proceed to submit
            handlePostProject();
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
        toast.success(t("projectDraftPaymentReminder"));
        router.push(isUserRoute ? `/user/listings?tab=projects&lang=${lang}` : `/agent/dashboard?lang=${lang}`);
    };

    const handlePostProject = async (options = {}) => {
        const { showSwal = true } = options;
        try {
            setShowLoader(true);
            const plans = []; // Initialize an empty array for plans

            // Loop through floorFields and push each entry into plans array
            for (const field of floorFormData) {
                const title = field.floorTitle;
                const document = field.floorImage;

                // Loop through documents array to handle multiple images
                // for (const document of documents) {
                if (document) {
                    plans.push({
                        id: "",
                        title: title,
                        document: document,
                        // You may need to adjust these fields based on your data structure
                    });
                }
            }

            // Format translations for API
            const formattedTranslations = translations.map((translation, index) => ({
                translation_id: index,
                language_id: translation.language_id,
                title: translation.title || "",
                description: translation.description || ""
            }));

            const data = {
                category_id: selectedCategory.id,
                title: projectFormData.projectTitle,
                description: projectFormData.projectDescription,
                slug: projectFormData.projectSlug,
                type: projectFormData.projectType,

                // SEO Details
                meta_title: seoFormData.metaTitle,
                meta_description: seoFormData.metaDescription,
                meta_keywords: seoFormData.metaKeywords,
                meta_image: seoFormData.ogImage,
                // Location Details
                city: selectedLocationAddress.city,
                state: selectedLocationAddress.state,
                country: selectedLocationAddress.country,
                location: selectedLocationAddress.formattedAddress,
                manual_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                customer_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                latitude: selectedLocationAddress.latitude,
                longitude: selectedLocationAddress.longitude,
                state_id: selectedLocationAddress.state_id,
                city_id: selectedLocationAddress.city_id,
                area_id: selectedLocationAddress.area_id,
                sub_area_id: selectedLocationAddress.sub_area_id,
                area_name: selectedLocationAddress.area_name,
                sub_area_name: selectedLocationAddress.sub_area_name,
                detected_area_name: selectedLocationAddress.detected_area_name,
                detected_sub_area_name: selectedLocationAddress.detected_sub_area_name,
                area_listing_source: selectedLocationAddress.area_listing_source || "google",
                location_is_verified: selectedLocationAddress.location_is_verified || false,
                client_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                ...(isUserRoute ? { area_listing_user_portal: 1 } : {}),

                // Floor Details
                plans: plans,

                // Media Details
                image: mediaFormData.titleImage, // Main title image
                gallery_images: mediaFormData.galleryImages,
                documents: mediaFormData.documents,
                video_link: mediaFormData.videoLink,
                ...(mediaFormData.videoLink || mediaFormData.customVideo
                    ? { video_type: mediaFormData.videoType }
                    : {}),
                custom_video: mediaFormData.customVideo,

                // Translations
                translations: formattedTranslations,
                is_premium: projectFormData.isPremiumProject,
            };

            // Make the API call
            const response = await postProjectApi(data);

            if (!response?.error) {
                if (showSwal) {
                    await Swal.fire({
                        imageUrl: successMark.src,
                        imageWidth: 160,
                        imageHeight: 160,
                        title: t("projectSubmittedSuccess"),
                        text: !websettings?.auto_approve
                            ? websettings?.text_project_submission
                            : "",
                        allowOutsideClick: false,
                        showCancelButton: false,
                        customClass: {
                            confirmButton: "Swal-confirm-buttons",
                            cancelButton: "Swal-cancel-buttons",
                        },
                        confirmButtonText: t("viewProjects"),
                    });
                    router.push(router?.asPath?.includes("/user/") ? `/user/listings?tab=projects&lang=${lang}` : `/agent/projects?lang=${lang}`);
                }
                return true;
            } else {
                toast.error(t(response?.message) || t("somethingWentWrong"));
                return false;
            }
        } catch (error) {
            console.error("Error adding project:", error);
            toast.error(t(error?.response?.data?.message) || t(error?.message) || t("somethingWentWrong"));
            return false;
        } finally {
            setShowLoader(false);
        }
    };

    // Handler for generating Project Description using AI
    const handleGenerateAIDescription = async () => {
        try {
            setIsDescriptionGenerating(true);
            const response = await generateAIPropertyDescriptionApi({
                entity_type: "project",
                title: translations.find(tr => tr.language_id === selectedLanguage?.id)?.title || projectFormData.projectTitle || "",
                category_id: selectedCategory?.id,
                language_id: selectedLanguage?.id,
                project_type: projectFormData.projectType?.toLowerCase(),
                city: selectedLocationAddress.city,
                state: selectedLocationAddress.state,
                country: selectedLocationAddress.country
            });
            if (selectedLanguage?.code === defaultLanguage) {
                setProjectFormData((prev) => ({
                    ...prev,
                    projectDescription: response?.data?.description || "",
                }));
            } else {
                handleMultiLangChange(selectedLanguage, "description", response?.data?.description || "");
            }
        } catch (error) {
            console.error("AI Description Generation Error:", error);
            toast.error(error?.message);
        } finally {
            setIsDescriptionGenerating(false);
        }
    }

    // Handler to generate AI meta data
    const handleGenerateAIMetaData = async () => {
        try {
            setIsMetaDataGenerating(true);
            const response = await generateAIPropertyMetaDataApi({
                entity_type: "project",
                title: projectFormData.projectTitle || "",
                category_id: selectedCategory?.id,
                language_id: selectedLanguage?.id,
                project_type: projectFormData.projectType?.toLowerCase(),
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
        <PropertyFormShell
            title={t("addProject")}
            tabItems={tabItems}
            activeTab={activeTab}
            onTabChange={handleTabChange}
            showRoundedBorders={router?.asPath?.includes("/user")}
        >
            {activeTab === "categories" ? (
                <>
                    <div className="flex flex-wrap items-center gap-4">
                        {isCategoriesLoading ? (
                            Array.from({ length: 5 }).map((_, index) => (
                                <CategorySkeleton key={index} />
                            ))
                        ) : (
                            categories.map((category) => (
                                <div
                                    key={category.id}
                                    className={`flex items-center justify-center p-4 rounded-lg cursor-pointer transition-colors duration-500 primaryBackgroundBg gap-3 hover:primaryBg hover:text-white ${selectedCategory && selectedCategory?.id === category.id ? "!primaryBg text-white" : ""}`}
                                    onClick={() => handleSelectCategory(category)}
                                >
                                    <div className="w-10 h-10 flex justify-center items-center bg-white rounded-full">
                                        <ImageWithPlaceholder src={category.image} alt={category.translated_name || category.category} className="w-7 h-7" />
                                    </div>
                                    <span className="text-lg font-semibold">{category.translated_name || category?.category}</span>
                                </div>
                            ))
                        )}
                    </div>
                    {isFetchingMore && (
                        <div className="flex flex-wrap items-center gap-4 mt-4">
                            {Array.from({ length: 3 }).map((_, index) => (
                                <CategorySkeleton key={`more-${index}`} />
                            ))}
                        </div>
                    )}

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
                    {activeTab === "projectDetails" && (
                        <div className="flex flex-col gap-6 md:gap-14">
                            <ProjectDetailsTab
                                selectedCategory={selectedCategory}
                                handleRemoveCategory={handleRemoveCategory}
                                projectFormData={projectFormData}
                                handleUpdateProjectForm={handleUpdateProjectForm}
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

                    {activeTab === "imagesVideo" && (
                        <ImagesVideoTab
                            showLoader={showLoader}
                            handleCheckRequiredFields={handleCheckRequiredFields}
                            mediaFormData={mediaFormData}
                            setMediaFormData={setMediaFormData}
                            handleRemoveGalleryImages={handleRemoveGalleryImages}
                            handleRemoveDocuments={handleRemoveDocuments}
                            isCustomVideoUpload={isCustomVideoUpload}
                        />
                    )}

                    {activeTab === "floorDetails" && (
                        <FloorDetails
                            floorFormData={floorFormData}
                            setFloorFormData={setFloorFormData}
                            handleCheckRequiredFields={handleCheckRequiredFields}
                        />
                    )}

                    {activeTab === "location" && (
                        <LocationComponent
                            selectedLocationAddress={selectedLocationAddress}
                            setSelectedLocationAddress={setSelectedLocationAddress}
                            handleLocationSelect={handleLocationSelect}
                            handleCheckRequiredFields={handleCheckRequiredFields}
                            isProperty={false}
                        />
                    )}

                    {activeTab === "seoSettings" && (
                        <SEODetailsTab
                            showLoader={showLoader || isCheckingPackage}
                            handleSubmit={() => handleCheckRequiredFields("seoSettings", "submit")}
                            handleTabChange={handleTabChange}
                            seoFormData={seoFormData}
                            handleFillSeoFormData={handleFillSeoFormData}
                            isProperty={false}
                            isEditing={false}
                            handleGenerateAIMetaData={handleGenerateAIMetaData}
                            isMetaDataGenerating={isMetaDataGenerating}
                        />
                    )}
                </>
            )}

            {/* Draft Found Dialog */}
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
                    setPaymentReturnUrl(addProjectReturnPath());
                    setShowPaymentSelection(true);
                    setIsPayAsYouGoProcessing(false);
                }}
                onViewMorePlans={async () => {
                    if (isPayAsYouGoProcessing) return;
                    setIsPayAsYouGoProcessing(true);
                    setShowPayAsYouGoModal(false);
                    setPaymentReturnUrl(addProjectReturnPath());
                    router.push(isUserRoute ? `/user/subscription-plan?lang=${lang}` : `/agent/packages?lang=${lang}`);
                    setIsPayAsYouGoProcessing(false);
                }}
                isProject={true}
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
                        const isAvailable = await checkPackageAvailable(PackageTypes.PROJECT_LIST);
                        if (isAvailable) {
                            await handlePostProject({ showSwal: true });
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
        </PropertyFormShell>
    )
}

export default AddProject