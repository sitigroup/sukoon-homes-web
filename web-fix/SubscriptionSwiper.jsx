"use client";
import { useEffect, useState } from "react";
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  CarouselDots,
  CarouselPrevious,
  CarouselNext,
} from "@/components/ui/carousel";
import { useRouter } from "next/router";
import { useTranslation } from "../context/TranslationContext";
import SubscriptionCard from "./SubscriptionCard";
import SubscriptionSkeletonCard from "./SubscriptionSkeletonCard";
import {
  assignFreePackageApi, getPackagesApi,
  getAgentPackagesApi,
  getPaymentSettingsApi,
  getWebSetting
} from "@/api/apiRoutes";
import { useDispatch, useSelector } from "react-redux";
import LoginModal from "../modal/LoginModal";
import Swal from "sweetalert2";
import toast from "react-hot-toast";
import NewBreadcrumb from "../breadcrumb/NewBreadCrumb";
import PaymentHandlers from "./PaymentHandlers";
import PaymentSelectionModal from "./PaymentSelectionModal";
import { isRTL } from "@/utils/helperFunction";
import { getPostPaymentRedirect } from "@/utils/paymentReturn";
import NoDataFound from "../no-data-found/NoDataFound";
import { setWebSettings } from "@/redux/slices/webSettingSlice";
import { setLanguages } from "@/redux/slices/languageSlice";
import SubscriptionBenefits from "./SubscriptionBenefits";

