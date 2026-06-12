export const titleCaseLocation = (value = "") =>
  String(value || "")
    .trim()
    .replace(/\s+/g, " ")
    .toLowerCase()
    .replace(/\b\w/g, (letter) => letter.toUpperCase());

export const extractAreaListingFromPlace = (place = {}) => {
  const components = place.address_components || [];
  const find = (...types) => {
    const match = components.find((component) =>
      types.some((type) => component.types?.includes(type))
    );
    return titleCaseLocation(match?.long_name || "");
  };

  const area =
    find("sublocality_level_1", "neighborhood", "administrative_area_level_3") ||
    find("administrative_area_level_2");
  const subArea =
    find("sublocality_level_2", "sublocality_level_3", "route") ||
    find("premise", "point_of_interest");

  return {
    detected_area_name: area,
    detected_sub_area_name: subArea,
  };
};

export const buildLocationComponentsFromPlace = (place = {}) => {
  const components = place?.address_components || [];
  return components.map((component) => ({
    name: component.long_name || component.short_name || "",
    types: component.types || [],
  }));
};

export const applyResolveResultToLocation = (location = {}, resolveData = null, options = {}) => {
  const applyResolvedIds = options.applyResolvedIds !== false;

  if (!resolveData) {
    return location;
  }

  // User portal: detected names only — no auto-link to master area/sub IDs (user picks or suggests)
  if (resolveData.area_id && !applyResolvedIds) {
    return {
      ...location,
      city_id: resolveData.city_id != null ? String(resolveData.city_id) : location.city_id,
      area_id: "",
      sub_area_id: "",
      area_name: "",
      sub_area_name: "",
      detected_area_name: resolveData.area_name || resolveData.detected_area_name || location.detected_area_name || "",
      detected_sub_area_name:
        resolveData.detected_sub_area_name ||
        resolveData.sub_area_name ||
        location.detected_sub_area_name ||
        "",
      area_listing_user_confirmed: false,
    };
  }

  if (resolveData.area_id) {
    return {
      ...location,
      city_id: resolveData.city_id != null ? String(resolveData.city_id) : location.city_id,
      area_id: String(resolveData.area_id),
      area_name: resolveData.area_name || location.area_name || "",
      sub_area_id: resolveData.sub_area_id != null ? String(resolveData.sub_area_id) : "",
      sub_area_name: resolveData.sub_area_name || location.sub_area_name || "",
      detected_area_name: resolveData.area_name || location.detected_area_name || "",
      detected_sub_area_name: resolveData.sub_area_name || location.detected_sub_area_name || "",
    };
  }

  return {
    ...location,
    area_id: "",
    sub_area_id: "",
    area_name: location.area_id ? location.area_name : "",
    sub_area_name: location.sub_area_id ? location.sub_area_name : "",
    detected_area_name: resolveData.detected_area_name || location.detected_area_name || "",
    detected_sub_area_name: resolveData.detected_sub_area_name || location.detected_sub_area_name || "",
  };
};

export const publicAreaAddress = (areaListing = {}, fallback = {}) => {
  const parts = [
    areaListing?.sub_area_name,
    areaListing?.area_name,
    areaListing?.city || fallback.city,
    areaListing?.state || fallback.state,
  ].filter(Boolean);

  return [...new Set(parts)].join(", ");
};
