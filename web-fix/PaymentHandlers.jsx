import {
  createAllPaymentIntentApi,
  paymentTransactionFailApi
} from "@/api/apiRoutes";
import { useTranslation } from "../context/TranslationContext";
import toast from "react-hot-toast";
import { useRef } from "react";
import { getPostPaymentRedirect } from "@/utils/paymentReturn";

/**
 * Opens a centered popup window with the given URL.
 * @param {string} url - The URL to open
 * @param {string} name - The window name
 * @param {number} width - Popup width
 * @param {number} height - Popup height
 * @returns {Window|null} - The opened window reference
 */
const openCenteredPopup = (url, name = "PaymentPopup", width = 600, height = 700) => {
  const left = window.screenX + (window.outerWidth - width) / 2;
  const top = window.screenY + (window.outerHeight - height) / 2;

  const features = [
    `width=${width}`,
    `height=${height}`,
    `left=${left}`,
    `top=${top}`,
    `scrollbars=yes`,
    `resizable=yes`,
    `status=yes`,
    `toolbar=no`,
    `menubar=no`,
    `location=no`,
  ].join(",");

  return window.open(url, name, features);
};

const PaymentHandlers = ({
  priceData,
  payAsYouGoId,
  setLoading,
  router,
  onPaymentSuccess,
  onPaymentFailed,
}) => {
  const t = useTranslation();
  const lang = router?.query?.lang;
  const popupRef = useRef(null);
  const listenerRef = useRef(null);
  const cleanupListenerRef = useRef(null);

  // Build payment params based on whether this is a pay-as-you-go or package purchase
  const getPaymentParams = (payment_method) => {
    const params = {
      platform_type: "web",
      payment_method,
    };
    if (payAsYouGoId) {
      params.pay_as_you_go_id = payAsYouGoId;
    } else {
      params.package_id = priceData?.id;
    }
    return params;
  };

  const normalizePaymentMessage = (payload) => {
    if (!payload) return null;

    if (typeof payload === "string") {
      try {
        return normalizePaymentMessage(JSON.parse(payload));
      } catch (error) {
        return { status: payload };
      }
    }

    const status =
      payload.status ??
      payload.payment_status ??
      payload.paymentStatus ??
      payload.event ??
      payload.result;

    return {
      ...payload,
      status: typeof status === "string" ? status.toLowerCase() : status,
    };
  };

  const isSuccessfulPaymentStatus = (status) => {
    const normalizedStatus = String(status || "").toLowerCase();
    return ["success", "successful", "completed", "charge.completed"].includes(normalizedStatus);
  };

  const isFailedPaymentStatus = (status) => {
    const normalizedStatus = String(status || "").toLowerCase();
    return ["failed", "failure", "cancelled", "canceled", "error"].includes(normalizedStatus);
  };

  /**
   * Cleans up the message event listener.
   */
  if (!cleanupListenerRef.current) {
    cleanupListenerRef.current = () => {
      if (listenerRef.current) {
        window.removeEventListener("message", listenerRef.current);
        listenerRef.current = null;
      }
    };
  }

  const cleanupListener = cleanupListenerRef.current;

  const rememberPaymentRole = () => {
    if (typeof window === "undefined") return;
    const isAgent =
      priceData?.user_type === "agent" || router?.asPath?.includes("/agent/");
    sessionStorage.setItem("sukoon_payment_role", isAgent ? "agent" : "user");
  };

  /**
   * Opens the payment URL in a centered popup and listens for postMessage events.
   * @param {string} paymentUrl - The checkout URL
   * @param {string} paymentMethod - The payment method name (e.g. "stripe", "razorpay")
   * @param {string|number} transactionId - The payment_transaction_id from the API response
   */
  const openPaymentPopup = (paymentUrl, paymentMethod, transactionId) => {
    rememberPaymentRole();

    // Clean up any previous listener
    cleanupListener();

    // Close any previously opened popup
    if (popupRef.current && !popupRef.current.closed) {
      popupRef.current.close();
    }

    // Create and attach the message listener before opening popup to avoid race conditions
    const messageListener = (event) => {
      if (event.origin !== process.env.NEXT_PUBLIC_API_URL) {
        console.warn("Ignored postMessage from unknown origin:", event.origin);
        return;
      }

      const data = normalizePaymentMessage(event.data);
      if (!data?.status) return;

      const { status } = data;

      if (isSuccessfulPaymentStatus(status)) {
        // Payment succeeded
        cleanupListener();
        if (popup && !popup.closed) popup.close();
        toast.success(t("paymentSuccessfull"));
        setLoading(false);
        if (onPaymentSuccess) {
          // Promise.resolve(onPaymentSuccess(paymentMethod, transactionId)).catch((error) => {
          //   console.error("Error in onPaymentSuccess callback:", error);
          // });
          setTimeout(() => {
            try {
              onPaymentSuccess(paymentMethod, transactionId);
            } catch (error) {
              console.error("Error in onPaymentSuccess callback:", error);
            }
          }, 1500); // Delay to ensure any backend processing is completed
        } else {
          router.push(getPostPaymentRedirect(router, priceData));
        }
      } else if (isFailedPaymentStatus(status)) {
        // Payment failed
        cleanupListener();
        if (popup && !popup.closed) popup.close();
        toast.error(t("paymentFailed") || "Payment failed. Please try again.");
        setLoading(false);

        // Call the payment transaction fail API
        if (transactionId) {
          paymentTransactionFailApi({
            payment_transaction_id: String(transactionId),
          }).catch((err) => {
            console.error("Error reporting payment failure:", err);
          });
        }

        if (onPaymentFailed) {
          Promise.resolve(onPaymentFailed(paymentMethod, transactionId)).catch((error) => {
            console.error("Error in onPaymentFailed callback:", error);
          });
        }
      }
    };

    listenerRef.current = messageListener;
    window.addEventListener("message", messageListener);

    // Open the popup after listener is attached
    const popup = openCenteredPopup(paymentUrl, `${paymentMethod}_payment`);
    popupRef.current = popup;

    if (!popup) {
      cleanupListener();
      toast.error(t("popupBlocked"));
      setLoading(false);
      if (onPaymentFailed) {
        Promise.resolve(onPaymentFailed(paymentMethod, transactionId)).catch((error) => {
          console.error("Error in onPaymentFailed callback:", error);
        });
      }
      return;
    }

    // Also poll to detect if user manually closes the popup
    const pollTimer = setInterval(() => {
      if (popup.closed) {
        clearInterval(pollTimer);
        // Only clean up if the listener is still active (meaning no success/failed was received)
        if (listenerRef.current === messageListener) {
          cleanupListener();
          setLoading(false);
          if (onPaymentFailed) {
            Promise.resolve(onPaymentFailed(paymentMethod, transactionId)).catch((error) => {
              console.error("Error in onPaymentFailed callback:", error);
            });
          }
        }
      }
    }, 1000);
  };

  /**
   * Generic handler that creates a payment intent and opens the popup.
   * @param {string} paymentMethod - The payment method key
   */
  const handlePayment = async (paymentMethod) => {
    try {
      setLoading(true);
      const res = await createAllPaymentIntentApi(getPaymentParams(paymentMethod));

      if (!res?.error) {
        const paymentUrl = res?.data?.payment_intent?.payment_url;
        const transactionId = res?.data?.payment_transaction_id || res?.data?.payment_intent?.id || "";

        if (paymentUrl) {
          openPaymentPopup(paymentUrl, paymentMethod, transactionId);
        } else {
          console.error(`No ${paymentMethod} payment link found:`, res?.message);
          toast.error(t("paymentLinkNotFound"));
          setLoading(false);
        }
      } else {
        console.error(`${paymentMethod} API error:`, res?.message);
        toast.error(res?.message || t("unexpectedErrorOccurred"));
        setLoading(false);
      }
    } catch (error) {
      console.error(`${paymentMethod} payment error:`, error);
      toast.error(error?.message || t("unexpectedErrorOccurred"));
      setLoading(false);
    }
  };

  // Individual payment method handlers (all use the same generic flow)
  const createPaymentIntent = () => handlePayment("stripe");
  const handleRazorpayPayment = () => handlePayment("razorpay");
  const handlePayStackPayment = () => handlePayment("paystack");
  const handlePaypalPayment = () => handlePayment("paypal");
  const handleFlutterwavePayment = () => handlePayment("flutterwave");
  const handleCashfreePayment = () => handlePayment("cashfree");
  const handlePhonepePayment = () => handlePayment("phonepe");
  const handleMidtransPayment = () => handlePayment("midtrans");

  return {
    createPaymentIntent,
    handlePayStackPayment,
    handlePaypalPayment,
    handleFlutterwavePayment,
    handleRazorpayPayment,
    handleCashfreePayment,
    handlePhonepePayment,
    handleMidtransPayment,
    cleanupListener,
  };
};

export default PaymentHandlers;
