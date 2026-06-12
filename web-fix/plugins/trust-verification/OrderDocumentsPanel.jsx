"use client";

import { useState } from "react";
import toast from "react-hot-toast";
import { uploadTrustVerificationDocument } from "@/plugins/trust-verification/trustVerificationApi";
import VerificationDataUsageNotice from "@/plugins/trust-verification/ui/VerificationDataUsageNotice";
import { tv, cn } from "@/plugins/trust-verification/ui/trustVerificationTheme";

const DEFAULT_LABELS = {
  id_front: "ID front",
  id_back: "ID back",
  pan: "PAN",
  address_proof: "Address proof",
  selfie: "Selfie with ID",
};

export default function OrderDocumentsPanel({ order, onUploaded, variant = "default" }) {
  const [uploading, setUploading] = useState(null);
  const premium = variant === "premium";

  if (!order || order.documents_complete || !["submitted", "in_progress"].includes(order.status)) {
    return null;
  }

  const uploaded = new Set((order.documents || []).map((d) => d.doc_type));
  const suggested = ["id_front", "id_back", "pan"].filter((t) => !uploaded.has(t));

  if (suggested.length === 0) {
    return null;
  }

  const handleUpload = async (docType, file) => {
    if (!file) return;
    setUploading(docType);
    try {
      await uploadTrustVerificationDocument(order.id, docType, file);
      toast.success("Document uploaded");
      onUploaded?.();
    } catch (e) {
      toast.error(e?.response?.data?.message || "Upload failed");
    } finally {
      setUploading(null);
    }
  };

  return (
    <div
      className={cn(
        "mt-3 rounded-[12px] border border-dashed p-3",
        premium ? "border-[#E5E7EB] bg-[#F8F9FA]" : "newBorderColor bg-gray-50"
      )}
    >
      <p className={cn("text-xs font-medium", premium ? "text-[#B89A4A]" : "brandColor")}>
        Upload missing documents
      </p>
      <VerificationDataUsageNotice className="mt-2" compact />
      <div className="mt-2 space-y-2">
        {suggested.map((type) => (
          <label key={type} className={cn("block text-xs", premium ? "text-[#6B7280]" : "leadColor")}>
            {DEFAULT_LABELS[type] || type}
            <input
              type="file"
              accept="image/jpeg,image/png,image/webp,application/pdf"
              className={cn("mt-1 w-full text-xs", premium ? tv.input : "")}
              disabled={uploading === type}
              onChange={(e) => handleUpload(type, e.target.files?.[0])}
            />
          </label>
        ))}
      </div>
    </div>
  );
}
