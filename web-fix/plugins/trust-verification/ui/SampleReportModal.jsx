"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import {
  downloadTrustVerificationSampleReport,
  fetchTrustVerificationSampleReportPdf,
  getTrustVerificationSampleReport,
  recordTrustVerificationSampleReportView,
} from "../trustVerificationApi";
import { tv, cn } from "./trustVerificationTheme";

const DEFAULT_CHECKS = [
  "ID Verification",
  "Address Validation",
  "Criminal Record Check",
  "Civil Litigation Check",
  "Reference Check",
  "Risk Summary",
];

export default function SampleReportModal({
  open,
  onClose,
  reportType = "tenant",
  citySlug = "",
  packageId = null,
  source = "web",
}) {
  const [loading, setLoading] = useState(false);
  const [data, setData] = useState(null);
  const [error, setError] = useState("");
  const [previewOpen, setPreviewOpen] = useState(false);
  const [previewBlobUrl, setPreviewBlobUrl] = useState("");
  const [downloading, setDownloading] = useState(false);
  const viewedRef = useRef(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError("");
    setData(null);
    setPreviewOpen(false);
    if (previewBlobUrl) URL.revokeObjectURL(previewBlobUrl);
    setPreviewBlobUrl("");
    viewedRef.current = false;
    try {
      const res = await getTrustVerificationSampleReport({
        type: reportType,
        city: citySlug || undefined,
        package_id: packageId || undefined,
      });
      if (!res?.data) {
        setError("Sample report is not available yet. Please check back soon.");
        return;
      }
      setData(res.data);
    } catch (e) {
      setError(e?.message || "Could not load sample report.");
    } finally {
      setLoading(false);
    }
  }, [reportType, citySlug, packageId]);

  useEffect(() => {
    if (open) {
      load();
    }
  }, [open, load]);

  useEffect(() => {
    return () => {
      if (previewBlobUrl) URL.revokeObjectURL(previewBlobUrl);
    };
  }, [previewBlobUrl]);

  const cms = data?.cms || {};
  const title = cms.title || data?.title || "Sample verification report";
  const description =
    cms.description ||
    data?.description ||
    "See the kind of PDF report you receive after verification completes. This sample uses fictional data only.";
  const checks = data?.checks?.length ? data.checks : DEFAULT_CHECKS;

  const recordView = useCallback(async () => {
    if (!data?.id || viewedRef.current) return;
    viewedRef.current = true;
    try {
      await recordTrustVerificationSampleReportView(data.id, { source });
    } catch {
      /* non-blocking */
    }
  }, [data?.id, source]);

  const handlePreview = async () => {
    if (!data?.id) return;
    try {
      await recordView();
      const blob = await fetchTrustVerificationSampleReportPdf(data.id, { inline: true });
      if (previewBlobUrl) URL.revokeObjectURL(previewBlobUrl);
      const url = URL.createObjectURL(blob);
      setPreviewBlobUrl(url);
      setPreviewOpen(true);
    } catch (e) {
      setError(e?.message || "Could not load PDF preview.");
    }
  };

  const handleDownload = async () => {
    if (!data?.id) return;
    setDownloading(true);
    try {
      await downloadTrustVerificationSampleReport(data.id, { source });
    } catch (e) {
      setError(e?.message || "Download failed.");
    } finally {
      setDownloading(false);
    }
  };

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-[1200] flex items-end justify-center bg-[#1F2937]/55 p-0 sm:items-center sm:p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="tv-sample-report-title"
      onClick={onClose}
    >
      <div
        className="flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-white shadow-xl sm:rounded-2xl"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-start justify-between gap-3 border-b border-[#E5E7EB] px-5 py-4">
          <div className="min-w-0">
            <h2 id="tv-sample-report-title" className={cn(tv.heading, "text-lg")}>
              {title}
            </h2>
            <p className={cn(tv.muted, "mt-1 text-sm")}>{description}</p>
          </div>
          <button
            type="button"
            className="shrink-0 rounded-lg px-2 py-1 text-[#6B7280] hover:bg-[#F8F9FA]"
            onClick={onClose}
            aria-label="Close"
          >
            ✕
          </button>
        </div>

        <div className="overflow-y-auto px-5 py-4">
          {loading && <p className={cn(tv.muted, "text-sm")}>Loading sample report…</p>}
          {!loading && error && (
            <p className="rounded-lg border border-[#FCA5A5] bg-[#FEF2F2] px-3 py-2 text-sm text-[#B91C1C]">
              {error}
            </p>
          )}
          {!loading && data && (
            <>
              <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-[#6B7280]">
                Included checks
              </p>
              <ul className="mb-4 space-y-2 text-sm text-[#374151]">
                {checks.map((label) => (
                  <li key={label} className="flex gap-2">
                    <span className="text-[#B89A4A]" aria-hidden>
                      ✓
                    </span>
                    <span>{label}</span>
                  </li>
                ))}
              </ul>

              {previewOpen && previewBlobUrl && (
                <div className="mb-4 overflow-hidden rounded-xl border border-[#E5E7EB] bg-[#F8F9FA]">
                  <iframe
                    title="Sample report preview"
                    src={previewBlobUrl}
                    className="h-[min(50vh,420px)] w-full"
                  />
                </div>
              )}

              <div className="flex flex-col gap-2 sm:flex-row">
                <button
                  type="button"
                  className={cn(tv.btnPrimary, "w-full sm:flex-1")}
                  onClick={handlePreview}
                  disabled={!data?.id}
                >
                  Preview
                </button>
                <button
                  type="button"
                  className={cn(tv.btnSecondary, "w-full sm:flex-1")}
                  onClick={handleDownload}
                  disabled={downloading}
                >
                  {downloading ? "Downloading…" : "Download sample PDF"}
                </button>
              </div>
              <p className={cn(tv.muted, "mt-3 text-xs")}>
                Sample PDF only — no real personal data. For illustration before you purchase.
              </p>
            </>
          )}
        </div>
      </div>
    </div>
  );
}
