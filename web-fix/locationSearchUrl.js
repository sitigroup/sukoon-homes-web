import { generateSlug, generateBase64FilterUrl } from "@/utils/helperFunction";
import { getAreaListingAreas, getAreaListingSubAreas } from "@/plugins/area-listing/areaListingApi";

/** Turn a display name into a URL segment (matches backend Str::slug style). */
export const locationNameToSlug = (name) => {
  if (!name || typeof name !== "string") return "";
  return generateSlug(name);
};

export const slugToDisplayName = (slug = "") => {
  if (!slug) return "";
  return slug
    .split("-")
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
};

/**
 * Path segments for location-only search, e.g. /search/barmer/krishna-nagar/mansukhani-gali
 */
export const buildLocationSearchPath = ({ city, area_name, sub_area_name, citySlug, areaSlug, subAreaSlug } = {}) => {
  const citySegment = citySlug || locationNameToSlug(city);
  if (!citySegment) return "/search";

  const segments = [citySegment];
  const areaSegment = areaSlug || locationNameToSlug(area_name);
  if (areaSegment) {
    segments.push(areaSegment);
    const subSegment = subAreaSlug || locationNameToSlug(sub_area_name);
    if (subSegment) segments.push(subSegment);
  }

  return `/search/${segments.join("/")}`;
};

const LOCATION_FILTER_KEYS = new Set([
  "city",
  "state",
  "country",
  "state_id",
  "city_id",
  "area_id",
  "area_name",
  "detected_area_name",
  "sub_area_id",
  "sub_area_name",
  "detected_sub_area_name",
  "latitude",
  "longitude",
  "range",
]);

/** True when filters only set location (no price, amenities, keywords, etc.). */
export const hasNonLocationFilters = (filters = {}) => {
  if (filters.keywords) return true;
  if (filters.min_price || filters.max_price) return true;
  if (filters.posted_since && filters.posted_since !== "anytime" && filters.posted_since !== "") return true;
  if (filters.promoted) return true;
  if (filters.is_premium) return true;
  if (filters.most_viewed) return true;
  if (filters.most_liked) return true;
  if (filters.category_id) return true;
  if (filters.category_slug_id) return true;
  if (filters.property_type && filters.property_type !== "All" && filters.property_type !== "") return true;
  if (Array.isArray(filters.amenities) && filters.amenities.length > 0) return true;
  if (Array.isArray(filters.nearbyPlaces) && filters.nearbyPlaces.length > 0) return true;
  return false;
};

/**
 * Build search URL: short path for location; ?filters= for extra criteria.
 */
export const buildPropertySearchUrl = (filters = {}, options = {}) => {
  const lang = options.lang || "en";
  const path = buildLocationSearchPath(filters);
  const query = { lang };

  if (hasNonLocationFilters(filters)) {
    const encoded = encodeURIComponent(generateBase64FilterUrl(filters, options));
    query.filters = encoded;
  }

  const qs = new URLSearchParams(query).toString();
  return qs ? `${path}?${qs}` : path;
};

export const parseLocationPathFromRouter = (router) => {
  if (!router?.isReady) return null;

  const { citySlug, areaSlug, subAreaSlug } = router.query || {};
  if (citySlug) {
    return {
      citySlug: String(citySlug),
      areaSlug: areaSlug ? String(areaSlug) : "",
      subAreaSlug: subAreaSlug ? String(subAreaSlug) : "",
    };
  }

  const pathMatch = (router.asPath || "").match(/\/search\/([^/?]+)(?:\/([^/?]+))?(?:\/([^/?]+))?/);
  if (!pathMatch) return null;

  return {
    citySlug: pathMatch[1] || "",
    areaSlug: pathMatch[2] || "",
    subAreaSlug: pathMatch[3] || "",
  };
};

const matchBySlug = (row, slug) => {
  if (!row || !slug) return false;
  const rowSlug = row.slug || locationNameToSlug(row.name);
  return rowSlug === slug || locationNameToSlug(row.name) === slug;
};

/** Resolve URL slugs (and/or ids) into filter fields used by the sidebar and API. */
export const resolveLocationSearchFilters = async ({ citySlug, areaSlug, subAreaSlug, partial = {} } = {}) => {
  const cityLabel = partial.city || slugToDisplayName(citySlug);
  const base = {
    city: cityLabel,
    state: partial.state || "",
    country: partial.country || "",
    state_id: partial.state_id ? String(partial.state_id) : "",
    city_id: partial.city_id ? String(partial.city_id) : "",
    area_id: partial.area_id ? String(partial.area_id) : "",
    area_name: partial.area_name || "",
    detected_area_name: partial.detected_area_name || partial.area_name || "",
    sub_area_id: partial.sub_area_id ? String(partial.sub_area_id) : "",
    sub_area_name: partial.sub_area_name || "",
    detected_sub_area_name: partial.detected_sub_area_name || partial.sub_area_name || "",
  };

  if (!cityLabel && !base.area_id) return base;

  let areas = [];
  try {
    const res = await getAreaListingAreas({
      city: cityLabel,
      state: base.state,
      country: base.country,
      city_id: base.city_id,
      state_id: base.state_id,
    });
    areas = res?.data || [];
  } catch {
    areas = [];
  }

  let area = null;
  if (base.area_id) {
    area = areas.find((a) => String(a.id) === String(base.area_id)) || null;
  }
  if (!area && areaSlug) {
    area = areas.find((a) => matchBySlug(a, areaSlug)) || null;
  }

  if (area) {
    base.area_id = String(area.id);
    base.area_name = area.name || base.area_name;
    base.detected_area_name = area.name || base.detected_area_name;
    base.city_id = String(area.city_id || base.city_id || "");
    base.state_id = String(area.state_id || base.state_id || "");
    base.city = area.city || area.city_name || base.city;
    base.state = area.state || base.state;
    base.country = area.country || base.country;
  }

  if (base.area_id && (subAreaSlug || base.sub_area_id)) {
    try {
      const subRes = await getAreaListingSubAreas({ area_id: base.area_id });
      const subs = subRes?.data || [];
      let sub = null;
      if (base.sub_area_id) {
        sub = subs.find((s) => String(s.id) === String(base.sub_area_id)) || null;
      }
      if (!sub && subAreaSlug) {
        sub = subs.find((s) => matchBySlug(s, subAreaSlug)) || null;
      }
      if (sub) {
        base.sub_area_id = String(sub.id);
        base.sub_area_name = sub.name || base.sub_area_name;
        base.detected_sub_area_name = sub.name || base.detected_sub_area_name;
      }
    } catch {
      // keep partial sub fields
    }
  }

  return base;
};

/** Merge decoded ?filters= with path-based location (path wins for location fields). */
export const mergePathAndDecodedFilters = (decoded = {}, locationFromPath = {}) => ({
  ...decoded,
  city: locationFromPath.city || decoded.city || "",
  state: locationFromPath.state || decoded.state || "",
  country: locationFromPath.country || decoded.country || "",
  state_id: locationFromPath.state_id || decoded.state_id || "",
  city_id: locationFromPath.city_id || decoded.city_id || "",
  area_id: locationFromPath.area_id || decoded.area_id || "",
  area_name: locationFromPath.area_name || decoded.area_name || "",
  detected_area_name: locationFromPath.detected_area_name || decoded.detected_area_name || "",
  sub_area_id: locationFromPath.sub_area_id || decoded.sub_area_id || "",
  sub_area_name: locationFromPath.sub_area_name || decoded.sub_area_name || "",
  detected_sub_area_name: locationFromPath.detected_sub_area_name || decoded.detected_sub_area_name || "",
});
