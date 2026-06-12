import { store } from "@/redux/store";
import { getTranslationByLocale } from "./translation";
import toast from "react-hot-toast";
import Swal from "sweetalert2";
import { PackageTypes } from "./checkPackages/packageTypes";
import { checkPackageAvailable } from "./checkPackages/checkPackage";
import { featurePropertyApi, getMapDetailsApi } from "@/api/apiRoutes";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import { setLocationAction } from "@/redux/slices/locationSlice";
import { setRole } from "@/redux/slices/authSlice";
// Helper function to get translations in non-component files
const t = (label) => {
  try {
    const LanguageSettings = store?.getState()?.LanguageSettings;
    if (!LanguageSettings) {
      return label; // Fallback if store not ready
    }
    const translations = LanguageSettings?.currentLang?.file_name;
    const currentLocale = LanguageSettings?.default_language;

    if (translations) {
      return translations[label] || label;
    } else {
      const fallbackTranslations = getTranslationByLocale(currentLocale);
      return fallbackTranslations[label] || label;
    }
  } catch (error) {
    console.error("Error in translation function:", error);
    return label;
  }
};

export const truncate = (input, maxLength) => {
  // Check if input is undefined or null
  if (!input) {
    return ""; // or handle the case as per your requirement
  }
  // Convert input to string to handle both numbers and text
  let text = String(input);
  // If the text length is less than or equal to maxLength, return the original text
  if (text.length <= maxLength) {
    return text;
  } else {
    // Otherwise, truncate the text to maxLength characters and append ellipsis
    return text.slice(0, maxLength) + "...";
  }
};

export const formatPriceAbbreviated = (price) => {
  try {
    const WebSetting = store?.getState()?.WebSetting;
    const systemSettingsData = WebSetting?.data;
    const CurrencySymbol = systemSettingsData?.currency_symbol || "";
    const FullPriceShow = systemSettingsData?.number_with_suffix === "1";

    if (FullPriceShow) {
      if (price >= 1000000000) {
        // Billions
        return CurrencySymbol + (price / 1000000000).toFixed(1) + "B";
      } else if (price >= 1000000) {
        // Millions
        return CurrencySymbol + (price / 1000000).toFixed(1) + "M";
      } else if (price >= 1000) {
        // Thousands
        return CurrencySymbol + (price / 1000).toFixed(1) + "K";
      } else {
        // Less than 1K
        return CurrencySymbol + price ? CurrencySymbol + price?.toString() : "";
      }
    } else {
      return CurrencySymbol + price ? CurrencySymbol + price?.toString() : "";
    }
  } catch (error) {
    console.error("Error in formatPriceAbbreviated:", error);
    return price ? price.toString() : "";
  }
};

export const formatPriceAbbreviatedIndian = (price) => {
  const systemSettingsData = store.getState()?.WebSetting?.data;
  const FullPriceShow = systemSettingsData?.number_with_suffix === "1";
  if (FullPriceShow) {
    if (price >= 1000000000) {
      return (price / 1000000000).toFixed(1) + "Ab";
    } else if (price >= 10000000) {
      return (price / 10000000).toFixed(1) + "Cr";
    } else if (price >= 100000) {
      return (price / 100000).toFixed(1) + "L";
    } else if (price >= 1000) {
      return (price / 1000).toFixed(1) + "K";
    } else {
      return price ? price?.toString() : "";
    }
  }
};

export const timeAgo = (dateString) => {
  const now = new Date();
  const date = new Date(dateString);
  const secondsAgo = Math.floor((now - date) / 1000);

  const intervals = [
    { label: "year", seconds: 31536000 },
    { label: "month", seconds: 2592000 },
    { label: "week", seconds: 604800 },
    { label: "day", seconds: 86400 },
    { label: "hour", seconds: 3600 },
    { label: "minute", seconds: 60 },
    { label: "second", seconds: 1 },
  ];

  for (const interval of intervals) {
    const count = Math.floor(secondsAgo / interval.seconds);
    if (count >= 1) {
      return `${count} ${interval.label}${count > 1 ? "s" : ""} ago`;
    }
  }

  return "just now";
};

export const BadgeSvg = ({
  color = "#fff"
}) => (
  <svg
    width="26"
    height="26"
    viewBox="0 0 20 21"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <path
      d="M15 2.9165H5C4.46957 2.9165 3.96086 3.12923 3.58579 3.50788C3.21071 3.88654 3 4.4001 3 4.9356V13.8592C3.00019 14.2151 3.09353 14.5646 3.27057 14.8723C3.44762 15.18 3.70208 15.435 4.00817 15.6115L9.50409 18.7832C9.65504 18.8705 9.82601 18.9165 10 18.9165C10.174 18.9165 10.345 18.8705 10.4959 18.7832L15.9918 15.6115C16.298 15.4351 16.5526 15.1802 16.7296 14.8724C16.9067 14.5647 17 14.2151 17 13.8592V4.9356C17 4.4001 16.7893 3.88654 16.4142 3.50788C16.0391 3.12923 15.5304 2.9165 15 2.9165ZM13.9223 9.31352L9.63897 13.6447L6.71662 10.6889C6.53155 10.4991 6.42828 10.2431 6.42933 9.97675C6.43038 9.7104 6.53565 9.45525 6.72222 9.2669C6.90878 9.07856 7.16151 8.97228 7.42535 8.97122C7.68919 8.97016 7.94275 9.07441 8.13079 9.26126L9.63897 10.7825L12.515 7.88585C12.703 7.69901 12.9566 7.59476 13.2204 7.59582C13.4843 7.59687 13.737 7.70315 13.9236 7.8915C14.1101 8.07984 14.2154 8.33499 14.2164 8.60135C14.2175 8.8677 14.1142 9.12369 13.9292 9.31352H13.9223Z"
      fill={color}
    />
  </svg>
);
export const VerifiedAgentBadge = ({ color = "#363636", width = 12, height = 14 }) => (
  <svg width={width} height={height} viewBox={`0 0 12 14`} xmlns="http://www.w3.org/2000/svg">
    <path
      d="M9.99333 1.47833L6.32667 0.105C5.94667 -0.035 5.32667 -0.035 4.94667 0.105L1.28 1.47833C0.573333 1.745 0 2.57167 0 3.325V8.725C0 9.265 0.353333 9.97833 0.786667 10.2983L4.45333 13.0383C5.1 13.525 6.16 13.525 6.80667 13.0383L10.4733 10.2983C10.9067 9.97167 11.26 9.265 11.26 8.725V3.325C11.2667 2.57167 10.6933 1.745 9.99333 1.47833ZM7.95333 5.21167L5.08667 8.07833C4.98667 8.17833 4.86 8.225 4.73333 8.225C4.60667 8.225 4.48 8.17833 4.38 8.07833L3.31333 6.99833C3.12 6.805 3.12 6.485 3.31333 6.29167C3.50667 6.09833 3.82667 6.09833 4.02 6.29167L4.74 7.01167L7.25333 4.49833C7.44667 4.305 7.76667 4.305 7.96 4.49833C8.15333 4.69167 8.15333 5.01833 7.95333 5.21167Z"
      fill={color} />
  </svg>
);

