"use client";

import { EMPTY_POLICE_FORM } from "../trustVerificationPoliceUtils";
import { tv, cn } from "./trustVerificationTheme";

export default function PoliceVerificationDetailsForm({
  value = EMPTY_POLICE_FORM,
  onChange,
  className = "",
  title = "Police verification",
  description = "If you have already applied on the Rajasthan Police citizen portal, share your station details and reference number.",
}) {
  const form = { ...EMPTY_POLICE_FORM, ...value };

  const setField = (field, fieldValue) => {
    onChange?.({ ...form, [field]: fieldValue });
  };

  return (
    <div className={cn("space-y-3", className)}>
      <div>
        <p className={cn(tv.heading, "text-base")}>{title}</p>
        <p className={cn(tv.subheading, "mt-1 text-sm")}>{description}</p>
      </div>

      <div className="grid gap-3 sm:grid-cols-2">
        <label className="block text-sm sm:col-span-2">
          <span className="mb-1 block font-medium text-[#374151]">Police station name</span>
          <input
            type="text"
            className={tv.input}
            value={form.police_station_name}
            maxLength={160}
            onChange={(e) => setField("police_station_name", e.target.value)}
            placeholder="e.g. Barmer Kotwali"
          />
        </label>
        <label className="block text-sm">
          <span className="mb-1 block font-medium text-[#374151]">City</span>
          <input
            type="text"
            className={tv.input}
            value={form.city}
            maxLength={80}
            onChange={(e) => setField("city", e.target.value)}
          />
        </label>
        <label className="block text-sm">
          <span className="mb-1 block font-medium text-[#374151]">District</span>
          <input
            type="text"
            className={tv.input}
            value={form.district}
            maxLength={80}
            onChange={(e) => setField("district", e.target.value)}
          />
        </label>
        <label className="block text-sm">
          <span className="mb-1 block font-medium text-[#374151]">Applicant mobile</span>
          <input
            type="tel"
            className={tv.input}
            value={form.applicant_mobile}
            maxLength={10}
            onChange={(e) => setField("applicant_mobile", e.target.value.replace(/\D/g, "").slice(0, 10))}
            placeholder="10-digit mobile used on police portal"
          />
        </label>
        <label className="block text-sm">
          <span className="mb-1 block font-medium text-[#374151]">Reference number (if available)</span>
          <input
            type="text"
            className={tv.input}
            value={form.reference_number}
            maxLength={64}
            onChange={(e) => setField("reference_number", e.target.value)}
            placeholder="Rajasthan Police reference no."
          />
        </label>
        <label className="block text-sm sm:col-span-2">
          <span className="mb-1 block font-medium text-[#374151]">Notes (optional)</span>
          <textarea
            className={cn(tv.input, "min-h-[72px]")}
            rows={2}
            value={form.customer_notes}
            maxLength={2000}
            onChange={(e) => setField("customer_notes", e.target.value)}
            placeholder="Any details for our verification team"
          />
        </label>
      </div>
    </div>
  );
}
