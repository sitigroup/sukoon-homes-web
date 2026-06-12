import { useState, useMemo } from "react";
import ImageWithPlaceholder from "../image-with-placeholder/ImageWithPlaceholder";
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  CarouselNext,
  CarouselPrevious,
} from "@/components/ui/carousel";
import Autoplay from "embla-carousel-autoplay";
import { formatPriceAbbreviated, isRTL, truncate } from "@/utils/helperFunction";
import { buildPropertySearchUrl } from "@/utils/locationSearchUrl";
import { useRouter } from "next/router";
import { useTranslation } from "../context/TranslationContext";
import { FaArrowRight } from "react-icons/fa";
import CustomLink from "../context/CustomLink";
import SearchBox from "./SearchBox";
import { ReactSVG } from "react-svg";
import { premiumIcon } from "@/assets/svg";

const MainSwiper = ({ slides }) => {
  const router = useRouter();
  const t = useTranslation();
  const lang = router?.query?.lang;

  const images = slides;

  const isRtl = isRTL();

  // SearchBox state management for home page - using flat structure
  const [propertyType, setPropertyType] = useState('All');
  const [selectedCategory, setSelectedCategory] = useState('');
  const [keywords, setKeywords] = useState('');
  const [city, setCity] = useState('');
  const [state, setState] = useState('');
  const [country, setCountry] = useState('');
  const [locationInput, setLocationInput] = useState('');
  const [minPrice, setMinPrice] = useState('');
  const [maxPrice, setMaxPrice] = useState('');
  const [postedSince, setPostedSince] = useState('anytime');
  const [amenities, setAmenities] = useState([]);
  const [nearbyPlaces, setNearbyPlaces] = useState([]);
  const [areaLocation, setAreaLocation] = useState({});
  const [showAdvancedFilters, setShowAdvancedFilters] = useState(false);

  const handleImageClick = (image) => {
    if (image?.property?.id) {
      router.push(`/property-details/${image.property.slug_id}?lang=${lang}`);
    } else if (image?.slider_type === "4") {
      window.open(`${image?.link}`, '_blank');
    } else if (image?.slider_type === "2") {
      router?.push(`/properties/category/${image?.category?.slug_id}?lang=${lang}`)
    }
  };

  // SearchBox handlers for home page - using flat structure
  const handlePropertyTypeChange = (value) => {
    setPropertyType(value);
  };

  const handleCategoryChange = (value) => {
    setSelectedCategory(value);
  };

  const handleKeywordsChange = (value) => {
    setKeywords(value);
  };

  const handleCityChange = (value) => {
    setCity(value);
  };

  const handleStateChange = (value) => {
    setState(value);
  };

  const handleCountryChange = (value) => {
    setCountry(value);
  };

  const handleLocationInputChange = (value) => {
    setLocationInput(value || '');
  };

  const handleMinPriceChange = (value) => {
    setMinPrice(value);
  };

  const handleMaxPriceChange = (value) => {
    setMaxPrice(value);
  };

  const handlePostedSinceChange = (value) => {
    setPostedSince(value);
  };

  const handleAmenitiesChange = (value) => {
    setAmenities(value);
  };

  const handleNearbyPlacesChange = (value) => {
    setNearbyPlaces(value);
  };

  const handleAreaLocationChange = (value) => {
    setAreaLocation(value || {});
    if (value?.city) setCity(value.city);
    if (value?.state) setState(value.state);
    if (value?.country) setCountry(value.country);
  };

  const handleShowAdvancedFiltersChange = (value) => {
    setShowAdvancedFilters(value);
  };

  const handleApplyFilters = () => {
    // Build filters object in the same format as PropertyList.jsx
    const filters = {
      property_type: propertyType === 'All' ? '' : propertyType,
      category_id: selectedCategory || '',
      keywords: keywords || '',
      city: city || areaLocation?.city || '',
      state: state || areaLocation?.state || '',
      country: country || areaLocation?.country || '',
      state_id: areaLocation?.state_id || '',
      city_id: areaLocation?.city_id || '',
      area_id: areaLocation?.area_id || '',
      area_name: areaLocation?.area_name || areaLocation?.detected_area_name || '',
      detected_area_name: areaLocation?.detected_area_name || areaLocation?.area_name || '',
      sub_area_id: areaLocation?.sub_area_id || '',
      sub_area_name: areaLocation?.sub_area_name || areaLocation?.detected_sub_area_name || '',
      detected_sub_area_name: areaLocation?.detected_sub_area_name || areaLocation?.sub_area_name || '',
      min_price: minPrice || '',
      max_price: maxPrice || '',
      posted_since: postedSince === 'anytime' ? '' : postedSince,
      promoted: false,
      is_premium: false,
      amenities: amenities || [],
      nearbyPlaces: nearbyPlaces || [],
      latitude: undefined,
      longitude: undefined,
      range: undefined,
    };

    router.push(buildPropertySearchUrl(filters, { lang }));
    setShowAdvancedFilters(false);
  };

  const handleClearFilters = () => {
    setPropertyType('All');
    setSelectedCategory('');
    setKeywords('');
    setCity('');
    setState('');
    setCountry('');
    setLocationInput('');
    setMinPrice('');
    setMaxPrice('');
    setPostedSince('anytime');
    setAmenities([]);
    setNearbyPlaces([]);
    setAreaLocation({});
    setShowAdvancedFilters(false);
  };

  const autoplayPlugin = useMemo(
    () => Autoplay({ delay: 3000, stopOnInteraction: false, stopOnMouseEnter: false }),
    []
  );

  if (!images || images.length === 0) {
    return null;
  }


  return (
    <div className="relative w-full h-full pb-6 sm:pb-0">
      <Carousel
        plugins={images?.length > 1 ? [autoplayPlugin] : []}
        opts={{
          loop: images.length > 1,
          direction: isRtl ? "rtl" : "ltr",
        }}
        onMouseEnter={images.length > 1 ? autoplayPlugin.stop : undefined}
        onMouseLeave={images.length > 1 ? autoplayPlugin.play : undefined}
        onTouchStart={images.length > 1 ? autoplayPlugin.stop : undefined}
        onTouchEnd={images.length > 1 ? () => setTimeout(() => autoplayPlugin.play, 3000) : undefined}
        className="w-full h-full relative"
      >
        <CarouselContent>
          {images.map((image, index) => (
            <CarouselItem key={index}>
              <div className={`relative w-full h-full ${image?.slider_type === "4" || image?.slider_type === "2" ? "cursor-pointer" : ""}`} onClick={() => handleImageClick(image)} >
                <ImageWithPlaceholder
                  width={1920}
                  height={700}
                  src={image?.web_image}
                  alt={`Property ${index + 1}`}
                  className="w-full h-full aspect-[1920/1080] lg:aspect-[1920/700] xl:aspect-[auto/700] object-cover"
                  priority={true}
                />
                {image?.property && image?.show_property_details && (
                  <div className="absolute inset-0 pointer-events-none">
                    <div className="container mx-auto h-full relative">
                      <div className="absolute top-1/2 left-0 transform -translate-y-1/2 z-10 pl-9 lg:pl-9 xl:pl-0">
                        <div className="flex flex-col items-start p-2 sm:p-4 md:p-5 bg-white w-[214px] md:w-[400px] lg:w-[550px] shadow-lg pointer-events-auto gap-1 md:gap-2">
                          <div className="flex w-full justify-between">
                            <h2 className="text-base line-clamp-2 sm:text-lg md:text-xl lg:text-2xl font-bold text-gray-900 leading-tight">
                              {image.property.translated_title || image.property.title}
                            </h2>
                            <div className="flex items-center gap-1">
                              {image.property.property_type && (
                                <span
                                  className={`mt-0.5 shrink-0 flex items-center justify-center rounded-full px-3 py-1 text-xs font-semibold ${image.property.property_type === "sell" ? "primarySellBg primarySellText" : "primaryRentBg primaryRentText"}`}
                                >
                                  {t(image.property.property_type)}
                                </span>
                              )}
                              {image.property.is_premium == 1 ? (
                                <span className="flex items-center gap-1 rounded-full bg-[#F9EDD7] py-1 px-2 text-sm font-semibold capitalize">
                                  {premiumIcon()}
                                  <span className="hidden md:block">{t("premium")}</span>
                                </span>
                              ) : null}
                            </div>
                          </div>
                          <div className="flex items-center gap-3">
                            <ReactSVG
                              src={image?.category?.image}
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
                              className="w-4 h-4 flex items-center justify-center object-contain shrink-0"
                              alt={image?.category?.translated_name || image?.category?.category || 'parameter icon'}
                            />
                            <span className="text-gray-500">{image?.category?.translated_name || image?.category?.category}</span>
                          </div>
                          <div className="p-1 md:p-2 text-sm text-gray-500 font-medium">
                            {image?.property?.city ? `${image?.property?.city}, ` : ""}{image?.property?.state ? `${image?.property?.state}, ` : ""}{image?.property?.country}
                          </div>

                          {/* Display parameters */}
                          <div className="flex flex-wrap gap-1 md:gap-3 border-y p-1 md:p-2 w-full text-sm text-gray-500 font-medium">
                            {image?.property?.parameters?.slice(0, 4).map((p) => (
                              <div key={p.id} className="flex flex-row items-center justify-center gap-1 border-r last:border-r-0 pr-2 ">
                                <ReactSVG
                                  src={p.image}
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
                                  className="w-4 h-4 flex items-center justify-center object-contain shrink-0"
                                  alt={p.translated_name || p.name || 'parameter icon'}
                                />
                                {`${truncate(p.translated_name || p.name, 6)}: ${truncate(p.value, 6)}`}
                              </div>
                            ))}
                          </div>


                          {/* <hr className="w-full border-t border-gray-200 my-3 sm:my-4 md:my-6" /> */}

                          <div className="flex items-center justify-between w-full">
                            <div className="text-lg md:text-xl font-bold primaryColor">
                              {formatPriceAbbreviated(image.property.price)} <span className="text-sm font-normal">{image?.property?.property_type === "rent" && image?.property?.rentduration ? "/ " + image?.property?.rentduration : ""}</span>
                            </div>
                            <CustomLink
                              href={`/property-details/${image.property.slug_id}`}
                              className="bg-gray-900 text-white px-2 py-1 md:px-3 md:py-2 rounded-md text-sm font-normal h-auto pointer-events-auto flex items-center gap-1"
                            >
                              <span className="hidden md:block">{t("viewDetails")}</span>
                              <FaArrowRight size={14} className={`flex-shrink-0 ${isRtl ? "rotate-180" : ""}`} />
                            </CustomLink>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            </CarouselItem>
          ))}
        </CarouselContent>

        {images && images.length > 1 && (
          <>
            <CarouselPrevious className={"hidden md:absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-white hover:bg-gray-100 text-gray-800 border-0 w-8 h-10 md:w-10 md:h-16 rounded-none"} />
            <CarouselNext className="hidden md:absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-white hover:bg-gray-100 text-gray-800 border-0 w-8 h-10 md:w-10 md:h-16 rounded-none" />
          </>
        )}
      </Carousel>

      {/* Search Box Container - Restore absolute positioning */}
      <div className="absolute -bottom-2 md:-bottom-6 left-0 right-0 z-20 px-4">
        <div className="container mx-auto shadow-[0px_14px_36px_3px_#ADB3B852]">
          <SearchBox
            propertyType={propertyType}
            selectedCategory={selectedCategory}
            keywords={keywords}
            city={city}
            state={state}
            country={country}
            locationInput={locationInput}
            minPrice={minPrice}
            maxPrice={maxPrice}
            postedSince={postedSince}
            amenities={amenities}
            nearbyPlaces={nearbyPlaces}
            areaLocation={areaLocation}
            showAdvancedFilters={showAdvancedFilters}
            onPropertyTypeChange={handlePropertyTypeChange}
            onCategoryChange={handleCategoryChange}
            onKeywordsChange={handleKeywordsChange}
            onCityChange={handleCityChange}
            onStateChange={handleStateChange}
            onCountryChange={handleCountryChange}
            onLocationInputChange={handleLocationInputChange}
            onMinPriceChange={handleMinPriceChange}
            onMaxPriceChange={handleMaxPriceChange}
            onPostedSinceChange={handlePostedSinceChange}
            onAmenitiesChange={handleAmenitiesChange}
            onNearbyPlacesChange={handleNearbyPlacesChange}
            onAreaLocationChange={handleAreaLocationChange}
            onShowAdvancedFiltersChange={handleShowAdvancedFiltersChange}
            onApplyFilters={handleApplyFilters}
            onClearFilters={handleClearFilters}
          />
        </div>
      </div>
    </div>
  );
};

export default MainSwiper;
