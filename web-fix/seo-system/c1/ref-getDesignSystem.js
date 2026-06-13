import {
  DEFAULT_UI_MODE,
  getCurrentUiMode,
  isSukoonCustomMode,
} from "@/config/ui-mode";

import sukoonCustom from "./modes/sukoon_custom";
import wrteamDefault from "./modes/wrteam_default";

const MODE_REGISTRY = {
  wrteam_default: wrteamDefault,
  sukoon_custom: sukoonCustom,
};

/**
 * Resolve the active design-system mode pack (tokens + component variant maps).
 * Typography, radius, and shadow classes delegate to Phase 1 canonical tokens in sukoon_custom mode.
 */
export default function getDesignSystem() {
  const mode = getCurrentUiMode();
  const pack = MODE_REGISTRY[mode] ?? MODE_REGISTRY[DEFAULT_UI_MODE];

  return {
    mode,
    tokens: pack.tokens,
    isSukoonCustom: isSukoonCustomMode(),
    buttonVariants: pack.buttonVariants,
    buttonSpinnerClasses: pack.buttonSpinnerClasses,
    cardVariants: pack.cardVariants,
    cardPaddingMap: pack.cardPaddingMap,
    badgeTierStyles: pack.badgeTierStyles,
    badgeColorByVariant: pack.badgeColorByVariant,
    badgeTierByVariant: pack.badgeTierByVariant,
    inputStyles: pack.inputStyles,
    modalStyles: pack.modalStyles,
    skeletonVariants: pack.skeletonVariants,
    skeletonComposites: pack.skeletonComposites,
    typographyClasses: pack.typographyClasses,
    radiusClasses: pack.radiusClasses,
    shadowClasses: pack.shadowClasses,
    layoutStyles: pack.layoutStyles,
    dsWrapperClass: pack.dsWrapperClass,
  };
}

export { getDesignSystem };
