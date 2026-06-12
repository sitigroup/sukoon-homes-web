import { GoogleMap, InfoWindow } from "@react-google-maps/api";
import AdvancedMapMarker, { withGoogleMapId } from "@/components/google-maps/AdvancedMapMarker";
import MapPropertyCard from '../cards/MapPropertyCard';

const PropertyOnMapView = ({
    containerStyle,
    defaultCenter,
    onLoad,
    onUnmount,
    isMapLoaded,
    selectedProperty,
    handleMarkerClick,
    handleInfoWindowClose,
    iconConfig,
    data,
    isInteractive
}) => {
    // Make sure data is valid
    const validProperties = Array.isArray(data) ? data.filter(property =>
        property.latitude && property.longitude
    ) : [];

    return (
        <GoogleMap
            mapContainerStyle={containerStyle}
            center={defaultCenter}
            zoom={4}
            onLoad={onLoad}
            onUnmount={onUnmount}
            onClick={handleInfoWindowClose}
            options={withGoogleMapId({
                streetViewControl: false,
                mapTypeControl: false,
                gestureHandling: isInteractive ? 'auto' : 'none',
                zoomControl: isInteractive,
                scrollwheel: isInteractive,
                draggable: isInteractive,
                disableDoubleClickZoom: !isInteractive,
                fullscreenControl: true,
            })}
        >
            {/* Render markers */}
            {isMapLoaded && validProperties.map((property) => {
                // Convert latitude and longitude to numbers
                const position = {
                    lat: parseFloat(property.latitude),
                    lng: parseFloat(property.longitude)
                };

                return (
                    <div className="relative" key={property?.id}>
                        <AdvancedMapMarker
                            key={property.id}
                            position={position}
                            onClick={() => handleMarkerClick(property)}
                            icon={iconConfig}
                            zIndex={selectedProperty?.id === property.id ? 1000 : 1}
                            title={property.title}
                        />
                        {/* Render InfoWindow if this property is selected */}
                        {selectedProperty?.id === property.id && (
                            <InfoWindow
                                position={position}
                                onCloseClick={handleInfoWindowClose}
                                options={{
                                    pixelOffset: isMapLoaded ? new window.google.maps.Size(0, -45) : undefined,
                                    maxWidth: 320,
                                    disableAutoPan: true,
                                    minHeight: 400,
                                    borderRadius: "16px",
                                }}
                            >
                                {/* InfoWindow content container */}
                                <div className="rounded-t-2xl p-0 m-0 gmaps-infowindow-content">
                                    <MapPropertyCard property={selectedProperty} />
                                </div>
                            </InfoWindow>
                        )
                        }
                    </div>
                );
            })}
        </GoogleMap>
    );
};

export default PropertyOnMapView;
