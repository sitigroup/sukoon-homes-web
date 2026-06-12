"use client";

import { tv, cn } from "@/plugins/trust-verification/ui/trustVerificationTheme";
import VerificationDataUsageNotice from "@/plugins/trust-verification/ui/VerificationDataUsageNotice";

const LABELS = {
  id_front: "ID front (Aadhaar / PAN)",
  id_back: "ID back",
  pan: "PAN card",
  address_proof: "Address proof",
  selfie: "Selfie with ID",
};

export default function VerificationDocumentFields({
  enabled = false,
  requiredTypes = [],
  labels = LABELS,
  files = {},
  onChange,
  variant = "default",
}) {
  if (!enabled) {
    return null;
  }

  const premium = variant === "premium";
  const types = Object.keys(labels);

  return (
    <div
      className={cn(
        "mt-4 rounded-[12px] border p-4",
        premium ? "border-[#E5E7EB] bg-[#F8F9FA]" : "newBorderColor bg-gray-50"
      )}
    >
      <h4 className={cn("text-sm font-semibold", premium ? "text-[#B89A4A]" : "brandColor")}>
        Supporting documents
      </h4>
      <p className={cn("mt-1 text-xs", premium ? "text-[#6B7280]" : "leadColor")}>
        Upload clear photos or PDFs (max 5 MB each).{" "}
        {requiredTypes.length ? "Required types are marked." : "Optional but speeds up verification."}
      </p>
      <VerificationDataUsageNotice className="mt-3" compact={!premium} />
      <div className="mt-3 space-y-3">
        {types.map((type) => {
          const required = requiredTypes.includes(type);
          const file = files[type];
          return (
            <label key={type} className="block text-sm">
              <span className={cn("font-medium", premium ? "text-[#111827]" : "brandColor")}>
                {labels[type] || type}
                {required ? " *" : ""}
              </span>
              <input
                type="file"
                accept="image/jpeg,image/png,image/webp,application/pdf"
                className={cn(
                  "mt-1 w-full text-xs",
                  premium ? tv.input : "newBorderColor rounded-lg border bg-white px-3 py-2"
                )}
                onChange={(e) => onChange(type, e.target.files?.[0] || null)}
              />
              {file && (
                <span className={cn("mt-1 block text-xs", premium ? "text-emerald-600" : "text-green-700")}>
                  Selected: {file.name}
                </span>
              )}
            </label>
          );
        })}
      </div>
    </div>
  );
}
