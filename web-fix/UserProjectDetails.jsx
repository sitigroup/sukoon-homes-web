"use client";
import { useCallback, useEffect, useState } from "react";
import { useRouter } from "next/router";
import {
  getUserProjectsApi
} from "@/api/apiRoutes";
import ProjectDetailsSkeleton from "../skeletons/project-skeletons/ProjectDetailsSkeleton";
import NoDataFound from "../no-data-found/NoDataFound";
import { useTranslation } from "../context/TranslationContext";
import toast from "react-hot-toast";
import Video from "../property-detail/Video";
import PropertyAddress from "../property-detail/PropertyAddress";
import FeaturesAmenities from "../property-detail/FeatureAmenities";
import AboutProperty from "../property-detail/AboutProperty";
import { useDispatch, useSelector } from "react-redux";
import FloorAccordion from "../reusable-components/FloorAccordion";
import FileAttachments from "./FileAttachments";
import ChangeStatus from "../reusable-components/ChangeStatus";
import FeatureCard from "../reusable-components/FeatureCard";
import NewBreadcrumb from "../breadcrumb/NewBreadCrumb";
import ShareDialog from "../reusable-components/ShareDialog";
import ProjectInfoBanner from "./ProjectInfoBanner";
import ProjectGallery from "./ProjectGallery";
import HomeNewSectionTwo from "../homepagesections/HomeNewSectionTwo";
import LightBox from "../property-detail/LightBox";
import { useIsMobile } from "@/hooks/use-mobile";
import MobileBottomSheet from "../mobile-bottom-sheet/MobileBottomSheet";
import Swal from "sweetalert2";
import { setRole } from "@/redux/slices/authSlice";
import { openDirectionsForListing } from "@/utils/openGoogleMapsDirections";