export const VerifiedUserBadge = ({ color = "#087c7c", width = 18, height = 18 }) => (
  <svg width={width} height={height} viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M16.1702 8.05492L15.1502 6.86992C14.9552 6.64492 14.7977 6.22492 14.7977 5.92492V4.64992C14.7977 3.85492 14.1452 3.20242 13.3502 3.20242H12.0752C11.7827 3.20242 11.3552 3.04492 11.1302 2.84992L9.94516 1.82992C9.42766 1.38742 8.58016 1.38742 8.05516 1.82992L6.87766 2.85742C6.65266 3.04492 6.22516 3.20242 5.93266 3.20242H4.63516C3.84016 3.20242 3.18766 3.85492 3.18766 4.64992V5.93242C3.18766 6.22492 3.03016 6.64492 2.84266 6.86992L1.83016 8.06242C1.39516 8.57992 1.39516 9.41992 1.83016 9.93742L2.84266 11.1299C3.03016 11.3549 3.18766 11.7749 3.18766 12.0674V13.3499C3.18766 14.1449 3.84016 14.7974 4.63516 14.7974H5.93266C6.22516 14.7974 6.65266 14.9549 6.87766 15.1499L8.06266 16.1699C8.58016 16.6124 9.42766 16.6124 9.95266 16.1699L11.1377 15.1499C11.3627 14.9549 11.7827 14.7974 12.0827 14.7974H13.3577C14.1527 14.7974 14.8052 14.1449 14.8052 13.3499V12.0749C14.8052 11.7824 14.9627 11.3549 15.1577 11.1299L16.1777 9.94492C16.6127 9.42742 16.6127 8.57242 16.1702 8.05492ZM12.1202 7.58242L8.49766 11.2049C8.39266 11.3099 8.25016 11.3699 8.10016 11.3699C7.95016 11.3699 7.80766 11.3099 7.70266 11.2049L5.88766 9.38992C5.67016 9.17242 5.67016 8.81242 5.88766 8.59492C6.10516 8.37742 6.46516 8.37742 6.68266 8.59492L8.10016 10.0124L11.3252 6.78742C11.5427 6.56992 11.9027 6.56992 12.1202 6.78742C12.3377 7.00492 12.3377 7.36492 12.1202 7.58242Z" fill={color} />
  </svg>
);

export const showSwal = ({
  icon,
  title,
  text,
  onConfirm,
  isPremiumCheck,
  router,
  showCancelButton = false,
  t = () => { },
  isUser = false,
}) =>
  Swal.fire({
    icon,
    title,
    text,
    showCancelButton,
    customClass: { confirmButton: "Swal-confirm-buttons", cancelButton: "Swal-cancel-buttons" },
    confirmButtonText: t("ok"),
  }).then((result) => {
    if (result.isConfirmed) {
      if (isPremiumCheck)
        router.push(`${isUser ? "/subscription-plan" : "/agent/packages"}?lang=${router?.query?.lang}`);
      else if (onConfirm) onConfirm();
    }
  });

// utils/stickyNote.js
export const createStickyNote = () => {
  const systemSettingsData = store.getState()?.WebSetting?.data;
  const appUrl = systemSettingsData?.appstore_id;

  // Create the sticky note container
  const stickyNote = document.createElement("div");
  stickyNote.style.position = "fixed";
  stickyNote.style.bottom = "0";
  stickyNote.style.width = "100%";
  stickyNote.style.backgroundColor = "#ffffff";
  stickyNote.style.color = "#000000";
  stickyNote.style.padding = "10px";
  stickyNote.style.textAlign = "center";
  stickyNote.style.fontSize = "14px";
  stickyNote.style.zIndex = "99999";

  // Create the close button
  const closeButton = document.createElement("span");
  closeButton.style.cursor = "pointer";
  closeButton.style.float = "right";
  closeButton.innerHTML = "&times;";
  closeButton.onclick = function () {
    document.body.removeChild(stickyNote);
  };

  // Add content to the sticky note
  stickyNote.innerHTML = t("chatAndNotificationNotSupported");
  stickyNote.appendChild(closeButton);

  // Conditionally add the "Download Now" link if appUrl exists
  if (appUrl) {
    const link = document.createElement("a");
    link.style.textDecoration = "underline";
    link.style.color = "#3498db";
    link.style.marginLeft = "10px"; // Add spacing between text and link
    link.innerText = t("downloadNow");
    link.href = appUrl;
    link.target = "_blank"; // Open link in a new tab
    stickyNote.appendChild(link);
  }

  // Append the sticky note to the document body
  document.body.appendChild(stickyNote);
};

/**
 * Creates a FormData object containing only non-falsy values
 * @param {Object} data - Object containing the data to be added to FormData
 * @returns {FormData} FormData object with only non-falsy values
 */
export const createFilteredFormData = (data) => {
  const formData = new FormData();

  // Iterate through all properties and only append non-falsy values
  Object.entries(data).forEach(([key, value]) => {
    // Check if value is not null, undefined, empty string, 0, false, or NaN
    if (value !== undefined && value !== null && value !== "") {
      formData.append(key, value);
    }
  });

  return formData;
};

// Utility function to filter out falsy values from params
export const getFilteredParams = (params) => {
  const filteredParams = {};
  Object.entries(params).forEach(([key, value]) => {
    // Check if value is not null, undefined, empty string, 0, false, or NaN
    if (value !== undefined && value !== null && value !== "") {
      filteredParams[key] = value;
    }
  });
  return filteredParams;
};

