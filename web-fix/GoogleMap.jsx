import React, { useEffect, useRef, useState } from 'react'
import { Circle, GoogleMap } from '@react-google-maps/api';
import AdvancedMapMarker, { withGoogleMapId } from '@/components/google-maps/AdvancedMapMarker';
import { extractAddressComponents } from '@/utils/helperFunction';
import { useSelector } from 'react-redux';
import { useTranslation } from '@/components/context/TranslationContext';
import { getMapDetailsApi } from '@/api/apiRoutes';

const DEFAULT_CENTER = {
    lat: 25.7506,
    lng: 71.3930,
};

const toFiniteNumber = (value, fallback) => {
    const number = Number(value);
    return Number.isFinite(number) ? number : fallback;
};

const makeCenter = (latitude, longitude, webSettings) => ({
    lat: toFiniteNumber(latitude, toFiniteNumber(webSettings?.latitude, DEFAULT_CENTER.lat)),
    lng: toFiniteNumber(longitude, toFiniteNumber(webSettings?.longitude, DEFAULT_CENTER.lng)),
    radius: 1,
});

const Map = ({ onSelectLocation, latitude, longitude, showLabel = false, isDraggable = true, showOnlyRadius = false }) => {
    const t = useTranslation()
    const webSettings = useSelector(state => state.WebSetting?.data)
    const initialCenter = makeCenter(latitude, longitude, webSettings);
    const [location, setLocation] = useState(initialCenter);

    const [mapType, setMapType] = useState("roadmap");
    const mapRef = useRef(null);
    const [mapError, setMapError] = useState(null);
    const [selectedLocationAddress, setSelectedLocationAddress] = useState({
        city: "",
        state: "",
        country: "",
        formattedAddress: "",
        lat: initialCenter.lat,
        lng: initialCenter.lng,
    });

    useEffect(() => {
        const nextCenter = makeCenter(latitude, longitude, webSettings);
        setLocation(prev => ({
            ...prev,
            lat: nextCenter.lat,
            lng: nextCenter.lng,
        }));
    }, [latitude, longitude, webSettings?.latitude, webSettings?.longitude]);

    const containerStyle = {
        width: "100%",
        height: "400px",
    };

    const handleMarkerDragEnd = async (e) => {
        const { lat, lng } = e.latLng;
        const newLat = lat();
        const newLng = lng();

        const updatedLocation = {
            ...location,
            lat: newLat,
            lng: newLng,
        };
        setLocation(updatedLocation);

        const reverseGeocodedData = await performReverseGeocoding(newLat, newLng);
        if (reverseGeocodedData) {
            const { city, country, state, formattedAddress, place } = reverseGeocodedData;
            const fullLocationData = {
                ...updatedLocation,
                city,
                country,
                state,
                formattedAddress,
                latitude: newLat,
                longitude: newLng,
                lat: newLat,
                lng: newLng,
                _place: place || null,
            };

            setSelectedLocationAddress({
                ...selectedLocationAddress,
                city,
                country,
                state,
                formattedAddress,
                lat: newLat,
                lng: newLng,
            });

            onSelectLocation?.(fullLocationData);
        } else {
            const fallbackLocationData = {
                ...updatedLocation,
                city: "",
                country: "",
                state: "",
                latitude: newLat,
                longitude: newLng,
                lat: newLat,
                lng: newLng,
            };

            setSelectedLocationAddress({
                ...selectedLocationAddress,
                lat: newLat,
                lng: newLng,
            });

            onSelectLocation?.(fallbackLocationData);
        }
    };

    const performReverseGeocoding = async (lat, lng) => {
        try {
            const response = await getMapDetailsApi({
                latitude: lat.toString(),
                longitude: lng.toString(),
                place_id: ""
            });
            if (response?.error === false && response?.data) {
                const placeDetails = response.data?.result;

                if (placeDetails) {
                    const addressData = extractAddressComponents(placeDetails);

                    return {
                        city: addressData.city || "",
                        country: addressData.country || "",
                        state: addressData.state || "",
                        formattedAddress: addressData.formattedAddress || placeDetails.formatted_address || "",
                        place: placeDetails,
                    };
                }
                return null;
            }
            return null;
        } catch (error) {
            console.error("Error performing reverse geocoding via API:", error);
            return null;
        }
    };

    const safeLocation = {
        ...location,
        lat: toFiniteNumber(location?.lat, DEFAULT_CENTER.lat),
        lng: toFiniteNumber(location?.lng, DEFAULT_CENTER.lng),
    };

    return (
        <div>
            {mapError ?
                <div>{mapError}</div>
                :
                <div className="relative">
                    {showLabel && (
                        <p className="secondaryTextColor font-medium">
                            {t("map")} <span className="text-red-500">*</span>
                        </p>
                    )}
                    <GoogleMap
                        mapContainerStyle={containerStyle}
                        center={safeLocation}
                        zoom={14}
                        options={withGoogleMapId({
                            fullscreenControl: true,
                            streetViewControl: false,
                            cameraControl: false,
                        })}
                        onLoad={(map) => {
                            mapRef.current = map;
                            setMapType(map.getMapTypeId());
                        }}
                        onMapTypeIdChanged={() => {
                            if (mapRef.current) {
                                setMapType(mapRef.current.getMapTypeId());
                            }
                        }}>
                        <Circle
                            center={{ lat: safeLocation.lat, lng: safeLocation.lng }}
                            radius={(safeLocation?.radius || 1) * 1000}
                            options={{
                                strokeColor: mapType === "hybrid" || mapType === "satellite" ? "#ffffff" : webSettings?.system_color,
                                fillColor: mapType === "hybrid" || mapType === "satellite" ? "#ffffff" : webSettings?.system_color,
                                strokeOpacity: 0.8,
                                strokeWeight: 2,
                                fillOpacity: 0.2,
                            }} />
                        {!showOnlyRadius && (
                            <AdvancedMapMarker
                                position={safeLocation}
                                draggable={isDraggable}
                                onDragEnd={handleMarkerDragEnd}
                                title={t("map")}
                            />
                        )}
                    </GoogleMap>
                </div>
            }
        </div>
    )
}

export default Map