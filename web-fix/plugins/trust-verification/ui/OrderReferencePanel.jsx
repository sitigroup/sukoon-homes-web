"use client";

import { useState } from "react";
import toast from "react-hot-toast";
import { submitTrustVerificationReferences } from "../trustVerificationApi";
import ReferenceDetailsForm from "./ReferenceDetailsForm";
import { referenceStatusLabel, referenceTypeLabel } from "../trustVerificationReferenceUtils";
import { tv, cn } from "./trustVerificationTheme";

function isValidReferenceRow(row) {
  return row?.name?.trim() && /^\d{10}$/.test(String(row?.mobile || "").replace(/\D/g, ""));
}

export default function OrderReferencePanel({ order, onSaved, variant = "premium" }) {
  const [refs, setRefs] = useState([]);
  const [saving, setSaving] = useState(false);
  const premium = variant === "premium";

  if (!order?.reference_check_enabled) {
    return null;
  }

  const existing = order.references || [];
  const progress = order.reference_progress;
  const canAdd = ["submitted", "in_progress"].includes(String(order.status || ""));

  const handleSubmit = async () => {
    const payload = refs.filter(isValidReferenceRow);
    if (!payload.length) {
      toast.error("Add at least one reference with name and 10-digit mobile");
      return;
    }
    setSaving(true);
    try {
      await submitTrustVerificationReferences(order.id, payload);
      toast.success("Reference contacts saved");
      setRefs([]);
      onSaved?.();
    } catch (e) {
      toast.error(e?.response?.data?.message || "Failed to save references");
    } finally {
      setSaving(false);
    }
  };

  return (
    <div
      className={cn(
        "mt-4 rounded-[12px] border border-dashed p-3",
        premium ? "border-[#E5E7EB] bg-[#F8F9FA]" : "newBorderColor bg-gray-50"
      )}
    >
      <p className={cn(tv.heading, "text-sm")}>Reference check</p>
      {progress?.summary ? (
        <p className={cn(tv.subheading, "mt-1 text-xs")}>{progress.summary}</p>
      ) : null}

      {existing.length > 0 ? (
        <ul className="mt-3 space-y-2">
          {existing.map((ref) => (
            <li
              key={ref.id}
              className="rounded-[10px] border border-[#E5E7EB] bg-white px-3 py-2 text-sm"
            >
              <div className="flex flex-wrap items-center justify-between gap-2">
                <span className="font-medium text-[#111827]">
                  {referenceTypeLabel(ref.reference_type)} · {ref.name}
                </span>
                <span className="rounded-full bg-[#F3F4F6] px-2 py-0.5 text-xs font-medium text-[#374151]">
                  {referenceStatusLabel(ref.status)}
                </span>
              </div>
              {ref.relation ? (
                <p className="mt-0.5 text-xs text-[#6B7280]">{ref.relation}</p>
              ) : null}
            </li>
          ))}
        </ul>
      ) : null}

      {canAdd ? (
        <div className="mt-4">
          <ReferenceDetailsForm value={refs} onChange={setRefs} />
          <button
            type="button"
            className={cn(tv.btnPrimary, "mt-3")}
            disabled={saving}
            onClick={handleSubmit}
          >
            {saving ? "Saving…" : "Save references"}
          </button>
        </div>
      ) : null}
    </div>
  );
}
