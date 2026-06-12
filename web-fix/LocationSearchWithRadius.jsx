"use client";
import { useState, useRef, useEffect } from "react";
import { GoogleMap, Circle } from "@react-google-maps/api";
import AdvancedMapMarker, { withGoogleMapId } from "@/components/google-maps/AdvancedMapMarker";
import CustomLocationAutocomplete from "./CustomLocationAutocomplete";
import { IoLocationOutline } from "react-icons/io5";
import { useDispatch, useSelector } from "react-redux";
import { setLocationAction } from "@/redux/slices/locationSlice";
import toast from "react-hot-toast";
import { useTranslation } from "@/components/context/TranslationContext";
import { extractAddressComponents, isRTL, requestAccurateBrowserPosition } from "@/utils/helperFunction";
import { getMapDetailsApi } from "@/api/apiRoutes";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/components/ui/dialog";
import { useIsMobile } from "@/hooks/use-mobile";
import { MdOutlineMyLocation } from "react-icons/md";

const mapContainerStyle = {
    width: "100%",
    height: "100%",
    borderRadius: "0.5rem",
};

const defaultCenter = {
    lat: 25.7506,
    lng: 71.3930,
    radius: 1
};

const toFiniteNumber = (value, fallback) => {
    const number = Number(value);
    return Number.isFinite(number) ? number : fallback;
};

const makeCenter = (lat, lng, radius = 1) => ({
    lat: toFiniteNumber(lat, defaultCenter.lat),
    lng: toFiniteNumber(lng, defaultCenter.lng),
    radius,
});

