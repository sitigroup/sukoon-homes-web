import { useEffect, useMemo, useState } from "react";
import {
  FiActivity,
  FiAward,
  FiBriefcase,
  FiCompass,
  FiHome,
  FiMapPin,
  FiNavigation,
  FiShield,
  FiShoppingBag,
  FiStar,
  FiSun,
  FiTarget,
  FiTruck,
  FiMap,
} from "react-icons/fi";
import { getPropertyNearbyPlaces } from "./nearbyPlacesApi";
import {
  insightActionClass,
  insightChipClass,
  insightMutedActionClass,
  insightOpenBadgeStyle,
  insightPlaceCardClass,
} from "./locationInsightsStyles";

const cleanPart = (v) => {
  if (v === undefined || v === null) return "";
  return String(v)
    .replace(/\s+/g, " ")
    .trim();
};

const CATEGORY_ICON_MAP = {
  hospital: FiHome,
  school: FiAward,
  restaurant: FiTarget,
  bank: FiBriefcase,
  market: FiShoppingBag,
  park: FiSun,
  gym: FiActivity,
  "bus-stand": FiTruck,
  "railway-station": FiMap,
};

const getCategoryIcon = (slug) => CATEGORY_ICON_MAP[slug] || FiMapPin;

/** Same preference as API `area_label`: sub+area, else area+city, else city+state. */
export const buildNearbyAreaLabel = (areaListing, city, state) => {
  const sub = cleanPart(areaListing?.sub_area_name);
  const area = cleanPart(areaListing?.area_name);
  const cityV = cleanPart(city ?? areaListing?.city);
  const stateV = cleanPart(state ?? areaListing?.state);

  if (sub && area) return `${sub}, ${area}`;
  if (area && cityV) return `${area}, ${cityV}`;
  if (cityV && stateV) return `${cityV}, ${stateV}`;

  const fallback = [area, cityV, stateV].filter(Boolean);
  return fallback.length ? fallback.join(", ") : "";
};

const isSameLocationPlace = (place) => Boolean(place?.is_same_location);

const PlaceOpenStatusBadge = ({ isOpen }) => {
  if (isOpen !== true && isOpen !== false) return null;

  return (
    <span
      className="pointer-events-none absolute right-2 top-2 inline-flex items-center gap-0.5 rounded-full px-1 py-px text-[9px] font-semibold leading-none sm:right-2.5 sm:top-2.5 sm:px-1.5 sm:text-[10px]"
      style={insightOpenBadgeStyle(isOpen)}
    >
      <span
        className="h-1.5 w-1.5 shrink-0 rounded-full"
        style={{ backgroundColor: isOpen ? "#10b981" : "#f87171" }}
        aria-hidden="true"
      />
      {isOpen ? "Open" : "Closed"}
    </span>
  );
};

const PlaceCardAction = ({ showDirections, navUnavailable, sameLocation, place, isDirectionsLoading, onDirectionsClick }) => {
  if (showDirections) {
    return (
      <a
        href={place.directions_url}
        target="_blank"
        rel="noopener noreferrer"
        title="Get directions"
        aria-label={`Directions to ${place.name}`}
        onClick={() => onDirectionsClick(place.google_place_id)}
        className={insightActionClass}
        style={{
          color: isDirectionsLoading ? "#94a3b8" : "#0f172a",
          backgroundColor: isDirectionsLoading ? "#f1f5f9" : "#ffffff",
          borderColor: "#e2e8f0",
          textDecoration: "none",
        }}
      >
        <FiNavigation
          className="nearby-directions-icon-mobile h-4 w-4 shrink-0"
          style={{ color: isDirectionsLoading ? "#94a3b8" : "#0f172a" }}
        />
        <span
          className="nearby-directions-label items-center gap-1"
          style={{
            display: "none",
            color: isDirectionsLoading ? "#94a3b8" : "#0f172a",
            fontSize: "12px",
            fontWeight: 600,
          }}
        >
          <span aria-hidden="true">↗</span>
          Directions
        </span>
      </a>
    );
  }

  if (navUnavailable) {
    return (
      <span className={insightMutedActionClass} style={{ borderColor: "#e2e8f0" }}>
        Location unavailable
      </span>
    );
  }

  if (sameLocation) {
    return (
      <span className={insightMutedActionClass} style={{ borderColor: "#e2e8f0" }}>
        Same location
      </span>
    );
  }

  return <span className="hidden h-8 min-w-[96px] sm:inline-flex" aria-hidden="true" />;
};

