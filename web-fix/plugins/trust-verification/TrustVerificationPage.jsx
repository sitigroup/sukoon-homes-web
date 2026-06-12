"use client";

import dynamic from "next/dynamic";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useRouter } from "next/router";
import toast from "react-hot-toast";
import { useSelector } from "react-redux";
import MetaData from "@/components/meta/MetaData";
import NewBreadcrumb from "@/components/breadcrumb/NewBreadCrumb";
import { useAuthStatus } from "@/hooks/useAuthStatus";
import { showLoginSwal } from "@/utils/helperFunction";
import { useTranslation } from "@/components/context/TranslationContext";
import {
  getTrustVerificationPackages,
  getTrustVerificationPaymentSettings,
  getTrustVerificationCities,
  submitTrustVerificationOrder,
  uploadTrustVerificationDocument,
} from "@/plugins/trust-verification/trustVerificationApi";
import {
  resolveCity,
  formatInr,
  verificationCopy,
  verificationWizardPath,
} from "@/plugins/trust-verification/trustVerificationUtils";
import { buildReturnPathFromRouter } from "@/plugins/trust-verification/verificationLegalNavigation";
import { packageIncludesReferenceCheck } from "@/plugins/trust-verification/trustVerificationReferenceUtils";
import {
  packageIncludesPoliceVerification,
  isPoliceFormComplete,
  EMPTY_POLICE_FORM,
} from "@/plugins/trust-verification/trustVerificationPoliceUtils";
import { useTrustVerificationContent } from "@/plugins/trust-verification/useTrustVerificationContent";
import { applyCityTemplate } from "@/plugins/trust-verification/trustVerificationContentMerge";
import { useTrustVerificationPayment } from "@/plugins/trust-verification/useTrustVerificationPayment";
import MyVerificationOrdersLink from "@/plugins/trust-verification/MyVerificationOrdersLink";
import TrustVerificationSiteLayout from "@/plugins/trust-verification/TrustVerificationSiteLayout";
import VerificationDocumentFields from "@/plugins/trust-verification/VerificationDocumentFields";
import {
  TrustVerificationPremiumShell,
  VerificationPackageCard,
  VerificationProgressStepper,
  VerificationSuccessScreen,
  VerificationEmptyState,
  VerificationTrustBadges,
  VerificationConsentCheckbox,
  VerificationReportDisclaimer,
  VerificationLegalLinks,
  ReferenceDetailsForm,
  PoliceVerificationDetailsForm,
  SampleReportTrigger,
  TrustCard,
  tv,
  cn,
} from "@/plugins/trust-verification/ui";

const LoginModal = dynamic(() => import("@/components/modal/LoginModal"), { ssr: false });

function cleanPhoneDigits(value) {
  let digits = String(value ?? "").replace(/\D/g, "");
  if (digits.length > 10 && digits.startsWith("91")) {
    digits = digits.slice(2);
  }
  return digits.slice(0, 10);
}

const emptySubject = {
  full_name: "",
  phone: "",
  email: "",
  current_address: "",
  permanent_address: "",
  property_address: "",
  id_type: "Aadhaar",
  id_number: "",
  employment_company: "",
  employment_role: "",
  consent_given: false,
};

