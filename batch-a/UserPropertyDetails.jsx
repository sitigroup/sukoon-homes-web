import { useEffect, useState } from "react";
import { PropertyDetailSkeleton } from "../skeletons/property-skeletons";
import { useRouter } from "next/router";
import ChangeStatus from "../reusable-components/ChangeStatus";
import PropertyGallery from "./PropertyGallery";
import AboutProperty from "./AboutProperty";
import MortgageLoanCalculator from "./MortgageLoanCalculator";
import PropertyAddress from "./PropertyAddress";
import FeaturesAmenities from "./FeatureAmenities";
import PropertyInfoBanner from "./PropertyInfoBanner";
import LocationInsightsGroup from "./LocationInsightsGroup";
import { useDispatch, useSelector } from "react-redux";
import { useTranslation } from "../context/TranslationContext";
import FileAttachments from "../project-details/FileAttachments";
import { getAdBannerApi, getAddedPropertiesApi } from "@/api/apiRoutes";
import ChangePropertyType from "./ChangePropertyType";
import FeatureCard from "../reusable-components/FeatureCard";
import NewBreadCrumb from "../breadcrumb/NewBreadCrumb";
import SimilarPropertySlider from "./SimilarPropertySlider";
import LightBox from "./LightBox";
import { useQuery } from "@tanstack/react-query";
import ImageWithPlaceholder from "../image-with-placeholder/ImageWithPlaceholder";
import Swal from "sweetalert2";
import { setRole } from "@/redux/slices/authSlice";