const NearbyPlacesSkeleton = ({ embedded = false }) => (
  <div className={embedded ? "px-2.5 py-2 sm:px-3" : "overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm"}>
    {!embedded ? (
      <div className="border-b border-slate-100 px-3 py-2.5 sm:px-4">
        <div className="flex animate-pulse items-center gap-2.5">
          <div className="h-8 w-8 rounded-lg bg-slate-200" />
          <div className="flex-1 space-y-1.5">
            <div className="h-3.5 w-36 rounded bg-slate-200" />
            <div className="h-3 w-48 rounded bg-slate-100" />
          </div>
        </div>
      </div>
    ) : null}
    <div className="flex gap-1.5 overflow-hidden border-b border-slate-100 px-2.5 py-1.5 sm:px-3">
      {[1, 2, 3, 4].map((i) => (
        <div key={i} className="h-7 w-16 shrink-0 animate-pulse rounded-full bg-slate-100" />
      ))}
    </div>
    <div className="grid grid-cols-1 gap-2 p-2.5 sm:grid-cols-2 sm:p-3">
      {[1, 2, 3, 4].map((i) => (
        <div key={i} className="animate-pulse rounded-lg border border-slate-100 p-2.5">
          <div className="mb-2 h-3.5 w-3/4 rounded bg-slate-200" />
          <div className="flex gap-1.5">
            <div className="h-4 w-14 rounded-full bg-slate-100" />
            <div className="h-4 w-10 rounded-full bg-slate-100" />
          </div>
        </div>
      ))}
    </div>
  </div>
);

const EmptyNearbyState = ({ message }) => (
  <div className="flex flex-col items-center justify-center px-3 py-6 text-center">
    <div className="mb-2 flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
      <FiCompass className="h-5 w-5" />
    </div>
    <p className="text-xs font-medium text-slate-700 sm:text-sm">{message}</p>
  </div>
);

