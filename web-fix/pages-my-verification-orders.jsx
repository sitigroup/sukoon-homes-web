"use client";

import { useEffect, useRef, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/router";
import { useSelector } from "react-redux";
import toast from "react-hot-toast";
import Layout from "@/components/layout/Layout";
import UserSideBar from "@/components/user/UserSideBar";
import MetaData from "@/components/meta/MetaData";
import { useAuthStatus } from "@/hooks/useAuthStatus";
import {
  formatInr,
  canDownloadTrustVerificationReport,
  isTrustVerificationReportAwaiting,
} from "@/plugins/trust-verification/trustVerificationUtils";
import {
  ORDER_FILTER_ALL,
  filterMyVerificationOrders,
  countMyVerificationOrdersByFilter,
  getMyVerificationOrderFilterEmptyMessage,
} from "@/plugins/trust-verification/myVerificationOrderFilters";
import {
  getTrustVerificationOrders,
  downloadTrustVerificationReport,
  cancelTrustVerificationOrder,
  confirmTrustVerificationPayment,
} from "@/plugins/trust-verification/trustVerificationApi";
import OrderDocumentsPanel from "@/plugins/trust-verification/OrderDocumentsPanel";
import { useTrustVerificationContent } from "@/plugins/trust-verification/useTrustVerificationContent";
import { useTrustVerificationPayment } from "@/plugins/trust-verification/useTrustVerificationPayment";
import styles from "@/plugins/trust-verification/ui/trustVerificationPremium.module.css";
import {
  MyVerificationOrdersAccountLayout,
  StartNewVerificationButton,
  MyOrdersFilterBar,
  TrustCard,
  VerificationStatusBadge,
  VerificationEmptyState,
  VerificationReportDisclaimer,
  OrderProgressStrip,
  OrderReferencePanel,
  OrderPoliceVerificationPanel,
  VerificationOrderBadgePanel,
  MyOrdersPagination,
  ORDERS_PER_PAGE,
  getPaginationSlice,
  buildVerificationTimelineSimple,
  getTimelineStepLabel,
  formatOrderLastUpdated,
  isOrderPaid,
  tv,
  cn,
} from "@/plugins/trust-verification/ui";

async function syncPendingPayments(orders) {
  const pending = (orders || []).filter(
    (o) => !isOrderPaid(o) && o.status !== "cancelled"
  );
  for (const order of pending) {
    try {
      await confirmTrustVerificationPayment(order.id);
    } catch {
      /* webhook may still be in flight */
    }
  }
}

function orderNeedsAttention(order) {
  return (
    !isOrderPaid(order) &&
    ["pending", "failed"].includes(String(order.payment_status || "pending")) &&
    order.status !== "cancelled"
  );
}

function currentStepLabel(order) {
  const steps = buildVerificationTimelineSimple(order);
  const current = steps.find((s) => s.status === "current");
  return current?.label || getTimelineStepLabel("report", order);
}

export default function MyVerificationOrdersPage() {
  const router = useRouter();
  const lang = router?.query?.lang || "en";
  const isLoggedIn = useAuthStatus();
  const userLoading = useSelector((state) => state.User?.loading);
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [payingId, setPayingId] = useState(null);
  const [downloadingId, setDownloadingId] = useState(null);
  const [cancellingId, setCancellingId] = useState(null);
  const [expandedId, setExpandedId] = useState(null);
  const [filter, setFilter] = useState(ORDER_FILTER_ALL);
  const [page, setPage] = useState(1);
  const paymentReturnHandled = useRef(false);
  const didPickDefaultExpand = useRef(false);
  const listTopRef = useRef(null);
  const { content } = useTrustVerificationContent();

  const reload = async () => {
    const res = await getTrustVerificationOrders();
    setOrders(res?.data || []);
    return res?.data || [];
  };

  const { payWithCashfree, cleanup } = useTrustVerificationPayment({
    onPaid: async (order) => {
      if (isOrderPaid(order)) {
        toast.success("Payment confirmed");
      } else {
        toast.success("Payment received — updating status…");
      }
      const list = await reload();
      if (order?.id) setExpandedId(order.id);
      return list;
    },
  });

  useEffect(() => () => cleanup(), [cleanup]);

  useEffect(() => {
    if (!isLoggedIn) {
      setLoading(false);
      return;
    }
    (async () => {
      try {
        await reload();
      } catch (e) {
        toast.error(e?.response?.data?.message || "Failed to load orders");
      } finally {
        setLoading(false);
      }
    })();
  }, [isLoggedIn]);

  useEffect(() => {
    if (loading || !orders.length || didPickDefaultExpand.current) return;
    const visible = filterMyVerificationOrders(orders, filter);
    const needsPay = visible.find(orderNeedsAttention);
    setExpandedId(needsPay?.id ?? visible[0]?.id ?? orders[0]?.id ?? null);
    didPickDefaultExpand.current = true;
  }, [loading, orders, filter]);

  useEffect(() => {
    if (!router.isReady || !isLoggedIn || loading || paymentReturnHandled.current) return;

    const payment = router.query.payment;
    if (!payment) return;

    paymentReturnHandled.current = true;

    (async () => {
      try {
        if (payment === "success") {
          const orderId = router.query.order_id ? Number(router.query.order_id) : null;
          if (orderId) {
            try {
              await confirmTrustVerificationPayment(orderId);
            } catch {
              /* fall through */
            }
          }
          await reload();
          await syncPendingPayments(orders);
          const refreshed = await reload();
          const target = orderId
            ? refreshed.find((o) => o.id === orderId)
            : refreshed.find((o) => isOrderPaid(o));
          if (target) setExpandedId(target.id);
          if (target && isOrderPaid(target)) {
            toast.success(`Payment confirmed for ${target.order_number}`);
          } else {
            toast.success(
              "Payment received. Status updates in a minute — refresh if it still shows pending.",
              { duration: 6000 }
            );
          }
        } else if (payment === "failed") {
          toast.error("Payment failed or was cancelled.");
        }
      } finally {
        router.replace(`/my-verification-orders?lang=${lang}`, undefined, { shallow: true });
      }
    })();
  }, [router.isReady, router.query.payment, router.query.order_id, isLoggedIn, loading, lang, router, orders]);

  const handlePay = async (orderId) => {
    setPayingId(orderId);
    try {
      await payWithCashfree(orderId);
    } catch (e) {
      if (e?.message !== "Payment window closed") {
        toast.error(e?.message || "Payment failed");
      }
    } finally {
      setPayingId(null);
    }
  };

  const handleDownload = async (order) => {
    setDownloadingId(order.id);
    try {
      const blob = await downloadTrustVerificationReport(order.id);
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `${order.order_number || "report"}-report.pdf`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (e) {
      toast.error(
        e?.message || e?.response?.data?.message || "Report is not available yet. Please contact support."
      );
    } finally {
      setDownloadingId(null);
    }
  };

  const handleCancel = async (order) => {
    if (!window.confirm(`Cancel order ${order.order_number}? This cannot be undone.`)) return;
    setCancellingId(order.id);
    try {
      await cancelTrustVerificationOrder(order.id);
      toast.success("Order cancelled");
      await reload();
    } catch (e) {
      toast.error(e?.response?.data?.message || "Could not cancel order");
    } finally {
      setCancellingId(null);
    }
  };

  const filterCounts = countMyVerificationOrdersByFilter(orders);
  const visibleOrders = filterMyVerificationOrders(orders, filter);

  const { totalPages, safePage, start: pageStart, end: pageEnd } = getPaginationSlice(
    page,
    visibleOrders.length,
    ORDERS_PER_PAGE
  );
  const paginatedOrders = visibleOrders.slice(pageStart, pageEnd);

  useEffect(() => {
    setPage(1);
    setExpandedId(null);
    didPickDefaultExpand.current = false;
  }, [filter]);

  useEffect(() => {
    if (page > totalPages) {
      setPage(totalPages);
      setExpandedId(null);
    }
  }, [page, totalPages]);

  const handlePageChange = (nextPage) => {
    setPage(nextPage);
    setExpandedId(null);
    listTopRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
  };

  const handleFilterChange = (nextFilter) => {
    setFilter(nextFilter);
  };

  return (
    <Layout>
      <div className="primaryBackgroundBg">
        <div className="container mx-auto gap-3 px-2 py-[60px] xl:flex 2xl:gap-6">
          <UserSideBar isLoading={userLoading || !router.isReady} />
          <div className="relative z-0 min-w-0 w-full flex-1 isolate">
      <MetaData title="My Verification Orders | Sukoon Homes" pageName="/my-verification-orders" />
      <MyVerificationOrdersAccountLayout
        lang={lang}
        headerAction={<StartNewVerificationButton lang={lang} />}
      >
        <section className={cn(!isLoggedIn && "pb-2")}>
          {!isLoggedIn && (
            <VerificationEmptyState
              title="Sign in required"
              description="Please log in to view your verification orders."
            />
          )}
          {isLoggedIn && loading && <p className={tv.subheading}>Loading…</p>}
          {isLoggedIn && !loading && orders.length === 0 && (
            <VerificationEmptyState
              title="No verification orders yet"
              description="Start a background check for a tenant or owner from the verification hub."
              action={
                <Link href={`/verification?lang=${lang}`} className={tv.btnPrimary}>
                  Start verification
                </Link>
              }
            />
          )}
          {isLoggedIn && orders.length > 0 && (
            <>
              <div ref={listTopRef} className={styles.myOrdersListToolbar}>
                <p className={cn(tv.subheading, styles.myOrdersListSummary, "text-sm")}>
                  {visibleOrders.length} order{visibleOrders.length === 1 ? "" : "s"}
                  {visibleOrders.length > ORDERS_PER_PAGE
                    ? ` — page ${safePage} of ${totalPages}`
                    : ""}{" "}
                  · expand one to see progress and actions.
                </p>
                <MyOrdersFilterBar
                  activeFilter={filter}
                  counts={filterCounts}
                  onFilterChange={handleFilterChange}
                />
              </div>

              <div className="space-y-3">
                {visibleOrders.length === 0 && (
                  <VerificationEmptyState
                    title="No orders found"
                    description={getMyVerificationOrderFilterEmptyMessage(filter)}
                    action={
                      filter !== ORDER_FILTER_ALL ? (
                        <button
                          type="button"
                          className={tv.btnSecondary}
                          onClick={() => handleFilterChange(ORDER_FILTER_ALL)}
                        >
                          Show all orders
                        </button>
                      ) : (
                        <Link href={`/verification?lang=${lang}`} className={tv.btnPrimary}>
                          Start verification
                        </Link>
                      )
                    }
                  />
                )}
                {paginatedOrders.map((order) => {
                  const expanded = expandedId === order.id;
                  const timeline = buildVerificationTimelineSimple(order);
                  const showPay = orderNeedsAttention(order);

                  return (
                    <TrustCard key={order.id} padding="p-0" className="overflow-hidden">
                      <button
                        type="button"
                        className="flex w-full flex-wrap items-start justify-between gap-3 p-4 text-left sm:p-5"
                        onClick={() => setExpandedId(expanded ? null : order.id)}
                        aria-expanded={expanded}
                      >
                        <div className="min-w-0 flex-1">
                          <code className="text-xs text-[#D4AF37]">{order.order_number}</code>
                          <h3 className={cn(tv.heading, "mt-1 text-base")}>
                            {order.package?.name} · {order.city_label}
                          </h3>
                          <p className={cn(tv.subheading, "mt-1 text-sm")}>
                            {order.subject?.full_name} · {formatInr(order.amount)}
                          </p>
                          {!expanded && (
                            <p className="mt-2 text-xs text-[#6B7280]">
                              {currentStepLabel(order)}
                            </p>
                          )}
                        </div>
                        <div className="flex shrink-0 flex-wrap items-center gap-2">
                          <VerificationStatusBadge label={order.status} variant={order.status} />
                          <VerificationStatusBadge
                            label={order.payment_status}
                            variant={order.payment_status}
                          />
                          <span className="text-[#9CA3AF]" aria-hidden>
                            {expanded ? "▲" : "▼"}
                          </span>
                        </div>
                      </button>

                      {expanded && (
                        <div className="border-t border-[#E5E7EB] px-4 pb-4 pt-3 sm:px-5 sm:pb-5">
                          {formatOrderLastUpdated(order) && (
                            <p className="mb-3 text-xs text-[#6B7280]">
                              Last updated:{" "}
                              <span className="font-medium text-[#374151]">
                                {formatOrderLastUpdated(order)}
                              </span>
                            </p>
                          )}
                          <OrderProgressStrip steps={timeline} />
                          <VerificationOrderBadgePanel order={order} />
                          {showPay && (
                            <p className="mt-3 text-sm text-amber-800">
                              Payment is still pending. Complete payment below to start
                              verification.
                            </p>
                          )}
                          {showPay && (
                            <VerificationReportDisclaimer className="mt-4" compact />
                          )}
                          <div className="mt-4 flex flex-wrap gap-2">
                            {showPay && (
                              <button
                                type="button"
                                className={tv.btnPrimary}
                                disabled={payingId === order.id}
                                onClick={() => handlePay(order.id)}
                              >
                                {payingId === order.id ? "Opening…" : "Pay with Cashfree"}
                              </button>
                            )}
                            {canDownloadTrustVerificationReport(order) && (
                              <button
                                type="button"
                                className={tv.btnSecondary}
                                disabled={downloadingId === order.id}
                                onClick={() => handleDownload(order)}
                              >
                                {downloadingId === order.id
                                  ? "Downloading…"
                                  : "Download report"}
                              </button>
                            )}
                            {isTrustVerificationReportAwaiting(order) && (
                              <p className="w-full text-sm text-[#6B7280]">
                                Report is not available yet. Please contact support.
                              </p>
                            )}
                            {order.can_cancel && (
                              <button
                                type="button"
                                className="rounded-[16px] border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700 disabled:opacity-60 hover:bg-red-100"
                                disabled={cancellingId === order.id}
                                onClick={() => handleCancel(order)}
                              >
                                {cancellingId === order.id ? "Cancelling…" : "Cancel order"}
                              </button>
                            )}
                          </div>
                          <div className={cn("mt-4 border-t pt-4", tv.divider)}>
                            <OrderDocumentsPanel
                              order={order}
                              onUploaded={reload}
                              variant="premium"
                            />
                            <OrderReferencePanel
                              order={order}
                              onSaved={reload}
                              variant="premium"
                            />
                            <OrderPoliceVerificationPanel
                              order={order}
                              onSaved={reload}
                              variant="premium"
                              content={content?.wizard || {}}
                            />
                          </div>
                        </div>
                      )}
                    </TrustCard>
                  );
                })}
              </div>

              <MyOrdersPagination
                page={page}
                totalItems={visibleOrders.length}
                onPageChange={handlePageChange}
              />
            </>
          )}
        </section>
      </MyVerificationOrdersAccountLayout>
          </div>
        </div>
      </div>
    </Layout>
  );
}
