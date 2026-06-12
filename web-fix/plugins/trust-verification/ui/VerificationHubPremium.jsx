"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { getTrustVerificationPackages } from "../trustVerificationApi";
import { verificationWizardPath } from "../trustVerificationUtils";
import MyVerificationOrdersLink from "../MyVerificationOrdersLink";
import VerificationFaq from "./VerificationFaq";
import { useTrustVerificationContent } from "../useTrustVerificationContent";
import VerificationEmptyState from "./VerificationEmptyState";
import VerificationLegalLinks from "./VerificationLegalLinks";
import { verificationHubReturnPath } from "../verificationLegalNavigation";
import VerificationReportDisclaimer from "./VerificationReportDisclaimer";
import styles from "./trustVerificationPremium.module.css";
import { startingPriceParts } from "./trustVerificationHubPricing";
import SampleReportTrigger from "./SampleReportTrigger";

function cityServicesLabel(city) {
  const parts = [];
  if (city?.has_tenant_packages) parts.push("Tenant verification");
  if (city?.has_owner_packages) parts.push("Owner verification");
  return parts.join(" · ") || "Verification available";
}

function ServicePrice({ loading, helper, packages }) {
  if (loading) {
    return <span className={styles.servicePriceLabel}>{helper || "Loading pricing…"}</span>;
  }
  const parts = startingPriceParts(packages);
  if (!parts.amount) {
    return <span className={styles.servicePriceLabel}>{parts.label}</span>;
  }
  return (
    <>
      <span className={styles.servicePriceLabel}>{parts.label} </span>
      <span className={styles.servicePriceAmount}>{parts.amount}</span>
    </>
  );
}

