"use client";

import PhoneInput from "react-phone-input-2";
import {
  DEFAULT_PHONE_COUNTRY,
  INDIA_DIAL_CODE,
  cleanIndiaNationalMobile,
  isIndiaDefaultCountry,
  normalizePhoneInputChange,
  toPhoneInputNationalValue,
} from "@/utils/phoneFieldUtils";

function IndiaFlagIcon() {
  return (
    <span
      className="inline-flex h-4 w-6 shrink-0 overflow-hidden rounded-[2px] border border-[#E5E7EB] shadow-sm"
      aria-hidden="true"
    >
      <svg viewBox="0 0 900 600" className="h-full w-full" role="img" aria-label="India">
        <rect width="900" height="200" fill="#FF9933" />
        <rect y="200" width="900" height="200" fill="#FFFFFF" />
        <rect y="400" width="900" height="200" fill="#138808" />
        <circle cx="450" cy="300" r="60" fill="#000080" />
        <circle cx="450" cy="300" r="48" fill="#FFFFFF" />
        <circle cx="450" cy="300" r="40" fill="#000080" />
      </svg>
    </span>
  );
}

/**
 * India: fixed flag (no dropdown), input = 10 digits only — no +91 shown in the field.
 */
export default function IndiaPhoneInput({
  value,
  onChange,
  disabled = false,
  id = "phone",
  name = "phone",
  required = false,
  autoFocus = false,
  placeholder = "Enter 10-digit mobile number",
  containerClass = "w-full",
  inputClass = "primaryBackgroundBg h-14 w-full min-w-0 flex-1 border-0 p-2 text-sm outline-none sm:p-[10px] sm:text-base md:text-base",
  buttonClass = "",
  dropdownClass = "",
}) {
  const nationalValue = cleanIndiaNationalMobile(
    toPhoneInputNationalValue(value?.number, value?.countryCode),
  );

  const emitChange = (digits) => {
    onChange({
      number: cleanIndiaNationalMobile(digits),
      countryCode: INDIA_DIAL_CODE,
    });
  };

  if (isIndiaDefaultCountry()) {
    return (
      <div className={`india-mobile-field flex h-14 w-full overflow-hidden rounded-lg border newBorderColor ${containerClass}`}>
        <div
          className="primaryBackgroundBg flex shrink-0 items-center justify-center border-e newBorderColor px-3"
          title="India"
        >
          <IndiaFlagIcon />
        </div>
        <input
          type="tel"
          inputMode="numeric"
          autoComplete="tel-national"
          name={name}
          id={id}
          required={required}
          autoFocus={autoFocus}
          maxLength={10}
          pattern="[0-9]{10}"
          placeholder={placeholder}
          className={inputClass}
          value={nationalValue}
          disabled={disabled}
          onChange={(e) => emitChange(e.target.value)}
        />
      </div>
    );
  }

  const handleIntlChange = (phone, data) => {
    onChange(normalizePhoneInputChange(phone, data));
  };

  return (
    <PhoneInput
      country={DEFAULT_PHONE_COUNTRY}
      countryCodeEditable={false}
      enableSearch
      value={toPhoneInputNationalValue(value?.number, value?.countryCode)}
      onChange={handleIntlChange}
      disabled={disabled}
      inputProps={{ name, id, required, autoFocus, autoComplete: "tel" }}
      containerClass={containerClass}
      inputClass={`!primaryBackgroundBg !w-full !rounded-lg !h-14 !border !newBorderColor ${inputClass}`}
      buttonClass={`!primaryBackgroundBg !w-10 !h-14 !rounded-tl-lg !rounded-bl-lg !newBorderColor ${buttonClass}`}
      dropdownClass={`!primaryBackgroundBg ${dropdownClass}`}
    />
  );
}
