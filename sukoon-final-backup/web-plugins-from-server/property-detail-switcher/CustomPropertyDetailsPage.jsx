import { useEffect, useMemo, useState, useCallback } from "react";
import { useRouter } from "next/router";
import Link from "next/link";
import { useSelector } from "react-redux";
import { useQuery } from "@tanstack/react-query";
import { BiSolidErrorAlt } from "react-icons/bi";
import {
  FiArrowRight,
  FiCalendar,
  FiCheck,
  FiCheckCircle,
  FiChevronDown,
  FiChevronLeft,
  FiChevronRight,
  FiGrid,
  FiHeart,
  FiHome,
  FiMapPin,
  FiMaximize2,
  FiMessageCircle,
  FiPhone,
  FiShare2,
  FiShield,
} from "react-icons/fi";
import { FaBath, FaBed, FaStar } from "react-icons/fa";
import { FaArrowRightArrowLeft } from "react-icons/fa6";
import ComparePropertyModal from "@/components/compare-property/ComparePropertyModal";
import { ReactSVG } from "react-svg";
import * as api from "@/api/apiRoutes";
import ImageWithPlaceholder from "@/components/image-with-placeholder/ImageWithPlaceholder";
import { PropertyDetailSkeleton } from "@/components/skeletons/property-skeletons";
import LightBox from "@/components/property-detail/LightBox";
import SimilarPropertySlider from "@/components/property-detail/SimilarPropertySlider";
import MortgageLoanCalculator from "@/components/property-detail/MortgageLoanCalculator";
import ReportModal from "@/components/property-detail/ReportModal";
import LoginModal from "@/components/modal/LoginModal";
import ChangeStatus from "@/components/reusable-components/ChangeStatus";
import ChangePropertyType from "@/components/property-detail/ChangePropertyType";
import FeatureCard from "@/components/reusable-components/FeatureCard";
import { AppointmentScheduleModal } from "@/components/appointment-modal";
import { useTranslation } from "@/components/context/TranslationContext";
import { useAuthStatus } from "@/hooks/useAuthStatus";
import Swal from "sweetalert2";
import toast from "react-hot-toast";
import { useCustomPropertyLayoutSettings } from "./useCustomPropertyLayoutSettings";
import LocationInsightsGroup from "@/components/property-detail/LocationInsightsGroup";

const joinLocation = (parts) => parts.filter(Boolean).join(", ");