export default function VerificationHubPremium({ cities = [], loading = false, lang = "en" }) {
  const { content } = useTrustVerificationContent();
  const hub = content.hub || {};

  const activeCities = useMemo(
    () => cities.filter((c) => c.has_tenant_packages || c.has_owner_packages),
    [cities]
  );

  const [selectedSlug, setSelectedSlug] = useState("");
  const [tenantPackages, setTenantPackages] = useState([]);
  const [ownerPackages, setOwnerPackages] = useState([]);
  const [pricingLoading, setPricingLoading] = useState(false);

  useEffect(() => {
    if (activeCities.length && !selectedSlug) {
      setSelectedSlug(activeCities[0].slug);
    }
  }, [activeCities, selectedSlug]);

  const displayCity = activeCities.find((c) => c.slug === selectedSlug) || activeCities[0];

  useEffect(() => {
    if (!displayCity?.slug) {
      setTenantPackages([]);
      setOwnerPackages([]);
      return;
    }

    let cancelled = false;
    setPricingLoading(true);

    const loads = [];
    if (displayCity.has_tenant_packages) {
      loads.push(
        getTrustVerificationPackages({ type: "tenant", city: displayCity.slug })
          .then((res) => res?.data || [])
          .catch(() => [])
      );
    } else {
      loads.push(Promise.resolve([]));
    }
    if (displayCity.has_owner_packages) {
      loads.push(
        getTrustVerificationPackages({ type: "owner", city: displayCity.slug })
          .then((res) => res?.data || [])
          .catch(() => [])
      );
    } else {
      loads.push(Promise.resolve([]));
    }

    Promise.all(loads).then(([tenant, owner]) => {
      if (cancelled) return;
      setTenantPackages(tenant);
      setOwnerPackages(owner);
      setPricingLoading(false);
    });

    return () => {
      cancelled = true;
    };
  }, [displayCity?.slug, displayCity?.has_tenant_packages, displayCity?.has_owner_packages]);

  const otherCities = activeCities.filter((c) => c.slug !== displayCity?.slug);

  return (
    <div className={styles.container}>
      <section className={styles.hero}>
        <div className={styles.heroTopRow}>
          <span className={styles.badge}>{hub.badge || "Sukoon Homes Trust Verification"}</span>
          <div className={styles.heroOrdersDesktop}>
            <MyVerificationOrdersLink lang={lang} variant="compact" />
          </div>
        </div>
        <h1 className={styles.heroTitle}>{hub.hero_title || "Background verification you can trust"}</h1>
        <p className={styles.heroSubtitle}>
          {hub.hero_subtitle ||
            "Verify tenants or property owners before you sign — city-wise packages, secure documents, and PDF reports delivered by email."}
        </p>

        <div className={styles.trustBadges}>
          {(Array.isArray(hub.trust_badges) && hub.trust_badges.length
            ? hub.trust_badges
            : [
                { icon: "🔒", label: "Secure verification" },
                { icon: "🛡️", label: "Privacy protected" },
                { icon: "✉️", label: "Report by email" },
              ]
          ).map((badge) => (
            <span key={badge.label} className={styles.trustBadge}>
              <span aria-hidden>{badge.icon}</span> {badge.label}
            </span>
          ))}
        </div>

        <div className={styles.heroOrdersMobile}>
          <MyVerificationOrdersLink lang={lang} variant="compact" fullWidth />
        </div>

        {!loading && activeCities.length > 0 && activeCities.length > 1 && (
          <div className={styles.heroToolbar}>
            <div>
              <p className={styles.cityLabel}>{hub.city_selector_helper || "Select city"}</p>
              <div className={styles.cityPills}>
                {activeCities.map((city) => (
                  <button
                    key={city.slug}
                    type="button"
                    className={`${styles.cityPill} ${
                      displayCity?.slug === city.slug ? styles.cityPillActive : ""
                    }`}
                    onClick={() => setSelectedSlug(city.slug)}
                  >
                    {city.label}
                  </button>
                ))}
              </div>
            </div>
          </div>
        )}
      </section>

      {loading && <p className={styles.loadingText}>Loading cities…</p>}

      {!loading && activeCities.length === 0 && (
        <VerificationEmptyState
          title={hub.empty_state_title || "Not available in your area yet"}
          description={
            hub.empty_state_description ||
            "We are expanding verification services to more cities. Check back soon or contact support."
          }
        />
      )}

      {!loading && displayCity && (
        <>
          <section>
            <h2 className={styles.sectionTitle}>{displayCity.label}</h2>
            <p className={styles.sectionSubtitle}>Choose verification type for {displayCity.label}</p>

            <div className={styles.serviceGrid}>
              {displayCity.has_tenant_packages && (
                <Link
                  href={verificationWizardPath("tenant", displayCity.slug, lang)}
                  className={`${styles.serviceCard} ${styles.serviceCardTenant}`}
                >
                  <span className={styles.serviceIcon} aria-hidden>
                    🏠
                  </span>
                  <h3 className={styles.serviceCardTitle}>{hub.tenant_card_title || "Tenant verification"}</h3>
                  <p className={styles.serviceCardDesc}>
                    {hub.tenant_card_description ||
                      "For landlords — verify a renter's identity and background before handing over keys."}
                  </p>
                  <p className={styles.servicePrice}>
                    <ServicePrice
                      loading={pricingLoading}
                      helper={hub.starting_price_helper}
                      packages={tenantPackages}
                    />
                  </p>
                  <span className={styles.serviceCta}>
                    Get started <span className={styles.serviceCtaArrow} aria-hidden>→</span>
                  </span>
                </Link>
              )}

              {displayCity.has_owner_packages && (
                <Link
                  href={verificationWizardPath("owner", displayCity.slug, lang)}
                  className={`${styles.serviceCard} ${styles.serviceCardOwner}`}
                >
                  <span className={styles.serviceIcon} aria-hidden>
                    🔑
                  </span>
                  <h3 className={styles.serviceCardTitle}>{hub.owner_card_title || "Owner verification"}</h3>
                  <p className={styles.serviceCardDesc}>
                    {hub.owner_card_description ||
                      "For tenants — confirm the landlord and property are genuine before you pay token or rent."}
                  </p>
                  <p className={styles.servicePrice}>
                    <ServicePrice
                      loading={pricingLoading}
                      helper={hub.starting_price_helper}
                      packages={ownerPackages}
                    />
                  </p>
                  <span className={styles.serviceCta}>
                    Get started <span className={styles.serviceCtaArrow} aria-hidden>→</span>
                  </span>
                </Link>
              )}
            </div>

            <div className={styles.sampleReportRow}>
              <p className={styles.sampleReportLabel}>
                {hub.sample_report_description ||
                  "See the kind of PDF report you receive after verification completes."}
              </p>
              <div className={styles.sampleReportActions}>
                {displayCity.has_tenant_packages && (
                  <SampleReportTrigger
                    reportType="tenant"
                    citySlug={displayCity.slug}
                    source="hub"
                  />
                )}
                {displayCity.has_owner_packages && (
                  <SampleReportTrigger
                    reportType="owner"
                    citySlug={displayCity.slug}
                    source="hub"
                    variant="secondary"
                  />
                )}
              </div>
            </div>
          </section>

          {otherCities.length > 0 && (
            <section className={styles.otherCities}>
              <h2 className={styles.otherCitiesTitle}>Other cities</h2>
              <div className={styles.cityCardGrid}>
                {otherCities.map((city) => (
                  <button
                    key={city.slug}
                    type="button"
                    className={styles.cityCard}
                    onClick={() => setSelectedSlug(city.slug)}
                  >
                    <p className={styles.cityCardName}>{city.label}</p>
                    <p className={styles.cityCardMeta}>{cityServicesLabel(city)}</p>
                  </button>
                ))}
              </div>
            </section>
          )}

          <VerificationFaq items={content.faq} />

          <footer className={styles.legalCard}>
            <p className={styles.legalLabel}>Legal &amp; disclaimer</p>
            <VerificationLegalLinks lang={lang} returnTo={verificationHubReturnPath(lang)} centered />
            <VerificationReportDisclaimer
              className={styles.legalDisclaimer}
              compact
              text={content.report?.disclaimer || hub.footer_disclaimer}
            />
          </footer>
        </>
      )}
    </div>
  );
}
