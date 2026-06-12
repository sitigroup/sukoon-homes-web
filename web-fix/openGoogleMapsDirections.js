/**
 * Open Google Maps directions to a listing location.
 * Prefers the displayed Google/full address; falls back to coordinates with swap correction.
 */
export const normalizeLatLng = (latitude, longitude) => {
  let lat = Number(latitude);
  let lng = Number(longitude);
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    return null;
  }
  if (Math.abs(lat) > 90 || Math.abs(lng) > 180) {
    [lat, lng] = [lng, lat];
  }
  // Common data entry mistake: lat/lng reversed (e.g. India ~26°N, ~71°E stored as 71, 26)
  if (lat >= 68 && lat <= 97 && lng >= 8 && lng <= 37) {
    [lat, lng] = [lng, lat];
  }
  if (lat === 0 && lng === 0) {
    return null;
  }
  return { lat, lng };
};

export const openGoogleMapsDirections = ({
  latitude,
  longitude,
  address = "",
  areaListing = null,
} = {}) => {
  const listing = areaListing || {};
  const addressText = String(
    listing.full_address || address || "",
  ).trim();
  const coords = normalizeLatLng(
    listing.latitude ?? latitude,
    listing.longitude ?? longitude,
  );

  let url = "";
  if (addressText) {
    url = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(addressText)}`;
  } else if (coords) {
    url = `https://www.google.com/maps/dir/?api=1&destination=${coords.lat},${coords.lng}`;
  } else {
    return false;
  }

  window.open(url, "_blank", "noopener,noreferrer");
  return true;
};

export const openDirectionsForListing = (details) => {
  if (!details) {
    return false;
  }
  return openGoogleMapsDirections({
    latitude: details.latitude,
    longitude: details.longitude,
    address: details.address,
    areaListing: details.area_listing,
  });
};
