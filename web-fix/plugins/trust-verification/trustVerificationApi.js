import api from "@/api/axiosMiddleware";
import { normalizeTrustVerificationOrder } from "./trustVerificationUtils";

const BASE = "trust-verification";

function extractOrdersList(body) {
  if (Array.isArray(body)) return body;
  if (Array.isArray(body?.data)) return body.data;
  return [];
}

export const getTrustVerificationPublicContent = async () => {
  const res = await api.get(`${BASE}/content/public`);
  return res.data;
};

export const getTrustVerificationCities = async () => {
  const res = await api.get(`${BASE}/cities`);
  return res.data;
};

export const getTrustVerificationPackages = async ({ type = "tenant", city = "barmer" } = {}) => {
  const res = await api.get(`${BASE}/packages`, { params: { type, city } });
  return res.data;
};

export const getTrustVerificationPaymentSettings = async () => {
  const res = await api.get(`${BASE}/payment-settings`);
  return res.data;
};

export const getTrustVerificationOrders = async () => {
  const res = await api.get(`${BASE}/orders`);
  const body = res?.data ?? {};
  const rows = extractOrdersList(body).map(normalizeTrustVerificationOrder);
  if (Array.isArray(body)) {
    return { data: rows };
  }
  return { ...body, data: rows };
};

export const getTrustVerificationOrder = async (orderId) => {
  const res = await api.get(`${BASE}/orders/${orderId}`);
  const body = res?.data ?? {};
  if (body?.data) {
    return { ...body, data: normalizeTrustVerificationOrder(body.data) };
  }
  if (body?.id) {
    return { data: normalizeTrustVerificationOrder(body) };
  }
  return body;
};

export const submitTrustVerificationOrder = async (payload) => {
  const res = await api.post(`${BASE}/orders`, payload);
  return res.data;
};

export const createTrustVerificationPaymentIntent = async (orderId, payload = {}) => {
  const res = await api.post(`${BASE}/orders/${orderId}/payment-intent`, {
    payment_method: "cashfree",
    platform_type: "web",
    ...payload,
  });
  return res.data;
};

export const confirmTrustVerificationPayment = async (orderId, paymentTransactionId) => {
  const res = await api.post(`${BASE}/orders/${orderId}/confirm-payment`, {
    payment_transaction_id: paymentTransactionId,
  });
  return res.data;
};

const REPORT_UNAVAILABLE_MSG =
  "Report is not available yet. Please contact support.";

function headerContentType(res) {
  const raw = res?.headers?.["content-type"] || res?.headers?.["Content-Type"] || "";
  return String(raw).split(";")[0].trim().toLowerCase();
}

function headerContentDisposition(res) {
  const raw =
    res?.headers?.["content-disposition"] || res?.headers?.["Content-Disposition"] || "";
  return String(raw);
}

/** Normalize axios blob/arraybuffer payload to a Blob for download. */
function normalizeDownloadBlob(data, contentType) {
  const mime = contentType || "application/octet-stream";
  if (data instanceof Blob) {
    if (!data.type && contentType) {
      return new Blob([data], { type: mime });
    }
    return data;
  }
  if (data instanceof ArrayBuffer) {
    return new Blob([data], { type: mime });
  }
  if (ArrayBuffer.isView(data)) {
    return new Blob([data], { type: mime });
  }
  return null;
}

/** PDF magic bytes (%PDF) — use binary read, not text(). */
async function blobHasPdfMagic(blob) {
  if (!blob || typeof blob.slice !== "function") return false;
  const buffer = await blob.slice(0, 5).arrayBuffer();
  const bytes = new Uint8Array(buffer);
  return (
    bytes.length >= 4 &&
    bytes[0] === 0x25 &&
    bytes[1] === 0x50 &&
    bytes[2] === 0x44 &&
    bytes[3] === 0x46
  );
}

async function readJsonMessageFromBlob(blob) {
  if (!blob || typeof blob.text !== "function") return null;
  try {
    const text = await blob.text();
    const parsed = JSON.parse(text);
    return typeof parsed?.message === "string" ? parsed.message : null;
  } catch {
    return null;
  }
}

async function rejectFromErrorPayload(payload, fallback = REPORT_UNAVAILABLE_MSG) {
  if (payload instanceof Blob) {
    const message = await readJsonMessageFromBlob(payload);
    throw new Error(message || fallback);
  }
  if (payload && typeof payload === "object" && payload.message) {
    throw new Error(payload.message);
  }
  throw new Error(fallback);
}

export const downloadTrustVerificationReport = async (orderId) => {
  try {
    const res = await api.get(`${BASE}/orders/${orderId}/report/download`, {
      responseType: "blob",
    });

    const status = res?.status ?? 0;
    const contentType = headerContentType(res);
    const disposition = headerContentDisposition(res);
    const blob = normalizeDownloadBlob(res.data, contentType);

    if (status !== 200) {
      await rejectFromErrorPayload(res.data);
    }

    if (!blob) {
      throw new Error(REPORT_UNAVAILABLE_MSG);
    }

    const isPdfMime = contentType.includes("pdf");
    const isAttachment = disposition.toLowerCase().includes("attachment");
    const hasPdfMagic = await blobHasPdfMagic(blob);

    if (hasPdfMagic || isPdfMime || isAttachment) {
      return blob;
    }

    if (contentType.includes("json")) {
      const message = await readJsonMessageFromBlob(blob);
      throw new Error(message || REPORT_UNAVAILABLE_MSG);
    }

    throw new Error(REPORT_UNAVAILABLE_MSG);
  } catch (e) {
    if (e instanceof Error && e.message) {
      throw e;
    }
    await rejectFromErrorPayload(e, REPORT_UNAVAILABLE_MSG);
  }
};

