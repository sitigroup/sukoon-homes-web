import { ReactSVG } from "react-svg";
import { FiMapPin } from "react-icons/fi";
import { useTranslation } from "../context/TranslationContext";
import { getDisplayValueForOption } from "@/utils/helperFunction";
import Link from "next/link";
import {
  insightChipClass,
  insightIconBoxClass,
  insightPlaceCardClass,
  insightSectionLabelClass,
} from "@/plugins/nearby-places/locationInsightsStyles";

const sectionShell =
  "overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition-shadow hover:shadow-md";

const sectionHeader =
  "flex items-center gap-3 border-b border-slate-100 px-4 py-3.5 md:px-5 md:py-4";

const headerIcon =
  "flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-slate-900 to-slate-700 text-white shadow-sm";

const FeaturesAmenities = ({
  data,
  themeEnabled,
  DistanceSymbol,
  embedded = false,
  outdoorOnly = false,
  hideOutdoor = false,
}) => {
  const t = useTranslation();

  const hasParameters =
    !outdoorOnly &&
    data?.parameters?.length > 0 &&
    data.parameters.some(
      (elem) => elem.value !== null && elem.value !== "" && elem.value !== "0",
    );

  const hasFacilities =
    !hideOutdoor &&
    data?.assign_facilities?.length > 0 &&
    data.assign_facilities.some(
      (elem) =>
        elem.distance !== null && elem.distance !== "" && elem.distance !== 0,
    );

  const outdoorCards = hasFacilities
    ? data.assign_facilities
        .map((elem, index) =>
          elem.distance !== "" && elem.distance !== 0 ? (
            <article key={index} className={insightPlaceCardClass}>
              <div className="flex items-center gap-2">
                <div className={insightIconBoxClass}>
                  <ReactSVG
                    src={elem?.image}
                    beforeInjection={(svg) => {
                      svg.setAttribute("style", "height: 100%; width: 100%;");
                      svg.querySelectorAll("path").forEach((path) => {
                        path.setAttribute("style", "fill: var(--facilities-icon-color)");
                      });
                    }}
                    className="flex h-4 w-4 items-center justify-center object-cover sm:h-5 sm:w-5"
                    alt={`Facility ${index + 1}`}
                  />
                </div>
                <div className="min-w-0 flex-1">
                  <h3
                    className="truncate text-[13px] font-semibold leading-tight sm:text-sm"
                    style={{ color: "#0f172a" }}
                  >
                    {elem?.translated_name || elem?.name}
                  </h3>
                  <span className={`${insightChipClass} mt-1`}>
                    <FiMapPin className="h-2.5 w-2.5 sm:h-3 sm:w-3" style={{ color: "#94a3b8" }} />
                    {elem.distance}{" "}
                    {elem.distance > 1 ? t(DistanceSymbol + "s") : t(DistanceSymbol)}
                  </span>
                </div>
              </div>
            </article>
          ) : null,
        )
        .filter(Boolean)
    : null;

  if (embedded && outdoorOnly) {
    if (!outdoorCards?.length) return null;

    return (
      <div className="border-b border-slate-100 pb-2">
        <h3 className={insightSectionLabelClass}>Outdoor facilities</h3>
        <div className="grid grid-cols-1 gap-2 px-2.5 sm:grid-cols-2 sm:px-3 lg:grid-cols-3">
          {outdoorCards}
        </div>
      </div>
    );
  }

  return (
    <>
      {hasParameters && (
        <div className={`cardBg newBorder mb-4 flex flex-col rounded-2xl md:mb-5 ${sectionShell}`}>
          <div className={sectionHeader}>
            <div className={headerIcon}>
              <FiMapPin className="h-5 w-5" />
            </div>
            <h2 className="blackTextColor text-base font-bold md:text-lg">
              {t("feature&Amenities")}
            </h2>
          </div>
          <div className="p-3 md:p-4">
            <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-2 md:grid-cols-3 md:gap-3 xl:grid-cols-4">
              {data.parameters.map((elem, index) =>
                elem.value !== "" && elem.value !== "0" ? (
                  <div
                    key={index}
                    className="flex flex-row items-center gap-3 rounded-xl border border-slate-200/90 bg-white p-3 transition-colors hover:border-slate-300"
                  >
                    <div className="flex h-9 w-9 min-w-9 items-center justify-center rounded-lg border border-slate-200 bg-slate-50">
                      <ReactSVG
                        src={elem?.image}
                        beforeInjection={(svg) => {
                          svg.setAttribute("style", "height: 100%; width: 100%;");
                          svg.querySelectorAll("path").forEach((path) => {
                            path.setAttribute("style", "fill: var(--facilities-icon-color)");
                          });
                        }}
                        className="flex h-5 w-5 items-center justify-center object-cover"
                        alt={`Feature ${index + 1}`}
                      />
                    </div>
                    <div className="blackTextColor min-w-0 flex flex-col overflow-hidden text-sm">
                      <span className="font-semibold">{elem?.translated_name || elem?.name}</span>
                      <span className="truncate text-xs font-medium text-slate-500">
                        {elem?.value &&
                        typeof elem?.value === "string" &&
                        elem?.value?.startsWith("https://") ? (
                          <Link href={elem?.value || "#"} target="_blank" rel="noopener noreferrer">
                            {elem.value}
                          </Link>
                        ) : (
                          getDisplayValueForOption(elem)
                        )}
                      </span>
                    </div>
                  </div>
                ) : null,
              )}
            </div>
          </div>
        </div>
      )}

      {hasFacilities && !hideOutdoor && (
        <div className={`cardBg newBorder mb-4 flex flex-col rounded-2xl ${sectionShell}`}>
          <div className={sectionHeader}>
            <div className={headerIcon}>
              <FiMapPin className="h-5 w-5" />
            </div>
            <div>
              <h2 className="blackTextColor text-base font-bold md:text-lg">{t("OTF")}</h2>
              <p className="mt-0.5 text-xs text-slate-500">Outdoor facilities & distances</p>
            </div>
          </div>
          <div className="p-3 md:p-4">
            <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-2 md:gap-3">
              {outdoorCards}
            </div>
          </div>
        </div>
      )}
    </>
  );
};

export default FeaturesAmenities;
