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

export const publicAreaAddress = (areaListing = {}, fallback = {}) => {
  const parts = [
    areaListing?.sub_area_name,
    areaListing?.area_name,
    areaListing?.city || fallback.city,
    areaListing?.state || fallback.state,
  ].filter(Boolean);

  return [...new Set(parts)].join(", ");
};