// Error handling function
export const handleFirebaseAuthError = (errorCode, t) => {
  const ERROR_CODES = {
    "auth/user-not-found": t("userNotFound"),
    "auth/wrong-password": t("invalidPassword"),
    "auth/email-already-in-use": t("emailInUse"),
    "auth/invalid-email": t("invalidEmail"),
    "auth/user-disabled": t("userAccountDisabled"),
    "auth/too-many-requests": t("tooManyRequests"),
    "auth/operation-not-allowed": t("operationNotAllowed"),
    "auth/internal-error": t("internalError"),
    "auth/invalid-login-credentials": t("incorrectDetails"),
    "auth/invalid-credential": t("incorrectDetails"),
    "auth/admin-restricted-operation": t("adminOnlyOperation"),
    "auth/already-initialized": t("alreadyInitialized"),
    "auth/app-not-authorized": t("appNotAuthorized"),
    "auth/app-not-installed": t("appNotInstalled"),
    "auth/argument-error": t("argumentError"),
    "auth/captcha-check-failed": t("captchaCheckFailed"),
    "auth/code-expired": t("codeExpired"),
    "auth/cordova-not-ready": t("cordovaNotReady"),
    "auth/cors-unsupported": t("corsUnsupported"),
    "auth/credential-already-in-use": t("credentialAlreadyInUse"),
    "auth/custom-token-mismatch": t("customTokenMismatch"),
    "auth/requires-recent-login": t("requiresRecentLogin"),
    "auth/dependent-sdk-initialized-before-auth": t(
      "dependentSdkInitializedBeforeAuth",
    ),
    "auth/dynamic-link-not-activated": t("dynamicLinkNotActivated"),
    "auth/email-change-needs-verification": t("emailChangeNeedsVerification"),
    "auth/emulator-config-failed": t("emulatorConfigFailed"),
    "auth/expired-action-code": t("expiredActionCode"),
    "auth/cancelled-popup-request": t("cancelledPopupRequest"),
    "auth/invalid-api-key": t("invalidApiKey"),
    "auth/invalid-app-credential": t("invalidAppCredential"),
    "auth/invalid-app-id": t("invalidAppId"),
    "auth/invalid-user-token": t("invalidUserToken"),
    "auth/invalid-auth-event": t("invalidAuthEvent"),
    "auth/invalid-cert-hash": t("invalidCertHash"),
    "auth/invalid-verification-code": t("invalidVerificationCode"),
    "auth/invalid-continue-uri": t("invalidContinueUri"),
    "auth/invalid-cordova-configuration": t("invalidCordovaConfiguration"),
    "auth/invalid-custom-token": t("invalidCustomToken"),
    "auth/invalid-dynamic-link-domain": t("invalidDynamicLinkDomain"),
    "auth/invalid-emulator-scheme": t("invalidEmulatorScheme"),
    "auth/invalid-message-payload": t("invalidMessagePayload"),
    "auth/invalid-multi-factor-session": t("invalidMultiFactorSession"),
    "auth/invalid-oauth-client-id": t("invalidOauthClientId"),
    "auth/invalid-oauth-provider": t("invalidOauthProvider"),
    "auth/invalid-action-code": t("invalidActionCode"),
    "auth/unauthorized-domain": t("unauthorizedDomain"),
    "auth/invalid-persistence-type": t("invalidPersistenceType"),
    "auth/invalid-phone-number": t("invalidPhoneNumber"),
    "auth/invalid-provider-id": t("invalidProviderId"),
    "auth/invalid-recaptcha-action": t("invalidRecaptchaAction"),
    "auth/invalid-recaptcha-token": t("invalidRecaptchaToken"),
    "auth/invalid-recaptcha-version": t("invalidRecaptchaVersion"),
    "auth/invalid-recipient-email": t("invalidRecipientEmail"),
    "auth/invalid-req-type": t("invalidReqType"),
    "auth/invalid-sender": t("invalidSender"),
    "auth/invalid-verification-id": t("invalidVerificationId"),
    "auth/invalid-tenant-id": t("invalidTenantId"),
    "auth/multi-factor-info-not-found": t("multiFactorInfoNotFound"),
    "auth/multi-factor-auth-required": t("multiFactorAuthRequired"),
    "auth/missing-android-pkg-name": t("missingAndroidPkgName"),
    "auth/missing-app-credential": t("missingAppCredential"),
    "auth/auth-domain-config-required": t("authDomainConfigRequired"),
    "auth/missing-client-type": t("missingClientType"),
    "auth/missing-verification-code": t("missingVerificationCode"),
    "auth/missing-continue-uri": t("missingContinueUri"),
    "auth/missing-iframe-start": t("missingIframeStart"),
    "auth/missing-ios-bundle-id": t("missingIosBundleId"),
    "auth/missing-multi-factor-info": t("missingMultiFactorInfo"),
    "auth/missing-multi-factor-session": t("missingMultiFactorSession"),
    "auth/missing-or-invalid-nonce": t("missingOrInvalidNonce"),
    "auth/missing-phone-number": t("missingPhoneNumber"),
    "auth/missing-recaptcha-token": t("missingRecaptchaToken"),
    "auth/missing-recaptcha-version": t("missingRecaptchaVersion"),
    "auth/missing-verification-id": t("missingVerificationId"),
    "auth/app-deleted": t("appDeleted"),
    "auth/account-exists-with-different-credential": t(
      "accountExistsWithDifferentCredential",
    ),
    "auth/network-request-failed": t("networkRequestFailed"),
    "auth/no-auth-event": t("noAuthEvent"),
    "auth/no-such-provider": t("noSuchProvider"),
    "auth/null-user": t("nullUser"),
    "auth/operation-not-supported-in-this-environment": t(
      "operationNotSupportedInThisEnvironment",
    ),
    "auth/popup-blocked": t("popupBlocked"),
    "auth/popup-closed-by-user": t("popupClosedByUser"),
    "auth/provider-already-linked": t("providerAlreadyLinked"),
    "auth/quota-exceeded": t("quotaExceeded"),
    "auth/recaptcha-not-enabled": t("recaptchaNotEnabled"),
    "auth/redirect-cancelled-by-user": t("redirectCancelledByUser"),
    "auth/redirect-operation-pending": t("redirectOperationPending"),
    "auth/rejected-credential": t("rejectedCredential"),
    "auth/second-factor-already-in-use": t("secondFactorAlreadyInUse"),
    "auth/maximum-second-factor-count-exceeded": t(
      "maximumSecondFactorCountExceeded",
    ),
    "auth/tenant-id-mismatch": t("tenantIdMismatch"),
    "auth/timeout": t("timeout"),
    "auth/user-token-expired": t("userTokenExpired"),
    "auth/unauthorized-continue-uri": t("unauthorizedContinueUri"),
    "auth/unsupported-first-factor": t("unsupportedFirstFactor"),
    "auth/unsupported-persistence-type": t("unsupportedPersistenceType"),
    "auth/unsupported-tenant-operation": t("unsupportedTenantOperation"),
    "auth/unverified-email": t("unverifiedEmail"),
    "auth/user-cancelled": t("userCancelled"),
    "auth/user-mismatch": t("userMismatch"),
    "auth/user-signed-out": t("userSignedOut"),
    "auth/weak-password": t("weakPassword"),
    "auth/web-storage-unsupported": t("webStorageUnsupported"),
  };

  // Check if the error code exists in the ERROR_CODES object
  if (ERROR_CODES.hasOwnProperty(errorCode)) {
    // If the error code exists, log the corresponding error message
    toast.error(ERROR_CODES[errorCode]);
  } else {
    // If the error code is not found, log a generic error message
    toast.error(`${t("errorOccurred")}:${errorCode}`);
  }
  // Optionally, you can add additional logic here to handle the error
  // For example, display an error message to the user, redirect to an error page, etc.
};

export const formatDuration = (hours, t) => {
  if (hours >= 24) {
    const days = Math.floor(hours / 24);
    return `${days} ${t("days")}`;
  } else {
    return `${hours} ${t("hours")}`;
  }
};

export const canRedirectToDetails = (data, router, isUserProperty, locale) =>
  router.push(
    isUserProperty
      ? `/my-property/${data?.slug_id}?lang=${locale}`
      : `/property-details/${data?.slug_id}?lang=${locale}`,
  );

export const getLimitErrorMessageKey = (type) =>
  ({
    [PackageTypes.PROPERTY_LIST]: "propertyListLimitOrPackageNotAvailable",
    [PackageTypes.PROJECT_LIST]: "projectListLimitOrPackageNotAvailable",
    [PackageTypes.PROPERTY_FEATURE]:
      "propertyFeatureLimitOrPackageNotAvailable",
    [PackageTypes.PROJECT_FEATURE]: "projectFeatureLimitOrPackageNotAvailable",
    [PackageTypes.MORTGAGE_CALCULATOR_DETAIL]:
      "mortgageCalculatorLimitOrPackageNotAvailable",
    [PackageTypes.PREMIUM_PROPERTIES]:
      "premiumPropertiesLimitOrPackageNotAvailable",
    [PackageTypes.PROJECT_ACCESS]: "projectAccessLimitOrPackageNotAvailable",
    [PackageTypes.PREMIUM_PROJECTS]: "premiumProjectsLimitOrPackageNotAvailable",
  })[type] || "limitOrPackageNotAvailable";

