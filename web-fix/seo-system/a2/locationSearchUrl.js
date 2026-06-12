import { generateSlug, generateBase64FilterUrl } from "@/utils/helperFunction";
import {
  getAreaListingAreas,
  getAreaListingSubAreas,
  getAreaListingCities,
} from "@/plugins/area-listing/areaListingApi";

/** Turn a display name into a URL segment (legacy / fallback only). */
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
 * Path segments for location-only search using stored AreaListing slugs when available.
 * e.g. /search/barmer/baldev-nagar/gali-wala
 */
export const buildLocationSearchPath = ({
  city,
  area_name,
  sub_area_name,
  citySlug,
  city_slug,
  areaSlug,
  area_slug,
  subAreaSlug,
  sub_area_slug,
} = {}) => {
  const citySegment = citySlug || city_slug || locationNameToSlug(city);
  if (!citySegment) return "/search";

  const segments = [citySegment];
  const areaSegment = areaSlug || area_slug;
  if (areaSegment) {
    segments.push(areaSegment);
    const subSegment = subAreaSlug || sub_area_slug;
    if (subSegment) segments.push(subSegment);
  } else if (area_name && !area_slug && !areaSlug) {
    // Legacy callers without stored slug — keep name-derived segment for backward compatibility
    const legacyArea = locationNameToSlug(area_name);
    if (legacyArea) {
      segments.push(legacyArea);
      const legacySub = sub_area_name ? locationNameToSlug(sub_area_name) : "";
      if (legacySub) segments.push(legacySub);
    }
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
  if (row.slug && row.slug === slug) return true;
  // Legacy URLs that used name-derived slugs
  return locationNameToSlug(row.name) === slug;
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
    city_slug: partial.city_slug || citySlug || "",
    area_id: partial.area_id ? String(partial.area_id) : "",
    area_name: partial.area_name || "",
    area_slug: partial.area_slug || areaSlug || "",
    detected_area_name: partial.detected_area_name || partial.area_name || "",
    sub_area_id: partial.sub_area_id ? String(partial.sub_area_id) : "",
    sub_area_name: partial.sub_area_name || "",
    sub_area_slug: partial.sub_area_slug || subAreaSlug || "",
    detected_sub_area_name: partial.detected_sub_area_name || partial.sub_area_name || "",
  };

  if (!cityLabel && !base.area_id && !citySlug) return base;

  let cities = [];
  try {
    const citiesRes = await getAreaListingCities({
      state: base.state,
      country: base.country,
      state_id: base.state_id,
    });
    cities = citiesRes?.data || [];
  } catch {
    cities = [];
  }

  let cityRow = null;
  if (citySlug) {
    cityRow = cities.find((c) => c.slug === citySlug) || null;
  }
  if (!cityRow && cityLabel) {
    cityRow =
      cities.find((c) => c.name === cityLabel) ||
      cities.find((c) => locationNameToSlug(c.name) === locationNameToSlug(cityLabel)) ||
      null;
  }

  if (cityRow) {
    base.city_id = String(cityRow.id);
    base.city = cityRow.name || base.city;
    base.city_slug = cityRow.slug || base.city_slug;
    base.state = cityRow.state || base.state;
    base.country = cityRow.country || base.country;
    base.state_id = String(cityRow.state_id || base.state_id || "");
  }

  let areas = [];
  try {
    const res = await getAreaListingAreas({
      city: base.city,
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
    base.area_slug = area.slug || base.area_slug;
    base.detected_area_name = area.name || base.detected_area_name;
    base.city_id = String(area.city_id || base.city_id || "");
    base.state_id = String(area.state_id || base.state_id || "");
    base.city = area.city || area.city_name || base.city;
    base.state = area.state || base.state;
    base.country = area.country || base.country;
    if (area.city && cities.length > 0) {
      const areaCity = cities.find((c) => c.name === area.city);
      if (areaCity?.slug) base.city_slug = areaCity.slug;
    }
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
        base.sub_area_slug = sub.slug || base.sub_area_slug;
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
  city_slug: locationFromPath.city_slug || decoded.city_slug || "",
  area_id: locationFromPath.area_id || decoded.area_id || "",
  area_name: locationFromPath.area_name || decoded.area_name || "",
  area_slug: locationFromPath.area_slug || decoded.area_slug || "",
  detected_area_name: locationFromPath.detected_area_name || decoded.detected_area_name || "",
  sub_area_id: locationFromPath.sub_area_id || decoded.sub_area_id || "",
  sub_area_name: locationFromPath.sub_area_name || decoded.sub_area_name || "",
  sub_area_slug: locationFromPath.sub_area_slug || decoded.sub_area_slug || "",
  detected_sub_area_name: locationFromPath.detected_sub_area_name || decoded.detected_sub_area_name || "",
});
