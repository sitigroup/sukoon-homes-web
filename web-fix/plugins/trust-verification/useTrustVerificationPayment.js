"use client";

import { useCallback, useRef } from "react";
import toast from "react-hot-toast";
import {
  confirmTrustVerificationPayment,
  createTrustVerificationPaymentIntent,
} from "@/plugins/trust-verification/trustVerificationApi";

const allowedPaymentOrigins = () =>
  [
    process.env.NEXT_PUBLIC_API_URL,
    process.env.NEXT_PUBLIC_WEB_URL,
    typeof window !== "undefined" ? window.location.origin : null,
  ].filter(Boolean);

const openCenteredPopup = (url, name = "PaymentPopup", width = 600, height = 700) => {
  const left = window.screenX + (window.outerWidth - width) / 2;
  const top = window.screenY + (window.outerHeight - height) / 2;
  const features = [
    `width=${width}`,
    `height=${height}`,
    `left=${left}`,
    `top=${top}`,
    "scrollbars=yes",
    "resizable=yes",
    "status=yes",
    "toolbar=no",
    "menubar=no",
    "location=no",
  ].join(",");
  return window.open(url, name, features);
};

export function useTrustVerificationPayment({ onPaid }) {
  const listenerRef = useRef(null);
  const popupRef = useRef(null);

  const cleanup = useCallback(() => {
    if (listenerRef.current) {
      window.removeEventListener("message", listenerRef.current);
      listenerRef.current = null;
    }
  }, []);

  const payWithCashfree = useCallback(
    async (orderId) => {
      cleanup();
      if (popupRef.current && !popupRef.current.closed) {
        popupRef.current.close();
      }

      const res = await createTrustVerificationPaymentIntent(orderId);
      if (res?.error) {
        throw new Error(res?.message || "Could not start payment");
      }

      const paymentUrl = res?.data?.payment_intent?.payment_url;
      const transactionId = res?.data?.payment_transaction_id;

      if (!paymentUrl) {
        throw new Error("Payment link not found");
      }

      return new Promise((resolve, reject) => {
        const listener = async (event) => {
          if (!allowedPaymentOrigins().includes(event.origin)) return;

          let data = event.data;
          if (typeof data === "string") {
            try {
              data = JSON.parse(data);
            } catch {
              data = { status: data };
            }
          }

          const status = String(data?.status || data?.payment_status || "").toLowerCase();
          if (!status) return;

          const success = ["success", "successful", "completed", "charge.completed"].includes(status);
          const failed = ["failed", "failure", "cancelled", "canceled", "error"].includes(status);

          if (success) {
            cleanup();
            if (popupRef.current && !popupRef.current.closed) popupRef.current.close();
            try {
              const confirmed = await confirmTrustVerificationPayment(orderId, transactionId);
              const order = confirmed?.data;
              const paid = ["paid", "waived"].includes(
                String(order?.payment_status || "").toLowerCase()
              );
              if (!paid) {
                toast("Payment received. If status does not update, refresh in a minute.", {
                  icon: "⏳",
                });
              }
              if (onPaid) onPaid(order);
              resolve(order);
            } catch (e) {
              reject(e);
            }
          } else if (failed) {
            cleanup();
            if (popupRef.current && !popupRef.current.closed) popupRef.current.close();
            toast.error("Payment failed or was cancelled");
            reject(new Error("Payment failed"));
          }
        };

        listenerRef.current = listener;
        window.addEventListener("message", listener);

        const popup = openCenteredPopup(paymentUrl, "cashfree_verification");
        popupRef.current = popup;

        if (!popup) {
          cleanup();
          toast.error("Popup blocked — allow popups and try again");
          reject(new Error("Popup blocked"));
          return;
        }

        const poll = setInterval(() => {
          if (popup.closed) {
            clearInterval(poll);
            if (listenerRef.current === listener) {
              cleanup();
              reject(new Error("Payment window closed"));
            }
          }
        }, 1000);
      });
    },
    [cleanup, onPaid]
  );

  return { payWithCashfree, cleanup };
}