const LocationSearchWithRadius = ({ isOpen, onClose }) => {
    const t = useTranslation();
    const dispatch = useDispatch();
    const isMobile = useIsMobile();
    const isRtl = isRTL();
    const webSettings = useSelector(state => state.WebSetting?.data);
    const systemSettings = webSettings || {};
    const currentLocation = useSelector(state => state.location);
    const minRadius = systemSettings?.min_radius_range || 1;
    const maxRadius = systemSettings?.max_radius_range || 50;

    const [searchInput, setSearchInput] = useState("");
    const [radius, setRadius] = useState(minRadius);
    const [isUserTyping, setIsUserTyping] = useState(false);
    const [location, setLocation] = useState(makeCenter(webSettings?.latitude, webSettings?.longitude, minRadius));
    const [address, setAddress] = useState("");
    const [showTooltip, setShowTooltip] = useState(false);
    const [tooltipPosition, setTooltipPosition] = useState("0%");
    const [selectedLocation, setSelectedLocation] = useState(currentLocation?.isLocationSet ? {
        lat: toFiniteNumber(currentLocation.latitude, defaultCenter.lat),
        lng: toFiniteNumber(currentLocation.longitude, defaultCenter.lng),
        address: currentLocation.formatted_address || "",
        city: currentLocation.city || "",
        state: currentLocation.state || "",
        country: currentLocation.country || "",
        radius: currentLocation.radius || minRadius
    } : makeCenter(webSettings?.latitude, webSettings?.longitude, minRadius));
    const [map, setMap] = useState(null);
    const sliderRef = useRef(null);
    const mapRef = useRef(null);
    const tooltipRef = useRef(null);
    const typingTimeoutRef = useRef(null);
    const [mapType, setMapType] = useState("roadmap");


    // API-based reverse geocoding for address lookup
    async function fetchAddressFromCoordinates(lat, lng) {
        try {
            const response = await getMapDetailsApi({
                latitude: lat.toString(),
                longitude: lng.toString(),
                place_id: ""
            });

            if (response?.error === false && response?.data?.result) {
                const firstResult = response.data.result;
                const { city, state, country, formattedAddress } = extractAddressComponents(firstResult);
                const resolvedAddress = formattedAddress || `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

                setAddress(resolvedAddress);
                if (!isUserTyping) {
                    setSearchInput(resolvedAddress);
                }

                const locationData = {
                    lat: parseFloat(lat),
                    lng: parseFloat(lng),
                    address: resolvedAddress,
                    city,
                    state,
                    country,
                    radius: radius
                };

                setSelectedLocation(locationData);
                return locationData;
            } else if (response?.data && response.data.formatted_address) {
                // Fallback to old format if exists
                const formattedAddress = response.data.formatted_address || response.data.address || `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

                setAddress(formattedAddress);
                // Only update searchInput if user is not actively typing
                if (!isUserTyping) {
                    setSearchInput(formattedAddress);
                }
                setSelectedLocation({
                    lat: parseFloat(lat),
                    lng: parseFloat(lng),
                    address: formattedAddress,
                    city: response.data.city || "",
                    state: response.data.state || "",
                    country: response.data.country || "",
                    radius: radius
                });
            } else {
                // Fallback to coordinates if API fails
                const fallbackAddress = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                setAddress(fallbackAddress);
                // Only update searchInput if user is not actively typing
                if (!isUserTyping) {
                    setSearchInput(fallbackAddress);
                }
                setSelectedLocation({
                    lat,
                    lng,
                    address: fallbackAddress,
                    city: "",
                    state: "",
                    country: "",
                    radius: radius
                });
            }
        } catch (error) {
            console.error("Error fetching address from coordinates:", error?.message);
            setIsUserTyping(false);
        }
    }

    // Handle place selection from custom autocomplete
    const handlePlaceSelect = (placeData, placeDetails) => {
        if (placeData) {
            const lat = toFiniteNumber(
                placeData.latitude ?? placeData.geometry?.location?.lat?.() ?? placeData.geometry?.location?.lat,
                defaultCenter.lat
            );
            const lng = toFiniteNumber(
                placeData.longitude ?? placeData.geometry?.location?.lng?.() ?? placeData.geometry?.location?.lng,
                defaultCenter.lng
            );
            const { city, state, country, formattedAddress } = extractAddressComponents(placeData);
            const resolvedAddress = formattedAddress || placeData.formatted_address || "";

            setLocation({ lat, lng, radius });
            setAddress(resolvedAddress);
            setSearchInput(resolvedAddress);
            setSelectedLocation({ lat, lng, address: resolvedAddress, city, state, country, radius });
            // Reset typing state since place was selected
            setIsUserTyping(false);

            if (map || mapRef.current) {
                const mapInstance = map || mapRef.current;
                mapInstance.panTo({ lat, lng });
                mapInstance.setZoom(14);
            }
        }
    };

    // Map click handler
    const handleMapClick = async (event) => {
        const lat = event.latLng.lat();
        const lng = event.latLng.lng();

        // Update location state immediately
        setLocation({ lat, lng, radius });

        // Fetch address details using the API
        await fetchAddressFromCoordinates(lat, lng);

        // Update map center to clicked position
        if (map || mapRef.current) {
            const mapInstance = map || mapRef.current;
            mapInstance.panTo({ lat, lng });
        }
    };

    // Marker drag handler
    const handleMarkerDragEnd = async (event) => {
        const lat = event.latLng.lat();
        const lng = event.latLng.lng();

        // Update location state immediately
        setLocation({ lat, lng, radius });

        // Fetch address details using the API
        await fetchAddressFromCoordinates(lat, lng);

        // Update map center to marker position
        if (map || mapRef.current) {
            const mapInstance = map || mapRef.current;
            mapInstance.panTo({ lat, lng });
        }
    };

    // Find my location handler
    // const handleFindMyLocation = () => {
    //     if (navigator.geolocation) {
    //         navigator.geolocation.getCurrentPosition(
    //             async (position) => {
    //                 const lat = position.coords.latitude;
    //                 const lng = position.coords.longitude;

    //                 // Update location state immediately
    //                 setLocation({ lat, lng });

    //                 // Fetch address details using the API
    //                 await fetchAddressFromCoordinates(lat, lng);

    //                 // Update map view
    //                 if (map || mapRef.current) {
    //                     const mapInstance = map || mapRef.current;
    //                     mapInstance.panTo({ lat, lng });
    //                     mapInstance.setZoom(14);
    //                 }
    //             },
    //             () => {
    //                 toast.error(t("locationAccessDenied"));
    //             },
    //             {
    //                 enableHighAccuracy: true,
    //                 timeout: 10000,
    //                 maximumAge: 0,
    //             },
    //         );
    //     } else {
    //         toast.error(t("geolocationNotSupported"));
    //     }
    // };
    const handleFindMyLocation = async () => {
        if (!("geolocation" in navigator)) {
            toast.error(t("geolocationNotSupported"));
            return;
        }

        try {
            // 1️⃣ Check permission explicitly
            if ("permissions" in navigator) {
                const status = await navigator.permissions.query({ name: "geolocation" });

                if (status.state === "denied") {
                    toast.error(t("locationAccessDenied"));
                    return;
                }
            }

            // 2️⃣ Get fresh position and reject low-accuracy IP/network fallbacks
            const position = await requestAccurateBrowserPosition({ timeout: 25000, maxAccuracy: 5000 });

            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            // 3️⃣ Reset typing state so fetchAddressFromCoordinates updates the search input
            setIsUserTyping(false);

            // 4️⃣ Update state
            setLocation({ lat, lng, radius });

            await fetchAddressFromCoordinates(lat, lng);

            // 5️⃣ Update map
            if (map || mapRef.current) {
                const mapInstance = map || mapRef.current;
                mapInstance.panTo({ lat, lng });
                mapInstance.setZoom(14);
            }
        } catch (error) {
            // 6️⃣ Only show toast for REAL permission denial
            if (error?.code === 1) {
                toast.error(t("locationAccessDenied"));
            } else if (error?.code === "LOW_ACCURACY_LOCATION") {
                console.warn("Low accuracy browser location rejected:", error);
                toast.error("Location accuracy is too low. Please search your address or drag the map pin.");
            } else {
                console.warn("Temporary geolocation failure:", error);
                toast.error(t("locationUnavailable"));
            }
        }
    };

    // Slider change handler
    // const handleRadiusChange = (e) => {
    //     setRadius(Number(e.target.value));
    //     setTooltipPosition(calculateTooltipPosition(e.target.value));
    // };

    // Clear handler
    const handleClear = async () => {
        const defaultLocation = {
            lat: parseFloat(webSettings?.latitude),
            lng: parseFloat(webSettings?.longitude),
            radius: minRadius
        };
        const data = await fetchAddressFromCoordinates(webSettings?.latitude, webSettings?.longitude);
        setSelectedLocation({
            lat: parseFloat(webSettings?.latitude),
            lng: parseFloat(webSettings?.longitude),
            address: data?.address || "",
            city: data?.city || "",
            state: data?.state || "",
            country: data?.country || "",
            radius: minRadius
        });
        setLocation(defaultLocation);
        setSearchInput(data?.address);
        setRadius(minRadius);
        setShowTooltip(false);
        setIsUserTyping(false); // Reset typing state
        updateSliderUI(minRadius);
        if (map || mapRef.current) {
            const mapInstance = map || mapRef.current;
            mapInstance.panTo({ lat: defaultLocation.lat, lng: defaultLocation.lng });
            mapInstance.setZoom(14);
        }
    };

    // Save handler — map pin (location) is source of truth for coordinates
    const handleSaveLocation = async () => {
        try {
            const latitude = toFiniteNumber(location?.lat ?? selectedLocation?.lat, NaN);
            const longitude = toFiniteNumber(location?.lng ?? selectedLocation?.lng, NaN);

            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                toast.error(t("enterLocation"));
                return;
            }

            let formatted_address = (selectedLocation.address || address || "").trim();
            let city = selectedLocation.city || "";
            let state = selectedLocation.state || "";
            let country = selectedLocation.country || "";

            if ((!city && !state && !country) && formatted_address) {
                const segments = formatted_address.split(",").map((s) => s.trim()).filter(Boolean);
                if (segments.length >= 1) country = segments[segments.length - 1];
                if (segments.length >= 2) state = segments[segments.length - 2];
                if (segments.length >= 3) city = segments[0];
            }

            if (!formatted_address) {
                formatted_address = `${latitude.toFixed(4)}, ${longitude.toFixed(4)}`;
            }

            dispatch(setLocationAction({
                formatted_address,
                latitude,
                longitude,
                city,
                state,
                country,
                radius,
            }));
            toast.success(t("locationUpdated"));
            onClose();
        } catch (error) {
            console.error('Error saving location:', error);
            toast.error(t("locationSaveError"));
        }
    };

    useEffect(() => {
        if (currentLocation?.isLocationSet && isOpen) {
            setLocation({
                lat: parseFloat(currentLocation.latitude),
                lng: parseFloat(currentLocation.longitude),
                radius: currentLocation.radius || minRadius
            });
            setSelectedLocation({
                lat: parseFloat(currentLocation.latitude) || parseFloat(webSettings?.latitude),
                lng: parseFloat(currentLocation.longitude) || parseFloat(webSettings?.longitude),
                address: currentLocation.formatted_address || "",
                city: currentLocation.city || "",
                state: currentLocation.state || "",
                country: currentLocation.country || "",
                radius: currentLocation.radius || minRadius
            });
            setSearchInput(currentLocation.formatted_address || "");
        }
    }, [currentLocation, isOpen]);

    // Initialize location with webSettings when available
    useEffect(() => {
        if (webSettings?.latitude && webSettings?.longitude && !currentLocation?.isLocationSet) {
            const lat = Number(webSettings.latitude);
            const lng = Number(webSettings.longitude);
            if (!isNaN(lat) && !isNaN(lng)) {
                setLocation({ lat, lng, radius: minRadius });
                setSelectedLocation(prev => ({
                    ...prev,
                    lat,
                    lng
                }));
            }
        }
    }, [webSettings, currentLocation, minRadius]);

    useEffect(() => {
        if (isOpen) {
            const radiusValue = currentLocation?.radius || minRadius;
            setRadius(radiusValue);
            // Use setTimeout to ensure DOM elements are ready
            setTimeout(() => {
                updateSliderUI(radiusValue);
            }, 0);
        }
    }, [isOpen, currentLocation?.radius, minRadius]);

    // Additional effect to ensure slider UI is updated when modal content is rendered
    useEffect(() => {
        if (isOpen && sliderRef.current) {
            updateSliderUI(radius);
        }
    }, [isOpen, radius, sliderRef.current]);

    // Prevent autocomplete input from auto-focusing on mobile when modal opens
    useEffect(() => {
        if (isOpen && isMobile) {
            // Small delay to ensure DOM is ready
            setTimeout(() => {
                const autocompleteInput = document.querySelector('input[placeholder*="' + t("enterLocation") + '"]');
                if (autocompleteInput) {
                    autocompleteInput.blur();
                    // Clear any user interaction flags when modal opens
                    delete autocompleteInput.dataset.userClicked;
                }
            }, 100);
        }
    }, [isOpen, isMobile, t]);

    const handleKeyPress = (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
        }
    };

    // Handle search input changes while tracking typing state
    const handleSearchInputChange = (e) => {
        setIsUserTyping(true);
        setSearchInput(e.target.value);

        // Clear typing state after user stops typing
        if (typingTimeoutRef.current) {
            clearTimeout(typingTimeoutRef.current);
        }
        typingTimeoutRef.current = setTimeout(() => {
            setIsUserTyping(false);
        }, 1000); // 1 second delay after stopping typing
    };


    const updateSliderUI = (value) => {
        if (!sliderRef.current) return;

        const percent = ((value - minRadius) / (maxRadius - minRadius)) * 100;

        // Set the progress percentage - CSS handles the direction
        sliderRef.current.style.setProperty("--range-progress", `${percent}%`);

        if (tooltipRef.current) {
            const sliderWidth = sliderRef.current.offsetWidth;
            const thumbWidth = 20;

            // Calculate tooltip position based on text direction
            let left;
            if (isRtl) {
                // For RTL, position from right side
                left = ((100 - percent) / 100) * (sliderWidth - thumbWidth) + thumbWidth / 2;
            } else {
                // For LTR, position from left side (original logic)
                left = (percent / 100) * (sliderWidth - thumbWidth) + thumbWidth / 2;
            }

            tooltipRef.current.style.left = `${left}px`;
        }
    };

    const handleRadiusChange = (e) => {
        const value = Number(e.target.value);
        setRadius(value);
        updateSliderUI(value);
    };

    useEffect(() => {
        updateSliderUI(radius);
        const handleResize = () => updateSliderUI(radius);
        window.addEventListener("resize", handleResize);
        return () => {
            window.removeEventListener("resize", handleResize);
            // Cleanup typing timeout
            if (typingTimeoutRef.current) {
                clearTimeout(typingTimeoutRef.current);
            }
        };
    }, [radius, minRadius, maxRadius]);

    return (
        <Dialog open={isOpen} onOpenChange={(open) => {
            // Only close if explicitly clicking outside or pressing escape
            if (!open) {
                onClose();
            }
        }}>
            <DialogContent
                className="md:max-w-2xl p-0 rounded-xl max-h-screen overflow-y-auto custom-scrollbar"
                onPointerDownOutside={(e) => {
                    // Prevent closing when clicking on the autocomplete dropdown
                    if (e.target.closest('.pac-container')) {
                        e.preventDefault();
                    }
                }}
                onOpenAutoFocus={(e) => {
                    // Prevent auto-focus on mobile devices to avoid keyboard popup
                    if (isMobile) {
                        e.preventDefault();
                    }
                }}>
                <DialogHeader className={`text-start space-y-4 px-5 pt-5 ${isRtl ? 'text-right' : 'text-left'}`}>
                    <DialogTitle>{t("selectLocation")}</DialogTitle>
                    <DialogDescription className="mt-2">
                        {t("locationDescription")}
                    </DialogDescription>
                </DialogHeader>

                <div className="p-4">
                    {/* Search and Find My Location */}
                    <div className="flex justify-center primaryBackgroundBg items-center border rounded-lg p-1 mb-4 relative">
                        <div className="w-full">
                            <CustomLocationAutocomplete
                                value={searchInput}
                                onChange={handleSearchInputChange}
                                onPlaceSelect={handlePlaceSelect}
                                placeholder={t("enterLocation")}
                                className="w-full flex-grow rounded-l-lg primaryBackgroundBg px-2 py-2 focus:outline-none border-0"
                                showFindMyLocation={false}
                                debounceMs={1000}
                                maxResults={10}
                                inputProps={{
                                    onKeyDown: handleKeyPress,
                                    onClick: (e) => e.stopPropagation(),
                                    autoFocus: false,
                                    autoComplete: "off",
                                    onFocus: (e) => {
                                        // Mark user as actively typing when focused
                                        setIsUserTyping(true);
                                        // Prevent focus on mobile devices unless explicitly clicked
                                        if (isMobile && !e.target.dataset.userClicked) {
                                            e.target.blur();
                                        }
                                    },
                                    onBlur: (e) => {
                                        // Clear typing state when input loses focus
                                        setTimeout(() => setIsUserTyping(false), 100);
                                    },
                                    onMouseDown: (e) => {
                                        // Mark that user explicitly clicked the input
                                        e.target.dataset.userClicked = "true";
                                    },
                                    onTouchStart: (e) => {
                                        // Mark that user explicitly touched the input
                                        e.target.dataset.userClicked = "true";
                                    }
                                }}
                            />
                        </div>
                        <button
                            type="button"
                            onClick={handleFindMyLocation}
                            className="flex items-center text-nowrap justify-center px-2.5 py-2 primaryBg text-white rounded-md hover:opacity-80 gap-2"
                        >
                            <MdOutlineMyLocation className="fill-white" />
                            {!isMobile ? t("findMyLocation") : null}
                        </button>
                    </div>

                    {/* Map */}
                    <div className="mb-4">
                        <div className="relative rounded-lg overflow-hidden h-[13rem] md:h-[21rem]" >
                            <GoogleMap
                                mapContainerStyle={mapContainerStyle}
                                center={makeCenter(selectedLocation?.lat ?? location?.lat, selectedLocation?.lng ?? location?.lng, radius)}
                                zoom={12}
                                onLoad={(map) => {
                                    mapRef.current = map;
                                    setMap(map);
                                    setMapType(map.getMapTypeId()); // initial type
                                }}
                                onClick={handleMapClick}
                                options={withGoogleMapId({
                                    draggable: true,
                                    fullscreenControl: false,
                                    cameraControl: false,
                                    streetViewControl: false,
                                })}
                                onMapTypeIdChanged={() => {
                                    if (mapRef.current) {
                                        setMapType(mapRef.current.getMapTypeId());
                                    }
                                }}

                            >
                                <AdvancedMapMarker
                                    position={makeCenter(location?.lat, location?.lng, radius)}
                                    draggable
                                    onDragEnd={handleMarkerDragEnd}
                                    title={t("location") || "Location"}
                                />
                                <Circle

                                    center={makeCenter(location?.lat, location?.lng, radius)}
                                    radius={radius * 1000}
                                    options={{
                                        strokeColor: mapType === "hybrid" || mapType === "satellite" ? "#ffffff" : webSettings?.system_color,
                                        fillColor: mapType === "hybrid" || mapType === "satellite" ? "#ffffff" : webSettings?.system_color,
                                        strokeOpacity: 0.8,
                                        strokeWeight: 2,
                                        fillOpacity: 0.2,
                                    }}
                                />
                            </GoogleMap>
                        </div>
                    </div>

                    {/* KM Range Slider */}
                    <div className="mb-3 primaryBackgroundBg px-4 py-3 rounded-lg">
                        <div className="flex items-center justify-between mb-1">
                            <h3 className="font-medium text-gray-800">{t("kmRange")}</h3>
                        </div>
                        <div className="relative w-full" dir={isRtl ? "rtl" : "ltr"}>
                            <div className="relative w-full">
                                {/* Tooltip */}
                                <div
                                    ref={tooltipRef}
                                    className={`absolute -top-12 z-10 -translate-x-1/2 flex flex-col items-center pointer-events-none transition-all duration-200 ${showTooltip ? 'opacity-100 visible' : 'opacity-0 invisible'
                                        }`}
                                >
                                    <div className="bg-black text-white text-sm font-semibold px-3 py-1 rounded">
                                        {radius} {t("km")}
                                    </div>
                                    <div className="w-3 h-3 bg-black rotate-45 mt-[-6px]" />
                                </div>

                                {/* Slider */}
                                <input
                                    ref={sliderRef}
                                    type="range"
                                    min={minRadius}
                                    max={maxRadius}
                                    value={radius}
                                    onChange={handleRadiusChange}
                                    className="w-full h-3 appearance-none bg-transparent location-slider"
                                    onMouseEnter={() => setShowTooltip(true)}
                                    onMouseLeave={() => setShowTooltip(false)}
                                    onMouseDown={() => setShowTooltip(true)}
                                    onMouseUp={() => setShowTooltip(false)}
                                    onTouchStart={() => setShowTooltip(true)}
                                    onTouchEnd={() => setShowTooltip(false)}
                                    onFocus={() => setShowTooltip(true)}
                                    onBlur={() => setShowTooltip(false)}
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter className={`!flex-row px-4 pb-4 ${isRtl ? 'justify-start' : 'justify-end'}`}>
                    <button
                        onClick={handleClear}
                        className="px-6 py-2 rounded-lg hover:bg-gray-50"
                    >
                        {t("clear")}
                    </button>
                    <button
                        onClick={handleSaveLocation}
                        className="px-6 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700"
                    >
                        {t("saveLocation")}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
};

export default LocationSearchWithRadius;