export default function TrustVerificationPage({ orderType = "tenant", pageTitle = "", citySlug: citySlugProp = "" }) {
  const t = useTranslation();
  const router = useRouter();
  const lang = router?.query?.lang;
  const policyReturnTo = buildReturnPathFromRouter(router);
  const isLoggedIn = useAuthStatus();
  const userData = useSelector((state) => state.User?.data);

  const citySlugRaw = citySlugProp || router?.query?.citySlug || router?.query?.city || "";
  const [knownCities, setKnownCities] = useState([]);
  const city = useMemo(() => resolveCity(citySlugRaw, knownCities), [citySlugRaw, knownCities]);

  const { content } = useTrustVerificationContent();
  const wiz = content.wizard || {};
  const copy = verificationCopy[orderType] || verificationCopy.tenant;
  const cityLabel = city.label;
  const citySlug = city.slug;
  const resolvedPageTitle =
    pageTitle ||
    applyCityTemplate(
      orderType === "owner" ? wiz.owner_page_title : wiz.tenant_page_title,
      cityLabel
    ) ||
    copy.title(cityLabel);

  const [packages, setPackages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [step, setStep] = useState(1);
  const [selectedPackageId, setSelectedPackageId] = useState(null);
  const [subject, setSubject] = useState(emptySubject);
  const [notes, setNotes] = useState("");
  const [showLogin, setShowLogin] = useState(false);
  const [successOrder, setSuccessOrder] = useState(null);
  const [pendingOrder, setPendingOrder] = useState(null);
  const [cashfreeActive, setCashfreeActive] = useState(false);
  const [paying, setPaying] = useState(false);
  const [docSettings, setDocSettings] = useState({
    documents_enabled: false,
    documents_required: false,
    required_document_types: [],
    document_type_labels: {},
  });
  const [docFiles, setDocFiles] = useState({});
  const [referenceRows, setReferenceRows] = useState([]);
  const [policeForm, setPoliceForm] = useState({ ...EMPTY_POLICE_FORM, city: cityLabel, district: cityLabel });
  const [phoneTouched, setPhoneTouched] = useState(false);
  const profilePrefilledRef = useRef(false);

  const { payWithCashfree, cleanup: cleanupPayment } = useTrustVerificationPayment({
    onPaid: (order) => {
      setSuccessOrder(order);
      setPendingOrder(null);
      setStep(5);
      toast.success("Payment confirmed");
    },
  });

  const selectedPackage = useMemo(
    () => packages.find((p) => p.id === selectedPackageId) || packages[0] || null,
    [packages, selectedPackageId]
  );
  const showReferenceForm = packageIncludesReferenceCheck(selectedPackage);
  const showPoliceForm = packageIncludesPoliceVerification(selectedPackage);

  const loadPackages = useCallback(async () => {
    if (city.unknown) {
      setPackages([]);
      setLoading(false);
      return;
    }
    setLoading(true);
    try {
      const res = await getTrustVerificationPackages({
        type: orderType,
        city: citySlug,
      });
      const list = res?.data || [];
      setPackages(list);
      if (list.length) {
        setSelectedPackageId(list.find((p) => p.slug?.includes("standard"))?.id || list[0].id);
      }
    } catch (e) {
      toast.error(e?.response?.data?.message || "Failed to load packages");
    } finally {
      setLoading(false);
    }
  }, [orderType, citySlug, city.unknown]);

  useEffect(() => {
    getTrustVerificationCities()
      .then((res) => setKnownCities(res?.data || []))
      .catch(() => setKnownCities([]));
  }, []);

  useEffect(() => {
    loadPackages();
    getTrustVerificationPaymentSettings()
      .then((res) => {
        setCashfreeActive(!!res?.data?.cashfree_active);
        setDocSettings({
          documents_enabled: !!res?.data?.documents_enabled,
          documents_required: !!res?.data?.documents_required,
          required_document_types: res?.data?.required_document_types || [],
          document_type_labels: res?.data?.document_type_labels || {},
        });
      })
      .catch(() => setCashfreeActive(false));
    return () => cleanupPayment();
  }, [loadPackages, cleanupPayment]);

  useEffect(() => {
    if (!router.isReady || orderType !== "owner") return;
    const { property_address, listing_title, property_id } = router.query;
    if (property_address) {
      setSubject((s) => ({
        ...s,
        property_address: String(property_address),
      }));
    }
    const listingNote = [
      listing_title ? `Listing: ${listing_title}` : null,
      property_id ? `Property ID: ${property_id}` : null,
    ]
      .filter(Boolean)
      .join(" · ");
    if (listingNote) {
      setNotes((n) => n || listingNote);
    }
  }, [router.isReady, router.query, orderType]);

  useEffect(() => {
    if (!userData || profilePrefilledRef.current || phoneTouched) {
      return;
    }
    profilePrefilledRef.current = true;
    setSubject((s) => ({
      ...s,
      phone: cleanPhoneDigits(userData?.mobile) || s.phone,
      email: userData?.email || s.email,
    }));
  }, [userData, phoneTouched]);

  const handleSubjectPhoneChange = (value) => {
    setPhoneTouched(true);
    const cleanPhone = cleanPhoneDigits(value);
    setSubject((prev) => ({
      ...prev,
      phone: cleanPhone,
    }));
  };

  const clearSubjectPhone = () => {
    setPhoneTouched(true);
    setSubject((prev) => ({
      ...prev,
      phone: "",
    }));
  };

  const requireLogin = () => {
    showLoginSwal("oops", "plzLoginFirst", () => setShowLogin(true), t);
  };

  const handlePayOnline = async () => {
    if (!pendingOrder?.id) return;
    setPaying(true);
    try {
      await payWithCashfree(pendingOrder.id);
    } catch (e) {
      if (e?.message !== "Payment window closed") {
        toast.error(e?.message || "Payment could not be completed");
      }
    } finally {
      setPaying(false);
    }
  };

  const handleSubmit = async () => {
    if (!isLoggedIn) {
      requireLogin();
      return;
    }
    if (!selectedPackage?.id) {
      toast.error("Please select a package");
      return;
    }
    if (!subject.full_name?.trim() || !subject.phone?.trim()) {
      toast.error("Name and phone are required");
      setStep(2);
      return;
    }
    if (!/^\d{10}$/.test(subject.phone)) {
      toast.error("Enter a valid 10-digit mobile number");
      setStep(2);
      return;
    }
    if (!subject.consent_given) {
      toast.error("Please confirm you have permission to request this verification");
      setStep(3);
      return;
    }

    if (docSettings.documents_required) {
      const missing = (docSettings.required_document_types || []).filter((t) => !docFiles[t]);
      if (missing.length) {
        toast.error("Please upload all required documents");
        setStep(2);
        return;
      }
    }

    setSubmitting(true);
    try {
      const payload = {
        package_id: selectedPackage.id,
        city_slug: citySlug,
        requester_name: [userData?.name, userData?.last_name].filter(Boolean).join(" ").trim() || undefined,
        requester_email: userData?.email,
        requester_phone: userData?.mobile,
        notes: notes || undefined,
        subject: {
          ...subject,
          consent_given: true,
        },
      };

      if (showReferenceForm) {
        const refs = referenceRows.filter(
          (r) => r.name?.trim() && /^\d{10}$/.test(String(r.mobile || "").replace(/\D/g, ""))
        );
        if (refs.length) {
          payload.references = refs.map((r) => ({
            reference_type: r.reference_type,
            name: r.name.trim(),
            relation: r.relation?.trim() || undefined,
            mobile: String(r.mobile).replace(/\D/g, "").slice(-10),
            email: r.email?.trim() || undefined,
            notes: r.notes?.trim() || undefined,
          }));
        }
      }

      if (showPoliceForm && isPoliceFormComplete(policeForm)) {
        payload.police_verification = {
          police_station_name: policeForm.police_station_name.trim(),
          city: policeForm.city.trim(),
          district: policeForm.district.trim(),
          applicant_mobile: String(policeForm.applicant_mobile).replace(/\D/g, "").slice(-10),
          reference_number: policeForm.reference_number?.trim() || undefined,
          customer_notes: policeForm.customer_notes?.trim() || undefined,
        };
      }

      const res = await submitTrustVerificationOrder(payload);
      const orderData = res?.data || null;

      if (orderData?.id && docSettings.documents_enabled) {
        const uploads = Object.entries(docFiles).filter(([, file]) => file);
        for (const [docType, file] of uploads) {
          await uploadTrustVerificationDocument(orderData.id, docType, file);
        }
      }

      setSuccessOrder(orderData);
      setPendingOrder(orderData);
      toast.success(res?.message || "Request submitted");
      if (cashfreeActive && res?.data?.payment_status === "pending") {
        setStep(4);
      } else {
        setStep(5);
      }
    } catch (e) {
      toast.error(e?.response?.data?.message || "Failed to submit request");
    } finally {
      setSubmitting(false);
    }
  };

  const breadcrumbTitle = resolvedPageTitle;

  return (
    <TrustVerificationSiteLayout>
      <MetaData
        title={`${breadcrumbTitle} | Sukoon Homes`}
        description={copy.lead}
        pageName={router?.asPath || `/verification`}
      />
      <NewBreadcrumb
        title={breadcrumbTitle}
        subtitle={copy.lead}
        items={[
          { href: `/verification?lang=${lang || "en"}`, label: "Verification" },
          { href: router?.asPath, label: orderType === "owner" ? "Owner" : "Tenant" },
        ]}
      />

      <TrustVerificationPremiumShell>
        <div className={tv.section}>
          <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <span className={tv.badge}>{cityLabel} · Online pay · Email report</span>
              <div className="mt-4">
                <VerificationTrustBadges />
              </div>
            </div>
            <div className="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center">
              <MyVerificationOrdersLink lang={lang || "en"} variant="compact" />
              <div className="flex min-w-0 gap-2">
                <a
                  href={verificationWizardPath("tenant", citySlug, lang || "en")}
                  className={cn(
                    tv.btnSecondary,
                    "min-w-0 flex-1 whitespace-nowrap px-3 text-center text-xs sm:flex-none sm:px-5 sm:text-sm",
                    orderType === "tenant" && "!border-[#B89A4A] !bg-[rgba(184,154,74,0.1)] !text-[#B89A4A]"
                  )}
                >
                  <span className="sm:hidden">Landlords</span>
                  <span className="hidden sm:inline">For landlords</span>
                </a>
                <a
                  href={verificationWizardPath("owner", citySlug, lang || "en")}
                  className={cn(
                    tv.btnSecondary,
                    "min-w-0 flex-1 whitespace-nowrap px-3 text-center text-xs sm:flex-none sm:px-5 sm:text-sm",
                    orderType === "owner" && "!border-[#B89A4A] !bg-[rgba(184,154,74,0.1)] !text-[#B89A4A]"
                  )}
                >
                  <span className="sm:hidden">Tenants</span>
                  <span className="hidden sm:inline">For tenants</span>
                </a>
              </div>
            </div>
          </div>

          <div className="grid gap-6 xl:grid-cols-5">
            <div className="xl:col-span-3">
              <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 className={cn(tv.heading, "text-xl")}>Select package</h2>
                {!loading && packages.length > 0 && (
                  <SampleReportTrigger
                    reportType={orderType}
                    citySlug={citySlug}
                    packageId={selectedPackageId}
                    source="wizard"
                    variant="link"
                  />
                )}
              </div>
              {city.unknown ? (
                <VerificationEmptyState
                  title={`Not available in ${cityLabel}`}
                  description="Choose another city from the verification hub."
                  action={
                    <a href={`/verification?lang=${lang || "en"}`} className={tv.btnPrimary}>
                      See available cities
                    </a>
                  }
                />
              ) : loading ? (
                <p className={tv.subheading}>Loading packages…</p>
              ) : packages.length === 0 ? (
                <VerificationEmptyState
                  title="No packages yet"
                  description={`Packages for ${cityLabel} will appear here when configured.`}
                />
              ) : (
                <div className="grid gap-4 md:grid-cols-2">
                  {packages.map((pkg) => {
                    const recommended = !!pkg.slug?.includes("standard");
                    return (
                      <VerificationPackageCard
                        key={pkg.id}
                        name={pkg.name}
                        price={formatInr(pkg.price)}
                        deliveryHours={pkg.delivery_hours}
                        features={pkg.features || []}
                        selected={selectedPackageId === pkg.id}
                        recommended={recommended}
                        onSelect={() => setSelectedPackageId(pkg.id)}
                        showSampleReport
                        sampleReportType={orderType}
                        citySlug={citySlug}
                        packageId={pkg.id}
                      />
                    );
                  })}
                </div>
              )}
            </div>

            <div className="xl:col-span-2">
              <TrustCard>
                <VerificationProgressStepper
                  currentStep={step}
                  totalSteps={5}
                  labels={["Package", "Details", "Review", "Pay", "Done"]}
                />

                {step === 1 && (
                  <div>
                    <h3 className={cn(tv.heading, "text-base")}>Step 1 — Package</h3>
                    <p className={cn(tv.subheading, "mt-2 text-sm")}>
                      Selected: <strong className={tv.strong}>{selectedPackage?.name || "—"}</strong> (
                      {formatInr(selectedPackage?.price)})
                    </p>
                    <p className={cn(tv.muted, "mt-2")}>
                      {cashfreeActive
                        ? "Pay securely online with Cashfree after you submit, or our team can assist offline."
                        : "Submit your request and our team will contact you for payment."}
                    </p>
                    <button
                      type="button"
                      className={cn(tv.btnPrimary, "mt-4 w-full")}
                      onClick={() => setStep(2)}
                      disabled={!selectedPackage}
                    >
                      Continue
                    </button>
                  </div>
                )}

                {step === 2 && (
                  <div>
                    <h3 className={cn(tv.heading, "text-base")}>Step 2 — {copy.subjectLabel} details</h3>
                    <div className="mt-3 space-y-3">
                      <input className={tv.input} placeholder="Full name *" value={subject.full_name} onChange={(e) => setSubject({ ...subject, full_name: e.target.value })} />
                      <div className="relative">
                        <input
                          className={cn(tv.input, subject.phone ? "pr-16" : "")}
                          type="tel"
                          inputMode="numeric"
                          autoComplete="tel"
                          maxLength={10}
                          placeholder="Mobile *"
                          value={subject.phone}
                          onChange={(e) => handleSubjectPhoneChange(e.target.value)}
                        />
                        {subject.phone ? (
                          <button
                            type="button"
                            className="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 text-xs font-medium text-[#087C7C] hover:bg-[rgba(8,124,124,0.08)]"
                            onClick={clearSubjectPhone}
                            aria-label="Clear mobile number"
                          >
                            Clear
                          </button>
                        ) : null}
                      </div>
                      <input className={tv.input} placeholder="Email" value={subject.email} onChange={(e) => setSubject({ ...subject, email: e.target.value })} />
                      <input className={tv.input} placeholder="Current address" value={subject.current_address} onChange={(e) => setSubject({ ...subject, current_address: e.target.value })} />
                      {orderType === "owner" && (
                        <input className={tv.input} placeholder="Property address *" value={subject.property_address} onChange={(e) => setSubject({ ...subject, property_address: e.target.value })} />
                      )}
                      <div className="grid grid-cols-2 gap-2">
                        <select className={tv.input} value={subject.id_type} onChange={(e) => setSubject({ ...subject, id_type: e.target.value })}>
                          <option>Aadhaar</option>
                          <option>PAN</option>
                          <option>Passport</option>
                        </select>
                        <input className={tv.input} placeholder="ID number" value={subject.id_number} onChange={(e) => setSubject({ ...subject, id_number: e.target.value })} />
                      </div>
                    </div>
                    <VerificationDocumentFields
                      variant="premium"
                      enabled={docSettings.documents_enabled}
                      requiredTypes={docSettings.required_document_types}
                      labels={docSettings.document_type_labels}
                      files={docFiles}
                      onChange={(type, file) => setDocFiles((prev) => ({ ...prev, [type]: file }))}
                    />
                    <div className="mt-4 flex gap-2">
                      <button type="button" className={cn(tv.btnSecondary, "flex-1")} onClick={() => setStep(1)}>Back</button>
                      <button type="button" className={cn(tv.btnPrimary, "flex-1")} onClick={() => setStep(3)}>Review</button>
                    </div>
                  </div>
                )}

                {step === 3 && (
                  <div>
                    <h3 className={cn(tv.heading, "text-base")}>Step 3 — Review</h3>
                    <div className={cn("mt-3 space-y-2 text-sm", tv.subheading)}>
                      <div className={cn("flex justify-between border-b py-2", tv.divider)}><span>Package</span><strong className={tv.accent}>{selectedPackage?.name}</strong></div>
                      <div className={cn("flex justify-between border-b py-2", tv.divider)}><span>Amount</span><strong className={tv.strong}>{formatInr(selectedPackage?.price)}</strong></div>
                      <div className={cn("flex justify-between border-b py-2", tv.divider)}><span>{copy.subjectLabel}</span><strong className={tv.strong}>{subject.full_name}</strong></div>
                      <div className="flex justify-between py-2"><span>City</span><strong className={tv.strong}>{cityLabel}</strong></div>
                    </div>
                    <textarea className={cn(tv.input, "mt-3")} rows={2} placeholder="Notes (optional)" value={notes} onChange={(e) => setNotes(e.target.value)} />
                    {showReferenceForm ? (
                      <ReferenceDetailsForm
                        className="mt-4"
                        value={referenceRows}
                        onChange={setReferenceRows}
                      />
                    ) : null}
                    {showPoliceForm ? (
                      <PoliceVerificationDetailsForm
                        className="mt-4"
                        value={policeForm}
                        onChange={setPoliceForm}
                        title={wiz.police_verification_title || "Police verification (optional)"}
                        description={
                          wiz.police_verification_description ||
                          "If you have already applied on the Rajasthan Police portal, you may share details now. You can also complete this later from My verification orders."
                        }
                      />
                    ) : null}
                    <VerificationConsentCheckbox
                      className="mt-4"
                      checked={subject.consent_given}
                      onChange={(checked) => setSubject({ ...subject, consent_given: checked })}
                      text={wiz.consent_checkbox_text}
                    />
                    <VerificationReportDisclaimer
                      className="mt-4"
                      text={content.report?.disclaimer}
                    />
                    <VerificationLegalLinks lang={lang || "en"} returnTo={policyReturnTo} className="mt-4" />
                    <div className="mt-4 flex gap-2">
                      <button type="button" className={cn(tv.btnSecondary, "flex-1")} onClick={() => setStep(2)}>Back</button>
                      <button type="button" className={cn(tv.btnPrimary, "flex-1")} disabled={submitting} onClick={handleSubmit}>
                        {submitting ? "Submitting…" : copy.cta}
                      </button>
                    </div>
                  </div>
                )}

                {step === 4 && pendingOrder && cashfreeActive && pendingOrder.payment_status !== "paid" && (
                  <div>
                    <h3 className={cn(tv.heading, "text-base")}>Step 4 — Pay online</h3>
                    <p className={cn(tv.subheading, "mt-2 text-sm")}>
                      Order <strong className={tv.strong}>{pendingOrder.order_number}</strong> · {formatInr(pendingOrder.amount)}
                    </p>
                    <p className={cn(tv.muted, "mt-2")}>Complete payment to start verification. You can also pay later from My verification orders.</p>
                    <VerificationReportDisclaimer className="mt-4" />
                    <button type="button" className={cn(tv.btnPrimary, "mt-4 w-full")} disabled={paying} onClick={handlePayOnline}>
                      {paying ? "Opening Cashfree…" : "Pay with Cashfree"}
                    </button>
                    <button type="button" className={cn(tv.btnSecondary, "mt-3 w-full")} onClick={() => { setSuccessOrder(pendingOrder); setStep(5); }}>
                      Skip for now
                    </button>
                  </div>
                )}

                {step === 5 && successOrder && (
                  <VerificationSuccessScreen
                    title={successOrder.payment_status === "paid" ? "Payment confirmed" : "Request received"}
                    orderNumber={successOrder.order_number}
                    message={
                      successOrder.payment_status === "paid"
                        ? `Our ${cityLabel} team will email your PDF report within ${selectedPackage?.delivery_hours || 48}–72 hours.`
                        : "Our team will contact you for payment and email your PDF report when ready."
                    }
                    primaryHref={`/my-verification-orders?lang=${lang || "en"}`}
                    lang={lang || "en"}
                    returnTo={policyReturnTo}
                    className="!border-0 !bg-transparent !p-0 !shadow-none"
                  />
                )}
              </TrustCard>
            </div>
          </div>
        </div>
      </TrustVerificationPremiumShell>

      {showLogin && <LoginModal showLogin={showLogin} setShowLogin={setShowLogin} />}
    </TrustVerificationSiteLayout>
  );
}
