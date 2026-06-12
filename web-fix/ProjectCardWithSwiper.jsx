import { useState, useEffect } from "react";
import {
  FaChevronLeft,
  FaChevronRight
} from "react-icons/fa";
import ImageWithPlaceholder from "../image-with-placeholder/ImageWithPlaceholder";
import { useTranslation } from "../context/TranslationContext";
import { useRouter } from "next/router";
import {
  Carousel,
  CarouselContent,
  CarouselDots,
  CarouselItem,
} from "@/components/ui/carousel";
import PremiumIcon from "@/assets/premium.svg";
import { useSelector } from "react-redux";
import { PackageTypes } from "@/utils/checkPackages/packageTypes";
import { handlePackageCheck, isRTL, truncate } from "@/utils/helperFunction";
import { ReactSVG } from "react-svg";

// ProjectCardWithSwiper component definition
// This component displays a project card with an image carousel and project details.
const ProjectCardWithSwiper = ({ data }) => {
  const t = useTranslation();
  const router = useRouter();
  const { lang } = router.query;
  const isRtl = isRTL();

  const userData = useSelector((state) => state?.User?.data);

  // State for the carousel API and current slide
  const [api, setApi] = useState(null);
  const [current, setCurrent] = useState(0);
  const [count, setCount] = useState(0);
  const [isFavorite, setIsFavorite] = useState(false);
  const [isDragging, setIsDragging] = useState(false);
  const isUserProject = data?.added_by == userData?.id;

  // Extract images for the carousel: primary image and up to 4 gallery images
  const galleryImages = data?.gallary_images
    ? data?.gallary_images.map((image) => image.name)
    : [];
  const images = [data.image, ...galleryImages]?.slice(0, 5);

  // Effect to update carousel state when API or images change
  useEffect(() => {
    if (!api) {
      return;
    }
    setCount(api.scrollSnapList().length);
    setCurrent(api.selectedScrollSnap());

    const handleSelect = () => {
      setCurrent(api.selectedScrollSnap());
    };

    // Set up drag detection
    const handleDragStart = () => {
      setIsDragging(true);
    };

    const handleDragEnd = () => {
      setTimeout(() => {
        setIsDragging(false);
      }, 100); // Small delay to prevent immediate click after drag
    };

    api.on("select", handleSelect);
    api.on("pointerDown", handleDragStart);
    api.on("pointerUp", handleDragEnd);

    return () => {
      api.off("select", handleSelect);
      api.off("pointerDown", handleDragStart);
      api.off("pointerUp", handleDragEnd);
    };
  }, [api, images]);


  // Handler for card click, navigates to project details page
  const handleCardClick = async (e) => {
    if (!isDragging && data.is_premium) {
      handlePackageCheck(
        e,
        PackageTypes.PREMIUM_PROJECTS,
        router,
        data?.slug_id,
        data,
        false,
        false,
        t
      );
    } else if (!isDragging && isUserProject && data.role_context !== "agent") {
      router?.push(`/my-project/${data.slug_id}?lang=${router.query?.lang}`);
    } else {
      router?.push(`/project-details/${data.slug_id}?lang=${router.query?.lang}`);
    }
  };

  // Handler for keydown events on the card for accessibility
  const handleCardKeyDown = (event) => {
    if (event.key === "Enter" || event.key === " ") {
      handleCardClick();
    }
  };

  // Prevent event propagation to parent carousel
  const stopPropagation = (e) => {
    e.stopPropagation();
  };

  // Handle carousel navigation button clicks
  const handlePrevClick = (e) => {
    e.stopPropagation();
    if (api) {
      api.scrollPrev();
    }
  };

  const handleNextClick = (e) => {
    e.stopPropagation();
    if (api) {
      api.scrollNext();
    }
  };

  const renderCarousel = () => {
    if (images?.length > 1) {
      return (
        <div
          className="w-full"
          onPointerDown={stopPropagation}
          onMouseDown={stopPropagation}
          onTouchStart={stopPropagation}
          onTouchMove={stopPropagation}
          onTouchEnd={stopPropagation}
        >
          <Carousel
            setApi={setApi}
            className="w-full"
            opts={{
              watchDrag: (enabled, event) => {
                // This ensures the parent carousel doesn't react to this carousel's drag events
                event?.stopPropagation();
                return enabled;
              },
              direction: isRTL() ? "rtl" : "ltr",
            }}
          >
            <CarouselContent>
              {images?.map((image, index) => (
                <CarouselItem key={index}>
                  <div className="relative w-full rounded-b-2xl">
                    <div
                      className="pointer-events-none absolute bottom-0 left-0 z-10 h-2/3 w-full rounded-b-2xl bg-gradient-to-t from-black/60 to-transparent opacity-0 transition-opacity group-hover:opacity-100"
                      aria-hidden="true"
                    />
                    <ImageWithPlaceholder
                      src={image}
                      width={536}
                      height={400}
                      alt={`Image ${index + 1}`}
                      className="aspect-[536/400] rounded-b-2xl object-cover"
                      loading="lazy"
                    />
                  </div>
                </CarouselItem>
              ))}
            </CarouselContent>
            {/* Carousel Controls */}
            {images && images.length > 1 && (
              <>
                <button
                  onClick={handlePrevClick}
                  className="absolute left-0 top-1/2 z-20 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-none border-none bg-white text-black !opacity-0 transition-opacity duration-500 ease-in-out group-hover:!opacity-100 sm:h-10 sm:w-10"
                  aria-label="Previous image"
                  disabled={current === 0}
                >
                  <FaChevronLeft size={16} className="sm:h-5 sm:w-5" />
                </button>
                <button
                  onClick={handleNextClick}
                  className="absolute right-0 top-1/2 z-20 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-none border-none bg-white text-black !opacity-0 transition-opacity duration-500 ease-in-out group-hover:!opacity-100 sm:h-10 sm:w-10"
                  aria-label="Next image"
                  disabled={current === count - 1}
                >
                  <FaChevronRight size={16} className="sm:h-5 sm:w-5" />
                </button>

                {/* Pagination Dots */}
                <div
                  className="absolute bottom-4 left-1/2 z-30 flex justify-center items-center h-8 -translate-x-1/2 space-x-2 !opacity-0 transition-opacity duration-500 ease-in-out group-hover:!opacity-100"
                  onClick={stopPropagation}
                >
                  <CarouselDots className="z-30 !overflow-x-visible [&>button]:after:!ring-white" />
                </div>
              </>
            )}
          </Carousel>
        </div>
      );
    } else {
      return (
        <div className="relative w-full rounded-b-lg">
          {/* Shadow overlay for single image */}
          <div
            className="pointer-events-none absolute bottom-0 left-0 z-10 h-1/3 w-full rounded-b-2xl bg-gradient-to-t from-black/60 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"
            aria-hidden="true"
          />
          <ImageWithPlaceholder
            src={images[0]}
            width={536}
            height={400}
            alt={data?.title}
            className="aspect-[536/400] rounded-b-2xl object-cover"
            loading="lazy"
          />
        </div>
      );
    }
  };

  const title = data?.translated_title || data?.title;
  const location = data?.city + ", " + data?.state + ", " + data?.country;

  return (
    <div
      className="group mx-auto flex w-full flex-col gap-4 overflow-hidden rounded-2xl"
      onClick={handleCardClick}
      onKeyDown={handleCardKeyDown}
      tabIndex={0}
      role="button"
      dir={isRtl ? "rtl" : "ltr"}
    >
      {/* Image Carousel */}
      <div className="relative h-full w-full">
        {/* Status Badge */}
        <div className="absolute left-4 top-4 z-[5]">
          <div className="primaryBg rounded p-2 text-xs font-medium text-white">
            {t(data?.type)}
          </div>
        </div>

        <div className="relative">{renderCarousel()}</div>
      </div>

      {/* Content */}
      <div
        className="rounded-t-2xl bg-zinc-900 p-4 text-white"
        onClick={handleCardClick}
      >
        <div className="flex w-full flex-row gap-3 sm:flex-row sm:items-end justify-between md:gap-1">
          <div className="flex w-fit flex-col gap-6">
            <div className="leadColor flex w-fit gap-2 items-center rounded-lg bg-white p-2 text-xs font-bold">
              <ReactSVG
                src={data?.category?.image}
                beforeInjection={(svg) => {
                  svg.setAttribute(
                    "style",
                    `height: 100%; width: 100%;`,
                  );
                  svg.querySelectorAll("path").forEach((path) => {
                    path.setAttribute(
                      "style",
                      `fill: var(--facilities-icon-color);`,
                    );
                  });
                }}
                className="w-5 h-5 flex items-center justify-center object-contain shrink-0"
                alt={data?.category?.translated_name || data?.category?.name || 'category icon'}
              />
              <span className="text-wrap line-clamp-1">{data?.category?.translated_name || data?.category?.category}</span>
            </div>
            <div>
              <h3 className="line-clamp-1 text-base font-semibold sm:text-xl">
                {truncate(title, 18)}
              </h3>
              <p className="line-clamp-1 text-sm md:text-base font-normal text-white opacity-65">
                {truncate(location, 18)}
              </p>
            </div>
          </div>
          {data?.is_premium === true ? <div className="md:ml-4">
            <div className="premiumBgColor flex items-center justify-center gap-1 rounded-full p-2 text-white">
              <ImageWithPlaceholder
                src={PremiumIcon}
                alt="Premium Icon"
                className="h-5 w-5 rounded-full"
                priority={false}
              />
              <span className="brandColor block px-1 text-sm font-semibold md:hidden xl:block">
                {t("premium")}
              </span>
            </div>
          </div> : null}
        </div>
      </div>
    </div>
  );
};

export default ProjectCardWithSwiper;
