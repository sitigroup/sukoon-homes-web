import { useState, useEffect } from "react";
import {
  FaUniversity,
  FaArrowLeft, FaCopy,
  FaCloudUploadAlt,
  FaFileAlt,
  FaTimes,
  FaInfoCircle
} from "react-icons/fa";
import Image from "next/image";
import toast from "react-hot-toast";
import { useDropzone } from "react-dropzone";
import {
  initiateBankTransferApi
} from "@/api/apiRoutes";
import { useRouter } from "next/router";
import { useTranslation } from "../context/TranslationContext";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

// Payment gateway icons - you may need to update these paths based on your actual asset structure
import stripeIcon from "@/assets/paymentIcons/stripe.png";
import razorpayIcon from "@/assets/paymentIcons/razorpay.png";
import paypalIcon from "@/assets/paymentIcons/paypal.png";
import paystackIcon from "@/assets/paymentIcons/paystack.png";
import flutterwaveIcon from "@/assets/paymentIcons/flutterwave.png";
import cashfreeIcon from "@/assets/paymentIcons/cashfree.png";
import phonepeIcon from "@/assets/paymentIcons/phonepe.png";
import midtransIcon from "@/assets/paymentIcons/midtrans.png";
import { isRTL } from "@/utils/helperFunction";
import { getPostPaymentRedirect } from "@/utils/paymentReturn";

