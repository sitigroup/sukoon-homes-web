import { useRef, useState } from 'react'
import { useRouter } from 'next/router'
import { useSelector } from 'react-redux'
import { useTranslation } from '@/components/context/TranslationContext'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Autocomplete } from '@react-google-maps/api'
import { extractAddressComponents, requestAccurateBrowserPosition } from '@/utils/helperFunction'
import toast from 'react-hot-toast'
import Map from '@/components/google-maps/GoogleMap'
import CustomLocationAutocomplete from '@/components/location-search/CustomLocationAutocomplete'
import AreaSubAreaSelector from '@/plugins/area-listing/AreaSubAreaSelector'
import {
    applyResolveResultToLocation,
    buildLocationComponentsFromPlace,
    extractAreaListingFromPlace,
} from '@/plugins/area-listing/areaListingUtils'
import { resolveAreaListingCoordinates } from '@/plugins/area-listing/areaListingApi'
import { canManageAreaListingInContext } from '@/plugins/area-listing/areaListingPermissions'
import { getMapDetailsApi } from '@/api/apiRoutes'

const GPS_LOW_ACCURACY_STYLE = { whiteSpace: 'pre-line', maxWidth: '420px', lineHeight: 1.45 };


const LocationComponent = ({
    selectedLocationAddress,
    setSelectedLocationAddress,
    handleLocationSelect,
    handleCheckRequiredFields,
    isEditing = false,
    isProperty = true
}) => {
    const t = useTranslation();
    const router = useRouter();
    const userData = useSelector((state) => state.User?.data);
    const canManageAreaListing = canManageAreaListingInContext(userData, router.asPath);
    const autocompleteRef = useRef(null);
    const [isDetectingLocation, setIsDetectingLocation] = useState(false);
    const [locationConfirmation, setLocationConfirmation] = useState({ required: false, source: "", summary: "" });
    const [cityMismatch, setCityMismatch] = useState(null);

    const buildDetectedSummary = (location = {}) => [
        location.formattedAddress,
        location.detected_sub_area_name,
        location.detected_area_name,
        location.city,
        location.state,
    ].filter(Boolean).filter((value, index, values) => values.indexOf(value) === index).join(", ");

    const requireLocationConfirmation = (source, location) => {
        setLocationConfirmation({
            required: true,
            source,
            summary: buildDetectedSummary(location)
        });
    };

    const normalizeLocationName = (value = "") => String(value || "").toLowerCase().replace(/\s+/g, " ").trim();

    const detectedCityDiffers = (detectedLocation = {}, currentLocation = {}) => {
        const detectedCity = normalizeLocationName(detectedLocation.city);
        const currentCity = normalizeLocationName(currentLocation.city);
        return Boolean(detectedCity && currentCity && detectedCity !== currentCity);
    };

    /** GPS / map pin = user chose a new physical location; apply detected city. */
    const shouldUseDetectedCity = (source = "") => {
        const s = String(source).toLowerCase();
        return (
            s.includes("map pin") ||
            s.includes("current location") ||
            s.includes("browser current")
        );
    };

    const keepSelectedCityLocation = (detectedLocation = {}, currentLocation = {}) => ({
        ...detectedLocation,
        city: currentLocation.city || detectedLocation.city,
        state: currentLocation.state || detectedLocation.state,
        country: currentLocation.country || detectedLocation.country,
        state_id: currentLocation.state_id || detectedLocation.state_id || "",
        city_id: currentLocation.city_id || detectedLocation.city_id || "",
        area_id: detectedLocation.area_id || currentLocation.area_id || "",
        area_name: detectedLocation.area_name || currentLocation.area_name || "",
        detected_area_name:
            detectedLocation.detected_area_name ||
            detectedLocation.area_name ||
            currentLocation.detected_area_name ||
            "",
        sub_area_id: "",
        sub_area_name: "",
        detected_sub_area_name:
            detectedLocation.detected_sub_area_name ||
            detectedLocation.sub_area_name ||
            currentLocation.detected_sub_area_name ||
            "",
    });

    const clearAreaSubAreaSelection = (location = {}) => ({
        ...location,
        area_id: '',
        sub_area_id: '',
        area_name: '',
        sub_area_name: '',
        detected_sub_area_name: '',
        area_listing_user_confirmed: false,
    });

    const enrichLocationWithAreaResolve = async (source, baseLocation, place = null) => {
        let merged = clearAreaSubAreaSelection({ ...baseLocation });
        if (place) {
            merged = { ...merged, ...extractAreaListingFromPlace(place) };
        }

        const lat = merged.latitude ?? merged.lat;
        const lng = merged.longitude ?? merged.lng;
        if (lat != null && lng != null && !Number.isNaN(Number(lat)) && !Number.isNaN(Number(lng))) {
            try {
                const response = await resolveAreaListingCoordinates({
                    latitude: lat,
                    longitude: lng,
                    city_id: merged.city_id || '',
                    city: merged.city || '',
                    state: merged.state || '',
                    country: merged.country || 'India',
                    location_components: JSON.stringify(buildLocationComponentsFromPlace(place || {})),
                });
                if (!response?.error && response?.data) {
                    merged = applyResolveResultToLocation(merged, response.data, {
                        applyResolvedIds: canManageAreaListing,
                    });
                    if (response.data.area_id && !response.data.sub_area_id && place) {
                        const extracted = extractAreaListingFromPlace(place);
                        if (extracted.detected_sub_area_name) {
                            merged.detected_sub_area_name = extracted.detected_sub_area_name;
                        }
                    }
                    if (!canManageAreaListing) {
                        merged.sub_area_id = '';
                        merged.sub_area_name = '';
                    }
                }
            } catch (error) {
                console.warn('Area resolve failed:', error);
            }
        }

        applyDetectedLocation(source, merged);
    };

    const applyDetectedLocation = (source, detectedLocation) => {
        const currentLocation = selectedLocationAddress || {};
        if (!shouldUseDetectedCity(source) && detectedCityDiffers(detectedLocation, currentLocation)) {
            const keptLocation = keepSelectedCityLocation(detectedLocation, currentLocation);
            setSelectedLocationAddress(prev => ({ ...prev, ...keptLocation }));
            handleLocationSelect?.(keptLocation);
            setCityMismatch({
                source,
                detected: detectedLocation,
                kept: keptLocation,
                selectedCity: currentLocation.city,
                detectedCity: detectedLocation.city,
            });
            requireLocationConfirmation(source, keptLocation);
            return;
        }

        setCityMismatch(null);
        setSelectedLocationAddress(prev => ({ ...prev, ...detectedLocation }));
        handleLocationSelect?.(detectedLocation);
        setLocationConfirmation({ required: false, source: '', summary: '' });
    };

    const keepSelectedCity = () => {
        setCityMismatch(null);
        toast.success("Selected city kept. Please verify area and sub area before saving.");
    };

    const switchToDetectedCity = () => {
        if (!cityMismatch?.detected) return;
        const detectedLocation = {
            ...cityMismatch.detected,
            state_id: "",
            city_id: "",
            area_id: "",
            sub_area_id: "",
            area_name: "",
            sub_area_name: "",
        };
        setSelectedLocationAddress(prev => ({ ...prev, ...detectedLocation }));
        handleLocationSelect?.(detectedLocation);
        setCityMismatch(null);
        requireLocationConfirmation(cityMismatch.source || "Detected location", detectedLocation);
        toast.success("Detected city applied. Please select the correct area and sub area.");
    };

    const confirmDetectedLocation = () => {
        setLocationConfirmation({
            required: false,
            source: "",
            summary: buildDetectedSummary(selectedLocationAddress)
        });
        setCityMismatch(null);
        toast.success("Location confirmed. You can save after verifying the area and sub area.");
    };

    const handleLocationStepNext = () => {
        if (locationConfirmation.required) {
            toast.error("Please confirm the detected location, or change city/area/sub area before saving.");
            return;
        }
        handleCheckRequiredFields("location", isProperty ? "seoSettings" : "floorDetails");
    };

    const handleCityPlaceSelect = () => {
        if (autocompleteRef.current) {
            const place = autocompleteRef.current.getPlace();
            if (place && place.geometry && place.geometry.location) {
                try {
                    const location = place.geometry.location;

                    // Extract address components
                    const addressData = extractAddressComponents(place);

                    // Update the form with city and state from the selected place
                    setSelectedLocationAddress(prev => ({
                        ...prev,
                        city: addressData.city || '',
                        state: addressData.state || '',
                        latitude: location.lat ? location.lat() : prev.latitude,
                        longitude: location.lng ? location.lng() : prev.longitude,
                        country: addressData.country || prev.country,
                        formattedAddress: addressData.formattedAddress || prev.formattedAddress
                    }));
                } catch (error) {
                    console.error("Error processing place data:", error);
                }
            }
        }
    };

    // Handle place selection from CustomLocationAutocomplete
    const handleCustomLocationSelect = (placeData, detailResponse) => {
        try {
            if (placeData) {
                // Extract address components from the place data
                let addressData = {};

                // If we have detailed response with address_components, use extractAddressComponents
                if (detailResponse && detailResponse.address_components) {
                    addressData = extractAddressComponents(detailResponse);
                } else if (placeData.address_components && placeData.address_components.length > 0) {
                    // Use the address_components from placeData if available
                    addressData = extractAddressComponents(placeData);
                } else {
                    // Fallback: try to parse from formatted_address
                    const formattedAddress = placeData.formatted_address || '';
                    const parts = formattedAddress.split(',').map(part => part.trim());

                    addressData = {
                        formattedAddress: formattedAddress,
                        city: parts.length > 1 ? parts[parts.length - 3] || '' : '',
                        state: parts.length > 2 ? parts[parts.length - 2] || '' : '',
                        country: parts.length > 0 ? parts[parts.length - 1] || '' : '',
                    };
                }

                // Create the location data object
                const updatedLocationData = {
                    city: addressData.city || selectedLocationAddress.city,
                    state: addressData.state || selectedLocationAddress.state,
                    country: addressData.country || selectedLocationAddress.country,
                    formattedAddress: addressData.formattedAddress || placeData.formatted_address || selectedLocationAddress.formattedAddress,
                    ...extractAreaListingFromPlace(detailResponse || placeData),
                    area_listing_source: 'google',
                    latitude: placeData.latitude || (placeData.geometry?.location?.lat ? placeData.geometry.location.lat() : selectedLocationAddress.latitude),
                    longitude: placeData.longitude || (placeData.geometry?.location?.lng ? placeData.geometry.location.lng() : selectedLocationAddress.longitude),
                    lat: placeData.latitude || (placeData.geometry?.location?.lat ? placeData.geometry.location.lat() : selectedLocationAddress.latitude),
                    lng: placeData.longitude || (placeData.geometry?.location?.lng ? placeData.geometry.location.lng() : selectedLocationAddress.longitude)
                };

                enrichLocationWithAreaResolve('Google search', updatedLocationData, detailResponse || placeData);
            }
        } catch (error) {
            console.error("Error processing custom location data:", error);
        }
    };

    const handleMapLocationSelect = async (address = {}) => {
        const baseLocation = {
            ...selectedLocationAddress,
            city: address.city || "",
            state: address.state || "",
            country: address.country || selectedLocationAddress.country,
            state_id: "",
            city_id: "",
            formattedAddress: address.formattedAddress || selectedLocationAddress.formattedAddress,
            latitude: address.latitude ?? address.lat ?? selectedLocationAddress.latitude,
            longitude: address.longitude ?? address.lng ?? selectedLocationAddress.longitude,
            lat: address.lat ?? address.latitude ?? selectedLocationAddress.lat,
            lng: address.lng ?? address.longitude ?? selectedLocationAddress.lng,
            area_listing_source: 'google',
        };
        await enrichLocationWithAreaResolve('Map pin', baseLocation, address._place || null);
    };

    const reverseGeocodeCurrentPosition = async (lat, lng) => {
        if (window.google?.maps?.Geocoder) {
            try {
                const geocoder = new window.google.maps.Geocoder();
                const response = await geocoder.geocode({ location: { lat, lng } });
                if (response?.results?.[0]) return response.results[0];
            } catch (error) {
                console.warn('Google JS geocoder failed, trying backend geocoder:', error);
            }
        }

        const response = await getMapDetailsApi({
            latitude: String(lat),
            longitude: String(lng),
            place_id: "",
        });
        return response?.data?.result || response?.data?.results?.[0] || null;
    };

    const handleFindCurrentLocation = async () => {
        setIsDetectingLocation(true);
        try {
            const position = await requestAccurateBrowserPosition({ timeout: 30000, maxAccuracy: 5000, settleTime: 15000, allowLowAccuracy: true });
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            let place = null;

            try {
                place = await reverseGeocodeCurrentPosition(lat, lng);
            } catch (error) {
                console.warn('Reverse geocoding failed:', error);
                toast.error('Location detected, but address lookup failed. Please adjust the address manually.');
            }

            const addressData = place ? extractAddressComponents(place) : {};
            const updatedLocation = {
                city: place ? (addressData.city || "") : (addressData.city || selectedLocationAddress.city),
                state: place ? (addressData.state || "") : (addressData.state || selectedLocationAddress.state),
                country: addressData.country || selectedLocationAddress.country,
                state_id: place ? "" : selectedLocationAddress.state_id,
                city_id: place ? "" : selectedLocationAddress.city_id,
                formattedAddress: addressData.formattedAddress || selectedLocationAddress.formattedAddress,
                latitude: lat,
                longitude: lng,
                lat,
                lng,
                ...extractAreaListingFromPlace(place || {}),
                area_listing_source: place ? 'google' : 'browser',
                location_is_verified: !position.lowAccuracy && Number(position.coords.accuracy || 0) <= 100,
            };

            await enrichLocationWithAreaResolve(
                place ? 'Google current location' : 'Browser current location',
                updatedLocation,
                place || null
            );

            const accuracyM = Number(position.coords.accuracy || 0);
            if (position.lowAccuracy || accuracyM > 500) {
                const rounded = Math.round(accuracyM || 0);
                toast.error(
                    `GPS accuracy is low (${rounded}m).\n\nYour location may be incorrect.\n\nPlease drag the map pin to the correct property location.\n\nArea will update automatically after you move the pin.`,
                    { duration: 8000, style: GPS_LOW_ACCURACY_STYLE }
                );
            }
        } catch (error) {
            console.warn('Current location rejected:', error);
            const message = error?.code === 1
                ? 'Location permission is blocked. Please allow location access in your browser settings.'
                : error?.code === 'LOW_ACCURACY_LOCATION'
                    ? 'Location accuracy is too low. Please turn on device GPS/location services, then try again, or enter latitude and longitude manually.'
                    : 'Unable to get current location. Please allow location access and ensure device location/GPS is enabled.';
            toast.error(message);
        } finally {
            setIsDetectingLocation(false);
        }
    };

    // Handle input change for city field
    const handleCityInputChange = (e) => {
        const value = e.target.value;
        // Create a new object to avoid mutating the original data
        setSelectedLocationAddress(prev => ({
            ...prev,
            city: value,
            area_id: "",
            area_name: "",
            sub_area_id: "",
            sub_area_name: ""
        }));
        if (locationConfirmation.required) {
            setLocationConfirmation(prev => ({ ...prev, summary: "" }));
        }
    };

    return (
        <div className="flex flex-col gap-8">
            <div className='font-medium text-gray-800'>{isProperty ? t("selectPropertyLocationNote") : t("selectProjectLocationNote")}</div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-4">
                    {/* City */}
                    <div className='flex w-full gap-3'>
                        <div className="w-1/2">
                            <Label htmlFor="city" className="font-medium text-gray-800">
                                {t("city")} <span className="text-red-500">*</span>
                            </Label>
                            <div className="relative">
                                <CustomLocationAutocomplete
                                    value={selectedLocationAddress.city || ''}
                                    onChange={handleCityInputChange}
                                    onPlaceSelect={handleCustomLocationSelect}
                                    placeholder={t("searchCity")}
                                    className="w-full px-3 py-2 primaryBackgroundBg rounded-md focus:outline-none focus:border-none focus:border-transparent pr-10"
                                    debounceMs={1000}
                                    maxResults={10}
                                    isPropertyOrProjectOperation={true}
                                />
                            </div>
                        </div>
                        <div className="w-1/2">
                            <Label htmlFor="state" className="font-medium text-gray-800">
                                {t("state")} <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                type="text"
                                id="state"
                                value={selectedLocationAddress.state || ''}
                                onChange={(e) => setSelectedLocationAddress(prev => ({ ...prev, state: e.target.value }))}
                                placeholder={t("enterState")}
                                className="w-full px-3 py-2 primaryBackgroundBg rounded-md focus:outline-none focus:border-none focus:border-transparent"
                            />
                        </div>
                    </div>

                    {/* Country */}
                    <div>
                        <Label htmlFor="country" className="font-medium text-gray-800">
                            {t("country")} <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            type="text"
                            id="country"
                            value={selectedLocationAddress.country || ''}
                            onChange={(e) => setSelectedLocationAddress(prev => ({ ...prev, country: e.target.value }))}
                            placeholder={t("enterCountry")}
                            className="w-full px-3 py-2 primaryBackgroundBg rounded-md focus:outline-none focus:border-none focus:border-transparent"
                        />
                    </div>

                    {/* Address */}
                    <div>
                        <Label htmlFor="address" className="font-medium text-gray-800">
                            By Google <span className="text-red-500">*</span>
                        </Label>
                        <div className="relative">
                            <div className="mb-2 flex justify-end">
                                <Button type="button" onClick={handleFindCurrentLocation} disabled={isDetectingLocation} className="px-4 py-2 text-sm">
                                    {isDetectingLocation ? 'Detecting...' : 'Get Current Location'}
                                </Button>
                            </div>
                            <Textarea
                                id="address"
                                value={selectedLocationAddress.formattedAddress || ''}
                                onChange={(e) => setSelectedLocationAddress(prev => ({ ...prev, formattedAddress: e.target.value }))}
                                placeholder="Address fetched from Google location"
                                className="w-full px-3 py-2 primaryBackgroundBg rounded-md focus:outline-none focus:border-none focus:border-transparent resize-none h-24"
                            />
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="client_address" className="font-medium text-gray-800">
                            By Customer
                        </Label>
                        <Textarea
                            id="client_address"
                            value={selectedLocationAddress.clientAddress || selectedLocationAddress.manualAddress || ''}
                            onChange={(e) => setSelectedLocationAddress(prev => ({ ...prev, clientAddress: e.target.value, manualAddress: e.target.value }))}
                            placeholder="Address as told by the customer"
                            className="w-full px-3 py-2 primaryBackgroundBg rounded-md focus:outline-none focus:border-none focus:border-transparent resize-none h-24"
                        />
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <Label htmlFor="latitude" className="font-medium text-gray-800">Latitude</Label>
                            <Input
                                type="number"
                                step="any"
                                id="latitude"
                                value={selectedLocationAddress.latitude || ''}
                                onChange={(e) => setSelectedLocationAddress(prev => ({ ...prev, latitude: e.target.value, lat: e.target.value }))}
                                placeholder="Latitude"
                                className="w-full px-3 py-2 primaryBackgroundBg rounded-md focus:outline-none focus:border-none focus:border-transparent"
                            />
                        </div>
                        <div>
                            <Label htmlFor="longitude" className="font-medium text-gray-800">Longitude</Label>
                            <Input
                                type="number"
                                step="any"
                                id="longitude"
                                value={selectedLocationAddress.longitude || ''}
                                onChange={(e) => setSelectedLocationAddress(prev => ({ ...prev, longitude: e.target.value, lng: e.target.value }))}
                                placeholder="Longitude"
                                className="w-full px-3 py-2 primaryBackgroundBg rounded-md focus:outline-none focus:border-none focus:border-transparent"
                            />
                        </div>
                    </div>

                    <AreaSubAreaSelector
                        compactSeparate
                        requiresCity
                        canManageAreaListing={canManageAreaListing}
                        selectedLocationAddress={selectedLocationAddress}
                        setSelectedLocationAddress={setSelectedLocationAddress}
                        showStateCity={false}
                    />

                    {selectedLocationAddress?.detected_area_name && !selectedLocationAddress?.area_id && (
                        <div className="rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm text-sky-900" role="status">
                            <div className="font-medium">Detected area from Google</div>
                            <div className="mt-1">
                                <strong>{selectedLocationAddress.detected_area_name}</strong>
                                {selectedLocationAddress.detected_sub_area_name ? (
                                    <span>, {selectedLocationAddress.detected_sub_area_name}</span>
                                ) : null}
                            </div>
                            <p className="mt-2 text-xs text-sky-800">
                                {canManageAreaListing
                                    ? 'Click the Area field above to choose a match or add this area (agent portal + Area Wise permission).'
                                    : 'Click Area to pick a match, or type a name and tap Use. For Sub Area, type a name and tap Use — both are saved with your property and sent for admin approval.'}
                            </p>
                        </div>
                    )}

                    {selectedLocationAddress?.area_id && selectedLocationAddress?.detected_sub_area_name && !selectedLocationAddress?.sub_area_id && (
                        <div className="rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm text-sky-900" role="status">
                            <div className="font-medium">Detected sub area from Google</div>
                            <div className="mt-1"><strong>{selectedLocationAddress.detected_sub_area_name}</strong></div>
                            <p className="mt-2 text-xs text-sky-800">
                                {canManageAreaListing
                                    ? 'Open Sub Area to select a match or add it.'
                                    : 'Open Sub Area, type the correct name, and tap Use — it stays on this form until you save the property.'}
                            </p>
                        </div>
                    )}

                    {selectedLocationAddress?.area_id && selectedLocationAddress?.sub_area_name && (
                        <p className="text-xs text-gray-600">
                            Wrong sub area? Open Sub Area, tap Clear Sub Area, then pick from the list or type a new name and tap Use (saved when you submit the property).
                        </p>
                    )}

                    {locationConfirmation.required && (
                        <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                            <div className="font-semibold">Detected location</div>
                            <div className="mt-1 text-xs uppercase tracking-wide">{locationConfirmation.source}</div>
                            <div className="mt-1">{locationConfirmation.summary || "Google filled the location fields. Please verify them before saving."}</div>
                            {cityMismatch && (
                                <div className="mt-3 rounded-md border border-amber-200 bg-white p-3">
                                    <div className="font-medium">Detected city is different</div>
                                    <div className="mt-1 text-sm">
                                        Current selected city: <strong>{cityMismatch.selectedCity}</strong>
                                        <br />
                                        Google detected city: <strong>{cityMismatch.detectedCity}</strong>
                                    </div>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        <Button type="button" variant="outline" onClick={keepSelectedCity} className="px-4 py-2 text-sm">
                                            Keep selected
                                        </Button>
                                        <Button type="button" onClick={switchToDetectedCity} className="px-4 py-2 text-sm">
                                            Switch detected
                                        </Button>
                                    </div>
                                </div>
                            )}
                            <div className="mt-3 flex flex-wrap gap-2">
                                <Button type="button" onClick={confirmDetectedLocation} className="px-4 py-2 text-sm">
                                    Confirm Location
                                </Button>
                            </div>
                        </div>
                    )}
                </div>

                <div className="w-full h-[350px] rounded-lg overflow-hidden">
                    <Map
                        latitude={selectedLocationAddress.latitude || 0}
                        longitude={selectedLocationAddress.longitude || 0}
                        showLabel={true}
                        onSelectLocation={handleMapLocationSelect}
                    />
                </div>
            </div>

            {/* Next Button */}
            <div className="flex justify-end">
                <Button
                    onClick={handleLocationStepNext}
                    className="px-10 py-5"
                >
                    {t("next")}
                </Button>
            </div>
        </div>
    );
};

export default LocationComponent