export const handlePackageCheck = async (
  e,
  type,
  router,
  id,
  propertyData = null,
  isUserProperty = false,
  isUserProject = false,
  t = () => { },
  isUser = false
) => {
  const WebSetting = store?.getState()?.WebSetting;
  const User = store?.getState()?.User;
  const LanguageSettings = store?.getState()?.LanguageSettings;

  const userData = User?.data;
  const systemSettingsData = WebSetting?.data;
  const locale = LanguageSettings?.active_language || "en";

  if (!userData?.id) {
    return showSwal({
      title: t("plzLogFirst"),
      icon: "warning",
      showCancelButton: false,
      customClass: {
        confirmButton: "Swal-confirm-buttons",
        cancelButton: "Swal-cancel-buttons",
      },
      confirmButtonText: t("ok"),
      t
    }).then((result) => {
    });
  }
  // User profile completeness check for property & project listing
  if ([PackageTypes.PROPERTY_LIST, PackageTypes.PROJECT_LIST].includes(type)) {


    const isProfileComplete = isUser ? [
      "name",
      "email",
      "mobile",
      "profile",
      "address",
    ].every((key) => userData?.[key])
      :
      ["agent_name",
        "agent_email",
        "agent_mobile",
        "agent_profile_photo",
        "agent_address",
      ].every((key) => userData?.agent_profile?.[key]);

    if (!isProfileComplete) {
      return showSwal({
        icon: "error",
        title: t("oops"),
        text: t("youHaveNotCompleteProfile"),
        onConfirm: () => router.push(`/${isUser ? "user" : "agent"}/profile?lang=${locale}`),
        t
      });
    }

    // Verification check
    const needToVerify = isUser ? systemSettingsData?.verification_required_for_user : systemSettingsData?.verification_required_for_agent;
    const verificationStatus = String(
      isUser ? userData?.user_verification_status : userData?.agent_verification_status,
    ).toLowerCase();

    if (needToVerify && verificationStatus !== "approved") {
      const statusMessages = {
        pending: {
          icon: "warning",
          title: t("verifyPendingTitle"),
          text: t("verifyPendingDesc"),
          t
        },
        failed: {
          icon: "error",
          title: t("verifyFailTitle"),
          text: t("verifyFailDesc"),
          onConfirm: () => router.push(`/${isUser ? "user/profile" : "agent/dashboard"}?lang=${locale}`),
          t
        },
        rejected: {
          icon: "error",
          title: t("verifyFailTitle"),
          text: t("verifyFailDesc"),
          onConfirm: () => router.push(`/${isUser ? "user/profile" : "agent/dashboard"}?lang=${locale}`),
          t
        },
        not_applied: {
          icon: "error",
          title: t("oops"),
          text: t("youHaveNotVerifiedUser"),
          onConfirm: () => router.push(`/${isUser ? "user" : "agent"}/verification-form?lang=${locale}`),
          t
        },
        default: {
          icon: "error",
          title: t("oops"),
          text: t("youHaveNotVerifiedUser"),
          onConfirm: () => router.push(`/${isUser ? "user" : "agent"}/verification-form?lang=${locale}`),
          t
        },
      };

      return showSwal(
        statusMessages[verificationStatus] || statusMessages.default,
      );
    }
  }

  // Special case handling for premium properties
  if (type === PackageTypes.PREMIUM_PROPERTIES) {
    if (isUserProperty) {
      // Show User to Switch to Agent Profile and navigate to /agent/properties
      return showSwal({
        icon: "warning",
        title: t("agentSwitchSwalTitle"),
        text: t("agentSwitchSwalText"),
        onConfirm: () => { store.dispatch(setRole({ data: "agent" })); router.push(`/agent/properties?lang=${locale}`) }, t
      });
    }
    if (propertyData?.is_premium) {
      const isAvailable = await checkPackageAvailable(type);
      if (!isAvailable) {
        return showSwal({
          icon: "error",
          title: t("oops"),
          text: t(getLimitErrorMessageKey(type)),
          isPremiumCheck: true,
          router,
          t,
          isUser
        });
      }
    }
    // After checks, just redirect to details
    return canRedirectToDetails(propertyData, router, isUserProperty, locale);
  }

  // 🔥 Special Handling for Premium Projects (if it requires similar flow)
  if (type === PackageTypes.PREMIUM_PROJECTS) {
    if (isUserProject) {
      // Show User to Switch to Agent Profile and navigate to /agent/projects
      return showSwal({
        icon: "warning",
        title: t("agentSwitchSwalTitle"),
        text: t("agentSwitchSwalText"),
        onConfirm: () => { store.dispatch(setRole({ data: "agent" })); router.push(`/agent/projects?lang=${locale}`) }, t
      });
    }
    const isAvailable = await checkPackageAvailable(type);
    if (!isAvailable) {
      return showSwal({
        icon: "error",
        title: t("oops"),
        text: t(getLimitErrorMessageKey(type)),
        isPremiumCheck: true,
        router,
        t
      });
    }
    return isUserProject
      ? router.push(`/my-project/${id}?lang=${locale}`)
      : router.push(`/project-details/${id}?lang=${locale}`);
  }

  // 🧵 For all other types (Generic Flow)
  const isAvailable = await checkPackageAvailable(type);
  if (!isAvailable) {
    return showSwal({
      icon: "error",
      title: t("oops"),
      text: t(getLimitErrorMessageKey(type)),
      isPremiumCheck: true,
      router,
      t
    });
  }

  const successActions = {
    [PackageTypes.PROPERTY_LIST]: () =>
      router.push(`/${isUser ? "user" : "agent"}/add-property?lang=${locale}`),
    [PackageTypes.PROJECT_LIST]: () =>
      router.push(`/${isUser ? "user" : "agent"}/add-project?lang=${locale}`),
    [PackageTypes.PROPERTY_FEATURE]: async () => {
      try {
        const res = await featurePropertyApi({
          feature_for: "property",
          property_id: id,
        });
        toast.success(t(res.message));
        router.push(`/${isUser ? "user" : "agent"}/advertisement?tab=property&lang=${locale}`);
      } catch (err) {
        // console.error("[DEBUG] Error featuring property:", err);
        toast.error(t(err.message));
      }
    },
    [PackageTypes.PROJECT_FEATURE]: async () => {
      try {
        const res = await featurePropertyApi({
          feature_for: "project",
          project_id: id,
        });
        toast.success(t(res.message));
        router.push(`/${isUser ? "user" : "agent"}/advertisement?tab=project&lang=${locale}`);
      } catch (err) {
        toast.error(t(err.message));
      }
    },
    [PackageTypes.MORTGAGE_CALCULATOR_DETAIL]: () => {
    },
  };

  return successActions[type]
    ? successActions[type]()
    : console.warn("[DEBUG] No action defined for type:", type);
};

/**
 * Checks profile completeness and verification status.
 * If user has an active package, uses the original handlePackageCheck flow (upfront limit check).
 * If user has no package, navigates directly to the form where check happens at submit time.
 */
