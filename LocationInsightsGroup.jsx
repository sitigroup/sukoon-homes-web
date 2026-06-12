import { FiMapPin, FiShield } from "react-icons/fi";
import FeaturesAmenities from "./FeatureAmenities";
import NearbyPlacesSection, {
  buildNearbyAreaLabel,
} from "@/plugins/nearby-places/NearbyPlacesSection";

const LocationInsightsGroup = ({
  propertyDetails,
  DistanceSymbol,
  themeEnabled,
}) => {
  const areaLabel = buildNearbyAreaLabel(
    propertyDetails?.area_listing,
    propertyDetails?.city,
    propertyDetails?.state,
  );

  const hasOutdoorFacilities =
    propertyDetails?.assign_facilities?.length > 0 &&
    propertyDetails.assign_facilities.some(
      (elem) => elem.distance !== null && elem.distance !== "" && elem.distance !== 0,
    );

  if (!propertyDetails?.id) {
    return null;
  }

  return (
    <section
      className="location-insights mb-3 overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm sm:mb-4"
      aria-label="Location insights"
    >
      <header className="border-b border-slate-100 px-2.5 py-2 sm:px-3 sm:py-2.5 md:px-4">
        <div className="flex items-center gap-2 sm:gap-2.5">
          <div
            className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg shadow-sm sm:h-9 sm:w-9"
            style={{
              background: "linear-gradient(to bottom right, #0f172a, #334155)",
            }}
          >
            <FiMapPin className="h-4 w-4 sm:h-[18px] sm:w-[18px]" style={{ color: "#ffffff" }} />
          </div>
          <div className="min-w-0 flex-1">
            <div className="flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
              <h2 className="text-sm font-bold tracking-tight sm:text-base" style={{ color: "#0f172a" }}>
                Location Insights
              </h2>
              <span
                className="inline-flex items-center gap-0.5 rounded-full px-1.5 py-px text-[9px] font-semibold uppercase tracking-wide sm:text-[10px]"
                style={{
                  color: "#047857",
                  backgroundColor: "#ecfdf5",
                  border: "1px solid #a7f3d0",
                }}
              >
                <FiShield className="h-2.5 w-2.5 sm:h-3 sm:w-3" style={{ color: "#047857" }} />
                Verified
              </span>
            </div>
            {areaLabel ? (
              <p className="truncate text-[11px] leading-tight sm:text-xs" style={{ color: "#64748b" }}>
                {areaLabel}
              </p>
            ) : null}
          </div>
        </div>
      </header>

      {hasOutdoorFacilities ? (
        <FeaturesAmenities
          data={propertyDetails}
          DistanceSymbol={DistanceSymbol}
          themeEnabled={themeEnabled}
          embedded
          outdoorOnly
        />
      ) : null}

      <NearbyPlacesSection
        propertyId={propertyDetails.id}
        areaListing={propertyDetails.area_listing}
        city={propertyDetails.city}
        state={propertyDetails.state}
        embedded
      />
    </section>
  );
};

export default LocationInsightsGroup;