const NearbyPlacesSection = ({
  propertyId,
  areaListing,
  city,
  state,
  className = "",
  embedded = false,
}) => {
  const [payload, setPayload] = useState(null);
  const [loading, setLoading] = useState(true);
  const [activeSlug, setActiveSlug] = useState("");
  const [directionsLoadingId, setDirectionsLoadingId] = useState(null);

  useEffect(() => {
    let alive = true;

    const load = async () => {
      if (!propertyId) {
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        const response = await getPropertyNearbyPlaces(propertyId);
        if (!alive) return;
        setPayload(response?.data || null);
      } catch (error) {
        if (alive) setPayload(null);
      } finally {
        if (alive) setLoading(false);
      }
    };

    load();
    return () => {
      alive = false;
    };
  }, [propertyId]);

  const categories = useMemo(
    () =>
      (payload?.categories || []).filter(
        (category) => cleanPart(category?.name) && cleanPart(category?.slug),
      ),
    [payload],
  );

  const placesByCategory = payload?.places || {};

  useEffect(() => {
    if (!categories.length) return;
    const slugValid = categories.some((c) => c.slug === activeSlug);
    if (!activeSlug || !slugValid) {
      setActiveSlug(categories[0].slug);
    }
  }, [categories, activeSlug]);

  const areaLabel = useMemo(() => {
    const fromApi = cleanPart(payload?.area_label);
    if (fromApi) return fromApi;
    return buildNearbyAreaLabel(areaListing, city, state);
  }, [payload, areaListing, city, state]);

  const publicDirectionsEnabled = payload?.public_directions_enabled === true;

  const activePlaces = useMemo(
    () => placesByCategory[activeSlug] || [],
    [placesByCategory, activeSlug],
  );

  const verifiedCount = useMemo(
    () =>
      Object.values(placesByCategory).reduce(
        (sum, items) => sum + (Array.isArray(items) ? items.length : 0),
        0,
      ),
    [placesByCategory],
  );

  const hasAnyPlaces = verifiedCount > 0;

  const handleDirectionsClick = (placeId) => {
    setDirectionsLoadingId(placeId);
    window.setTimeout(() => setDirectionsLoadingId(null), 600);
  };

  if (loading) {
    return (
      <div className={className}>
        <NearbyPlacesSkeleton embedded={embedded} />
      </div>
    );
  }

  if (!payload?.enabled || !categories.length) {
    return null;
  }

  const tabsBlock = (
    <div className={`sticky top-0 z-10 border-b border-slate-100 bg-white/95 backdrop-blur-sm ${embedded ? "" : ""}`}>
      <div
        className="flex gap-1 overflow-x-auto px-2.5 py-1.5 sm:gap-1.5 sm:px-3"
        style={{ WebkitOverflowScrolling: "touch", scrollbarWidth: "none" }}
      >
        {categories.map((category) => {
          const isActive = activeSlug === category.slug;
          const label = cleanPart(category.name);
          const Icon = getCategoryIcon(category.slug);
          const count = (placesByCategory[category.slug] || []).length;

          return (
            <button
              key={category.id}
              type="button"
              onClick={() => setActiveSlug(category.slug)}
              className={`inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold transition-all duration-150 sm:gap-1.5 sm:px-3 sm:py-1.5 sm:text-xs ${
                isActive ? "border border-slate-900 shadow-sm" : "border border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-slate-100"
              }`}
              style={
                isActive
                  ? { color: "#ffffff", background: "linear-gradient(to right, #0f172a, #1e293b)" }
                  : { color: "#334155", backgroundColor: "#f8fafc" }
              }
              aria-pressed={isActive}
            >
              <Icon className="h-3 w-3 shrink-0 sm:h-3.5 sm:w-3.5" style={isActive ? { color: "#ffffff" } : { color: "#475569" }} />
              <span style={isActive ? { color: "#ffffff" } : { color: "#334155" }}>{label}</span>
              <span
                className="rounded-full px-1 py-px text-[9px] font-bold sm:text-[10px]"
                style={
                  isActive
                    ? { color: "#ffffff", backgroundColor: "rgba(255,255,255,0.2)" }
                    : { color: "#475569", backgroundColor: "#e2e8f0" }
                }
              >
                {count}
              </span>
            </button>
          );
        })}
      </div>
    </div>
  );

  const cardsBlock = (
    <div className="p-2.5 sm:p-3">
      {!hasAnyPlaces ? (
        <EmptyNearbyState message="No verified nearby places yet" />
      ) : activePlaces.length > 0 ? (
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-2">
          {activePlaces.map((place) => {
            const sameLocation = isSameLocationPlace(place);
            const showDirections = publicDirectionsEnabled && place.show_directions === true;
            const navUnavailable =
              publicDirectionsEnabled &&
              !sameLocation &&
              !showDirections &&
              (place.invalid_navigation || !place.directions_url);
            const isRecommended = Boolean(place.is_recommended);
            const isDirectionsLoading = directionsLoadingId === place.google_place_id;
            const hasStatusBadge = place.is_open === true || place.is_open === false;

            return (
              <article
                key={place.google_place_id}
                className={`${insightPlaceCardClass} ${
                  isRecommended
                    ? "border-amber-200/90 bg-gradient-to-br from-amber-50/80 to-white shadow-[0_0_0_1px_rgba(251,191,36,0.15)]"
                    : ""
                }`}
              >
                <PlaceOpenStatusBadge isOpen={place.is_open} />

                {isRecommended ? (
                  <span
                    className="mb-1 inline-flex w-fit items-center gap-0.5 rounded-full px-1.5 py-px text-[9px] font-bold uppercase tracking-wide"
                    style={{ color: "#ffffff", backgroundColor: "#f59e0b" }}
                  >
                    <FiStar className="h-2.5 w-2.5" style={{ color: "#ffffff" }} />
                    Top
                  </span>
                ) : null}

                <h3
                  className={`text-[13px] font-semibold leading-snug sm:text-sm ${hasStatusBadge ? "pr-12 sm:pr-14" : ""}`}
                  style={{
                    color: "#0f172a",
                    display: "-webkit-box",
                    WebkitLineClamp: 2,
                    WebkitBoxOrient: "vertical",
                    overflow: "hidden",
                  }}
                >
                  {place.name}
                </h3>

                <div className="mt-1.5 flex flex-col gap-1.5 sm:mt-2 sm:flex-row sm:items-center sm:justify-between sm:gap-2">
                  <div className="flex min-w-0 flex-wrap items-center gap-1">
                    <span className={insightChipClass}>
                      <FiMapPin className="h-2.5 w-2.5 sm:h-3 sm:w-3" style={{ color: "#94a3b8" }} />
                      {place.distance_text || "Distance unavailable"}
                    </span>
                    <span className={insightChipClass}>
                      <FiStar className="h-2.5 w-2.5 sm:h-3 sm:w-3" style={{ color: "#f59e0b" }} />
                      {place.rating ? Number(place.rating).toFixed(1) : "—"}
                    </span>
                  </div>

                  <div className="flex shrink-0 sm:justify-end">
                    <PlaceCardAction
                      showDirections={showDirections}
                      navUnavailable={navUnavailable}
                      sameLocation={sameLocation}
                      place={place}
                      isDirectionsLoading={isDirectionsLoading}
                      onDirectionsClick={handleDirectionsClick}
                    />
                  </div>
                </div>
              </article>
            );
          })}
        </div>
      ) : (
        <EmptyNearbyState message="No places in this category" />
      )}
    </div>
  );

  if (embedded) {
    return (
      <div className={className}>
        <style>{`
          @media (min-width: 768px) {
            .nearby-directions-label { display: inline-flex !important; }
            .nearby-directions-icon-mobile { display: none !important; }
          }
        `}</style>
        <div className="border-b border-slate-100 px-2.5 py-1.5 sm:px-3">
          <div className="flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
            <h3 className="text-[10px] font-bold uppercase tracking-wide text-slate-500">
              Nearby places
            </h3>
            {hasAnyPlaces ? (
              <span className="text-[10px] font-medium text-slate-400">{verifiedCount} places</span>
            ) : null}
          </div>
        </div>
        {tabsBlock}
        {cardsBlock}
      </div>
    );
  }

  return (
    <section
      className={`overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition-shadow hover:shadow-md ${className}`}
      aria-label="Nearby places"
    >
      <style>{`
        @media (min-width: 768px) {
          .nearby-directions-label { display: inline-flex !important; }
          .nearby-directions-icon-mobile { display: none !important; }
        }
      `}</style>
      <header className="border-b border-slate-100 px-4 py-3.5 md:px-5 md:py-4">
        <div className="flex items-start gap-3">
          <div
            className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl shadow-sm"
            style={{ background: "linear-gradient(to bottom right, #0f172a, #334155)", color: "#ffffff" }}
          >
            <FiMapPin className="h-5 w-5" style={{ color: "#ffffff" }} />
          </div>
          <div className="min-w-0 flex-1">
            <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
              <h2 className="text-base font-bold tracking-tight md:text-lg" style={{ color: "#0f172a" }}>
                Nearby Places
              </h2>
              {payload?.quality_filter_enabled ? (
                <span
                  className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide md:text-[11px]"
                  style={{ color: "#047857", backgroundColor: "#ecfdf5", border: "1px solid #a7f3d0" }}
                >
                  <FiShield className="h-3 w-3" style={{ color: "#047857" }} />
                  Verified
                </span>
              ) : null}
            </div>
            {areaLabel ? (
              <p className="mt-0.5 truncate text-xs md:text-sm" style={{ color: "#64748b" }}>
                {areaLabel}
              </p>
            ) : null}
            {hasAnyPlaces ? (
              <p className="mt-1 text-[11px] font-medium md:text-xs" style={{ color: "#94a3b8" }}>
                {verifiedCount} verified {verifiedCount === 1 ? "place" : "places"} nearby
              </p>
            ) : null}
          </div>
        </div>
      </header>
      {tabsBlock}
      {cardsBlock}
    </section>
  );
};

export default NearbyPlacesSection;