export const handlePropertyAddCheck = async (e, router, t = () => { }, isUser = false) => {
  const WebSetting = store?.getState()?.WebSetting;
  const User = store?.getState()?.User;
  const LanguageSettings = store?.getState()?.LanguageSettings;

  const userData = User?.data;
  const systemSettingsData = WebSetting?.data;
  const locale = LanguageSettings?.active_language || "en";

  if (!userData?.id) {
    return showSwal({
      title: t("plzLogFirst"),
      icon: "warning",
      showCancelButton: false,
      customClass: {
        confirmButton: "Swal-confirm-buttons",
        cancelButton: "Swal-cancel-buttons",
      },
      confirmButtonText: t("ok"),
      t,
    });
  }


  // Profile completeness check
  const isProfileComplete = isUser ? [
    "name",
    "email",
    "mobile",
    "profile",
    "address",
  ].every((key) => userData?.[key]) : [
    "agent_name",
    "agent_email",
    "agent_mobile",
    "agent_profile_photo",
    "agent_address",
  ].every((key) => userData?.agent_profile?.[key]);

  if (!isProfileComplete) {
    return showSwal({
      icon: "error",
      title: t("oops"),
      text: t("youHaveNotCompleteProfile"),
      onConfirm: () => router.push(`/${isUser ? "user" : "agent"}/profile?lang=${locale}`),
      t,
    });
  }

  // Verification check
  const needToVerify = isUser ? systemSettingsData?.verification_required_for_user : systemSettingsData?.verification_required_for_agent;
  const verificationStatus = isUser ? userData?.user_verification_status?.toLowerCase() : userData?.agent_verification_status?.toLowerCase();

  if (needToVerify && verificationStatus !== "approved") {
    const statusMessages = {
      pending: {
        icon: "warning",
        title: t("verifyPendingTitle"),
        text: t("verifyPendingDesc"),
        t,
      },
      rejected: {
        icon: "error",
        title: t("verifyFailTitle"),
        text: t("verifyFailDesc"),
        onConfirm: () => router.push(`/${isUser ? "user/profile" : "agent/dashboard"}?lang=${locale}`),
        t,
      },
      not_applied: {
        icon: "error",
        title: t("oops"),
        text: t("youAreNotVerifiedAgent"),
        onConfirm: () => router.push(`/${isUser ? "user" : "agent"}/verification-form?lang=${locale}`),
        t,
      },
    };

    return showSwal(
      statusMessages[verificationStatus] || statusMessages.default,
    );
  }

  const isPackageAvailable = await checkPackageAvailable(PackageTypes.PROPERTY_LIST);
  if (!isPackageAvailable) {
    return showSwal({
      icon: "error",
      title: t("oops"),
      text: t(getLimitErrorMessageKey(PackageTypes.PROPERTY_LIST)),
      isPremiumCheck: true,
      router,
      t,
      isUser,
    });
  }
  router.push(`/${isUser ? "user" : "agent"}/add-property?lang=${locale}`);
};

/**
 * Checks profile completeness and verification status for project.
 * Navigates directly to the form where package check happens at submit time.
 */
export const handleProjectAddCheck = async (e, router, t = () => { }, isUser = false) => {
  const WebSetting = store?.getState()?.WebSetting;
  const User = store?.getState()?.User;
  const LanguageSettings = store?.getState()?.LanguageSettings;

  const userData = User?.data;
  const systemSettingsData = WebSetting?.data;
  const locale = LanguageSettings?.active_language || "en";

  if (!userData?.id) {
    return showSwal({
      title: t("plzLogFirst"),
      icon: "warning",
      showCancelButton: false,
      customClass: {
        confirmButton: "Swal-confirm-buttons",
        cancelButton: "Swal-cancel-buttons",
      },
      confirmButtonText: t("ok"),
      t,
    });
  }


  // Profile completeness check
  const isProfileComplete = isUser ? [
    "name",
    "email",
    "mobile",
    "profile",
    "address",
  ].every((key) => userData?.[key]) : [
    "agent_name",
    "agent_email",
    "agent_mobile",
    "agent_profile_photo",
    "agent_address",
  ].every((key) => userData?.agent_profile?.[key]);

  if (!isProfileComplete) {
    return showSwal({
      icon: "error",
      title: t("oops"),
      text: t("youHaveNotCompleteProfile"),
      onConfirm: () => router.push(`/${isUser ? "user" : "agent"}/profile?lang=${locale}`),
      t,
    });
  }

  // Verification check
  const needToVerify = isUser ? systemSettingsData?.verification_required_for_user : systemSettingsData?.verification_required_for_agent;
  const verificationStatus = isUser ? userData?.user_verification_status?.toLowerCase() : userData?.agent_verification_status?.toLowerCase();

  if (needToVerify && verificationStatus !== "approved") {
    const statusMessages = {
      pending: {
        icon: "warning",
        title: t("verifyPendingTitle"),
        text: t("verifyPendingDesc"),
        t,
      },
      failed: {
        icon: "error",
        title: t("verifyFailTitle"),
        text: t("verifyFailDesc"),
        onConfirm: () => router.push(`/${isUser ? "user/profile" : "agent/dashboard"}?lang=${locale}`),
        t,
      },
      default: {
        icon: "error",
        title: t("oops"),
        text: t("youAreNotVerifiedAgent"),
        onConfirm: () => router.push(`/${isUser ? "user" : "agent"}/verification-form?lang=${locale}`),
        t,
      },
    };

    return showSwal(
      statusMessages[verificationStatus] || statusMessages.default,
    );
  }

  const isPackageAvailable = await checkPackageAvailable(PackageTypes.PROJECT_LIST);
  if (!isPackageAvailable) {
    return showSwal({
      icon: "error",
      title: t("oops"),
      text: t(getLimitErrorMessageKey(PackageTypes.PROJECT_LIST)),
      isPremiumCheck: true,
      router,
      t,
      isUser,
    });
  }
  router.push(`/${isUser ? "user" : "agent"}/add-project?lang=${locale}`);
};

export const isDemoMode = () => {
  const WebSetting = store?.getState()?.WebSetting;
  return WebSetting?.data?.demo_mode;
};

/**
 * Extracts address components from a Google Place object
 * @param {Object} place - Google Place object
 * @returns {Object} Object containing city, state, country and formatted address
 */
export const extractAddressComponents = (place = {}) => {
  const components = place.address_components || [];
  const findComponent = (...typesToFind) => {
    const match = components.find((component) =>
      typesToFind.some((type) => component.types?.includes(type))
    );
    return match?.long_name || "";
  };

  return {
    city: findComponent("locality", "postal_town", "administrative_area_level_3", "administrative_area_level_2"),
    state: findComponent("administrative_area_level_1"),
    country: findComponent("country"),
    formattedAddress: place.formatted_address || place.name || "",
  };
};

/** Label for header / mobile location chip from persisted Redux location */
export const getLocationDisplayLabel = (location = {}) => {
  if (!location?.isLocationSet) return "";

  const parts = [location.city, location.state, location.country].filter(
    (part) => typeof part === "string" && part.trim() !== ""
  );
  if (parts.length > 0) {
    return [...new Set(parts)].join(", ");
  }

  return (location.formatted_address || "").trim();
};

/**
 * Creates a standardized rejection tooltip for displaying rejection reasons
 * @param {string} reason - The rejection reason text
 * @param {string} title - The title of the tooltip (defaults to "Rejection Reason")
 * @param {string} side - Tooltip position (left, right, top, bottom)
 * @param {function} t - Translation function
 * @returns {JSX.Element} - Returns a tooltip component
 */
export const RejectionTooltip = ({ reason, title, side = "left", t }) => {
  if (!reason) return null;

  return (
    <TooltipProvider>
      <Tooltip>
        <TooltipTrigger>
          <div className="flex h-5 w-5 cursor-pointer items-center justify-center rounded-full bg-[#ffdddd] text-[#DB3D26] transition-colors hover:bg-[#f9c6c6]">
            <span className="text-xs font-bold">?</span>
          </div>
        </TooltipTrigger>
        <TooltipContent
          side={side}
          align="center"
          className="z-50 max-w-lg rounded-lg border border-gray-200 bg-white p-3 shadow-lg"
        >
          <div className="flex flex-col gap-2">
            <h3 className="text-sm font-bold text-gray-800">
              {title || t("rejectionReason")}:
            </h3>
            <p className="break-words text-sm text-gray-600">{reason}</p>
          </div>
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  );
};
export const capitalizeFirstLetter = (string) => {
  // Handle null, undefined, or empty string cases
  if (!string || typeof string !== "string") {
    return "";
  }
  return string.charAt(0).toUpperCase() + string.slice(1);
};

