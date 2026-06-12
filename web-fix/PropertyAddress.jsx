// PropertyAddress.js
import MapImage from "@/assets/map.png";
import Map from "../google-maps/GoogleMap";
// import { t } from '@/utils/translation'
import { useTranslation } from "../context/TranslationContext";

const PropertyAddress = ({
  details,
  isPremiumProperty,
  isPremiumUser,
  showMap,
  handleShowMap,
  latitude,
  longitude,
  handleOpenGoogleMap,
  isProject = false,
  showExactLocation = false,
}) => {
  const t = useTranslation();
  const lat = Number(latitude);
  const lng = Number(longitude);
  const hasExactCoordinates = showExactLocation && latitude !== null && latitude !== "" && longitude !== null && longitude !== "" && Number.isFinite(lat) && Number.isFinite(lng) && !(lat === 0 && lng === 0);

  return (
    <div className="cardBg newBorder mb-7 flex flex-col rounded-2xl">
      <div className="blackTextColor flex flex-col sm:flex-row items-center justify-between border-b p-5 gap-4 md:gap-0">
        <div className="text-base place-self-start font-bold md:text-xl">
          {isProject ? t("project") : t("property")} {t("address")}
        </div>
      </div>
      {!isPremiumProperty || isPremiumUser ? (
        <div className="grid grid-cols-1 gap-4 p-5 md:grid-cols-2 md:gap-7">
          {details.map(
            (item, index) =>
              item.value && (
                <div key={index} className="grid grid-cols-2 max-w-xs">
                  <p className="blackTextColor text-sm font-semibold">
                    {item.label}
                  </p>
                  <p className="blackTextColor text-sm">{item.value}</p>
                </div>
              ),
          )}
        </div>
      ) : null}
      {hasExactCoordinates && (
        <div className="flex justify-end px-5 pt-5">
          <button
            type="button"
            className="brandBg primaryTextColor rounded px-4 py-2 text-sm font-semibold"
            onClick={handleOpenGoogleMap}
          >
            {t("getDirections") !== "getDirections" ? t("getDirections") : "Get Directions"}
          </button>
        </div>
      )}
      <div className="relative p-5">
        {showMap ? (
          <Map latitude={latitude} longitude={longitude} isDraggable={false} showOnlyRadius={!showExactLocation} />
        ) : (
          <>
            <div
              className="flex min-h-[400px] w-full items-center justify-center rounded blur-[5px]"
              style={{
                backgroundImage: `url(${MapImage?.src})`,
              }}
            ></div>
            <button
              className="brandBg primaryTextColor absolute left-[35%] top-[40%] rounded px-4 py-2 md:left-[45%] md:top-[45%]"
              onClick={handleShowMap}
            >
              {t("viewMap")}
            </button>
          </>
        )}
      </div>
    </div>
  );
};

export default PropertyAddress;
