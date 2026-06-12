/**
 * JSON-LD builders for property and listing pages (SSR-safe).
 */

const webBase = () => (process.env.NEXT_PUBLIC_WEB_URL || '').replace(/\/$/, '');

const parseBhkRooms = (property = {}) => {
  const parameters = Array.isArray(property.parameters) ? property.parameters : [];
  const bhkParam = parameters.find((param) => {
    const name = String(param?.translated_name || param?.name || '').toLowerCase();
    return name.includes('bhk') || name.includes('bed');
  });
  const raw = bhkParam?.value ?? bhkParam?.option_value ?? bhkParam?.selected_value;
  if (raw === undefined || raw === null || raw === '') return undefined;
  const match = String(raw).match(/(\d+)/);
  return match ? Number(match[1]) : undefined;
};

const resolveDwellingType = (property = {}) => {
  const haystack = [
    property?.category?.category,
    property?.category?.translated_name,
    property?.property_type,
    property?.propery_type,
    property?.title,
  ]
    .filter(Boolean)
    .join(' ')
    .toLowerCase();

  if (/(apartment|flat|bhk|studio|penthouse)/.test(haystack)) {
    return 'Apartment';
  }
  if (/(house|villa|bungalow|independent)/.test(haystack)) {
    return 'House';
  }
  return 'Apartment';
};

const canViewExactLocation = (property = {}) =>
  property.can_view_exact_location === true ||
  property.can_view_exact_location === 1 ||
  property.can_view_exact_location === '1';

const roundCoordinate = (value) => {
  const num = Number(value);
  if (!Number.isFinite(num)) return undefined;
  return Math.round(num * 1000) / 1000;
};

const localityAddress = (property = {}) => {
  const listing = property.area_listing || {};
  const locality =
    listing.sub_area_name ||
    listing.area_name ||
    listing.city_name ||
    property.city ||
    '';
  const region = listing.state || property.state || 'Rajasthan';
  const country = property.country || 'India';

  return {
    '@type': 'PostalAddress',
    addressLocality: locality,
    addressRegion: region,
    addressCountry: country,
  };
};

/**
 * RealEstateListing + nested Apartment/House entity. Locality-only address; no exact geo when privacy applies.
 */
export function realEstateListing(property = {}) {
  if (!property || !property.slug_id) return null;

  const base = webBase();
  const url = `${base}/property-details/${property.slug_id}/`;
  const privacyProtected = !canViewExactLocation(property);
  const address = localityAddress(property);
  const rooms = parseBhkRooms(property);
  const dwellingType = resolveDwellingType(property);

  const dwelling = {
    '@type': dwellingType,
    name: property.title || property.meta_title || 'Property',
    numberOfRooms: rooms,
    address,
  };

  if (!privacyProtected) {
    const lat = roundCoordinate(property.latitude);
    const lng = roundCoordinate(property.longitude);
    if (lat !== undefined && lng !== undefined) {
      dwelling.geo = {
        '@type': 'GeoCoordinates',
        latitude: lat,
        longitude: lng,
      };
    }
  }

  const price = Number(property.price);
  const offer =
    Number.isFinite(price) && price > 0
      ? {
          '@type': 'Offer',
          price,
          priceCurrency: 'INR',
          availability: 'https://schema.org/InStock',
        }
      : undefined;

  return {
    '@type': 'RealEstateListing',
    name: property.title || property.meta_title,
    url,
    datePosted: property.posted_on || property.created_at || undefined,
    offers: offer,
    mainEntity: dwelling,
  };
}

export function breadcrumbListJsonLd(items = []) {
  const list = items.filter((item) => item?.name && item?.url);
  if (!list.length) return null;

  return {
    '@type': 'BreadcrumbList',
    itemListElement: list.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.name,
      item: item.url,
    })),
  };
}

export function propertyBreadcrumbList(property = {}, lang = 'en') {
  const base = webBase();
  const listing = property.area_listing || {};
  const items = [{ name: 'Home', url: `${base}/?lang=${lang}` }];

  if (listing.city_name || property.city) {
    const citySlug = listing.city_slug || String(property.city || '').toLowerCase().replace(/\s+/g, '-');
    if (citySlug) {
      items.push({
        name: listing.city_name || property.city,
        url: `${base}/search/${citySlug}/?lang=${lang}`,
      });
    }
  }

  if (listing.area_name && listing.area_slug) {
    const citySlug = listing.city_slug || String(property.city || '').toLowerCase().replace(/\s+/g, '-');
    items.push({
      name: listing.area_name,
      url: `${base}/search/${citySlug}/${listing.area_slug}/?lang=${lang}`,
    });
  }

  if (listing.sub_area_name && listing.sub_area_slug && listing.area_slug) {
    const citySlug = listing.city_slug || String(property.city || '').toLowerCase().replace(/\s+/g, '-');
    items.push({
      name: listing.sub_area_name,
      url: `${base}/search/${citySlug}/${listing.area_slug}/${listing.sub_area_slug}/?lang=${lang}`,
    });
  }

  items.push({
    name: property.title || 'Property',
    url: `${base}/property-details/${property.slug_id}/?lang=${lang}`,
  });

  return breadcrumbListJsonLd(items);
}

export function searchBreadcrumbList({ citySlug, areaSlug, subAreaSlug, labels = {}, lang = 'en' } = {}) {
  const base = webBase();
  const items = [
    { name: 'Home', url: `${base}/?lang=${lang}` },
    { name: 'Search', url: `${base}/search/?lang=${lang}` },
  ];

  if (citySlug) {
    items.push({
      name: labels.city || citySlug,
      url: `${base}/search/${citySlug}/?lang=${lang}`,
    });
  }
  if (citySlug && areaSlug) {
    items.push({
      name: labels.area || areaSlug,
      url: `${base}/search/${citySlug}/${areaSlug}/?lang=${lang}`,
    });
  }
  if (citySlug && areaSlug && subAreaSlug) {
    items.push({
      name: labels.subArea || subAreaSlug,
      url: `${base}/search/${citySlug}/${areaSlug}/${subAreaSlug}/?lang=${lang}`,
    });
  }

  return breadcrumbListJsonLd(items);
}

export function mergeStructuredData(...blocks) {
  const graph = [];

  blocks.forEach((block) => {
    if (!block) return;
    if (typeof block === 'string') {
      try {
        const parsed = JSON.parse(block);
        if (parsed['@graph']) graph.push(...parsed['@graph']);
        else graph.push(parsed);
      } catch {
        // ignore invalid admin schema strings
      }
      return;
    }
    if (block['@graph']) graph.push(...block['@graph']);
    else graph.push(block);
  });

  if (!graph.length) return null;
  if (graph.length === 1) return { '@context': 'https://schema.org', ...graph[0] };

  return {
    '@context': 'https://schema.org',
    '@graph': graph,
  };
}