export const renderStatusBadge = (status, t) => {
  switch (status?.toLowerCase()) {
    case "success":
    case "approved":
      return (
        <div className="rounded-md bg-[#83B8071F] px-4 py-2 font-medium capitalize text-[#83B807]">
          {t("success")}
        </div>
      );
    case "review":
      return (
        <div className="rounded-md bg-[#0186D81F] px-4 py-2 font-medium capitalize text-[#0186D8]">
          {t("review")}
        </div>
      );
    case "pending":
      return (
        <div className="rounded-md bg-[#DB93051F] px-4 py-2 font-medium capitalize text-[#DB9305]">
          {t("pending")}
        </div>
      );
    case "rejected":
      return (
        <div className="rounded-md bg-[#DB3D261F] px-4 py-2 font-medium capitalize text-[#DB3D26]">
          {t("rejected")}
        </div>
      );
    case "failed":
      return (
        <div className="rounded-md bg-[#DB3D261F] px-4 py-2 font-medium capitalize text-[#DB3D26]">
          {t("failed")}
        </div>
      );
    default:
      return (
        <div className="rounded-md bg-[#808080] px-4 py-2 font-medium capitalize text-white">
          {status || "-"}
        </div>
      );
  }
};

// export const generateSlug = (text) => {
//   return text
//     .toLowerCase()
//     .replace(/[^a-z0-9\s-]/g, "") // Remove invalid characters
//     .replace(/\s+/g, "-") // Replace spaces with hyphens
//     .replace(/-+/g, "-"); // Replace multiple hyphens with a single hyphen
//   // .replace(/^-+|-+$/g, ''); // Remove leading or trailing hyphens
// };

export const generateSlug = (text) => {
  return text
    .toString()
    .normalize("NFC") // Keep characters + combining marks together
    .toLowerCase()
    .trim()
    .replace(/[^\p{L}\p{N}\p{M}\s-]/gu, "") // Keep letters, numbers, marks, spaces, hyphens
    .replace(/\s+/g, "-") // Replace spaces with hyphens
    .replace(/-+/g, "-") // Collapse multiple hyphens
    .replace(/^-+|-+$/g, ""); // Trim leading/trailing hyphens
};


export const getFormattedDate = (date, t) => {
  const dateObj = new Date(date);
  const day = dateObj.getDate();
  const month = dateObj.toLocaleString("en-US", { month: "long" });
  return `${t(month?.toLowerCase())} ${day}, ${dateObj.getFullYear()}`; // e.g., January 1, 2025
};

export const getBgColorConfig = (type) => {
  const bgColorConfig = {
    premium_properties_section: "primaryBgLight",
    featured_projects_section: "primaryBgLight",
    featured_properties_section: "primaryBgLight",
    properties_by_cities_section: "bg-white",
    similar_projects_section: "bg-transparent",
  };
  return bgColorConfig[type] || "bg-white";
};

export const formatTimeDifference = (timestamp) => {
  const now = new Date();
  const messageTime = new Date(timestamp);

  const diffInSeconds = Math.floor((now - messageTime) / 1000);

  if (diffInSeconds < 1) {
    return "1s ago";
  } else if (diffInSeconds < 60) {
    return `${diffInSeconds}s ago`;
  } else if (diffInSeconds < 3600) {
    return `${Math.floor(diffInSeconds / 60)}m ago`;
  } else if (diffInSeconds < 86400) {
    return `${Math.floor(diffInSeconds / 3600)}h ago`;
  } else {
    return messageTime.toLocaleTimeString([], {
      hour: "numeric",
      minute: "2-digit",
      hour12: true,
    });
  }
};

/**
 * Fetches current location data using browser geolocation API
 * Checks stored location first, then requests new geolocation if needed
 * Reverse geocodes coordinates to get address components
 * @returns {Promise<Object>} Location object with latitude, longitude, address components, and radius
 * @returns {number} locationData.latitude - Latitude coordinate
 * @returns {number} locationData.longitude - Longitude coordinate
 * @returns {string} locationData.formatted_address - Full formatted address
 * @returns {string} locationData.city - City name
 * @returns {string} locationData.state - State/province name
 * @returns {string} locationData.country - Country name
 * @returns {number} locationData.radius - Search radius (default: 1)
 * @throws {Error} Throws error if geolocation not supported, permission denied, or API fails
 */export const requestAccurateBrowserPosition = async ({
  timeout = 25000,
  maxAccuracy = 5000,
  settleTime = 12000,
  allowLowAccuracy = false,
} = {}) => {
  if (!("geolocation" in navigator)) {
    throw new Error(t("geolocationNotSupported"));
  }

  if ("permissions" in navigator) {
    const status = await navigator.permissions.query({ name: "geolocation" });
    if (status.state === "denied") {
      throw new Error(t("locationPermissionDenied"));
    }
  }

  const pickBetterPosition = (current, next) => {
    if (!next?.coords) return current;
    if (!current?.coords) return next;
    const currentAccuracy = Number(current.coords.accuracy || Number.MAX_SAFE_INTEGER);
    const nextAccuracy = Number(next.coords.accuracy || Number.MAX_SAFE_INTEGER);
    return nextAccuracy < currentAccuracy ? next : current;
  };

  const markLowAccuracy = (position, accuracy) => ({
    coords: position.coords,
    timestamp: position.timestamp,
    lowAccuracy: true,
    accuracy,
  });

  const rejectLowAccuracy = (position) => {
    const accuracy = Number(position?.coords?.accuracy || 0);
    if (accuracy && accuracy > maxAccuracy) {
      if (allowLowAccuracy) {
        return markLowAccuracy(position, accuracy);
      }
      const error = new Error("Browser returned a low accuracy location. Please search the address or drag the map pin.");
      error.code = "LOW_ACCURACY_LOCATION";
      error.accuracy = accuracy;
      throw error;
    }
    return position;
  };

  const getOnce = (options) => new Promise((resolve, reject) => {
    navigator.geolocation.getCurrentPosition(resolve, reject, options);
  });

  let bestPosition = null;

  try {
    bestPosition = await getOnce({
      enableHighAccuracy: true,
      timeout: Math.min(timeout, 12000),
      maximumAge: 0,
    });
    if (!bestPosition?.coords?.accuracy || Number(bestPosition.coords.accuracy) <= maxAccuracy) {
      return bestPosition;
    }
  } catch (error) {
    if (error?.code === 1) throw error;
  }

  const watchedPosition = await new Promise((resolve) => {
    let watchId = null;
    let finished = false;
    const finish = (position) => {
      if (finished) return;
      finished = true;
      if (watchId !== null) navigator.geolocation.clearWatch(watchId);
      resolve(position || bestPosition);
    };

    const timer = setTimeout(() => finish(bestPosition), Math.min(settleTime, timeout));

    watchId = navigator.geolocation.watchPosition(
      (position) => {
        bestPosition = pickBetterPosition(bestPosition, position);
        const accuracy = Number(bestPosition?.coords?.accuracy || 0);
        if (accuracy && accuracy <= maxAccuracy) {
          clearTimeout(timer);
          finish(bestPosition);
        }
      },
      (error) => {
        if (error?.code === 1) {
          clearTimeout(timer);
          finish(null);
        }
      },
      {
        enableHighAccuracy: true,
        timeout,
        maximumAge: 0,
      }
    );
  });

  if (!watchedPosition?.coords) {
    const fallbackPosition = await getOnce({
      enableHighAccuracy: false,
      timeout: Math.min(timeout, 10000),
      maximumAge: 15000,
    });
    bestPosition = pickBetterPosition(bestPosition, fallbackPosition);
  } else {
    bestPosition = pickBetterPosition(bestPosition, watchedPosition);
  }

  return rejectLowAccuracy(bestPosition);
};