const SubscriptionSwiper = ({ isAgentSubscriptionSwiper = false, page = "" }) => {
  const t = useTranslation();
  const router = useRouter();
  const dispatch = useDispatch();
  const lang = router?.query?.lang;

  const [packagedata, setPackageData] = useState([]);
  const [allFeatures, setAllFeatures] = useState([]);
  const [loading, setLoading] = useState(true);
  const [paymentSettingsdata, setPaymentSettingsData] = useState([]);
  const [priceData, setPriceData] = useState("");
  const [showModal, setShowModal] = useState(false);
  const [showPaymentSelection, setShowPaymentSelection] = useState(false);

  const user = useSelector((state) => state.User?.data);
  const webSettings = useSelector((state) => state.WebSetting?.data);
  const token = useSelector((state) => state.User?.jwtToken);
  const language = useSelector((state) => state.LanguageSettings?.active_language);
  const currentLanguage = useSelector((state) => state.LanguageSettings?.current_language);

  const isUserLoggedIn = token ? true : false;

  // Check if Stripe gateway is active
  const stripeActive = paymentSettingsdata.some(
    (item) => item.type === "stripe_gateway" && item.data === "1",
  );

  // Check if Razorpay gateway is active
  const razorpayActive = paymentSettingsdata.some(
    (item) => item.type === "razorpay_gateway" && item.data === "1",
  );

  // Check if PayStack gateway is active
  const payStackActive = paymentSettingsdata.some(
    (item) => item.type === "paystack_gateway" && item.data === "1",
  );

  // Check if PayPal gateway is active
  const payPalActive = paymentSettingsdata.some(
    (item) => item.type === "paypal_gateway" && item.data === "1",
  );

  // Check if Flutterwave gateway is active
  const FlutterwaveActive = paymentSettingsdata.some(
    (item) => item.type === "flutterwave_status" && item.data === "1",
  );

  // Check if Bank Transfer is active - you may need to adjust this based on your actual settings
  const bankTransferActive = paymentSettingsdata.some(
    (item) => item.type === "bank_transfer_status" && item.data === "1",
  );

  // Check if Cashfree gateway is active
  const cashFreeActive = paymentSettingsdata.some(
    (item) => item.type === "cashfree_gateway" && item.data === "1",
  );

  // Check if Midtrans gateway is active
  const midtransActive = paymentSettingsdata.some(
    (item) => item.type === "midtrans_gateway" && item.data === "1",
  );

  // Check if Phonepe gateway is active
  const phonepeActive = paymentSettingsdata.some(
    (item) => item.type === "phonepe_gateway" && item.data === "1",
  );

  // Sample bank details - you should fetch this from your backend or settings
  const bankDetails = webSettings?.bank_details;

  // Carousel options for responsive behavior
  const carouselOptions = {
    dragFree: true,
    loop: false,
    slidesToScroll: "auto",
    direction: isRTL() ? "rtl" : "ltr",
  };

  const fetchWebSettings = async () => {
    try {
      const res = await getWebSetting();
      if (!res?.error) {
        dispatch(setWebSettings({ data: res.data }));
        dispatch(setLanguages({ data: res.data.languages }));
        document.documentElement.lang = currentLanguage?.code;
        document.dir = currentLanguage?.rtl === 1 ? "rtl" : "ltr";
      }
    } catch (error) {
      console.error(error);
    }
  };

  // Fetch packages data
  useEffect(() => {
    fetchPackages();
    fetchWebSettings();
  }, [isUserLoggedIn, language]);

  // Fetch payment settings data
  useEffect(() => {
    if (isUserLoggedIn) {
      fetchPaymentSettings();
    }
  }, [isUserLoggedIn]);

  const fetchPaymentSettings = async () => {
    try {
      const res = await getPaymentSettingsApi();
      if (!res?.error) {
        setPaymentSettingsData(res?.data);
      } else {
        console.error("Error fetching payment settings:", res?.message);
      }
    } catch (err) {
      console.error("Error fetching payment settings:", err);
    }
  };

  const fetchPackages = async () => {
    try {
      setLoading(true);

      if (page === "become-agent") {
        // Use the dedicated agent packages endpoint for the become-agent page
        const res = await getAgentPackagesApi({});
        if (!res.error) {
          const packageData = res?.data || [];
          const allFeatures = res?.all_features || [];
          setPackageData(packageData);
          setAllFeatures(allFeatures);
        } else {
          console.error("Error fetching agent packages:", res.message);
        }
      } else {
        // const requestedUserType = isAgentSubscriptionSwiper ? "agent" : "user";
        const res = await getPackagesApi();
        if (!res.error) {
          const packageData = res?.data || [];
          const allFeatures = res?.all_features || [];
          const allActivePackages = res?.active_packages || [];

          // Combine both lists (active packages + all packages)
          const combinedPackages = [...allActivePackages, ...packageData];

          // Sort to ensure active packages (is_active === 1) appear first
          const sortedPackages = combinedPackages.sort((a, b) => {
            const getPriority = (pkg) => {
              if (pkg.is_active) return 3;
              if (pkg.package_status === "review") return 2;
              if (pkg.package_status === "rejected") return 1;
              return 0;
            };
            return getPriority(b) - getPriority(a);
          });

          // Set final sorted data
          setPackageData(sortedPackages);
          setAllFeatures(allFeatures);
        } else {
          console.error("Error fetching packages:", res.message);
        }
      }

      setLoading(false);
    } catch (err) {
      console.error("Error fetching packages:", err);
      setLoading(false);
    }
  };

  // Handle Payment Modal
  const subscribePayment = (e, data) => {
    e.preventDefault();

    if (!isUserLoggedIn) {
      Swal.fire({
        title: t("oops"),
        text: "You need to login!",
        icon: "warning",
        allowOutsideClick: false,
        showCancelButton: false,
        customClass: {
          confirmButton: "Swal-confirm-buttons",
          cancelButton: "Swal-cancel-buttons",
        },
        confirmButtonText: t("ok"),
      }).then((result) => {
        if (result.isConfirmed) {
          setShowModal(true);
        }
      });
      return false;
    }

    if (webSettings.demo_mode && user?.is_demo_user) {
      Swal.fire({
        title: t("oops"),
        text: t("notAllowdDemo"),
        icon: "warning",
        showCancelButton: false,
        customClass: {
          confirmButton: "Swal-confirm-buttons",
          cancelButton: "Swal-cancel-buttons",
        },
        confirmButtonText: t("ok"),
      });
      return false;
    }

    setPriceData(data);

    // Handle free packages
    if (data?.package_type === "free") {
      // Confirm before assigning free package
      Swal.fire({
        title: t("areYouSure"),
        text: t("areYouSureToBuyThisFreePackage"),
        icon: "warning",
        showCancelButton: true,
        customClass: {
          confirmButton: "Swal-confirm-buttons",
          cancelButton: "Swal-cancel-buttons",
        },
        cancelButtonColor: "#d33",
        confirmButtonText: t("yes"),
        cancelButtonText: t("no"),
      }).then((result) => {
        if (result.isConfirmed) {
          handleFreePackage(data);
        }
      });
      return;
    }

    // Handle paid packages based on active payment methods
    if (isUserLoggedIn) {
      const hasOnlinePayment = hasActiveOnlinePayment();

      if (hasOnlinePayment && bankTransferActive) {
        setShowPaymentSelection(true);
      } else if (hasOnlinePayment) {
        setShowPaymentSelection(true);
      } else if (bankTransferActive) {
        setShowPaymentSelection(true);
      } else {
        Swal.fire({
          title: t("oops"),
          text: t("noPaymentActive") || "No payment method is active",
          icon: "warning",
          showCancelButton: false,
          customClass: {
            confirmButton: "Swal-confirm-buttons",
            cancelButton: "Swal-cancel-buttons",
          },
          confirmButtonText: t("ok"),
        }).then(() => {
          router.push(`/contact-us/?lang=${lang}`);
        });
      }
    }
  };

  // Handle free package assignment
  const handleFreePackage = async (data) => {
    try {
      const res = await assignFreePackageApi({
        package_id: data?.id,
      });
      if (!res?.error) {
        toast.success(t(res?.message));
        router.push(getPostPaymentRedirect(router, data));
      } else {
        console.error("Error Assigning Free Package:", res?.message);
        toast.error(t(res?.message) || t("errorOccurred"));
      }
    } catch (error) {
      console.error("Error Assigning Free Package:", error);
      toast.error(t(error?.message) || t("errorOccurred"));
    } finally {
      setLoading(false);
    }
  };

  // Handle online payment selection — directly triggers the payment handler
  const handleOnlinePayment = (selectedGateway) => {
    setShowPaymentSelection(false);

    switch (selectedGateway) {
      case "stripe":
        if (stripeActive) createPaymentIntent();
        break;
      case "razorpay":
        if (razorpayActive) handleRazorpayPayment();
        break;
      case "paystack":
        if (payStackActive) handlePayStackPayment();
        break;
      case "paypal":
        if (payPalActive) handlePaypalPayment();
        break;
      case "flutterwave":
        if (FlutterwaveActive) handleFlutterwavePayment();
        break;
      case "cashfree":
        if (cashFreeActive) handleCashfreePayment();
        break;
      case "phonepe":
        if (phonepeActive) handlePhonepePayment();
        break;
      case "midtrans":
        if (midtransActive) handleMidtransPayment();
        break;
      default:
        console.error("Unknown payment gateway:", selectedGateway);
    }
  };

  // Close payment selection modal
  const handleClosePaymentSelection = () => {
    setShowPaymentSelection(false);
  };

  // Payment handlers — popup-based flow
  const {
    handleRazorpayPayment,
    handlePayStackPayment,
    handlePaypalPayment,
    handleFlutterwavePayment,
    createPaymentIntent,
    handleCashfreePayment,
    handlePhonepePayment,
    handleMidtransPayment,
    cleanupListener,
  } = PaymentHandlers({
    priceData,
    setLoading,
    router,
    onPaymentSuccess: async () => {
      await fetchPackages();
      router.push(getPostPaymentRedirect(router, priceData));
    },
  });

  // Clean up message listener on unmount
  useEffect(() => {
    return () => {
      cleanupListener();
    };
  }, [cleanupListener]);

  // Generate skeleton cards while loading
  const renderSkeletonCards = () => {
    return [...Array(4)].map((_, index) => (
      <CarouselItem key={`skeleton-${index}`} className="md:basis-1/2 lg:basis-1/3 xl:basis-1/4">
        <SubscriptionSkeletonCard />
      </CarouselItem>
    ));
  };

  // Function to check if any online payment gateway is active
  const hasActiveOnlinePayment = () => {
    return (
      stripeActive ||
      razorpayActive ||
      payStackActive ||
      payPalActive ||
      FlutterwaveActive ||
      cashFreeActive ||
      phonepeActive ||
      midtransActive
    );
  };

  // Empty state when no packages are available
  const renderEmptyState = () => (
    <div className="flex w-full flex-col items-center justify-center px-4 py-16">
      <NoDataFound />
    </div>
  );

  const renderCarouselControls = (dotsClassName = "") => (
    <div className="mt-4 sm:mt-6 md:mt-8 lg:mt-12 flex justify-center gap-3">
      <CarouselPrevious variant="outline" className="bg-transparent border-0 shadow-none text-lg static h-10 w-10 shrink-0 translate-x-0 translate-y-0 md:flex [&>svg]:!size-5" />
      <CarouselDots className={`min-w-0 justify-center px-0 ${dotsClassName}`} />
      <CarouselNext variant="outline" className="bg-transparent border-0 shadow-none text-lg static h-10 w-10 shrink-0 translate-x-0 translate-y-0 md:flex [&>svg]:!size-5" />
    </div>
  );

  if (page === "become-agent" && packagedata && packagedata.length === 0) {
    return null;
  }

  return (
    <div>
      {!isAgentSubscriptionSwiper && (
        <NewBreadcrumb
          title={t("subscriptionPlan")}
          items={[{ href: "/subscription-plan", label: t("subscriptionPlan") }]}
        />
      )}
      <section id="subscription" className={`${page === "become-agent" || page === "subscription-plan" ? "px-4 py-6 sm:py-8 md:py-10 lg:py-12 xl:py-[60px]" : ""} ${isAgentSubscriptionSwiper ? "primaryBackgroundBg" : ""}`}>
        <div className="container mx-auto">

          <div className={`flex flex-col gap-6 sm:gap-8 md:gap-10 lg:gap-12 ${isAgentSubscriptionSwiper ? "items-center " : "items-start"} `}>

            {page === "become-agent" && (
              <span className='flex items-center justify-center rounded-full border primaryBorderColor p-4 primaryBgLight08 text-sm md:text-base font-medium primaryColor'>{t("simpleTransparentPricing")}</span>
            )}

            {page === "become-agent" && (<div className={`flex flex-col gap-1 sm:gap-2 md:gap-3 lg:gap-4 ${isAgentSubscriptionSwiper ? "text-center mx-auto max-w-4xl" : ""}`}>
              <div className="text-xl font-bold brandColor md:text-3xl lg:text-[32px]">
                {t("subscriptionSectionTitle")}
              </div>
              <div className="text-base leadColor">
                {t("subscriptionSectionDescription")}
              </div>
            </div>
            )}

            {loading ? (
              <Carousel opts={carouselOptions} className="relative w-full">
                <CarouselContent>
                  {renderSkeletonCards()}
                </CarouselContent>
                {renderCarouselControls()}
              </Carousel>
            ) : packagedata && packagedata.length > 0 ? (
              <Carousel opts={carouselOptions} className="relative w-full">
                <CarouselContent>
                  {packagedata.map((data, index) => (
                    <CarouselItem key={index} className={`flex h-full ${!isAgentSubscriptionSwiper ? "md:basis-1/2 lg:basis-1/3" : "md:basis-1/2 lg:basis-1/3 xl:basis-1/4"}`}>
                      <SubscriptionCard
                        data={data}
                        index={index + 1}
                        allFeatures={allFeatures}
                        subscribePayment={subscribePayment}
                        page={page}
                      />
                    </CarouselItem>
                  ))}
                </CarouselContent>
                {renderCarouselControls("p-3 [&>div]:flex-wrap")}
              </Carousel>
            ) : (
              renderEmptyState()
            )}

            {page === "become-agent" && (
              <SubscriptionBenefits
                packages={packagedata}
                features={allFeatures}
                page={page}
                currencySymbol={webSettings?.currency_symbol}
              />
            )}
          </div>
        </div>
      </section>

      {showModal && (
        <LoginModal showLogin={showModal} setShowLogin={setShowModal} />
      )}
      {showPaymentSelection && (
        <PaymentSelectionModal
          isOpen={showPaymentSelection}
          onClose={handleClosePaymentSelection}
          onOnlinePayment={handleOnlinePayment}
          stripeActive={stripeActive}
          razorpayActive={razorpayActive}
          payStackActive={payStackActive}
          payPalActive={payPalActive}
          flutterwaveActive={FlutterwaveActive}
          cashFreeActive={cashFreeActive}
          phonepeActive={phonepeActive}
          midtransActive={midtransActive}
          bankDetails={bankDetails}
          bankTransferActive={bankTransferActive}
          hasOnlinePayment={hasActiveOnlinePayment()}
          packageId={priceData?.id}
          priceData={priceData}
          CurrencySymbol={webSettings?.currency_symbol}
        />
      )}
    </div>
  );
};

export default SubscriptionSwiper;