const titleCase = (value) => {
  if (!value || typeof value !== "string") return value;
  return value
    .replace(/[_-]+/g, " ")
    .replace(/\s+/g, " ")
    .trim()
    .replace(/\w\S*/g, (part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase());
};

const safeText = (value) => {
  if (value === undefined || value === null || value === "" || value === "0") return "";
  return String(value);
};

const cleanDescription = (value) => {
  const text = safeText(value);
  if (!text) return "More details will be available soon.";
  const cleaned = text
    .replace(/<br\s*\/?>/gi, "\n")
    .replace(/<\/p>/gi, "\n")
    .replace(/<[^>]+>/g, "")
    .replace(/\n{3,}/g, "\n\n")
    .trim();
  return cleaned || "More details will be available soon.";
};

const getPublicLocation = (property) => {
  const areaListing = property?.area_listing || {};
  return joinLocation([
    areaListing?.sub_area_name,
    areaListing?.area_name,
    areaListing?.city_name || property?.city,
    areaListing?.state || property?.state,
  ]);
};

const normalizeImageUrl = (src) => {
  if (!src) return "";
  if (typeof src === "string") return src;
  return src?.src || src?.url || "";
};

const getGalleryImages = (property, placeholder) => {
  const gallery = Array.isArray(property?.gallery) ? property.gallery : [];
  const galleryImages = gallery.map((item) => item?.image || item?.url || item).filter(Boolean);
  const merged = [property?.title_image, ...galleryImages].filter(Boolean);
  const unique = [...new Set(merged)];
  return unique.length ? unique.slice(0, 24) : [placeholder].filter(Boolean);
};

const formatPrice = (property, currencySymbol) => {
  const price = property?.price;
  if (price === undefined || price === null || price === "") return "Price on request";
  const numericPrice = Number(price);
  const displayPrice = Number.isFinite(numericPrice) ? numericPrice.toLocaleString("en-IN") : price;
  const symbol = currencySymbol || "₹";
  let formatted = `${symbol}${displayPrice}`;
  const listingType = String(property?.property_type || property?.propery_type || "").toLowerCase();
  if (listingType === "rent" && property?.rentduration) {
    formatted += ` /${property.rentduration}`;
  }
  return formatted;
};

const parameterName = (item) => (item?.translated_name || item?.name || "").toLowerCase();

const getParameterValue = (property, keywords) => {
  const parameters = Array.isArray(property?.parameters) ? property.parameters : [];
  const item = parameters.find((param) => keywords.some((keyword) => parameterName(param).includes(keyword)));
  return safeText(item?.value || item?.option_value || item?.selected_value);
};

const getDisplayParameterValue = (item) => safeText(item?.value || item?.option_value || item?.selected_value);

const getAmenityName = (item) => item?.translated_name || item?.name || "Feature";

const getFacilityName = (item) => item?.translated_name || item?.name || "Nearby place";

const compactValue = (value, fallback = "On request") => safeText(value) || fallback;

const getOwnerContact = (property) => {
  if (property?.role_context === "agent") {
    const profile = property?.agent_profile || {};
    return {
      name: profile?.agent_name || property?.customer_name || "Listing Owner",
      image: profile?.agent_profile_photo || property?.profile,
      mobile: profile?.agent_mobile && profile?.agent_mobile !== "null" ? profile.agent_mobile : "",
    };
  }

  return {
    name: property?.customer_name || "Listing Owner",
    image: property?.profile,
    mobile: property?.mobile && property?.mobile !== "null" ? property.mobile : "",
  };
};

const normalizePhone = (value) => safeText(value).replace(/[^\d+]/g, "");

const FeatureIcon = ({ src, label }) => {
  if (!src) {
    return (
      <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-emerald-50 text-emerald-800">
        <FiCheckCircle className="h-4 w-4" />
      </div>
    );
  }

  return (
    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-white p-1.5">
      <ReactSVG
        src={src}
        beforeInjection={(svg) => {
          if (!svg?.setAttribute) return;
          svg.setAttribute("style", "height:100%;width:100%");
          svg.querySelectorAll?.("path")?.forEach((path) => path.setAttribute("style", "fill:#065f46"));
        }}
        className="h-5 w-5"
        aria-label={label}
      />
    </div>
  );
};

const InfoCard = ({ icon: Icon, label, value }) => (
  <div className="rounded-lg border border-slate-200 bg-white px-2 py-2 shadow-sm">
    <div className="flex items-center gap-2">
      <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-emerald-50 text-emerald-800">
        <Icon className="h-3.5 w-3.5" />
      </div>
      <div className="min-w-0">
        <div className="text-[10px] uppercase tracking-wide text-slate-500">{label}</div>
        <div className="truncate text-[12px] font-semibold leading-tight text-slate-950">{value}</div>
      </div>
    </div>
  </div>
);

const ActionButton = ({
  children,
  icon: Icon,
  variant = "light",
  href,
  onClick,
  disabled = false,
  target,
  rel,
  className = "",
}) => {
  const variants = {
    green: "border-emerald-800 bg-emerald-800 text-white hover:bg-emerald-900",
    bronze: "border-[#b77b35] bg-[#b77b35] text-white hover:bg-[#9b6628]",
    outline: "border-emerald-800 bg-white text-emerald-800 hover:bg-emerald-50",
    schedule: "border-[#b77b35] bg-white text-[#9b6628] hover:bg-[#faf5ef]",
    light: "border-slate-200 bg-white text-slate-900 hover:border-slate-300",
  };
  const baseClassName = `inline-flex min-h-[38px] w-full items-center justify-center gap-2 rounded-md border px-3.5 py-2 text-sm font-semibold transition ${className} ${
    disabled ? "cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400" : variants[variant]
  }`;

  if (href && !disabled) {
    return (
      <a href={href} target={target} rel={rel} className={baseClassName}>
        {Icon && <Icon className="h-4 w-4 shrink-0" />}
        {children}
      </a>
    );
  }

  return (
    <button type="button" onClick={onClick} disabled={disabled} className={baseClassName}>
      {Icon && <Icon className="h-4 w-4 shrink-0" />}
      {children}
    </button>
  );
};

const SectionTitle = ({ children, action }) => (
  <div className="mb-3 flex items-center justify-between gap-2">
    <h2 className="text-lg font-semibold leading-snug text-slate-950">{children}</h2>
    {action}
  </div>
);

const ContentCard = ({ children, className = "" }) => (
  <section className={`rounded-lg border border-slate-200 bg-white p-3.5 md:p-4 ${className}`}>{children}</section>
);

const MobileAccordion = ({ title, children, defaultOpen = false }) => {
  const [open, setOpen] = useState(defaultOpen);

  return (
    <section className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
      <button
        type="button"
        onClick={() => setOpen((value) => !value)}
        className="flex w-full items-center justify-between gap-3 px-3.5 py-3 text-left text-sm font-semibold text-slate-950"
      >
        <span className="pr-2">{title}</span>
        <FiChevronDown className={`h-4 w-4 shrink-0 text-slate-500 transition ${open ? "rotate-180" : ""}`} />
      </button>
      {open && <div className="border-t border-slate-200 bg-slate-50/40 px-3.5 pb-3.5 pt-3">{children}</div>}
    </section>
  );
};

const PANORAMA_VIEWER_ID = "custom-property-panorama";

const CustomPropertyDetailsPage = ({ OfficialFallback, ownerMode = false }) => {
  const router = useRouter();
  const { slug, lang } = router.query;
  const [property, setProperty] = useState(null);
  const [similarProperties, setSimilarProperties] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [shouldFallback, setShouldFallback] = useState(false);
  const [activeImage, setActiveImage] = useState(0);
  const [activeTab, setActiveTab] = useState("overview");
  const [descriptionOpen, setDescriptionOpen] = useState(false);
  const [shareStatus, setShareStatus] = useState("");
  const [viewerIsOpen, setViewerIsOpen] = useState(false);
  const [lightboxIndex, setLightboxIndex] = useState(0);
  const [showAppointmentModal, setShowAppointmentModal] = useState(false);
  const [currentStep, setCurrentStep] = useState(2);
  const [isSaved, setIsSaved] = useState(false);
  const [isSaveLoading, setIsSaveLoading] = useState(false);
  const [isInterested, setIsInterested] = useState(false);
  const [imageURL, setImageURL] = useState(false);
  const [isReportModal, setIsReportModal] = useState(false);
  const [isReported, setIsReported] = useState(false);
  const [showReport, setShowReport] = useState(true);
  const [showLoginModal, setShowLoginModal] = useState(false);
  const [showCompareModal, setShowCompareModal] = useState(false);
  const [reloadToken, setReloadToken] = useState(0);
  const t = useTranslation();
  const isUserLoggedIn = useAuthStatus();
  const webSettings = useSelector((state) => state.WebSetting?.data);
  const userData = useSelector((state) => state.User?.data);
  const layoutSettings = useCustomPropertyLayoutSettings(webSettings);
  const currencySymbol = webSettings?.currency_symbol;
  const placeholder = webSettings?.web_placeholder_logo;
  const distanceSymbol = webSettings?.distance_option || "km";
  const userCurrentId = userData?.id;

  const loadProperty = useCallback(async () => {
    if (!slug) return;

    try {
      setIsLoading(true);
      setShouldFallback(false);
      const response = ownerMode
        ? await api.getAddedPropertiesApi({ slug_id: slug, property_type: " ", request_status: " " })
        : await api.getPropertyDetails({ slug_id: slug });
      const nextProperty = response?.data?.[0];

      if (!nextProperty?.id || !nextProperty?.title) {
        setShouldFallback(true);
        return;
      }

      setProperty(nextProperty);
      setSimilarProperties(response?.similar_properties || response?.similiar_properties || []);
      setIsSaved(Boolean(nextProperty?.is_favourite));
      setIsInterested(Boolean(nextProperty?.is_interested));
      setIsReported(Boolean(nextProperty?.is_reported));
      setImageURL(nextProperty?.three_d_image || false);
      setActiveImage(0);
      setLightboxIndex(0);
    } catch (error) {
      console.error("Custom property detail failed to load:", error);
      setShouldFallback(true);
    } finally {
      setIsLoading(false);
    }
  }, [slug, ownerMode]);

  useEffect(() => {
    loadProperty();
  }, [loadProperty, reloadToken]);

  useEffect(() => {
    if (!property?.three_d_image) {
      setImageURL(false);
      return;
    }
    setImageURL(property.three_d_image);
  }, [property?.three_d_image]);

  useEffect(() => {
    if (!imageURL || typeof window === "undefined" || !window.pannellum) return undefined;

    const timer = setTimeout(() => {
      const panoramaElement = document.getElementById(PANORAMA_VIEWER_ID);
      if (panoramaElement) {
        window.pannellum.viewer(PANORAMA_VIEWER_ID, {
          type: "equirectangular",
          panorama: imageURL,
          autoLoad: true,
        });
      }
    }, 500);

    return () => clearTimeout(timer);
  }, [imageURL]);

  const detailsAdBanner = useQuery({
    queryKey: ["customPropertyDetailAdBanners", lang],
    queryFn: async () => {
      try {
        const response = await api.getAdBannerApi({ page: "property_detail", platform: "web" });
        return response?.data || [];
      } catch (error) {
        console.error("Error fetching property detail ad banners:", error);
        return [];
      }
    },
    staleTime: 0,
  });

  const aboveBreadcrumbAdBanner = detailsAdBanner?.data?.find((banner) => banner?.placement === "above_breadcrumb");
  const belowMortgageAdBanner = detailsAdBanner?.data?.find(
    (banner) => banner?.placement === "sidebar_below_mortgage_loan_calculator",
  );
  const aboveSimilarPropertiesAdBanner = detailsAdBanner?.data?.find((banner) => banner?.placement === "above_footer");

  const handleAdBannerClick = (banner) => {
    if (banner?.external_link_url) {
      window.open(banner.external_link_url, "_blank");
      return;
    }
    if (banner?.property?.slug_id) {
      router.push(`/property-details/${banner.property.slug_id}/?lang=${lang || "en"}`);
    }
  };

  const handleReportModal = () => {
    setIsReportModal((value) => !value);
  };

  const handleReportProperty = () => {
    if (!isUserLoggedIn) {
      setShowLoginModal(true);
      return;
    }
    setIsReportModal(true);
  };

  const refreshOwnerProperty = () => {
    setReloadToken((value) => value + 1);
  };

  const images = useMemo(() => getGalleryImages(property, placeholder), [property, placeholder]);

  useEffect(() => {
    setActiveImage((index) => (images.length ? Math.min(index, images.length - 1) : 0));
  }, [images.length]);

  const publicLocation = getPublicLocation(property);
  const listingType = titleCase(property?.propery_type || property?.property_type || "Listing");
  const propertyType = titleCase(property?.property_type || property?.category?.translated_name || property?.category?.category);
  const price = formatPrice(property, currencySymbol);
  const bedrooms = getParameterValue(property, ["bed"]);
  const bathrooms = getParameterValue(property, ["bath"]);
  const areaSize = getParameterValue(property, ["area", "sq", "plot", "built"]);
  const furnishing = getParameterValue(property, ["furnish"]);
  const description = cleanDescription(property?.description);
  const isLongDescription = description.length > 460;
  const descriptionText = descriptionOpen || !isLongDescription ? description : `${description.slice(0, 460)}...`;
  const ownerContact = getOwnerContact(property);
  const ownerName = ownerContact.name;
  const ownerMobile = normalizePhone(ownerContact.mobile);
  const canCallOwner = Boolean(ownerMobile);
  const whatsappUrl = canCallOwner ? `https://wa.me/${ownerMobile.replace(/[^\d]/g, "")}` : "";
  const isOwnListing = ownerMode || (userCurrentId && property?.added_by && userCurrentId === property.added_by);
  const showContactSidebar = !isOwnListing && layoutSettings.showAgentCard;
  const showReportSection = !isOwnListing && !isReported && showReport;
  const isSellProperty = (property?.property_type || property?.propery_type || "").toLowerCase() === "sell";
  const shareUrl =
    typeof window !== "undefined"
      ? window.location.href
      : `${process.env.NEXT_PUBLIC_WEB_URL || ""}/property-details/${slug || property?.slug_id || ""}?lang=${lang || "en"}`;

  const featureParameters = useMemo(() => {
    const parameters = Array.isArray(property?.parameters) ? property.parameters : [];
    return parameters.filter((item) => {
      const value = item?.value;
      return value !== null && value !== "" && value !== "0";
    });
  }, [property]);

  const nearbyFacilities = useMemo(() => {
    const facilities = Array.isArray(property?.assign_facilities) ? property.assign_facilities : [];
    return facilities.filter((item) => {
      const distance = item?.distance;
      return distance !== null && distance !== "" && distance !== 0;
    });
  }, [property]);

  const infoCards = [
    { icon: FaBed, label: "Bedrooms", value: compactValue(bedrooms) },
    { icon: FaBath, label: "Bathrooms", value: compactValue(bathrooms) },
    { icon: FiMaximize2, label: "Area", value: compactValue(areaSize) },
    { icon: FiGrid, label: "Furnishing", value: compactValue(furnishing) },
    { icon: FiHome, label: "Property Type", value: compactValue(propertyType) },
    { icon: FiCheckCircle, label: "Availability", value: compactValue(property?.availability || property?.posted_on, "Ready") },
  ];

  const highlights = [
    publicLocation ? `Located in ${publicLocation}` : "Public area location available",
    propertyType ? `${propertyType} listing` : "Property details available",
    price !== "Price on request" ? "Listed price available" : "Price available on request",
  ];

  const outdoorDetails = [
    ["Parking", getParameterValue(property, ["parking"])],
    ["Open Area", getParameterValue(property, ["open", "garden", "balcony"])],
    ["Outdoor Feature", getParameterValue(property, ["outdoor", "terrace", "yard"])],
  ].filter(([, value]) => value);

  const openLightbox = (index = activeImage) => {
    const nextIndex = images.length ? Math.min(Math.max(index, 0), images.length - 1) : 0;
    setActiveImage(nextIndex);
    setLightboxIndex(nextIndex);
    setViewerIsOpen(true);
  };

  const showPreviousImage = () => {
    if (!images.length) return;
    setActiveImage((value) => (value === 0 ? images.length - 1 : value - 1));
  };

  const showNextImage = () => {
    if (!images.length) return;
    setActiveImage((value) => (value + 1) % images.length);
  };

  const handleShare = async () => {
    try {
      if (navigator?.share) {
        await navigator.share({
          title: property?.title || "Property Details",
          text: property?.title || "View this property",
          url: shareUrl,
        });
        setShareStatus("Shared");
        return;
      }

      await navigator?.clipboard?.writeText(shareUrl);
      setShareStatus("Link copied");
    } catch (error) {
      setShareStatus("Copy this page URL from your browser");
    }
  };

  const requireLogin = (messageKey = "plzLogFirst") =>
    Swal.fire({
      title: t(messageKey),
      icon: "warning",
      confirmButtonText: t("ok"),
      customClass: { confirmButton: "Swal-confirm-buttons" },
    });

  const handleSaveProperty = async () => {
    if (isSaveLoading || !property?.id) return;

    if (!isUserLoggedIn) {
      await requireLogin("plzLogFirst");
      return;
    }

    try {
      setIsSaveLoading(true);
      const nextSaved = !isSaved;
      const res = await api.addFavouritePropertyApi({
        property_id: property.id,
        type: nextSaved ? "1" : "0",
      });

      if (res?.error === false) {
        setIsSaved(nextSaved);
        toast.success(t(res.message));
      } else {
        toast.error(t(res?.message) || t("errorAddingToFavorites"));
      }
    } catch (error) {
      toast.error(t(error?.message) || t("errorAddingToFavorites"));
    } finally {
      setIsSaveLoading(false);
    }
  };

  const handleSendEnquiry = async () => {
    if (!property?.id) return;

    if (!isUserLoggedIn) {
      await requireLogin("plzLogFirstIntrest");
      return;
    }

    try {
      const res = await api.interestedPropertyApi({
        property_id: property.id,
        type: isInterested ? "0" : "1",
      });

      if (!res?.error) {
        setIsInterested(!isInterested);
        toast.success(t(res?.message));
      } else {
        toast.error(t(res?.message));
      }
    } catch (error) {
      toast.error(t(error?.message));
    }
  };

  const isAgentOwner = property?.added_by !== 0;
  const canScheduleVisit =
    layoutSettings.showScheduleVisit &&
    Boolean(
      property?.is_appointment_available === true ||
        property?.is_appointment_available === 1 ||
        property?.is_appointment_available === "1",
    ) &&
    Boolean(userCurrentId && userCurrentId !== property?.added_by && isAgentOwner);

  const openScheduleVisit = () => {
    if (!canScheduleVisit) return;

    if (!isUserLoggedIn) {
      requireLogin("plzLogFirst");
      return;
    }

    setCurrentStep(2);
    setShowAppointmentModal(true);
  };

  const handleNextStep = () => {
    if (currentStep < 3) {
      setCurrentStep(currentStep + 1);
    }
  };

  const handleBookStepPrev = () => {
    if (currentStep >= 2) {
      setCurrentStep(currentStep - 1);
    }
  };

  const handleBookingStep = (step) => {
    if (step === 1) {
      return;
    }
    setCurrentStep(step);
  };

  const tabItems = [
    { id: "overview", label: "Overview" },
    { id: "details", label: "Details" },
    { id: "features", label: "Features & Amenities" },
    ...(outdoorDetails.length > 0 ? [{ id: "outdoor", label: "Outdoor" }] : []),
    ...(property?.id ? [{ id: "insights", label: "Location Insights" }] : []),
    { id: "location", label: "Location" },
  ];

  const primaryContactActions = (
    <div className="grid gap-1.5">
      {canCallOwner && (
        <ActionButton href={`tel:${ownerMobile}`} icon={FiPhone} variant="green">
          Call Now
        </ActionButton>
      )}
      {canCallOwner && (
        <ActionButton href={whatsappUrl} icon={FiMessageCircle} variant="outline" target="_blank" rel="noopener noreferrer">
          WhatsApp
        </ActionButton>
      )}
      <ActionButton onClick={handleSendEnquiry} icon={FiMessageCircle} variant="bronze">
        {isInterested ? "Enquiry Sent" : "Send Enquiry"}
      </ActionButton>
      {!canCallOwner && !canScheduleVisit && (
        <p className="rounded-md bg-slate-50 p-2.5 text-xs leading-5 text-slate-600">
          Direct phone and WhatsApp are unavailable for this listing. Please send an enquiry instead.
        </p>
      )}
    </div>
  );

  const scheduleVisitAction = canScheduleVisit ? (
    <ActionButton onClick={openScheduleVisit} icon={FiCalendar} variant="schedule">
      Schedule a Visit
    </ActionButton>
  ) : null;

  const contactActions = (
    <div className="grid gap-1.5">
      {canCallOwner && (
        <ActionButton href={`tel:${ownerMobile}`} icon={FiPhone} variant="green">
          Call Now
        </ActionButton>
      )}
      {canCallOwner && (
        <ActionButton href={whatsappUrl} icon={FiMessageCircle} variant="outline" target="_blank" rel="noopener noreferrer">
          WhatsApp
        </ActionButton>
      )}
      <ActionButton onClick={handleSendEnquiry} icon={FiMessageCircle} variant="bronze">
        {isInterested ? "Enquiry Sent" : "Send Enquiry"}
      </ActionButton>
      {scheduleVisitAction}
      {!canCallOwner && !canScheduleVisit && (
        <p className="rounded-md bg-slate-50 p-2.5 text-xs leading-5 text-slate-600">
          Direct phone and WhatsApp are unavailable for this listing. Please send an enquiry instead.
        </p>
      )}
    </div>
  );

  if (isLoading) {
    return <PropertyDetailSkeleton />;
  }

  if (shouldFallback || !property) {
    return <OfficialFallback />;
  }

  const aboutContent = (
    <div className="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px]">
      <ContentCard>
        <SectionTitle>About This Property</SectionTitle>
        <p className="whitespace-pre-line text-sm leading-relaxed text-slate-700">{descriptionText}</p>
        {isLongDescription && (
          <button
            type="button"
            onClick={() => setDescriptionOpen((value) => !value)}
            className="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-emerald-800"
          >
            {descriptionOpen ? "Read less" : "Read more"}
            <FiChevronDown className={`h-4 w-4 transition ${descriptionOpen ? "rotate-180" : ""}`} />
          </button>
        )}
      </ContentCard>
      <ContentCard>
        <SectionTitle>Highlights</SectionTitle>
        <ul className="space-y-2">
          {highlights.map((item) => (
            <li key={item} className="flex gap-2 text-sm leading-snug text-slate-700">
              <FiCheck className="mt-0.5 h-4 w-4 shrink-0 text-emerald-700" />
              <span>{item}</span>
            </li>
          ))}
        </ul>
      </ContentCard>
    </div>
  );

  const detailContent = (
    <ContentCard>
      <SectionTitle>Property Details</SectionTitle>
      <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        {infoCards.map((item) => (
          <InfoCard key={item.label} {...item} />
        ))}
      </div>
    </ContentCard>
  );

  const amenitiesContent = (
    <ContentCard>
        <SectionTitle>Features & Amenities</SectionTitle>
        {featureParameters.length > 0 ? (
          <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {featureParameters.map((item, index) => (
              <div
                key={item?.id || `${getAmenityName(item)}-${index}`}
                className="flex min-w-0 items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-2.5"
              >
                <FeatureIcon src={item?.image} label={getAmenityName(item)} />
                <div className="min-w-0">
                  <div className="truncate text-xs font-semibold text-slate-950">{getAmenityName(item)}</div>
                  <div className="truncate text-xs text-slate-500">{getDisplayParameterValue(item)}</div>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
            Feature details are not available for this listing yet.
          </div>
        )}
    </ContentCard>
  );

  const outdoorContent = (
    <ContentCard>
      <SectionTitle>Outdoor</SectionTitle>
      <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        {outdoorDetails.map(([title, value]) => (
          <div key={title} className="rounded-lg bg-slate-50 p-3">
            <div className="text-sm font-semibold text-slate-950">{title}</div>
            <div className="mt-1 text-sm text-slate-600">{value}</div>
          </div>
        ))}
      </div>
    </ContentCard>
  );

  const locationInsightsContent = property?.id ? (
    <LocationInsightsGroup
      propertyDetails={property}
      DistanceSymbol={distanceSymbol}
      themeEnabled={webSettings?.svg_clr === "1"}
    />
  ) : null;

  const locationContent = (
    <ContentCard>
      <SectionTitle>Public Location</SectionTitle>
      <div className="rounded-lg bg-emerald-50 p-3">
        <div className="flex gap-2.5 text-sm leading-snug text-slate-800">
          <FiMapPin className="mt-0.5 h-4 w-4 shrink-0 text-emerald-800" />
          <div>
            <div className="font-semibold">{publicLocation || "Location details available on request."}</div>
            <p className="mt-1.5 leading-relaxed text-slate-600">
              Only sub-area, area, city, and state are shown publicly. Exact address, map pin, and coordinates stay private.
            </p>
          </div>
        </div>
      </div>
    </ContentCard>
  );

  const nearbyContent =
    layoutSettings.showNearby && nearbyFacilities.length > 0 ? (
      <ContentCard>
        <SectionTitle>Nearby Places</SectionTitle>
        <div className="flex gap-2 overflow-x-auto pb-1">
          {nearbyFacilities.map((item, index) => (
            <div
              key={item?.id || `${getFacilityName(item)}-${index}`}
              className="w-[220px] max-w-[78vw] shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-white"
            >
              <div className="flex h-24 items-center justify-center bg-slate-100 p-2.5">
                {item?.image ? (
                  <ReactSVG
                    src={item.image}
                    className="h-10 w-10"
                    aria-label={getFacilityName(item)}
                    beforeInjection={(svg) => {
                      if (!svg?.setAttribute) return;
                    }}
                  />
                ) : (
                  <FiMapPin className="h-8 w-8 text-emerald-800" />
                )}
              </div>
              <div className="p-3">
                <div className="truncate text-sm font-semibold text-slate-950">{getFacilityName(item)}</div>
                <div className="mt-2 text-sm font-semibold text-emerald-800">
                  {item.distance} {Number(item.distance) > 1 ? `${distanceSymbol}s` : distanceSymbol}
                </div>
              </div>
            </div>
          ))}
        </div>
      </ContentCard>
    ) : null;

  const tabContent = {
    overview: aboutContent,
    details: detailContent,
    features: amenitiesContent,
    ...(outdoorDetails.length > 0 ? { outdoor: outdoorContent } : {}),
    ...(locationInsightsContent ? { insights: locationInsightsContent } : {}),
    location: locationContent,
  };

  const visibleThumbs = images.slice(0, 6);
  const extraPhotoCount = Math.max(images.length - 6, 0);
  const activeHeroImage = normalizeImageUrl(images[activeImage] || placeholder);
  const hasDesktopSidebar = showContactSidebar || ownerMode;

  const ownerSidebar = ownerMode && property && (
    <aside className="hidden lg:col-[2] lg:block lg:w-[300px] lg:max-w-[300px]">
      <div className="sticky top-16 space-y-2.5" id="custom-property-owner-controls">
        {property?.request_status === "approved" && (
          <ChangeStatus
            type="property"
            id={property.id}
            initialStatus={property?.status === 0 ? "Deactive" : "Active"}
            onStatusChange={() => {}}
            fetchDetails={refreshOwnerProperty}
          />
        )}
        {property?.request_status === "approved" && property?.is_feature_available && (
          <FeatureCard propertyId={property.id} handleRefresh={refreshOwnerProperty} />
        )}
        {property?.request_status === "approved" &&
          property?.status === 1 &&
          property?.property_type !== "sold" && (
            <ChangePropertyType
              propertyId={property.id}
              propertyType={property.property_type}
              onStatusChange={refreshOwnerProperty}
            />
          )}
        {isSellProperty && (
          <MortgageLoanCalculator
            propertyDetails={property}
            showLoginModal={showLoginModal}
            setShowLoginModal={setShowLoginModal}
          />
        )}
        {belowMortgageAdBanner?.image && (
          <div
            className="relative h-[240px] max-h-[240px] w-full cursor-pointer overflow-hidden rounded-lg"
            onClick={() => handleAdBannerClick(belowMortgageAdBanner)}
            role="presentation"
          >
            <ImageWithPlaceholder
              src={belowMortgageAdBanner.image}
              alt="Advertisement"
              fill
              sizes="300px"
              className="rounded-2xl"
            />
          </div>
        )}
      </div>
    </aside>
  );

  const contactSidebar = showContactSidebar && (
    <aside className="hidden lg:col-[2] lg:block lg:w-[300px] lg:max-w-[300px]">
      <div className="sticky top-16 space-y-2" id="custom-property-contact">
        <ContentCard>
          <SectionTitle>Contact Agent</SectionTitle>
          <div className="flex items-center gap-2.5">
            <div className="relative h-12 w-12 overflow-hidden rounded-full bg-slate-100">
              <ImageWithPlaceholder
                src={ownerContact.image || placeholder}
                alt={ownerName}
                width={96}
                height={96}
                className="h-full w-full object-cover"
              />
            </div>
            <div className="min-w-0">
              <div className="truncate text-sm font-semibold text-slate-950">{ownerName}</div>
              <div className="mt-1 text-xs text-slate-500">Property consultant</div>
            </div>
          </div>
          <div className="mt-2.5">{primaryContactActions}</div>
          {property?.id && (
            <p className="mt-2.5 border-t border-slate-100 pt-2 text-xs text-slate-500">
              Property ID: <span className="font-semibold text-slate-800">#{property.id}</span>
            </p>
          )}
        </ContentCard>

        {scheduleVisitAction && <ContentCard className="!p-3">{scheduleVisitAction}</ContentCard>}

        {isSellProperty && (
          <MortgageLoanCalculator
            propertyDetails={property}
            showLoginModal={showLoginModal}
            setShowLoginModal={setShowLoginModal}
          />
        )}

        {similarProperties?.length > 0 && (
          <ContentCard className="!p-3.5">
            <div className="text-center">
              <div className="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50">
                <FaArrowRightArrowLeft className="text-emerald-800" size={18} />
              </div>
              <div className="text-sm font-semibold text-slate-950">{t("compareTitle")}</div>
              <p className="mt-1 line-clamp-3 text-xs leading-snug text-slate-600">{t("compareDesc")}</p>
              <button
                type="button"
                onClick={() => setShowCompareModal(true)}
                className="mt-3 w-full rounded-md bg-emerald-800 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-900"
              >
                {t("compareNow")}
              </button>
            </div>
            {showCompareModal && (
              <ComparePropertyModal
                show={showCompareModal}
                handleClose={() => setShowCompareModal(false)}
                similarProperties={similarProperties}
                currentPropertyId={property?.id}
              />
            )}
          </ContentCard>
        )}

        {belowMortgageAdBanner?.image && (
          <div
            className="relative h-[240px] max-h-[240px] w-full cursor-pointer overflow-hidden rounded-lg"
            onClick={() => handleAdBannerClick(belowMortgageAdBanner)}
            role="presentation"
          >
            <ImageWithPlaceholder
              src={belowMortgageAdBanner.image}
              alt="Advertisement"
              fill
              sizes="300px"
              className="rounded-lg"
            />
          </div>
        )}

        {showReportSection && (
          <div className="flex flex-col justify-between gap-2 rounded-lg border border-slate-200 bg-white p-3.5 shadow-sm md:flex-row">
            <button
              type="button"
              className="flex items-center gap-2 py-1 text-sm font-medium text-red-500"
              onClick={handleReportProperty}
            >
              <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 p-2">
                <BiSolidErrorAlt className="h-6 w-6 text-red-500" />
              </div>
              <span className="text-start text-sm font-medium text-slate-700">{t("reportPropertyPlaceholder")}</span>
            </button>
            <div className="flex items-center gap-2">
              <button
                type="button"
                className="flex-1 rounded-lg border border-emerald-800 px-3 py-2 text-center text-sm font-semibold text-emerald-800 hover:bg-emerald-50"
                onClick={handleReportProperty}
              >
                {t("yes")}
              </button>
              <button
                type="button"
                className="flex-1 rounded-lg px-3 py-2 text-center text-sm font-semibold text-slate-600 hover:bg-slate-50"
                onClick={() => setShowReport(false)}
              >
                {t("no")}
              </button>
            </div>
          </div>
        )}

        <ContentCard className="!p-3">
          <div className="flex gap-2.5">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-emerald-800 text-white">
              <FiShield />
            </div>
            <div>
              <div className="text-sm font-semibold text-slate-950">Verified Property</div>
              <p className="mt-0.5 text-xs leading-snug text-slate-600">
                Property details are reviewed before being shown publicly.
              </p>
            </div>
          </div>
        </ContentCard>
      </div>
    </aside>
  );

  return (
    <section className="overflow-x-hidden bg-[#f5f5f1] pb-20 text-[15px] text-slate-950 lg:pb-8">
      <div className="mx-auto w-full max-w-[1080px] px-4 py-3 md:px-5 md:py-4 lg:px-5 lg:py-5">
          {aboveBreadcrumbAdBanner?.image && (
            <div
              className="relative mb-4 h-[90px] max-h-[90px] w-full cursor-pointer overflow-hidden rounded-lg md:h-[110px] md:max-h-[110px]"
              onClick={() => handleAdBannerClick(aboveBreadcrumbAdBanner)}
              role="presentation"
            >
              <ImageWithPlaceholder
                src={aboveBreadcrumbAdBanner.image}
                alt="Advertisement"
                fill
                sizes="(max-width: 1080px) 100vw, 1080px"
                className="rounded-lg lg:rounded-2xl"
              />
            </div>
          )}

          {ownerMode && property && (
            <div className="mb-3 space-y-2.5 lg:hidden">
              {property?.request_status === "approved" && (
                <ChangeStatus
                  type="property"
                  id={property.id}
                  initialStatus={property?.status === 0 ? "Deactive" : "Active"}
                  onStatusChange={() => {}}
                  fetchDetails={refreshOwnerProperty}
                />
              )}
              {property?.request_status === "approved" && property?.is_feature_available && (
                <FeatureCard propertyId={property.id} handleRefresh={refreshOwnerProperty} />
              )}
              {property?.request_status === "approved" &&
                property?.status === 1 &&
                property?.property_type !== "sold" && (
                  <ChangePropertyType
                    propertyId={property.id}
                    propertyType={property.property_type}
                    onStatusChange={refreshOwnerProperty}
                  />
                )}
            </div>
          )}

          <div
            className={
              hasDesktopSidebar
                ? "w-full lg:grid lg:grid-cols-[700px_300px] lg:items-start lg:gap-6"
                : "w-full lg:max-w-[700px]"
            }
          >
            <div className="min-w-0 space-y-3 lg:col-[1] lg:max-w-[700px]">
              <nav className="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <Link href="/" className="hover:text-emerald-800">
                  Home
                </Link>
                <span>/</span>
                <Link href={`/properties/?lang=${lang || "en"}`} className="hover:text-emerald-800">
                  Properties
                </Link>
                <span>/</span>
                <span className="max-w-[min(70vw,320px)] truncate text-slate-700">{property?.title || "Property Details"}</span>
              </nav>

              <div className="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200/90 bg-gradient-to-r from-amber-50 to-white px-4 py-3">
                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-amber-900/70">Listing price</div>
                  <div className="text-2xl font-bold text-[#b77b35]">{price}</div>
                </div>
                <span className="rounded-md bg-emerald-800 px-3 py-1.5 text-xs font-semibold uppercase text-white">
                  {listingType}
                </span>
              </div>

              <section className="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div
                  role="button"
                  tabIndex={0}
                  onClick={() => openLightbox(activeImage)}
                  onKeyDown={(event) => {
                    if (event.key === "Enter" || event.key === " ") {
                      event.preventDefault();
                      openLightbox(activeImage);
                    }
                  }}
                  className="relative h-[220px] w-full cursor-pointer overflow-hidden rounded-xl bg-slate-100 md:h-[260px] lg:h-[290px]"
                  aria-label="Open photo gallery"
                >
                  {activeHeroImage ? (
                    <img
                      src={activeHeroImage}
                      alt={property?.title || "Property image"}
                      className="block h-full w-full object-cover object-center"
                      loading="eager"
                      decoding="async"
                    />
                  ) : (
                    <div className="flex h-full w-full items-center justify-center bg-slate-200 text-sm text-slate-600">
                      Image unavailable
                    </div>
                  )}
                  <div className="pointer-events-none absolute left-3 top-3 z-10 rounded-md bg-emerald-800 px-3 py-1.5 text-xs font-semibold text-white shadow-sm">
                    {listingType}
                  </div>
                  <div className="pointer-events-none absolute right-3 top-3 z-10 max-w-[55%] truncate rounded-md bg-[#b77b35] px-3 py-1.5 text-sm font-semibold text-white shadow-sm">
                    {price}
                  </div>
                  {images.length > 1 && (
                    <>
                      <button
                        type="button"
                        onClick={(event) => {
                          event.stopPropagation();
                          showPreviousImage();
                        }}
                        className="absolute left-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-slate-900 shadow-sm"
                        aria-label="Previous image"
                      >
                        <FiChevronLeft />
                      </button>
                      <button
                        type="button"
                        onClick={(event) => {
                          event.stopPropagation();
                          showNextImage();
                        }}
                        className="absolute right-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-slate-900 shadow-sm"
                        aria-label="Next image"
                      >
                        <FiChevronRight />
                      </button>
                    </>
                  )}
                  <div className="pointer-events-none absolute bottom-3 right-3 z-10 rounded-md bg-black/70 px-3 py-1.5 text-xs font-semibold text-white">
                    {activeImage + 1}/{Math.max(images.length, 1)}
                  </div>
                </div>

                {visibleThumbs.length > 1 && (
                  <div className="flex flex-wrap gap-1.5 p-2">
                    {visibleThumbs.map((image, index) => {
                      const isMoreTile = index === 5 && extraPhotoCount > 0;
                      const thumbSrc = normalizeImageUrl(image);
                      return (
                        <button
                          key={`${image}-${index}`}
                          type="button"
                          onClick={() => (isMoreTile ? openLightbox(index) : setActiveImage(index))}
                          className={`relative h-[42px] w-[56px] shrink-0 overflow-hidden rounded-md border lg:h-[48px] lg:w-[68px] ${
                            activeImage === index ? "border-emerald-800 ring-1 ring-emerald-800" : "border-slate-200"
                          }`}
                        >
                          {thumbSrc ? (
                            <img
                              src={thumbSrc}
                              alt={`${property?.title || "Property"} ${index + 1}`}
                              className="h-full w-full object-cover object-center"
                              loading="lazy"
                              decoding="async"
                            />
                          ) : null}
                          {isMoreTile && (
                            <span className="absolute inset-0 flex items-center justify-center bg-black/65 text-xs font-semibold text-white sm:text-sm">
                              +{extraPhotoCount} Photos
                            </span>
                          )}
                        </button>
                      );
                    })}
                  </div>
                )}
              </section>

              <section className="rounded-lg border border-slate-200 bg-white p-3.5 shadow-sm md:p-4">
                <div className="flex flex-wrap items-start gap-x-3 gap-y-2">
                  <div className="flex min-w-0 flex-1 items-start gap-2">
                    <h1 className="break-words text-[22px] font-semibold leading-tight text-slate-950 md:text-[24px]">
                      {property?.title || "Property Details"}
                    </h1>
                    <FiCheckCircle className="mt-1 h-4 w-4 shrink-0 text-emerald-700" aria-hidden="true" />
                  </div>
                  {layoutSettings.showShareSave && (
                    <div className="flex shrink-0 flex-wrap items-center gap-2">
                      <button
                        type="button"
                        onClick={handleShare}
                        className="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-slate-200 px-3 text-sm font-semibold text-slate-700 hover:border-slate-300"
                      >
                        <FiShare2 className="h-4 w-4" /> Share
                      </button>
                      <button
                        type="button"
                        onClick={handleSaveProperty}
                        disabled={isSaveLoading}
                        className={`inline-flex h-9 items-center justify-center gap-2 rounded-md border px-3 text-sm font-semibold ${
                          isSaved ? "border-emerald-800 bg-emerald-50 text-emerald-800" : "border-slate-200 text-slate-700 hover:border-slate-300"
                        }`}
                      >
                        <FiHeart className={`h-4 w-4 ${isSaved ? "fill-current" : ""}`} /> {isSaved ? "Saved" : "Save"}
                      </button>
                      {shareStatus && <span className="w-full text-center text-xs text-emerald-700 sm:text-right">{shareStatus}</span>}
                    </div>
                  )}
                </div>
                <p className="mt-2 flex items-start gap-2 text-sm leading-snug text-slate-600">
                  <FiMapPin className="mt-0.5 h-4 w-4 shrink-0 text-emerald-800" />
                  <span>{publicLocation || "Location available on request"}</span>
                </p>

                <div className="mt-3 grid grid-cols-2 gap-2 lg:grid-cols-3">
                  {infoCards.map((item) => (
                    <InfoCard key={item.label} {...item} />
                  ))}
                </div>
              </section>

              <div className="hidden lg:block">
                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                  <div className="flex flex-wrap gap-0.5 border-b border-slate-200 bg-slate-50/50 px-2 pt-2">
                    {tabItems.map((tab) => (
                      <button
                        key={tab.id}
                        type="button"
                        onClick={() => setActiveTab(tab.id)}
                        className={`whitespace-nowrap rounded-t-md border-b-2 px-3 py-2.5 text-sm font-semibold transition ${
                          activeTab === tab.id
                            ? "border-emerald-800 bg-white text-emerald-900 shadow-sm"
                            : "border-transparent text-slate-600 hover:bg-white/80 hover:text-slate-950"
                        }`}
                      >
                        {tab.label}
                      </button>
                    ))}
                  </div>
                  <div className="p-3.5 md:p-4">{tabContent[activeTab] ?? tabContent.overview}</div>
                </div>
              </div>

              <div className="space-y-2.5 lg:hidden">
                {tabItems.map((tab, index) => (
                  <MobileAccordion key={tab.id} title={tab.label} defaultOpen={index === 0}>
                    <div className="space-y-3">{tabContent[tab.id]}</div>
                  </MobileAccordion>
                ))}
              </div>

              {imageURL && (
                <ContentCard className="mt-3">
                  <SectionTitle>{t("virtualTour")}</SectionTitle>
                  <div className="h-[240px] overflow-hidden rounded-lg bg-slate-100 lg:h-[280px]">
                    <div id={PANORAMA_VIEWER_ID} className="h-full w-full" />
                  </div>
                </ContentCard>
              )}

              {aboveSimilarPropertiesAdBanner?.image && (
                <div
                  className="relative h-[90px] max-h-[90px] w-full cursor-pointer overflow-hidden rounded-lg md:h-[110px] md:max-h-[110px]"
                  onClick={() => handleAdBannerClick(aboveSimilarPropertiesAdBanner)}
                  role="presentation"
                >
                  <ImageWithPlaceholder
                    src={aboveSimilarPropertiesAdBanner.image}
                    alt="Advertisement"
                    fill
                    sizes="700px"
                    className="rounded-lg lg:rounded-2xl"
                  />
                </div>
              )}

              <div className="[&_.py-10]:py-5 [&_h2]:text-lg [&_img]:max-h-40">
                <SimilarPropertySlider data={similarProperties} isLoading={false} currentPropertyId={property?.id} />
              </div>
            </div>

            {ownerSidebar}
            {contactSidebar}
          </div>

          {showContactSidebar && (
            <div className="mt-3 lg:hidden" id="custom-property-contact-mobile">
              <ContentCard>
                <SectionTitle>Contact Agent</SectionTitle>
                <div className="mb-2.5 flex items-center gap-2.5">
                  <div className="relative h-10 w-10 overflow-hidden rounded-full bg-slate-100">
                    <ImageWithPlaceholder
                      src={ownerContact.image || placeholder}
                      alt={ownerName}
                      width={80}
                      height={80}
                      className="h-full w-full object-cover"
                    />
                  </div>
                  <div className="min-w-0">
                    <div className="truncate text-sm font-semibold text-slate-950">{ownerName}</div>
                    <div className="mt-1 text-xs text-slate-500">Property consultant</div>
                  </div>
                </div>
                {contactActions}
                {property?.id && (
                  <p className="mt-2.5 border-t border-slate-100 pt-2 text-xs text-slate-500">
                    Property ID: <span className="font-semibold text-slate-800">#{property.id}</span>
                  </p>
                )}
              </ContentCard>
            </div>
          )}
      </div>

      {!ownerMode && (
        <div className="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 py-2 shadow-[0_-8px_24px_rgba(15,23,42,0.1)] backdrop-blur lg:hidden">
          <div className={`mx-auto grid max-w-[1080px] gap-1.5 ${canCallOwner ? "grid-cols-3" : "grid-cols-1"}`}>
            {canCallOwner && (
              <ActionButton href={`tel:${ownerMobile}`} icon={FiPhone} variant="green" className="!min-h-[38px] !px-2 text-xs">
                Call
              </ActionButton>
            )}
            {canCallOwner && (
              <ActionButton
                href={whatsappUrl}
                icon={FiMessageCircle}
                target="_blank"
                rel="noopener noreferrer"
                className="!min-h-[38px] !px-2 text-xs"
              >
                WhatsApp
              </ActionButton>
            )}
            <ActionButton onClick={handleSendEnquiry} icon={FiMessageCircle} variant="bronze" className="!min-h-[38px] !px-2 text-xs">
              Enquiry
            </ActionButton>
          </div>
        </div>
      )}

      <LightBox
        photos={Array.isArray(property?.gallery) ? property.gallery : []}
        viewerIsOpen={viewerIsOpen}
        currentImage={lightboxIndex}
        onClose={() => setViewerIsOpen(false)}
        title_image={property?.title_image}
        setCurrentImage={setLightboxIndex}
      />

      {showAppointmentModal && (
        <AppointmentScheduleModal
          isOpen={showAppointmentModal}
          handlePrev={handleBookStepPrev}
          onClose={() => setShowAppointmentModal(false)}
          selectedProperty={property}
          userData={userData}
          currentStep={currentStep}
          totalSteps={3}
          onContinue={handleNextStep}
          handleBookingStep={handleBookingStep}
          isBookingFromProperty
        />
      )}

      {isReportModal && (
        <ReportModal
          open={isReportModal}
          handleReportModal={handleReportModal}
          propertyId={property?.id}
          setIsReported={setIsReported}
        />
      )}

      {showLoginModal && <LoginModal showLogin={showLoginModal} setShowLogin={setShowLoginModal} />}
    </section>
  );
};

export default CustomPropertyDetailsPage;