export const getCurrentLocationData = async () => {
  // 1️⃣ Check if location data already exists in Redux
  const existingLocation = store.getState()?.location;

  if (existingLocation?.latitude && existingLocation?.longitude) {
    return;
  }

  // 2️⃣ Check browser support
  if (!("geolocation" in navigator)) {
    throw new Error(t("geolocationNotSupported"));
  }

  // 3️⃣ Check permission state explicitly
  if ("permissions" in navigator) {
    const status = await navigator.permissions.query({
      name: "geolocation",
    });

    if (status.state === "denied") {
      throw new Error(t("locationPermissionDenied"));
    }
  }

  // 4️⃣ Get a fresh browser position. Reject low-accuracy IP/network fallbacks.
  const position = await requestAccurateBrowserPosition({ timeout: 20000, maxAccuracy: 5000 });

  const { latitude, longitude } = position.coords;

  // 5️⃣ Reverse geocode
  const response = await getMapDetailsApi({
    latitude: latitude.toString(),
    longitude: longitude.toString(),
    place_id: "",
  });

  if (response?.error || !response?.data?.result) {
    throw new Error(t("locationNotFound"));
  }

  const data = response.data.result;
  const { city, state, country, formattedAddress } =
    extractAddressComponents(data);

  const locationData = {
    latitude,
    longitude,
    formatted_address: formattedAddress,
    city,
    state,
    country,
    radius: 1,
  };

  // 6️⃣ Store once — single source of truth
  store.dispatch(
    setLocationAction({
      ...locationData,
    })
  );

  return locationData;
};


export const showLoginSwal = (
  title = "oops",
  text = "plzLoginFirst",
  onConfirm = () => { },
  t = () => { }
) => {
  Swal.fire({
    title: t(title),
    text: t(text),
    icon: "warning",
    allowOutsideClick: true,
    showCancelButton: false,
    customClass: {
      confirmButton: "Swal-confirm-buttons",
      cancelButton: "Swal-cancel-buttons",
    },
    confirmButtonText: t("ok"),
  }).then((result) => {
    if (result.isConfirmed) {
      onConfirm();
    }
  });
};

export const getPostedSince = (time) => {
  switch (time) {
    case "anytime":
      return "";
    case "lastWeek":
      return "0";
    case "yesterday":
      return "1";
    case "lastMonth":
      return "2";
    case "last3Months":
      return "3";
    case "last6Months":
      return "4";
    default:
      return "";
  }
};

export const getPostedSinceString = (code) => {
  switch (code?.toString()) {
    case "0":
      return "lastWeek";
    case "1":
      return "yesterday";
    case "2":
      return "lastMonth";
    case "3":
      return "last3Months";
    case "4":
      return "last6Months";
    default:
      return code || "";
  }
};

export const isRTL = () => {
  const language = store.getState().LanguageSettings?.current_language;
  return language?.rtl === 1;
};

export const extractSchemaMarkup = (markup) => {
  try {
    return JSON.parse(markup);
  } catch (error) {
    console.error("Error parsing schema markup:", error);
    return null;
  }
};

export const getDefaultSchemaMarkup = () => {
  return {
    "@context": "https://schema.org",
    "@type": "Organization",
    "@id": `${process.env.NEXT_PUBLIC_WEB_URL}/#organization`,
    name: process.env.NEXT_PUBLIC_APPLICATION_NAME,
    url: process.env.NEXT_PUBLIC_WEB_URL,
    logo: {
      "@type": "ImageObject",
      url: `${process.env.NEXT_PUBLIC_WEB_URL}/favicon.ico`,
      width: "512",
      height: "512",
    },
    description: process.env.NEXT_PUBLIC_META_DESCRIPTION,
    sameAs: [
      // Add your social media URLs here if available
    ],
    potentialAction: {
      "@type": "SearchAction",
      target: {
        "@type": "EntryPoint",
        urlTemplate: `${process.env.NEXT_PUBLIC_WEB_URL}/search/{search_term_string}`,
      },
      "query-input": "required name=search_term_string",
    },
    areaServed: {
      "@type": "Country",
      name: "Global",
    },
    mainEntityOfPage: {
      "@type": "WebPage",
      "@id": `${process.env.NEXT_PUBLIC_WEB_URL}/#webpage`,
    },
    publisher: {
      "@type": "Organization",
      name: process.env.NEXT_PUBLIC_APPLICATION_NAME,
    },
    datePublished: new Date().toISOString(),
  };
};

export const getDisplayValueForOption = (elem) => {
  // If value is an array, join its elements with a comma
  if (Array.isArray(elem?.value) && Array.isArray(elem?.translated_value)) {
    return truncate(elem?.translated_value.join(", "), 30);
  }

  // If value is a string containing a comma, split and translate each part
  if (typeof elem?.value === "string" && elem.value.includes(",")) {
    return truncate(elem.value
      .split(",")
      .map(
        (value) =>
          elem?.translated_option_value?.find(
            (option) => option.value === value
          )?.translated || value
      )
      .join(", "), 30);
  }

  // If translated_option_value exists, find the translation for the value
  if (elem?.translated_option_value) {
    const found = elem.translated_option_value.find(
      (option) => option.value === elem.value
    );
    return found ? truncate(found.translated, 26) : truncate(elem.value, 26);
  }

  // Otherwise, just return the value
  return truncate(elem?.value, 30);
};

/**
 * Validates social media URLs for different platforms
 * @param {string} url - The URL to validate
 * @param {string} platform - The social media platform (facebook, instagram, youtube, twitter)
 * @returns {Object} Object containing isValid boolean and error message if invalid
 */
export const validateSocialMediaUrl = (url, platform) => {
  return {
    isValid: isValidUrl(url, platform),
    error: isValidUrl(url, platform) ? null : t("invalidSocialMediaUrl")
  };
};

/**
 * Validates all social media URLs in a form data object
 * @param {Object} formData - Object containing social media URLs
 * @returns {Object} Object containing validation results for each platform
 */
export const validateAllSocialMediaUrls = (formData) => {
  const platformMappings = [
    { key: 'facebook_id', platform: 'facebook' },
    { key: 'instagram_id', platform: 'instagram' },
    { key: 'youtube_id', platform: 'youtube' },
    { key: 'twiiter_id', platform: 'twitter' } // Note: handling the typo in field name
  ];

  const hasErrors = platformMappings.some(({ key, platform }) => {
    if (formData[key]) {
      return !isValidUrl(formData[key], platform);
    }
    return false;
  });

  return {
    isValid: !hasErrors
  };
};


// Utility function to validate URLs for specific platforms
// Returns true if the URL is valid for the specified platform, false otherwise
export const isValidUrl = (url, platform) => {
  if (!url) return true; // Allow empty (optional)
  try {
    const parsed = new URL(url);
    switch (platform) {
      case "facebook":
        return /^(www\.)?facebook\.com/.test(parsed.hostname);
      case "instagram":
        return /^(www\.)?instagram\.com/.test(parsed.hostname);
      case "youtube":
        return /^(www\.)?youtube\.com|youtu\.be/.test(parsed.hostname);
      case "twitter":
        return /^(www\.)?twitter\.com|x\.com/.test(parsed.hostname);
      default:
        return false;
    }
  } catch {
    return false;
  }
};

