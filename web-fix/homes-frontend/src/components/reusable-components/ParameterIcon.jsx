import { ReactSVG } from "react-svg";
import {
  FaBed,
  FaBath,
  FaUtensils,
  FaParking,
  FaRulerCombined,
  FaBuilding,
  FaCouch,
  FaLayerGroup,
  FaDoorOpen,
  FaWater,
  FaBolt,
  FaTree,
  FaSnowflake,
} from "react-icons/fa";

const FALLBACK_BY_KEY = [
  ["bedroom", FaBed],
  ["bathroom", FaBath],
  ["washroom", FaBath],
  ["kitchen", FaUtensils],
  ["parking", FaParking],
  ["area", FaRulerCombined],
  ["property type", FaBuilding],
  ["furnishing", FaCouch],
  ["floor", FaLayerGroup],
  ["balcony", FaDoorOpen],
  ["water", FaWater],
  ["power", FaBolt],
  ["garden", FaTree],
  ["courtyard", FaTree],
  ["ac", FaSnowflake],
];

const injectSvgStyle = (svg) => {
  svg.setAttribute("style", "height: 100%; width: 100%;");
  svg.querySelectorAll("path").forEach((path) => {
    path.setAttribute("style", "fill: var(--facilities-icon-color);");
  });
};

const resolveFallbackIcon = (parameter) => {
  const label = `${parameter?.translated_name || ""} ${parameter?.name || ""}`.toLowerCase();
  for (const [key, Icon] of FALLBACK_BY_KEY) {
    if (label.includes(key)) {
      return Icon;
    }
  }
  return null;
};

const ParameterIcon = ({ parameter, className = "w-4 h-4 flex shrink-0 items-center justify-center object-contain" }) => {
  const image = parameter?.image;
  const FallbackIcon = resolveFallbackIcon(parameter);

  if (!image) {
    if (!FallbackIcon) {
      return null;
    }
    return (
      <FallbackIcon
        className={className}
        aria-hidden="true"
        style={{ color: "var(--facilities-icon-color, #595f65)" }}
      />
    );
  }

  return (
    <ReactSVG
      src={image}
      beforeInjection={injectSvgStyle}
      className={className}
      alt={parameter?.translated_name || parameter?.name || "parameter icon"}
    />
  );
};

export default ParameterIcon;
