import { canDownloadTrustVerificationReport } from "./trustVerificationUtils";

export const ORDER_FILTER_ALL = "all";

export const ORDER_FILTERS = [
  { id: ORDER_FILTER_ALL, label: "All" },
  { id: "pending_payment", label: "Pending Payment" },
  { id: "in_progress", label: "In Progress" },
  { id: "completed", label: "Completed" },
  { id: "failed", label: "Failed" },
  { id: "cancelled", label: "Cancelled" },
  { id: "report_ready", label: "Report Ready" },
];

const EMPTY_MESSAGES = {
  all: "No verification orders yet.",
  pending_payment: "No orders with pending payment.",
  in_progress: "No verification orders in progress yet.",
  completed: "No completed verification orders yet.",
  failed: "No failed verification orders.",
  cancelled: "No cancelled verification orders.",
  report_ready: "No orders with a report ready to download yet.",
};

export function matchMyVerificationOrderFilter(order, filterId) {
  if (!order || filterId === ORDER_FILTER_ALL) return true;

  const payment = String(order.payment_status || "").toLowerCase();
  const status = String(order.status || "").toLowerCase();

  switch (filterId) {
    case "pending_payment":
      return payment === "pending";
    case "in_progress":
      return status === "in_progress";
    case "completed":
      return status === "completed";
    case "failed":
      return payment === "failed" || status === "failed";
    case "cancelled":
      return status === "cancelled";
    case "report_ready":
      return status === "completed" && canDownloadTrustVerificationReport(order);
    default:
      return true;
  }
}

export function filterMyVerificationOrders(orders, filterId) {
  const list = orders || [];
  if (filterId === ORDER_FILTER_ALL) return list;
  return list.filter((order) => matchMyVerificationOrderFilter(order, filterId));
}

export function countMyVerificationOrdersByFilter(orders) {
  const counts = {};
  for (const { id } of ORDER_FILTERS) {
    counts[id] = filterMyVerificationOrders(orders, id).length;
  }
  return counts;
}

export function getMyVerificationOrderFilterEmptyMessage(filterId) {
  return EMPTY_MESSAGES[filterId] || "No orders match this filter.";
}
