/**
 * Phone helpers for react-phone-input-2 (India: flag shows +91, input = 10 digits only).
 */

export const DEFAULT_PHONE_COUNTRY = (
  process.env.NEXT_PUBLIC_DEFAULT_COUNTRY || "in"
).toLowerCase();

export const INDIA_DIAL_CODE = "91";

export function isIndiaDefaultCountry() {
  return DEFAULT_PHONE_COUNTRY === "in";
}

export function digitsOnly(value) {
  return String(value ?? "").replace(/\D/g, "");
}

/** National 10-digit mobile for India (strips leading 91 from paste). */
export function cleanIndiaNationalMobile(input) {
  let digits = digitsOnly(input);
  if (digits.startsWith(INDIA_DIAL_CODE) && digits.length > 10) {
    digits = digits.slice(INDIA_DIAL_CODE.length);
  }
  if (digits.startsWith("0") && digits.length > 10) {
    digits = digits.replace(/^0+/, "");
  }
  return digits.slice(0, 10);
}

/** National number without leading dial code. */
export function stripDialCodeDigits(phone, dialCode) {
  let digits = digitsOnly(phone);
  const code = digitsOnly(dialCode);
  if (code && digits.startsWith(code) && digits.length > code.length) {
    return digits.slice(code.length);
  }
  return digits;
}

/** Value for PhoneInput when disableCountryCode is true (national digits only). */
export function toPhoneInputNationalValue(phone, dialCode) {
  if (isIndiaDefaultCountry()) {
    return cleanIndiaNationalMobile(stripDialCodeDigits(phone, dialCode || INDIA_DIAL_CODE));
  }
  const code = digitsOnly(dialCode);
  const national = stripDialCodeDigits(phone, code);
  if (!code) return national;
  return `${code}${national}`;
}

/** @deprecated use toPhoneInputNationalValue for India */
export function toPhoneInputValue(phone, dialCode) {
  return toPhoneInputNationalValue(phone, dialCode);
}

export function normalizePhoneInputChange(phone, data) {
  const dialCode = data?.dialCode ?? (isIndiaDefaultCountry() ? INDIA_DIAL_CODE : "");
  const code = digitsOnly(dialCode);

  if (isIndiaDefaultCountry()) {
    const national = cleanIndiaNationalMobile(phone);
    return { number: national, countryCode: code || INDIA_DIAL_CODE };
  }

  const number = code ? `${code}${stripDialCodeDigits(phone, code)}` : digitsOnly(phone);
  return { number, countryCode: dialCode };
}

/** API payload: national mobile + separate country_code. */
export function phonePartsForApi(phone, dialCode) {
  const code = digitsOnly(dialCode || (isIndiaDefaultCountry() ? INDIA_DIAL_CODE : ""));
  const mobile = isIndiaDefaultCountry()
    ? cleanIndiaNationalMobile(phone)
    : stripDialCodeDigits(phone, code);
  return { mobile, country_code: code };
}

/** E.164-ish string for OTP / Firebase. */
export function toFullPhoneDigits(phone, dialCode) {
  const { mobile, country_code } = phonePartsForApi(phone, dialCode);
  return `${country_code}${mobile}`;
}