const PaymentSelectionModal = ({
  isOpen,
  onClose,
  onDismiss,
  onOnlinePayment,
  stripeActive,
  razorpayActive,
  payStackActive,
  payPalActive,
  flutterwaveActive,
  bankDetails,
  bankTransferActive,
  hasOnlinePayment,
  packageId,
  payAsYouGoId,
  cashFreeActive,
  phonepeActive,
  midtransActive,
  priceData,
  CurrencySymbol
}) => {
  const router = useRouter();
  const t = useTranslation();
  const lang = router?.query?.lang;

  const isRtl = isRTL();

  const [showBankDetails, setShowBankDetails] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState(null);
  const [transactionId, setTransactionId] = useState(null);
  const [file, setFile] = useState(null);
  const [fileError, setFileError] = useState("");
  const [isUploading, setIsUploading] = useState(false);
  const [selectedPaymentMethod, setSelectedPaymentMethod] = useState(null);
  const [selectedPaymentType, setSelectedPaymentType] = useState(null); // 'online' or 'offline'

  // Auto-show bank details if only bank transfer is active
  useEffect(() => {
    if (isOpen && bankTransferActive && !hasOnlinePayment) {
      setShowBankDetails(true);
      setSelectedPaymentMethod("bank");
      setSelectedPaymentType("offline");
    }
  }, [isOpen, bankTransferActive, hasOnlinePayment]);

  // Reset states when modal closes
  useEffect(() => {
    if (!isOpen) {
      setShowBankDetails(false);
      setError(null);
      setTransactionId(null);
      setFile(null);
      setFileError("");
      setSelectedPaymentMethod(null);
      setSelectedPaymentType(null);
    }
  }, [isOpen]);

  const onClosePaymentSelectionModal = () => {
    setShowBankDetails(false);
    setError(null);
    setTransactionId(null);
    setFile(null);
    setFileError("");
    setSelectedPaymentMethod(null);
    setSelectedPaymentType(null);
    if (onDismiss) {
      onDismiss();
      return;
    }
    onClose();
  };

  const handlePaymentSelection = (method, type) => {
    setSelectedPaymentMethod(method);
    setSelectedPaymentType(type);
    setError(null);
  };

  const handleContinue = () => {
    if (!selectedPaymentMethod) {
      toast.error(t("selectPaymentMethod") || "Please select a payment method");
      return;
    }

    if (selectedPaymentType === "offline" && selectedPaymentMethod === "bank") {
      setShowBankDetails(true);
    } else if (selectedPaymentType === "online") {
      onOnlinePayment(selectedPaymentMethod);
    }
  };

  const handleBack = () => {
    setShowBankDetails(false);
    setError(null);
  };

  // Validate file upload
  const validateFile = (file) => {
    const validTypes = [
      "image/jpeg",
      "image/png",
      "image/jpg",
      "application/pdf",
      "image/webp"
    ];
    const maxSize = 5 * 1024 * 1024; // 5MB

    if (!validTypes.includes(file.type)) {
      setFileError(
        t("invalidFileType") ||
        "Invalid file type. Please upload JPG, PNG, or PDF.",
      );
      return false;
    }

    if (file.size > maxSize) {
      setFileError(
        t("fileSizeTooLarge") ||
        "File size exceeds 5MB. Please upload a smaller file.",
      );
      return false;
    }

    setFileError("");
    return true;
  };

  // Initiate bank transfer and get transaction ID
  const handleInitiateBankTransfer = async () => {
    setIsUploading(true);
    setError(null);

    try {
      // First, initiate the bank transfer to get transaction ID
      const initiateRes = await initiateBankTransferApi({
        package_id: payAsYouGoId ? "" : packageId,
        pay_as_you_go_id: payAsYouGoId || "",
        file: file,
      });

      if (!initiateRes?.error) {
        setIsUploading(false);
        toast.success(t("receiptUploadedSuccessfully"));
        setShowBankDetails(false);
        setError(null);
        setTransactionId(null);
        setFile(null);
        setFileError("");
        setSelectedPaymentMethod(null);
        setSelectedPaymentType(null);
        onClose();
        router.push(getPostPaymentRedirect(router, priceData));
      } else {
        setIsUploading(false);
        const errorMessage =
          initiateRes?.message || t("bankTransferInitiationFailed");
        toast.error(errorMessage);
        setError(errorMessage);
      }
    } catch (error) {
      console.error("Bank transfer initiation error:", error);
      setIsUploading(false);
      const errorMessage = error?.message || t("receiptUploadFailed");
      toast.error(errorMessage);
      setError(errorMessage);
    }
  };

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    toast.success(t("copiedToClipboard"));
  };

  // Dropzone configuration
  const { getRootProps, getInputProps, isDragActive, isDragReject } =
    useDropzone({
      accept: {
        "image/jpeg": [".jpg", ".jpeg"],
        "image/png": [".png"],
        "application/pdf": [".pdf"],
        "image/webp": [".webp"]
      },
      maxSize: 5 * 1024 * 1024, // 5MB
      maxFiles: 1,
      onDrop: (acceptedFiles, rejectedFiles) => {
        if (rejectedFiles.length > 0) {
          if (rejectedFiles[0].errors[0].code === "file-too-large") {
            setFileError(t("fileSizeTooLarge"));
          } else if (rejectedFiles[0].errors[0].code === "file-invalid-type") {
            setFileError(t("invalidFileType"));
          } else {
            setFileError(t("invalidFileType"));
          }
          return;
        }

        if (acceptedFiles.length > 0) {
          const selectedFile = acceptedFiles[0];
          if (validateFile(selectedFile)) {
            setFile(selectedFile);
          }
        }
      },
    });

  // Payment method options configuration
  const onlinePaymentMethods = [
    {
      id: "razorpay",
      name: "Razorpay",
      icon: razorpayIcon,
      available: razorpayActive,
    },
    {
      id: "paypal",
      name: "PayPal",
      icon: paypalIcon,
      available: payPalActive,
    },
    {
      id: "stripe",
      name: "Stripe",
      icon: stripeIcon,
      available: stripeActive,
    },
    {
      id: "paystack",
      name: "Paystack",
      icon: paystackIcon,
      available: payStackActive,
    },
    {
      id: "flutterwave",
      name: "Flutterwave",
      icon: flutterwaveIcon,
      available: flutterwaveActive,
    },
    {
      id: "cashfree",
      name: "Cashfree",
      icon: cashfreeIcon,
      available: cashFreeActive,
    },
    {
      id: "phonepe",
      name: "PhonePe",
      icon: phonepeIcon,
      available: phonepeActive,
    },
    {
      id: "midtrans",
      name: "Midtrans",
      icon: midtransIcon,
      available: midtransActive,
    }
  ];

  const offlinePaymentMethods = [
    {
      id: "bank",
      name: t("bankTransfer") || "Bank Transfer",
      icon: <FaUniversity className="h-8 w-8" />,
      available: bankTransferActive,
    }
  ];

  // Filter available payment methods
  const availableOnlinePayments = onlinePaymentMethods.filter(
    (method) => method.available,
  );

  const availableOfflinePayments = offlinePaymentMethods.filter(
    (method) => method.available,
  );

  // Render upload section
  const renderUploadSection = () => {
    if (!showBankDetails) return null;

    return (
      <div className="space-y-4">
        <div className="space-y-4">
          <h3 className="text-lg font-semibold text-gray-800">
            {t("uploadPaymentReceipt")}
          </h3>

          {!file ? (
            <div
              {...getRootProps()}
              className={cn(
                "cursor-pointer rounded-lg border-2 border-dashed p-8 text-center transition-all duration-300",
                "hover:primaryBorderColor hover:primaryBgLight border-gray-300 bg-gray-50",
                isDragActive && "primaryBorderColor bg-teal-50",
                fileError && "border-red-500 bg-red-50",
              )}
            >
              <input {...getInputProps()} />
              <div className="flex flex-col items-center space-y-3">
                <div className="rounded-full bg-gray-100 p-3">
                  <FaCloudUploadAlt className="h-8 w-8 text-gray-500" />
                </div>
                <div className="space-y-1">
                  <p className="text-base text-gray-700">
                    {t("dragDropUpload") || "Drag & drop here or"}{" "}
                    <span className="font-semibold underline">
                      {t("clickToChoose") || "click to choose a file."}
                    </span>
                  </p>
                </div>
              </div>
            </div>
          ) : (
            <div className="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4">
              <div className="flex items-center space-x-3">
                <FaFileAlt className="primaryColor h-6 w-6" />
                <span className="max-w-[150px] truncate text-sm font-medium text-gray-800 md:max-w-[300px]">
                  {file.name}
                </span>
              </div>
              <button
                className="text-red-500 transition-colors hover:text-red-700"
                onClick={() => setFile(null)}
                aria-label="Remove file"
              >
                <FaTimes className="h-5 w-5" />
              </button>
            </div>
          )}

          {fileError && <p className="text-sm text-red-600">{fileError}</p>}
        </div>
      </div>
    );
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClosePaymentSelectionModal}>
      <DialogContent className="mx-auto max-w-2xl rounded-2xl bg-white p-0 shadow-lg [&>button]:hidden max-h-[85vh] overflow-y-auto">
        {!showBankDetails ? (
          // Payment Method Selection View
          <div className="flex flex-col bg-white overflow-hidden relative rounded-2xl size-full">
            {/* Header */}
            <div className="newBorderColor border-b border-solid flex gap-4 items-center px-6 py-4 relative rounded-t-[16px] shrink-0 w-full">
              <div className="flex flex-1 flex-col items-start justify-center relative min-w-px">
                <div className="flex items-center justify-center relative shrink-0">
                  <div className="flex flex-col font-bold justify-center relative shrink-0 brandColor text-base whitespace-nowrap">
                    <p className="">{t("completePayment")}</p>
                  </div>
                </div>
              </div>
              <button onClick={onClosePaymentSelectionModal} className="bg-[rgba(24,24,24,0.12)] cursor-pointer flex items-center justify-center p-1 relative rounded-lg shrink-0 hover:bg-black/20 transition-colors">
                <FaTimes className="brandColor size-[18px]" />
              </button>
            </div>

            {/* Body */}
            <div className="flex flex-col gap-8 items-end p-6 relative shrink-0 w-full">
              <div className="flex flex-col gap-6 items-center justify-center relative rounded-lg shrink-0 w-full">

                {/* Order Summary */}
                {priceData && (
                  <div className="primaryBackgroundBg newBorder border-solid flex flex-col items-start relative rounded-lg shrink-0 w-full">
                    <div className="flex flex-col items-start justify-center p-4 relative shrink-0 w-full gap-4">
                      <div className="flex flex-col md:flex-row font-bold items-center justify-between relative shrink-0 brandColor text-base w-full whitespace-pre-wrap">
                        <p className="relative shrink-0">{t("subscriptionPlan")}</p>
                        <p className="relative shrink-0 text-right">
                          {priceData?.translated_name || priceData?.name} {priceData?.duration > 0 ? `(${Math.round(priceData.duration / 30)} Month)` : ""}
                        </p>
                      </div>

                      {/* Divider */}
                      <div className="h-0 relative shrink-0 w-full border-t border-dashed newBorderColor"></div>

                      <div className="bg-white flex flex-col items-start p-3 relative rounded-lg shrink-0 w-full">
                        <div className="flex items-center justify-between relative shrink-0 w-full">
                          <p className="font-bold relative brandColor text-base">
                            {t("totalAmount")}
                          </p>
                          <div className="flex gap-2 items-center justify-end relative shrink-0 brandColor whitespace-nowrap">
                            <p className="font-bold relative shrink-0 text-xl">
                              {CurrencySymbol}{priceData?.price}
                            </p>
                            {priceData?.duration >= 30 && priceData?.price > 0 && (
                              <p className="font-medium opacity-66 relative shrink-0 text-base">
                                {CurrencySymbol}{(priceData.price / Math.round(priceData.duration / 30)).toFixed(2)}/{t("month")}
                              </p>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                )}

                {/* Payment Methods */}
                <div className="flex flex-col items-start relative shrink-0 w-full">
                  <div className="flex flex-col gap-3 items-start relative shrink-0 w-full">
                    <p className="font-bold relative brandColor text-base mb-1">
                      {t("choosePaymentMethod")}
                    </p>

                    {availableOfflinePayments.map((method) => (
                      <button
                        key={method.id}
                        onClick={() => handlePaymentSelection(method.id, "offline")}
                        className={cn(
                          "border border-solid cursor-pointer flex gap-4 items-center p-4 relative rounded-lg shrink-0 w-full transition-colors",
                          selectedPaymentMethod === method.id && selectedPaymentType === "offline"
                            ? "primaryBorderColor"
                            : "newBorderColor"
                        )}
                      >
                        <div className="flex items-center justify-center shrink-0 w-6 h-6 [&>svg]:size-6 [&>svg]:brandColor">
                          {method.icon}
                        </div>
                        <div className="flex flex-1 items-center min-w-px relative">
                          <p className="font-medium relative shrink-0 brandColor text-base text-left whitespace-nowrap">
                            {method.name}
                          </p>
                        </div>

                        {/* Radio selection indicator */}
                        <div className={cn(
                          "border border-solid flex items-center p-1 relative rounded-2xl shrink-0 size-5 transition-colors",
                          selectedPaymentMethod === method.id && selectedPaymentType === "offline" ? "primaryBorderColor" : "brandBorder"
                        )}>
                          {selectedPaymentMethod === method.id && selectedPaymentType === "offline" && (
                            <div className="primaryBg rounded-full size-full border-2 border-white box-content -m-0.5"></div>
                          )}
                        </div>
                      </button>
                    ))}

                    {availableOnlinePayments.map((method) => (
                      <button
                        key={method.id}
                        onClick={() => handlePaymentSelection(method.id, "online")}
                        className={cn(
                          "border border-solid cursor-pointer flex gap-4 items-center p-4 relative rounded-lg shrink-0 w-full transition-colors",
                          selectedPaymentMethod === method.id && selectedPaymentType === "online"
                            ? "primaryBorderColor"
                            : "newBorderColor"
                        )}
                      >
                        <div className="flex items-center justify-center shrink-0 w-6 h-6 relative overflow-hidden">
                          <Image
                            src={method.icon}
                            alt={method.name}
                            fill
                            className="object-contain"
                          />
                        </div>
                        <div className="flex flex-1 items-center min-w-px relative">
                          <p className="font-medium relative shrink-0 brandColor text-base text-left whitespace-nowrap">
                            {method.name}
                          </p>
                        </div>

                        {/* Radio selection indicator */}
                        <div className={cn(
                          "border border-solid flex items-center p-1 relative rounded-2xl shrink-0 size-5 transition-colors",
                          selectedPaymentMethod === method.id && selectedPaymentType === "online" ? "primaryBorderColor" : "brandBorder"
                        )}>
                          {selectedPaymentMethod === method.id && selectedPaymentType === "online" && (
                            <div className="primaryBg rounded-full size-full border-2 border-white box-content -m-0.5"></div>
                          )}
                        </div>
                      </button>
                    ))}
                  </div>
                </div>

              </div>

              {/* Action Button */}
              <button
                onClick={handleContinue}
                disabled={!selectedPaymentMethod}
                className="primaryBg cursor-pointer flex gap-2 items-center justify-center min-w-px px-4 py-3 relative rounded-lg shrink-0 w-full disabled:opacity-50 disabled:cursor-not-allowed hover:opacity-90 transition-opacity"
              >
                <p className="font-medium relative shrink-0 text-base md:text-xl text-center text-white whitespace-nowrap">
                  {t("completePayment")} {priceData?.price ? `- (${CurrencySymbol}${priceData.price})` : ""}
                </p>
              </button>
            </div>
          </div>
        ) : (
          // Bank Transfer Details View
          <div className="max-h-[600px] space-y-6 overflow-y-auto p-6 scrollbar-thin">
            <DialogHeader className="space-y-2">
              <div className="flex items-center space-x-3">
                {hasOnlinePayment && (
                  <button
                    className="primaryBackgroundBg flex h-8 w-8 items-center justify-center rounded-lg p-1 transition-colors hover:cursor-pointer"
                    onClick={handleBack}
                    aria-label="Go back"
                  >
                    <FaArrowLeft className="h-4 w-4" />
                  </button>
                )}
                <DialogTitle className="text-xl font-semibold text-gray-900">
                  {t("bankTransferDetails")}
                </DialogTitle>
              </div>
            </DialogHeader>

            {/* Bank Details Fields */}
            <div className="space-y-4">
              {bankDetails &&
                bankDetails.map((detail, index) => (
                  <div key={index} className="space-y-2">
                    <label className="block text-sm font-medium text-gray-700">
                      {detail.translated_title || detail.title}
                    </label>
                    <div className="relative">
                      <input
                        type="text"
                        value={detail.value}
                        readOnly
                        className="w-full rounded-lg border border-gray-300 bg-gray-50 p-3 font-mono text-sm text-gray-800"
                      />
                      <button
                        onClick={() => copyToClipboard(detail.value)}
                        className={`absolute top-1/2 -translate-y-1/2 transform rounded p-1 transition-colors hover:bg-gray-200
                        ${isRtl ? "left-3 right-auto rotate-180" : "left-auto right-3"}
                        `}
                        aria-label="Copy to clipboard"
                      >
                        <FaCopy className="h-4 w-4 text-gray-600" />
                      </button>
                    </div>
                  </div>
                ))}
            </div>

            {/* Upload Section */}
            {renderUploadSection()}

            {/* Info Note */}
            <div className="blueBgLight flex items-start space-x-3 rounded-lg border p-4">
              <FaInfoCircle className="blueTextColor mt-0.5 h-5 w-5 flex-shrink-0" />
              <p className="blueTextColor text-sm">{t("bankTransferNote")}</p>
            </div>

            {/* Error Message */}
            {error && (
              <div className="rounded-lg bg-red-50 p-3 text-center text-sm text-red-600">
                {error}
              </div>
            )}

            {/* Action Button - Only show Complete Payment when file is uploaded */}
            {file && (
              <Button
                className="primaryBg w-full rounded-lg py-3 font-medium text-white transition-colors hover:opacity-80 disabled:opacity-50"
                onClick={handleInitiateBankTransfer}
                disabled={isUploading}
              >
                {isUploading ? (
                  <div className="flex items-center justify-center space-x-2">
                    <div className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                    <span>{t("uploadingReceipt")}</span>
                  </div>
                ) : (
                  t("confirmPayment")
                )}
              </Button>
            )}
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
};

export default PaymentSelectionModal;