const UserPropertyDetails = () => {
  const t = useTranslation();
  const router = useRouter();
  const dispatch = useDispatch();
  const { slug, lang } = router.query;

  const [propertyDetails, setPropertyDetails] = useState(null);
  const [similarProperties, setSimilarProperties] = useState([]);
  const [imageURL, setImageURL] = useState(false);
  const [showMap, setShowMap] = useState(false);
  const [propertyStatus, setPropertyStatus] = useState("Active");
  const [isStatusUpdating, setIsStatusUpdating] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  // Lightbox states
  const [viewerIsOpen, setViewerIsOpen] = useState(false);
  const [currentImage, setCurrentImage] = useState(0);

  const webSettings = useSelector((state) => state.WebSetting?.data);
  const userData = useSelector((state) => state.User?.data);
  const DistanceSymbol = webSettings?.distance_option;
  const PlaceHolderImg = webSettings?.web_placeholder_logo;
  const galleryPhotos = propertyDetails?.gallery;

  const showExactLocation = propertyDetails?.can_view_exact_location === true || propertyDetails?.can_view_exact_location === 1 || propertyDetails?.can_view_exact_location === "1";
  const language = useSelector((state) => state.LanguageSettings?.active_language);
  const [isMessagingSupported, setIsMessagingSupported] = useState(false);
  const [notificationPermissionGranted, setNotificationPermissionGranted] =
    useState(false);

  const [isReportModal, setIsReportModal] = useState(false);
  const [interested, setInterested] = useState(false);
  const [isReported, setIsReported] = useState(false);
  const [showChat, setShowChat] = useState(true);
  const [chatData, setChatData] = useState({
    property_id: "",
    title: "",
    title_image: "",
    user_id: "",
    name: "",
    profile: "",
  });
  const isPremiumUser = userData && userData?.is_premium;
  const isPremiumProperty = propertyDetails && propertyDetails?.is_premium;

  // Function to open lightbox
  const openLightbox = (index) => {
    setCurrentImage(index);
    setViewerIsOpen(true);
  };

  useEffect(() => {
    if (slug && slug !== "") {
      getPropertyDetailsBySlug();
    }
  }, [slug, language]);

  const getPropertyDetailsBySlug = async () => {
    try {
      setIsLoading(true);
      const response = await getAddedPropertiesApi({ slug_id: slug, property_type: " ", request_status: " " });
      setPropertyDetails(response?.data?.[0]);
      if (response?.data?.[0]?.status) {
        setPropertyStatus(response?.data?.[0]?.status);
      }
      setImageURL(response?.data?.[0]?.three_d_image);
      setSimilarProperties(response?.similiar_properties);
    } catch (error) {
      console.error("error", error);
      if (
        error?.key === "requiredAgentRole" ||
        error?.code === 403 ||
        error?.message === "Unauthorized. Active role must be agent."
      ) {
        await showAgentSwitchSwal();
        return;
      }
    } finally {
      setIsLoading(false);
    }
  };

  // Property Address Details Section
  const details = showExactLocation
    ? [
        { label: "Google Address", value: propertyDetails?.area_listing?.full_address || propertyDetails?.address },
        { label: "Customer Address", value: propertyDetails?.area_listing?.manual_address || propertyDetails?.client_address },
        { label: "Area", value: propertyDetails?.area_listing?.area_name },
        { label: "Sub Area", value: propertyDetails?.area_listing?.sub_area_name },
        { label: t("country"), value: propertyDetails?.country },
        { label: t("city"), value: propertyDetails?.city },
        { label: t("zipCode"), value: propertyDetails?.zip_code },
        { label: t("state"), value: propertyDetails?.state },
      ]
    : [
        { label: t("address"), value: propertyDetails?.address },
        { label: t("country"), value: propertyDetails?.country },
        { label: t("city"), value: propertyDetails?.city },
        { label: t("zipCode"), value: propertyDetails?.zip_code },
        { label: t("state"), value: propertyDetails?.state },
      ];

  useEffect(() => {
    if (propertyDetails && propertyDetails?.three_d_image) {
      setImageURL(propertyDetails?.three_d_image); // Set Panorama Image
    }
    if (propertyDetails && userData?.id === propertyDetails?.added_by && propertyDetails?.role_context === "agent") {
      showAgentSwitchSwal();
    }
  }, [propertyDetails]);

  const showAgentSwitchSwal = async () => {
    return Swal.fire({
      title: t("agentSwitchSwalTitle"),
      text: t("agentSwitchSwalText"),
      icon: "warning",
      showCancelButton: true,
      customClass: {
        confirmButton: "Swal-confirm-buttons",
        cancelButton: "Swal-cancel-buttons",
      },
      confirmButtonText: t("yes_switch"),
      cancelButtonText: t("no"),

    }).then((result) => {
      if (result.isConfirmed) {
        dispatch(setRole({ data: "agent" }));
        router?.push(`/agent/properties?lang=${lang}`);
        return true;
      } else {
        router?.push(`/?lang=${lang}`);
        return false;
      }
    })
  }

  useEffect(() => {
    if (imageURL) {
      const initializePanorama = () => {
        const panoramaElement = document.getElementById("panorama");
        if (panoramaElement) {
          pannellum.viewer("panorama", {
            type: "equirectangular",
            panorama: imageURL,
            autoLoad: true,
          });
        } else {
          console.error("Panorama element not found");
        }
      };

      setTimeout(initializePanorama, 3000); // Slight delay to ensure the element is rendered
    }
  }, [imageURL]);

  const handleShowMap = () => {
    setShowMap(true);
  };

  const videoLink = propertyDetails && propertyDetails.video_link;

  const videoId = videoLink
    ? videoLink.includes("youtu.be")
      ? videoLink.split("/").pop().split("?")[0]
      : (videoLink.split("v=")[1]?.split("&")[0] ?? null)
    : null;

  const backgroundImageUrl = videoId
    ? `https://img.youtube.com/vi/${videoId}/sddefault.jpg`
    : PlaceHolderImg;

  const handleStatusChange = (newStatus) => {
    setPropertyStatus(newStatus);
  };


  const openDirections = (latitude, longitude) => {
    const lat = Number(latitude);
    const lng = Number(longitude);
    if (!Number.isFinite(lat) || !Number.isFinite(lng) || (lat === 0 && lng === 0)) {
      return;
    }
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, "_blank", "noopener,noreferrer");
  };
  const handleOpenGoogleMap = () => {
    if (isPremiumProperty) {
      if (isPremiumUser) {
        openDirections(propertyDetails?.latitude, propertyDetails?.longitude);
      } else {
        Swal.fire({
          title: t("opps"),
          text: t("itsPrivatePrperty"),
          icon: "warning",
          allowOutsideClick: true,
          showCancelButton: false,
          customClass: {
            confirmButton: "Swal-confirm-buttons",
            cancelButton: "Swal-cancel-buttons",
          },
        }).then((result) => {
          if (result.isConfirmed) {
            router.push(`/subscription-plan?lang=${lang}`);
          }
        });
      }
    } else {
      openDirections(propertyDetails?.latitude, propertyDetails?.longitude);
    }
  };

  const fetchAdBanners = async () => {
    try {
      const response = await getAdBannerApi({
        page: "property_detail",
        platform: "web"
      })
      return response?.data ?? [];
    } catch (error) {
      console.error("Error fetching ad banners:", error);
      return [];
    }
  }

  const userPropertyAdBanners = useQuery({
    queryKey: ['userPropertyAdBanners', lang],
    queryFn: fetchAdBanners,
    staleTime: 0
  });

  const aboveBreadCrumbAdBanner = userPropertyAdBanners?.data?.find((banner) => banner.placement === "above_breadcrumb");
  const belowMortgageAdBanner = userPropertyAdBanners?.data?.find((banner) => banner.placement === "sidebar_below_mortgage_loan_calculator");
  const aboveSimilarPropertiesAdBanner = userPropertyAdBanners?.data?.find((banner) => banner.placement === "above_footer");


  if (isLoading) {
    return <PropertyDetailSkeleton />;
  }

  return (
    <section>
      <div className="primaryBackgroundBg">
        <div className="container mx-auto px-3 pb-8">

          {aboveBreadCrumbAdBanner && (
            <div className="pt-12"
              onClick={() => {
                if (aboveBreadCrumbAdBanner?.external_link_url) {
                  window.open(aboveBreadCrumbAdBanner?.external_link_url, '_blank');
                } else if (aboveBreadCrumbAdBanner?.property?.slug_id) {
                  router.push(`/property-details/${aboveBreadCrumbAdBanner?.property?.slug_id}/?lang=${lang}`);
                }
              }}
            >
              <ImageWithPlaceholder
                src={aboveBreadCrumbAdBanner?.image}
                alt="Ad Banner"
                width={1920}
                height={350}
                className={`w-full aspect-[1920/350] object-cover rounded-lg lg:rounded-2xl ${aboveBreadCrumbAdBanner?.external_link_url || aboveBreadCrumbAdBanner?.property?.slug_id ? 'cursor-pointer' : ''}`}
              />
            </div>
          )}

          {/* Main Content */}
          <NewBreadCrumb
            title={t("myProperty")}
            items={[
              {
                label: propertyDetails?.slug_id,
                href: `/my-property/${propertyDetails?.slug_id}`,
              },
            ]}
          />
          <div className="grid grid-cols-12 items-center justify-center gap-3">
            <div className="col-span-12">
              {/* Property Gallery */}
              {galleryPhotos && (
                <PropertyGallery
                  galleryPhotos={galleryPhotos}
                  titleImage={propertyDetails?.title_image}
                  blurDataURL={propertyDetails?.low_quality_title_image}
                  PlaceholderImage={PlaceHolderImg}
                  onImageClick={openLightbox}
                  videoLink={propertyDetails?.video_link}
                  videoType={propertyDetails?.video_type}
                />
              )}
            </div>
          </div>

          {propertyDetails && <PropertyInfoBanner property={propertyDetails} />}
        </div>
      </div>

      <div className="bg-white">
        <div className="container mx-auto px-3 pt-8">
          <div className="grid grid-cols-12 gap-3">
            {/* Property Gallery */}
            <div className={`col-span-12 h-full w-full rounded-lg ${propertyDetails?.request_status === "draft" || propertyDetails?.request_status === "pending" ? 'lg:col-span-12' : 'lg:col-span-9'}`}>
              {/* About Property */}
              {propertyDetails && propertyDetails?.description && (
                <AboutProperty description={propertyDetails?.translated_description || propertyDetails?.description} />
              )}

              <FeaturesAmenities
                data={propertyDetails}
                DistanceSymbol={DistanceSymbol}
                themeEnabled={webSettings?.svg_clr === "1"}
                hideOutdoor
              />

              <LocationInsightsGroup
                propertyDetails={propertyDetails}
                DistanceSymbol={DistanceSymbol}
                themeEnabled={webSettings?.svg_clr === "1"}
              />

              {/* Property Address */}
              {propertyDetails &&
                propertyDetails?.latitude &&
                propertyDetails?.longitude && (
                  <PropertyAddress
                    latitude={propertyDetails?.latitude}
                    longitude={propertyDetails?.longitude}
                    handleShowMap={handleShowMap}
                    isPremiumProperty={isPremiumProperty}
                    isPremiumUser={isPremiumUser}
                    details={details}
                    showMap={showMap}
                    handleOpenGoogleMap={handleOpenGoogleMap}
                    showExactLocation={showExactLocation}
                  />
                )}

              {/* 360degree Virtual Tour */}
              {imageURL ? (
                <div className="cardBg newBorder mb-5 flex flex-col rounded-lg md:rounded-2xl">
                  <div className="blackTextColor border-b p-5 text-base font-bold md:text-xl">
                    {t("virtualTour")}
                  </div>
                  <div className="flex h-[500px] justify-center rounded p-5">
                    <div id="panorama"></div>
                  </div>
                </div>
              ) : null}

              {propertyDetails &&
                propertyDetails?.documents &&
                propertyDetails.documents.length > 0 && (
                  <FileAttachments
                    files={propertyDetails.documents}
                    projectCategory={propertyDetails.category?.category}
                    isProperty={true}
                  />
                )}
            </div>

            {/* Sidebar */}
            <div className="col-span-12 h-full w-full lg:col-span-3">
              {/* Change Property Status */}
              {propertyDetails?.request_status === "approved" && (
                <ChangeStatus
                  type="property"
                  id={propertyDetails?.id}
                  initialStatus={
                    propertyDetails?.status === 0 ? "Deactive" : "Active"
                  }
                  onStatusChange={handleStatusChange}
                  fetchDetails={getPropertyDetailsBySlug}
                />
              )}

              {/* Feature Property Card */}
              {propertyDetails &&
                propertyDetails?.request_status === "approved" &&
                propertyDetails?.is_feature_available && (
                  <FeatureCard
                    propertyId={propertyDetails?.id}
                    handleRefresh={getPropertyDetailsBySlug}
                  />
                )}

              {/* Mortgage Loan Calculator */}
              {/* {propertyDetails?.property_type === "sell" && propertyDetails?.request_status === "approved" && (
                <div className="mb-5">
                  <MortgageLoanCalculator propertyDetails={propertyDetails} />
                </div>
              )} */}

              {/* Change Property Type */}
              {propertyDetails &&
                propertyDetails?.request_status === "approved" &&
                propertyDetails?.status === 1 &&
                propertyDetails?.property_type !== "sold" && (
                  <ChangePropertyType
                    propertyId={propertyDetails?.id}
                    propertyType={propertyDetails?.property_type}
                    onStatusChange={getPropertyDetailsBySlug}
                  />
                )}

              {belowMortgageAdBanner && (
                <div className="mt-6"
                  onClick={() => {
                    if (belowMortgageAdBanner?.external_link_url) {
                      window.open(belowMortgageAdBanner?.external_link_url, '_blank');
                    } else if (belowMortgageAdBanner?.property?.slug_id) {
                      router.push(`/property-details/${belowMortgageAdBanner?.property?.slug_id}/?lang=${lang}`);
                    }
                  }}
                >

                  <ImageWithPlaceholder
                    src={belowMortgageAdBanner?.image}
                    alt="Ad Banner"
                    width={387}
                    height={587}
                    className={`w-full aspect-[387/587] object-cover rounded-2xl ${belowMortgageAdBanner?.external_link_url || belowMortgageAdBanner?.property?.slug_id ? 'cursor-pointer' : ''}`}
                  />
                </div>
              )}
            </div>
          </div>
          {aboveSimilarPropertiesAdBanner && (
            <div className="mt-4 mb-10 lg:mb-12"
              onClick={() => {
                if (aboveSimilarPropertiesAdBanner?.external_link_url) {
                  window.open(aboveSimilarPropertiesAdBanner?.external_link_url, '_blank');
                } else if (aboveSimilarPropertiesAdBanner?.property?.slug_id) {
                  router.push(`/property-details/${aboveSimilarPropertiesAdBanner?.property?.slug_id}/?lang=${lang}`);
                }
              }}
            >
              <ImageWithPlaceholder
                src={aboveSimilarPropertiesAdBanner?.image}
                alt="Ad Below Breadcrumb"
                width={1920}
                height={350}
                className={`w-full aspect-[1920/350] object-cover rounded-lg lg:rounded-2xl ${aboveSimilarPropertiesAdBanner?.external_link_url || aboveSimilarPropertiesAdBanner?.property?.slug_id ? 'cursor-pointer' : ''}`}
              />
            </div>
          )}
        </div>
      </div>

      <div className="primaryBackgroundBg">
        <div className="container mx-auto px-3 py-8">
          {/* Similar Properties */}
          <SimilarPropertySlider
            data={similarProperties}
            isLoading={false}
            isUserProperty={true}
            currentPropertyId={propertyDetails?.id}
          />
        </div>
      </div>

      {/* LightBox Component */}
      <LightBox
        photos={galleryPhotos}
        viewerIsOpen={viewerIsOpen}
        currentImage={currentImage}
        onClose={() => setViewerIsOpen(false)}
        title_image={propertyDetails?.title_image}
        setCurrentImage={setCurrentImage}
      />
    </section>
  );
};

export default UserPropertyDetails;
