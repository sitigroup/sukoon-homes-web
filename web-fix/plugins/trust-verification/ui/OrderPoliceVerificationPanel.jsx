"use client";

import { useRef, useState } from "react";
import toast from "react-hot-toast";
import {
  submitTrustVerificationPoliceVerification,
  uploadTrustVerificationPoliceAcknowledgement,
  downloadTrustVerificationPoliceAcknowledgement,
  downloadTrustVerificationPoliceCertificate,
} from "../trustVerificationApi";
import PoliceVerificationDetailsForm from "./PoliceVerificationDetailsForm";
import {
  EMPTY_POLICE_FORM,
  isPoliceFormComplete,
  orderCanSubmitPolice,
  orderHasPoliceVerification,
  policeStatusLabel,
} from "../trustVerificationPoliceUtils";
import { formatTrustVerificationDateTime } from "../trustVerificationDateTime";
import { tv, cn } from "./trustVerificationTheme";

export default function OrderPoliceVerificationPanel({ order, onSaved, variant = "premium", content = {} }) {
  const [form, setForm] = useState(EMPTY_POLICE_FORM);
  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState(false);
  const fileRef = useRef(null);
  const premium = variant === "premium";

  if (!orderHasPoliceVerification(order)) {
    return null;
  }

  const pv = order.police_verification || {};
  const canEdit = orderCanSubmitPolice(order);
  const title = content.police_verification_title || "Police verification";
  const description =
    content.police_verification_description ||
    "Track your Rajasthan Police tenant/owner verification. Upload acknowledgement after applying on the official portal.";

  const handleSave = async () => {
    if (!isPoliceFormComplete(form)) {
      toast.error("Fill police station, city, district, and 10-digit mobile");
      return;
    }
    setSaving(true);
    try {
      await submitTrustVerificationPoliceVerification(order.id, form);
      toast.success("Police verification details saved");
      setForm(EMPTY_POLICE_FORM);
      onSaved?.();
    } catch (e) {
      toast.error(e?.response?.data?.message || "Failed to save police verification");
    } finally {
      setSaving(false);
    }
  };

  const handleUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setUploading(true);
    try {
      await uploadTrustVerificationPoliceAcknowledgement(order.id, file);
      toast.success("Acknowledgement uploaded");
      if (fileRef.current) fileRef.current.value = "";
      onSaved?.();
    } catch (err) {
      toast.error(err?.response?.data?.message || "Upload failed");
    } finally {
      setUploading(false);
    }
  };

  const handleDownloadAck = async () => {
    try {
      await downloadTrustVerificationPoliceAcknowledgement(order.id);
    } catch {
      toast.error("Download failed");
    }
  };

  const handleDownloadCert = async () => {
    try {
      await downloadTrustVerificationPoliceCertificate(order.id);
    } catch {
      toast.error("Certificate not available");
    }
  };

  return (
    <div
      className={cn(
        "mt-4 rounded-[12px] border border-dashed p-3",
        premium ? "border-[#E5E7EB] bg-[#F8F9FA]" : "newBorderColor bg-gray-50"
      )}
    >
      <div className="flex flex-wrap items-center justify-between gap-2">
        <p className={cn(tv.heading, "text-sm")}>{title}</p>
        <span className="rounded-full bg-[#F3F4F6] px-2 py-0.5 text-xs font-medium text-[#374151]">
          {policeStatusLabel(pv.status || "not_submitted")}
        </span>
      </div>

      <div className={cn("mt-2 space-y-1 text-xs", tv.subheading)}>
        {pv.reference_number ? <p>Reference: {pv.reference_number}</p> : null}
        {pv.police_station_name ? (
          <p>
            {pv.police_station_name}
            {pv.city ? `, ${pv.city}` : ""}
          </p>
        ) : null}
        {pv.submitted_at ? <p>Submitted: {formatTrustVerificationDateTime(pv.submitted_at)}</p> : null}
        {pv.completed_at ? <p>Completed: {formatTrustVerificationDateTime(pv.completed_at)}</p> : null}
        {pv.admin_message ? (
          <p className="rounded-[8px] border border-amber-200 bg-amber-50 px-2 py-1 text-amber-900">
            {pv.admin_message}
          </p>
        ) : null}
      </div>

      <div className="mt-3 flex flex-wrap gap-2">
        {pv.can_download_acknowledgement ? (
          <button type="button" className={tv.btnSecondary} onClick={handleDownloadAck}>
            Download acknowledgement
          </button>
        ) : null}
        {pv.can_download_certificate ? (
          <button type="button" className={tv.btnSecondary} onClick={handleDownloadCert}>
            Download certificate
          </button>
        ) : null}
      </div>

      {canEdit && pv.can_upload_acknowledgement ? (
        <div className="mt-3">
          <label className="block text-xs font-medium text-[#374151]">
            Upload acknowledgement (PDF/JPG/PNG, max 5 MB)
          </label>
          <input
            ref={fileRef}
            type="file"
            accept=".pdf,.jpg,.jpeg,.png"
            className="mt-1 block w-full text-sm"
            disabled={uploading}
            onChange={handleUpload}
          />
        </div>
      ) : null}

      {canEdit && pv.status !== "completed" ? (
        <div className="mt-4">
          <PoliceVerificationDetailsForm
            value={form}
            onChange={setForm}
            title=""
            description={description}
          />
          <button type="button" className={cn(tv.btnPrimary, "mt-3")} disabled={saving} onClick={handleSave}>
            {saving ? "Saving…" : "Save police verification"}
          </button>
        </div>
      ) : null}
    </div>
  );
}