export const cancelTrustVerificationOrder = async (orderId) => {
  const res = await api.post(`${BASE}/orders/${orderId}/cancel`);
  return res.data;
};

export const submitTrustVerificationReferences = async (orderId, references) => {
  const res = await api.post(`${BASE}/orders/${orderId}/references`, { references });
  const body = res?.data ?? {};
  if (body?.data) {
    return { ...body, data: normalizeTrustVerificationOrder(body.data) };
  }
  return body;
};

export const getTrustVerificationReferences = async (orderId) => {
  const res = await api.get(`${BASE}/orders/${orderId}/references`);
  return res.data;
};

export const submitTrustVerificationPoliceVerification = async (orderId, payload) => {
  const res = await api.post(`${BASE}/orders/${orderId}/police-verification`, payload);
  const body = res?.data ?? {};
  if (body?.data) {
    return { ...body, data: normalizeTrustVerificationOrder(body.data) };
  }
  return body;
};

export const getTrustVerificationPoliceVerification = async (orderId) => {
  const res = await api.get(`${BASE}/orders/${orderId}/police-verification`);
  return res.data;
};

export const uploadTrustVerificationPoliceAcknowledgement = async (orderId, file) => {
  const form = new FormData();
  form.append("acknowledgement", file);
  const res = await api.post(`${BASE}/orders/${orderId}/police-verification/acknowledgement`, form, {
    headers: { "Content-Type": "multipart/form-data" },
  });
  const body = res?.data ?? {};
  if (body?.data) {
    return { ...body, data: normalizeTrustVerificationOrder(body.data) };
  }
  return body;
};

async function downloadPoliceFile(path) {
  const res = await api.get(path, { responseType: "blob" });
  const blob = res.data;
  const disposition = res.headers?.["content-disposition"] || "";
  const match = /filename="?([^";]+)"?/i.exec(disposition);
  const filename = match?.[1] || "police-document";
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
}

export const downloadTrustVerificationPoliceAcknowledgement = async (orderId) => {
  await downloadPoliceFile(`${BASE}/orders/${orderId}/police-verification/acknowledgement/download`);
};

export const downloadTrustVerificationPoliceCertificate = async (orderId) => {
  await downloadPoliceFile(`${BASE}/orders/${orderId}/police-verification/certificate/download`);
};

export const uploadTrustVerificationDocument = async (orderId, docType, file) => {
  const form = new FormData();
  form.append("doc_type", docType);
  form.append("file", file);
  const res = await api.post(`${BASE}/orders/${orderId}/documents`, form, {
    headers: { "Content-Type": "multipart/form-data" },
  });
  return res.data;
};

export const getTrustVerificationSampleReport = async ({ type, city, package_id } = {}) => {
  const res = await api.get(`${BASE}/sample-reports/public`, {
    params: { type, city, package_id },
  });
  return res.data;
};

export const recordTrustVerificationSampleReportView = async (sampleId, { source = "web" } = {}) => {
  const res = await api.post(`${BASE}/sample-reports/${sampleId}/viewed`, { source });
  return res.data;
};

const assertSamplePdfBlob = async (blob) => {
  if (!(await blobHasPdfMagic(blob))) {
    throw new Error("Sample file is not a valid PDF.");
  }
  return blob;
};

export const fetchTrustVerificationSampleReportPdf = async (
  sampleId,
  { inline = false, source = "web" } = {}
) => {
  const res = await api.get(`${BASE}/sample-reports/${sampleId}/download`, {
    params: {
      ...(inline ? { inline: 1 } : {}),
      source,
    },
    responseType: "blob",
  });
  return assertSamplePdfBlob(res.data);
};

export const downloadTrustVerificationSampleReport = async (sampleId, { source = "web" } = {}) => {
  const blob = await fetchTrustVerificationSampleReportPdf(sampleId, { inline: false, source });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = `sukoon-sample-report-${sampleId}.pdf`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
};

export const getTrustVerificationTrustProfile = async () => {
  const res = await api.get(`${BASE}/trust-profile`);
  const body = res?.data ?? {};
  return body?.data ?? body;
};

export const getPublicTrustProfile = async (customerId) => {
  if (!customerId) return null;
  const res = await api.get(`${BASE}/public-trust/${customerId}`);
  const body = res?.data ?? {};
  return body?.data ?? null;
};

/** Owner-safe tenant reliability (no fraud/risk/PII). */
export const getTenantReliabilityProfile = async (customerId) => {
  if (!customerId) return null;
  const res = await api.get(`${BASE}/tenant-reliability/${customerId}`);
  const body = res?.data ?? {};
  return body?.data ?? null;
};

export const getMyVerificationBadges = async () => {
  const res = await api.get(`${BASE}/verification-badges`);
  const body = res?.data ?? {};
  return body?.data ?? [];
};

export const downloadVerificationBadgeCertificate = async (badgeId) => {
  const res = await api.get(`${BASE}/verification-badges/${badgeId}/download`, {
    responseType: "blob",
  });
  const blob = res?.data;
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = `sukoon-verification-badge-${badgeId}.pdf`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
};