const UserProjectDetails = () => {
  const router = useRouter();
  const { slug, lang } = router.query;
  const t = useTranslation();
  const dispatch = useDispatch();

  const [projectDetails, setProjectDetails] = useState(null);
  const [projectStatus, setProjectStatus] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [similarProjects, setSimilarProjects] = useState([]);
  const [showChat, setShowChat] = useState(false);
  const [showMap, setShowMap] = useState(false);
  const [showVideo, setShowVideo] = useState(false);
  const [showGallery, setShowGallery] = useState(false);
  const [interested, setInterested] = useState(false);
  const [isReported, setIsReported] = useState(false);
  const [isMessagingSupported, setIsMessagingSupported] = useState(false);
  const [notificationPermissionGranted, setNotificationPermissionGranted] =
    useState(false);
  const [imageURL, setImageURL] = useState(null);
  const [playing, setPlaying] = useState(false);
  const [showThumbnail, setShowThumbnail] = useState(false);
  const [manualPause, setManualPause] = useState(false);
  const [seekPosition, setSeekPosition] = useState(0);
  const [isShareModalOpen, setIsShareModalOpen] = useState(false);
  // Lightbox states
  const [viewerIsOpen, setViewerIsOpen] = useState(false);
  const [currentImage, setCurrentImage] = useState(0);
  const currentUrl = `${process.env.NEXT_PUBLIC_WEB_URL}/project-details/${slug}?share=true&lang=${lang}`;

  const isMobile = useIsMobile();
  const isShare = router?.query?.share === "true";

  const webSettings = useSelector((state) => state.WebSetting?.data);
  const DistanceSymbol = webSettings?.distance_option;
  // const isPremiumUser = webSettings && webSettings?.is_premium;
  // const isPremiumProperty = projectDetails && !projectDetails?.is_premium;
  const PlaceHolderImg = webSettings?.web_placeholder_logo;
  const showExactLocation = projectDetails?.can_view_exact_location === true || projectDetails?.can_view_exact_location === 1 || projectDetails?.can_view_exact_location === "1";
  const userCurrentId = useSelector((state) => state.User?.data?.id);
  const language = useSelector((state) => state.LanguageSettings?.active_language);

  // Function to open lightbox
  const openLightbox = (index) => {
    setCurrentImage(index);
    setViewerIsOpen(true);
  };

  const fetchProjectDetails = useCallback(async () => {
    setLoading(true);
    try {
      const response = await getUserProjectsApi({
        slug_id: slug,
        get_similar: "1",
        type: " ",
        request_status: " "
      });

      if (response && !response.error) {
        setProjectDetails(response.data);
        if (response.similar_projects && response.similar_projects.length > 0) {
          setSimilarProjects(response.similar_projects);
        }
      } else {
        setError(response?.message || "Failed to fetch project details");
        toast.error(t(response?.message) || "Failed to fetch project details");
      }
    } catch (error) {
      console.error("Error fetching project details:", error);
      if (
        error?.key === "requiredAgentRole" ||
        error?.code === 403 ||
        error?.message === "Unauthorized. Active role must be agent."
      ) {
        await showAgentSwitchSwal();
        return;
      }
      setError(error?.message || "Something went wrong");
      toast.error(t(error?.message) || "Something went wrong");
    } finally {
      setLoading(false);
    }
  }, [slug, language]);

  useEffect(() => {
    if (slug) {
      fetchProjectDetails();
    }
  }, [slug, fetchProjectDetails]);

  useEffect(() => {
    if (projectDetails && projectDetails?.three_d_image) {
      setImageURL(projectDetails?.three_d_image); // Set Panorama Image
    }
  }, [projectDetails]);

  const showAgentSwitchSwal = async () => {
    return Swal.fire({
      title: t("agentSwitchSwalTitle"),
      text: t("agentSwitchSwalTextProject") || t("agentSwitchSwalText"),
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
        router?.push(`/agent/projects?lang=${lang}`);
        return true;
      } else {
        router?.push(`/project-details/${slug}?lang=${lang}`);
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

  const videoLink = projectDetails && projectDetails.video_link;

  const videoId = videoLink
    ? videoLink.includes("youtu.be")
      ? videoLink.split("/").pop().split("?")[0]
      : (videoLink.split("v=")[1]?.split("&")[0] ?? null)
    : null;

  const backgroundImageUrl = videoId
    ? `https://img.youtube.com/vi/${videoId}/sddefault.jpg`
    : PlaceHolderImg;

  const handleShowMap = (e) => {
    e.preventDefault();
    setShowMap(true);
  };

  const handleCheckPremiumUserAgent = (e) => {
    e.preventDefault();
    router.push(
      `/agent-details/${projectDetails?.customer?.slug_id}?lang=${lang}`,
    );
  };

  const galleryPhotos = projectDetails?.gallary_images;
  // Property Address Details Section
  const details = showExactLocation
    ? [
        { label: "Google Address", value: projectDetails?.area_listing?.full_address || projectDetails?.location },
        { label: "Customer Address", value: projectDetails?.area_listing?.manual_address },
        { label: "Area", value: projectDetails?.area_listing?.area_name },
        { label: "Sub Area", value: projectDetails?.area_listing?.sub_area_name },
        { label: t("country"), value: projectDetails?.country },
        { label: t("city"), value: projectDetails?.city },
        { label: t("zipCode"), value: projectDetails?.zip_code },
        { label: t("state"), value: projectDetails?.state },
      ]
    : [
        { label: t("address"), value: projectDetails?.location },
        { label: t("country"), value: projectDetails?.country },
        { label: t("city"), value: projectDetails?.city },
        { label: t("zipCode"), value: projectDetails?.zip_code },
        { label: t("state"), value: projectDetails?.state },
      ];
  const handleStatusChange = (newStatus) => {
    setProjectStatus(newStatus);
  };


  const handleOpenGoogleMap = () => {
    openDirectionsForListing(projectDetails);
  };

  // Show skeleton during loading
  if (loading) {
    return <ProjectDetailsSkeleton />;
  }

  // Show error state if there's an error or no data
  if (error || !projectDetails) {
    return (
      <div>
        <NewBreadcrumb
          items={[
            {
              label: projectDetails?.title || slug,
              href: `/my-project/${slug}`,
            },
          ]}
        />
        <div className="container mx-auto px-2 py-10">
          <NoDataFound />
        </div>
      </div>
    );
  }

  return (
    <section>
      <div className="primaryBackgroundBg">
        <div className="container mx-auto px-3 pb-8">
          {/* Main Content */}
          <NewBreadcrumb
            items={[
              {
                label: projectDetails?.title,
                href: `/my-project/${slug}`,
              },
            ]}
            setIsShareModalOpen={setIsShareModalOpen}
            layout="reverse"
          />
          {/* Project Info Banner */}
          {projectDetails && (
            <ProjectInfoBanner projectDetails={projectDetails} />
          )}

          <div className="grid grid-cols-12 items-center justify-center gap-3">
            <div className="col-span-12">
              {/* Property Gallery */}
              {galleryPhotos && (
                <ProjectGallery
                  galleryPhotos={galleryPhotos}
                  titleImage={projectDetails?.image}
                  blurDataURL={projectDetails?.low_quality_title_image}
                  PlaceholderImage={PlaceHolderImg}
                  isProject={true}
                  onImageClick={openLightbox}
                  videoLink={projectDetails?.video_link}
                  videoType={projectDetails?.video_type}
                />
              )}
            </div>
          </div>
        </div>
      </div>
      <div className="bg-white">
        <div className="container mx-auto px-3 pt-8">
          <div className="grid grid-cols-12 gap-3">
            {/* Project Details */}
            <div className="col-span-12 h-full w-full rounded-2xl lg:col-span-9">
              {/* About Property */}
              {projectDetails && (projectDetails?.translated_description || projectDetails?.description) && (
                <AboutProperty description={projectDetails?.translated_description || projectDetails?.description} isProject={true} />
              )}

              {/* Features & Amenities */}
              <FeaturesAmenities
                data={projectDetails}
                DistanceSymbol={DistanceSymbol}
              />

              {/* Property Address */}
              {projectDetails &&
                projectDetails?.latitude &&
                projectDetails?.longitude && (
                  <PropertyAddress
                    latitude={projectDetails?.latitude}
                    longitude={projectDetails?.longitude}
                    handleShowMap={handleShowMap}
                    showMap={showMap}
                    details={details}
                    isProject={true}
                    handleOpenGoogleMap={handleOpenGoogleMap}
                    showExactLocation={showExactLocation}
                  />
                )}

              {/* 360degree Virtual Tour */}
              {imageURL ? (
                <div className="cardBg newBorder mb-5 flex flex-col rounded-2xl">
                  <div className="blackTextColor border-b p-5 text-base font-bold md:text-xl">
                    {t("virtualTour")}
                  </div>
                  <div className="flex h-[500px] justify-center rounded p-5">
                    <div id="panorama"></div>
                  </div>
                </div>
              ) : null}

              {/* Video */}
              {/* {projectDetails && projectDetails?.video_link && (
                <Video
                  bgImageUrl={backgroundImageUrl}
                  videoLink={projectDetails?.video_link}
                />
              )} */}

              {/* Floor Plans */}
              {projectDetails && projectDetails?.plans && (
                <FloorAccordion plans={projectDetails?.plans} />
              )}

              {/* Documents/Attachments */}
              {projectDetails &&
                projectDetails?.documents &&
                projectDetails.documents.length > 0 && (
                  <FileAttachments
                    files={projectDetails.documents}
                    projectCategory={projectDetails.category?.translated_name || projectDetails.category?.category}
                  />
                )}
            </div>

            {/* Sidebar */}
            <div className="col-span-12 h-full w-full lg:col-span-3">
              {/* User Project Management Controls */}
              {projectDetails &&
                projectDetails?.request_status === "approved" && (
                  <ChangeStatus
                    type="project"
                    id={projectDetails?.id}
                    initialStatus={
                      projectDetails?.status === 0 ? "Deactive" : "Active"
                    }
                    fetchDetails={fetchProjectDetails}
                    onStatusChange={handleStatusChange}
                  />
                )}
              {projectDetails &&
                projectDetails?.request_status === "approved" &&
                projectDetails?.is_feature_available && (
                  <FeatureCard
                    propertyId={projectDetails?.id}
                    isProject={true}
                    handleRefresh={fetchProjectDetails}
                  />
                )}
            </div>
          </div>
        </div>
      </div>
      <div className="primaryBackgroundBg">
        <div className="container mx-auto px-3 py-8">
          {/* Similar Projects */}
          {similarProjects && similarProjects.length > 0 && (
            <HomeNewSectionTwo
              data={similarProjects}
              type="similar_projects_section"
              title={t("upcomingProjects")}
              name={"similarProjects"}
            />
          )}
        </div>
      </div>
      {isShareModalOpen && (
        <ShareDialog
          title={t("shareProjectTitle")}
          subtitle={t("shareProjectSubtitle")}
          open={isShareModalOpen}
          onOpenChange={() => setIsShareModalOpen(false)}
          pageUrl={currentUrl}
          slug={slug}
        />
      )}

      {/* LightBox Component */}
      <LightBox
        photos={projectDetails?.gallary_images || []}
        viewerIsOpen={viewerIsOpen}
        currentImage={currentImage}
        onClose={() => setViewerIsOpen(false)}
        title_image={projectDetails?.image}
        setCurrentImage={setCurrentImage}
        isProject={true}
      />
      {isMobile && isShare && <MobileBottomSheet isShare={true} />}
    </section>
  );
};

export default UserProjectDetails;