/**
 * Generates a base64-encoded URL parameter string from filter criteria
 * @param { Object } filters - Filter criteria object
 * @param { Object } options - Additional options object containing context variables
 * @returns { string } Base64 - encoded URL parameter string
**/
export const generateBase64FilterUrl = (filters, options = {}) => {
  const {
    isCityPage = false,
    citySlug = '',
    sortBy = '',
    isCategoryPage = false,
    categorySlug = ''
  } = options;

  const parameters = filters?.amenities

  // Build location object using filters and Redux location data
  const location = {
    country: filters.country,
    state: filters.state,
    city: isCityPage ? citySlug || filters.city || "" : filters.city || "",
    place_id: "", // This might need to be stored in Redux or passed from somewhere else
    latitude: filters.latitude,
    longitude: filters.longitude,
    range: filters.range,
    state_id: filters.state_id,
    city_id: filters.city_id,
    area_id: filters.area_id,
    area_name: filters.area_name,
    detected_area_name: filters.detected_area_name,
    sub_area_id: filters.sub_area_id,
    sub_area_name: filters.sub_area_name,
    detected_sub_area_name: filters.detected_sub_area_name
  };    // Build price object
  const price = {
    min_price: filters.min_price ? parseInt(filters.min_price) : 0,
    max_price: filters.max_price ? parseInt(filters.max_price) : 0
  };

  // Build flags object
  const flags = {
    promoted: filters.promoted === "1" || filters.promoted === true ? 1 : 0,
    get_all_premium_properties: filters.is_premium === "1" || filters.is_premium === true ? 1 : 0,
    most_views: filters?.most_viewed === "1" ? 1 : 0,
    most_liked: filters?.most_liked === "1" ? 1 : 0
  };

  // Handle property_type conversion
  let propertyType = "";
  if (filters.property_type === "Sell") {
    propertyType = 0;
  } else if (filters.property_type === "Rent") {
    propertyType = 1;
  }

  const params = {
    property_type: propertyType,
    category_id: filters.category_id ? parseInt(filters.category_id) : "",
    category_slug_id: isCategoryPage ? categorySlug || filters.category_slug_id || "" : filters.category_slug_id || "",
    parameters: parameters,
    nearby_places: filters?.nearbyPlaces,
    location: location,
    price: price,
    posted_since: parseInt(getPostedSince(filters.posted_since)) || 0,
    search: filters.keywords || "",
    flags: flags,
  };
  return btoa(JSON.stringify(params));
}

/**
 * Decodes a base64-encoded filter URL parameter string back to filter criteria object
 * @param { string } encodedString - Base64 encoded filter string
 * @returns { Object } Decoded filter criteria object
**/
export const decodeBase64FilterUrl = (encodedString) => {
  try {
    const decodedString = atob(encodedString);
    return JSON.parse(decodedString);
  } catch (error) {
    console.error("Error decoding base64 filter string:", error);
    return {};
  }
}


export const getLocationLatLngFilter = (is_premium = "", promoted = "", lang = "") => {
  const locationData = store.getState()?.location || {};
  const filters = {
    location: {
      latitude: locationData?.latitude || "",
      longitude: locationData?.longitude || "",
      range: locationData?.radius,
    },
    flags: {
      get_all_premium_properties: is_premium === "1" || is_premium === true ? 1 : 0,
      promoted: promoted === "1" || promoted === true ? 1 : 0
    }
  }
  return `?filters=${btoa(JSON.stringify(filters))}&lang=${lang}`;
}

export const getProjectFilters = (lang = "", extra = {}) => {
  const locationData = store.getState()?.location || {};
  const filters = {
    location: {
      latitude: locationData?.latitude || "",
      longitude: locationData?.longitude || "",
      range: locationData?.radius,
    },
    ...extra,
  };
  return `?filters=${encodeURIComponent(btoa(JSON.stringify(filters)))}&lang=${lang}`;
}

export const getCategoryLink = (category, lang) => {
  const location = store.getState()?.location || {};
  const filters = {
    location: {
      latitude: location?.latitude || "",
      longitude: location?.longitude || "",
      range: location?.radius,
    }
  };
  return `/properties/category/${category?.slug_id}?filters=${btoa(JSON.stringify(filters))}&lang=${lang}`;
}

export const getCamelCaseSlug = (slug) => {
  return slug.replace(/-([a-z])/g, (_, c) => c.toUpperCase());
}

/**
 * Builds a structured filter object for projects API and URL encoding
 * @param {Object} inputFilters - The raw filters from state or URL
 * @param {Object} options - Additional data like location info and slug flags
 * @returns {Object} An object containing the structured filters object and its base64 encoded string
 */
export const buildProjectFilters = (inputFilters, options = {}) => {
  const {
    latitude = "",
    longitude = "",
    range = "",
    slugFlags = { promoted: 0, most_viewed: 0, most_liked: 0 }
  } = options;

  const filtersObj = {};

  if (inputFilters?.keywords) filtersObj.search = inputFilters.keywords;
  if (inputFilters?.category_id) filtersObj.category_id = inputFilters.category_id;

  // Convert project_type: "upcoming" → 0, "under_construction" → 1, numeric passthrough
  if (inputFilters?.project_type !== undefined && inputFilters?.project_type !== "" && inputFilters?.project_type !== null) {
    const pt = inputFilters.project_type;
    if (pt === "upcoming") filtersObj.project_type = 0;
    else if (pt === "under_construction") filtersObj.project_type = 1;
    else if (pt === 0 || pt === 1 || pt === "0" || pt === "1") filtersObj.project_type = parseInt(pt);
  }

  const locationObj = {};
  if (inputFilters?.city) locationObj.city = inputFilters.city;
  if (inputFilters?.state) locationObj.state = inputFilters.state;
  if (inputFilters?.country) locationObj.country = inputFilters.country;
  if (latitude) locationObj.latitude = latitude;
  if (longitude) locationObj.longitude = longitude;
  if (range) locationObj.range = range;

  if (Object.keys(locationObj).length > 0) {
    filtersObj.location = locationObj;
  }

  if (inputFilters?.posted_since && inputFilters.posted_since !== "") {
    filtersObj.posted_since = parseInt(getPostedSince(inputFilters.posted_since));
    // for URL context it might not need getPostedSince but API needs it. 
    // Wait, the hook uses it differently for URL vs API.
    // We'll return both raw posted_since for URL and parsed for API
  }

  const flagsObj = {};

  const promotedFlag = slugFlags.promoted || (inputFilters?.promoted === "1" ? 1 : 0) || (inputFilters?.promoted ? 1 : 0);
  if (promotedFlag) flagsObj.promoted = 1;

  if (inputFilters?.is_premium === "1" || inputFilters?.is_premium) flagsObj.get_all_premium_properties = 1;

  const mostViewsFlag = slugFlags.most_viewed || (inputFilters?.most_viewed === "1" ? 1 : 0) || (inputFilters?.most_viewed ? 1 : 0);
  if (mostViewsFlag) flagsObj.most_views = 1;

  const mostLikedFlag = slugFlags.most_liked || (inputFilters?.most_liked === "1" ? 1 : 0) || (inputFilters?.most_liked ? 1 : 0);
  if (mostLikedFlag) flagsObj.most_liked = 1;

  if (Object.keys(flagsObj).length > 0) {
    filtersObj.flags = flagsObj;
  }

  const encodedFilters = Object.keys(filtersObj).length > 0
    ? btoa(JSON.stringify(filtersObj))
    : "";

  const encodedUrlFilters = Object.keys(filtersObj).length > 0
    ? encodeURIComponent(encodedFilters)
    : "";

  return { filtersObj, encodedFilters, encodedUrlFilters };
};