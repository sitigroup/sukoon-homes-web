import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  MdApartment,
  MdAssignment,
  MdChevronLeft,
  MdChevronRight,
  MdGavel,
  MdHomeWork,
  MdLocalPolice,
  MdOutlineVerifiedUser,
  MdPersonSearch,
  MdPlace,
  MdStorefront,
} from "react-icons/md";

import {
  PageContainer,
  SukoonBadge,
  SukoonButton,
  SukoonCard,
  SukoonFeatureBadge,
  SukoonProcessTimeline,
  SukoonQuickActionCard,
  SukoonSectionHeader,
} from "@/design-system";
import { cn } from "@/lib/utils";

import { resolveHomepageSectionVisibility } from "./homepageSectionVisibility";

const HP_CONTAINER = "max-w-6xl";

function PreviewSection({ title, subtitle, action, children }) {
  return (
    <section className="min-w-0 space-y-sukoon-6">
      <SukoonSectionHeader title={title} subtitle={subtitle} action={action} />
      {children}
    </section>
  );
}

function PropertyImagePlaceholder({ variant = "villa", className }) {
  return (
    <div
      className={cn(
        "relative overflow-hidden bg-sukoon-page",
        className,
      )}
      aria-hidden
    >
      <div className="absolute inset-0 bg-sukoon-gold-soft/25" />
      <div className="absolute inset-x-sukoon-6 bottom-sukoon-6 top-sukoon-8 rounded-sukoon-sm border border-sukoon-border bg-sukoon-background shadow-sukoon-sm">
        {variant === "villa" ? (
          <svg className="h-full w-full p-sukoon-6" viewBox="0 0 200 120" fill="none">
            <path
              d="M24 88V52L60 28L96 52V88H24Z"
              className="fill-sukoon-page stroke-sukoon-graphite/20"
              strokeWidth="1.5"
            />
            <rect x="44" y="64" width="16" height="24" rx="1" className="fill-sukoon-gold-soft/60 stroke-sukoon-border" />
            <path d="M60 28L60 18H84L84 36" className="stroke-sukoon-graphite/25" strokeWidth="1.5" />
          </svg>
        ) : null}
        {variant === "apartment" ? (
          <svg className="h-full w-full p-sukoon-6" viewBox="0 0 200 120" fill="none">
            <rect x="40" y="24" width="120" height="72" rx="3" className="fill-sukoon-page stroke-sukoon-graphite/20" strokeWidth="1.5" />
            <rect x="56" y="40" width="20" height="14" rx="1" className="fill-sukoon-background stroke-sukoon-border" />
            <rect x="84" y="40" width="20" height="14" rx="1" className="fill-sukoon-background stroke-sukoon-border" />
            <rect x="112" y="40" width="20" height="14" rx="1" className="fill-sukoon-background stroke-sukoon-border" />
            <rect x="56" y="62" width="20" height="14" rx="1" className="fill-sukoon-background stroke-sukoon-border" />
            <rect x="84" y="62" width="20" height="14" rx="1" className="fill-sukoon-background stroke-sukoon-border" />
            <rect x="112" y="62" width="20" height="14" rx="1" className="fill-sukoon-background stroke-sukoon-border" />
            <rect x="92" y="82" width="16" height="14" rx="1" className="fill-sukoon-gold-soft/50 stroke-sukoon-border" />
          </svg>
        ) : null}
        {variant === "commercial" ? (
          <svg className="h-full w-full p-sukoon-6" viewBox="0 0 200 120" fill="none">
            <rect x="32" y="48" width="136" height="44" rx="2" className="fill-sukoon-page stroke-sukoon-graphite/20" strokeWidth="1.5" />
            <rect x="48" y="60" width="28" height="20" rx="1" className="fill-sukoon-background stroke-sukoon-border" />
            <rect x="86" y="60" width="28" height="20" rx="1" className="fill-sukoon-background stroke-sukoon-border" />
            <rect x="124" y="60" width="28" height="20" rx="1" className="fill-sukoon-background stroke-sukoon-border" />
            <path d="M32 92H168" className="stroke-sukoon-graphite/15" strokeWidth="2" strokeDasharray="6 4" />
            <rect x="72" y="32" width="56" height="12" rx="2" className="fill-sukoon-gold-soft/50 stroke-sukoon-border" />
          </svg>
        ) : null}
      </div>
    </div>
  );
}

const WHY_BENEFITS = [
  { key: "hpWhy1", descKey: "hpWhy1Desc", icon: MdHomeWork },
  { key: "hpWhy2", descKey: "hpWhy2Desc", icon: MdPersonSearch },
  { key: "hpWhy3", descKey: "hpWhy3Desc", icon: MdAssignment },
  { key: "hpWhy4", descKey: "hpWhy4Desc", icon: MdLocalPolice },
  { key: "hpWhy5", descKey: "hpWhy5Desc", icon: MdPlace },
];

const SCROLL_TRACK_CLASS =
  "flex w-full max-w-full touch-pan-x flex-nowrap overflow-x-auto scroll-smooth snap-x snap-mandatory [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden";

function useHorizontalScrollTrack(itemCount) {
  const trackRef = useRef(null);
  const frameRef = useRef(null);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(false);

  const updateScrollState = useCallback(() => {
    const el = trackRef.current;
    if (!el) return;
    const nextLeft = el.scrollLeft > 4;
    const nextRight = el.scrollLeft + el.clientWidth < el.scrollWidth - 4;
    setCanScrollLeft((prev) => (prev === nextLeft ? prev : nextLeft));
    setCanScrollRight((prev) => (prev === nextRight ? prev : nextRight));
  }, []);

  const scheduleScrollStateUpdate = useCallback(() => {
    if (frameRef.current !== null) return;
    frameRef.current = requestAnimationFrame(() => {
      frameRef.current = null;
      updateScrollState();
    });
  }, [updateScrollState]);

  useEffect(() => {
    updateScrollState();
    const el = trackRef.current;
    if (!el) return undefined;
    const observer = new ResizeObserver(scheduleScrollStateUpdate);
    observer.observe(el);
    el.addEventListener("scroll", scheduleScrollStateUpdate, { passive: true });
    return () => {
      observer.disconnect();
      el.removeEventListener("scroll", scheduleScrollStateUpdate);
      if (frameRef.current !== null) {
        cancelAnimationFrame(frameRef.current);
      }
    };
  }, [itemCount, scheduleScrollStateUpdate, updateScrollState]);

  const scrollByStep = useCallback((direction, cardSelector) => {
    const el = trackRef.current;
    if (!el) return;
    const card = el.querySelector(cardSelector);
    const gap = 16;
    const step = card ? card.getBoundingClientRect().width + gap : el.clientWidth * 0.85;
    el.scrollBy({ left: direction * step, behavior: "smooth" });
  }, []);

  return { trackRef, canScrollLeft, canScrollRight, scrollByStep };
}

function HorizontalScrollCarousel({
  itemCount,
  cardSelector,
  prevLabel,
  nextLabel,
  cardClassName,
  children,
  showMobileHint = true,
  showArrows = true,
  gapClass = "gap-sukoon-4",
}) {
  const { trackRef, canScrollLeft, canScrollRight, scrollByStep } = useHorizontalScrollTrack(itemCount);

  const arrowButtonClass =
    "absolute top-1/2 z-10 hidden h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full border border-sukoon-border/80 bg-sukoon-background/90 text-sukoon-graphite shadow-sukoon-sm backdrop-blur-[2px] transition-colors hover:border-sukoon-gold/40 hover:text-sukoon-gold lg:flex";

  return (
    <div className="relative min-w-0 w-full">
      {showArrows && canScrollLeft ? (
        <>
          <div
            className="pointer-events-none absolute inset-y-0 left-0 z-[1] hidden w-7 bg-gradient-to-r from-sukoon-page/95 to-transparent lg:block"
            aria-hidden
          />
          <button
            type="button"
            onClick={() => scrollByStep(-1, cardSelector)}
            aria-label={prevLabel}
            className={cn(arrowButtonClass, "left-1")}
          >
            <MdChevronLeft className="h-4 w-4" aria-hidden />
          </button>
        </>
      ) : null}
      {showArrows && canScrollRight ? (
        <>
          <div
            className="pointer-events-none absolute inset-y-0 right-0 z-[1] hidden w-7 bg-gradient-to-l from-sukoon-page/95 to-transparent lg:block"
            aria-hidden
          />
          <button
            type="button"
            onClick={() => scrollByStep(1, cardSelector)}
            aria-label={nextLabel}
            className={cn(arrowButtonClass, "right-1")}
          >
            <MdChevronRight className="h-4 w-4" aria-hidden />
          </button>
        </>
      ) : null}
      <ul ref={trackRef} className={cn(SCROLL_TRACK_CLASS, gapClass)}>
        {children(cardClassName)}
      </ul>
      {showMobileHint && canScrollRight ? (
        <p className="mt-sukoon-2 text-center text-sukoon-caption text-sukoon-graphite-muted lg:hidden">
          Swipe for more →
        </p>
      ) : null}
    </div>
  );
}

function PropertyPreviewCard({ prop, t }) {
  return (
    <SukoonCard variant="default" padding="none" className="h-full overflow-hidden">
      <div className="relative aspect-[16/10]">
        <PropertyImagePlaceholder variant={prop.imageVariant} className="absolute inset-0" />
        <div className="absolute left-sukoon-3 top-sukoon-3">
          <SukoonBadge variant={prop.badgeVariant || "verified"}>{prop.badge}</SukoonBadge>
        </div>
      </div>
      <div className="space-y-sukoon-3 p-sukoon-6">
        <h3 className="text-sukoon-h3 text-sukoon-graphite">{prop.title}</h3>
        <p className="text-sukoon-body-sm text-sukoon-graphite-muted">
          {prop.area} · {prop.city}
        </p>
        <div className="flex items-end justify-between gap-sukoon-3 border-t border-sukoon-border pt-sukoon-4">
          <span className="text-sukoon-h3 font-semibold tabular-nums text-sukoon-graphite">{prop.price}</span>
          <SukoonButton variant="ghost" size="sm">
            {t("hpViewProperty")}
          </SukoonButton>
        </div>
      </div>
    </SukoonCard>
  );
}

function PropertyCardsCarousel({ properties, t, visibleLg = 4 }) {
  const cardBasisLg =
    visibleLg === 4
      ? "lg:flex-[0_0_calc((100%-3*1rem)/4)]"
      : "lg:flex-[0_0_calc((100%-2*1rem)/3)]";

  return (
    <HorizontalScrollCarousel
      itemCount={properties.length}
      cardSelector="[data-property-card]"
      prevLabel="Previous properties"
      nextLabel="Next properties"
      cardClassName={cn(
        "flex-[0_0_88%] snap-start sm:flex-[0_0_calc(50%-0.5rem)]",
        cardBasisLg,
      )}
    >
      {(cardClassName) =>
        properties.map((prop) => (
          <li key={prop.id} data-property-card className={cardClassName}>
            <PropertyPreviewCard prop={prop} t={t} />
          </li>
        ))
      }
    </HorizontalScrollCarousel>
  );
}

function WhyBenefitsCarousel({ items, t }) {
  return (
    <HorizontalScrollCarousel
      itemCount={items.length}
      cardSelector="[data-why-card]"
      prevLabel="Previous benefits"
      nextLabel="Next benefits"
      showMobileHint={false}
      gapClass="gap-sukoon-3"
      cardClassName="flex-[0_0_82%] snap-start sm:flex-[0_0_calc(50%-0.375rem)] lg:flex-[0_0_calc((100%-2*0.75rem)/3)]"
    >
      {(cardClassName) =>
        items.map((item) => {
          const Icon = item.icon;
          return (
            <li key={item.key} data-why-card className={cardClassName}>
              <div className="flex h-full items-start gap-sukoon-3 rounded-sukoon-card border border-sukoon-border-strong bg-sukoon-background p-sukoon-3 shadow-sukoon-sm">
                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-sukoon-input bg-sukoon-graphite text-sukoon-on-primary">
                  <Icon className="h-[18px] w-[18px]" aria-hidden />
                </div>
                <div className="min-w-0">
                  <p className="text-sukoon-body-sm font-semibold leading-snug text-sukoon-graphite">
                    {t(item.key)}
                  </p>
                  <p className="mt-sukoon-1 text-sukoon-caption leading-snug text-sukoon-graphite-muted">
                    {t(item.descKey)}
                  </p>
                </div>
              </div>
            </li>
          );
        })
      }
    </HorizontalScrollCarousel>
  );
}

export default function HomepagePreviewContent({
  t,
  shellT,
  onExplore,
  onVerify,
  sectionVisibility,
}) {
  const visibility = resolveHomepageSectionVisibility(sectionVisibility);
  const showTrustServices = visibility.rentalAgreements || visibility.policeVerification;
  const showBothTrustServices = visibility.rentalAgreements && visibility.policeVerification;

  const properties = useMemo(
    () => [
      { id: "fp-1", title: t("hpProp1Title"), area: t("hpProp1Area"), city: t("hpProp1City"), price: t("hpProp1Price"), badge: t("hpBadgeVerified"), imageVariant: "villa" },
      { id: "fp-2", title: t("hpProp2Title"), area: t("hpProp2Area"), city: t("hpProp2City"), price: t("hpProp2Price"), badge: t("hpBadgeFeatured"), badgeVariant: "info", imageVariant: "apartment" },
      { id: "fp-3", title: t("hpProp3Title"), area: t("hpProp3Area"), city: t("hpProp3City"), price: t("hpProp3Price"), badge: t("hpBadgeVerified"), imageVariant: "commercial" },
      { id: "fp-4", title: t("hpProp4Title"), area: t("hpProp4Area"), city: t("hpProp4City"), price: t("hpProp4Price"), badge: t("hpBadgeVerified"), imageVariant: "apartment" },
      { id: "fp-5", title: t("hpProp5Title"), area: t("hpProp5Area"), city: t("hpProp5City"), price: t("hpProp5Price"), badge: t("hpBadgeFeatured"), badgeVariant: "info", imageVariant: "apartment" },
      { id: "fp-6", title: t("hpProp6Title"), area: t("hpProp6Area"), city: t("hpProp6City"), price: t("hpProp6Price"), badge: t("hpBadgeVerified"), imageVariant: "villa" },
    ],
    [t],
  );

  const areaPropertyGroups = useMemo(
    () => [
      {
        id: "mahaveer-nagar",
        title: shellT("shAreaMahaveerNagar"),
        subtitle: t("hpAreaMahaveerSub"),
        properties: [0, 4, 0, 4].map((index, slot) => ({ ...properties[index], id: `mahaveer-${slot}` })),
      },
      {
        id: "ratanada",
        title: t("hpProp2Area"),
        subtitle: t("hpAreaRatanadaSub"),
        properties: [1, 5, 1, 5].map((index, slot) => ({ ...properties[index], id: `ratanada-${slot}` })),
      },
      {
        id: "mansarovar",
        title: t("hpProp3Area"),
        subtitle: t("hpAreaMansarovarSub"),
        properties: [2, 3, 2, 3].map((index, slot) => ({ ...properties[index], id: `mansarovar-${slot}` })),
      },
      {
        id: "hiran-magri",
        title: t("hpProp4Area"),
        subtitle: t("hpAreaHiranMagriSub"),
        properties: [3, 0, 3, 0].map((index, slot) => ({ ...properties[index], id: `hiran-${slot}` })),
      },
    ],
    [properties, shellT, t],
  );

  const viewAllAction = (
    <SukoonButton variant="ghost" size="sm" onClick={onExplore}>
      {t("hpViewAll")}
    </SukoonButton>
  );

  const quickActions = [
    { icon: MdHomeWork, title: t("hpQuickBuy"), description: t("hpQuickBuyDesc") },
    { icon: MdApartment, title: t("hpQuickRent"), description: t("hpQuickRentDesc") },
    { icon: MdStorefront, title: t("hpQuickList"), description: t("hpQuickListDesc") },
    { icon: MdPersonSearch, title: t("hpQuickTenant"), description: t("hpQuickTenantDesc"), premium: true },
    { icon: MdOutlineVerifiedUser, title: t("hpQuickOwner"), description: t("hpQuickOwnerDesc"), premium: true },
    { icon: MdAssignment, title: t("hpQuickAgreement"), description: t("hpQuickAgreementDesc") },
    { icon: MdGavel, title: t("hpQuickPolice"), description: t("hpQuickPoliceDesc") },
  ];

  const trustJourneySteps = useMemo(() => {
    const labels = [
      t("hpTrustJourney1"),
      t("hpTrustJourney2"),
      t("hpTrustJourney3"),
      t("hpTrustJourney4"),
      t("hpTrustJourney5"),
    ];
    const statuses = ["completed", "completed", "completed", "completed", "active"];

    return labels.map((label, index) => ({
      key: `j${index + 1}`,
      label,
      status: statuses[index],
    }));
  }, [t]);

  const cities = [
    { city: shellT("shAreaBarmer"), description: t("hpCityBarmerDesc"), listings: t("hpCityBarmerListings") },
    { city: shellT("shAreaJodhpur"), description: t("hpCityJodhpurDesc"), listings: t("hpCityJodhpurListings") },
    { city: shellT("shAreaJaipur"), description: t("hpCityJaipurDesc"), listings: t("hpCityJaipurListings") },
    { city: shellT("shAreaUdaipur"), description: t("hpCityUdaipurDesc"), listings: t("hpCityUdaipurListings") },
  ];

  return (
    <main className="bg-sukoon-page">
      <PageContainer className={HP_CONTAINER}>
        <div className="space-y-sukoon-12 py-sukoon-10">
          {visibility.quickActions ? (
            <PreviewSection title={t("hpSectionQuickActions")} subtitle={t("hpSectionQuickActionsSub")}>
              <div className="grid grid-cols-1 gap-sukoon-2 sm:grid-cols-2 lg:grid-cols-4">
                {quickActions.map((action) => (
                  <SukoonQuickActionCard
                    key={action.title}
                    compact
                    className="w-full"
                    {...action}
                    onClick={() => {}}
                  />
                ))}
              </div>
            </PreviewSection>
          ) : null}

          {visibility.featuredProperties ? (
            <PreviewSection
              title={t("hpSectionFeaturedProperties")}
              subtitle={t("hpSectionFeaturedPropertiesSub")}
              action={viewAllAction}
            >
              <PropertyCardsCarousel properties={properties} t={t} />
            </PreviewSection>
          ) : null}

          {visibility.areaProperties ? (
            <section className="min-w-0 space-y-sukoon-12">
              {areaPropertyGroups.map((group) => (
                <PreviewSection
                  key={group.id}
                  title={group.title}
                  subtitle={group.subtitle}
                  action={viewAllAction}
                >
                  <PropertyCardsCarousel properties={group.properties} t={t} />
                </PreviewSection>
              ))}
            </section>
          ) : null}

          {visibility.featuredProjects ? (
            <PreviewSection title={t("hpSectionProjects")} subtitle={t("hpSectionProjectsSub")}>
              <div className="grid gap-sukoon-6 md:grid-cols-3">
                {[
                  {
                    title: t("hpProj1Title"),
                    location: t("hpProj1Location"),
                    status: t("hpProj1Status"),
                    statusVariant: "pending",
                    units: t("hpProj1Units"),
                    imageVariant: "villa",
                  },
                  {
                    title: t("hpProj2Title"),
                    location: t("hpProj2Location"),
                    status: t("hpProj2Status"),
                    statusVariant: "success",
                    units: t("hpProj2Units"),
                    imageVariant: "apartment",
                  },
                  {
                    title: t("hpProj3Title"),
                    location: t("hpProj3Location"),
                    status: t("hpProj3Status"),
                    statusVariant: "info",
                    units: t("hpProj3Units"),
                    imageVariant: "commercial",
                  },
                ].map((project) => (
                  <SukoonCard key={project.title} variant="default" padding="none" className="overflow-hidden">
                    <div className="relative aspect-[16/10]">
                      <PropertyImagePlaceholder variant={project.imageVariant} className="absolute inset-0" />
                      <div className="absolute right-sukoon-3 top-sukoon-3">
                        <SukoonBadge variant={project.statusVariant}>{project.status}</SukoonBadge>
                      </div>
                    </div>
                    <div className="space-y-sukoon-3 p-sukoon-6">
                      <div className="flex items-start justify-between gap-sukoon-2">
                        <h3 className="text-sukoon-h3 text-sukoon-graphite">{project.title}</h3>
                        <SukoonBadge variant="info">{t("hpProjTagDevelopment")}</SukoonBadge>
                      </div>
                      <p className="text-sukoon-caption text-sukoon-graphite-muted">{project.units}</p>
                      <p className="text-sukoon-body-sm text-sukoon-graphite-muted">
                        {t("hpProjCommunity")} · {project.location}
                      </p>
                      <SukoonButton variant="secondary" size="sm">
                        {t("hpProjCta")}
                      </SukoonButton>
                    </div>
                  </SukoonCard>
                ))}
              </div>
            </PreviewSection>
          ) : null}

          {visibility.trustVerification ? (
            <PreviewSection title={t("hpSectionTrust")} subtitle={t("hpSectionTrustSub")}>
              <SukoonCard variant="default" padding="lg">
                <div className="lg:hidden">
                  <SukoonProcessTimeline dashboard steps={trustJourneySteps} />
                </div>
                <div className="hidden lg:block">
                  <SukoonProcessTimeline horizontal steps={trustJourneySteps} className="w-full" />
                </div>
              </SukoonCard>
            </PreviewSection>
          ) : null}

          {showTrustServices ? (
            <section className="space-y-sukoon-6">
              <div
                className={cn(
                  "grid gap-sukoon-6",
                  showBothTrustServices && "lg:grid-cols-2 lg:items-stretch",
                )}
              >
                {visibility.rentalAgreements ? (
                  <div className="flex flex-col gap-sukoon-6">
                    <SukoonSectionHeader
                      title={t("hpSectionAgreements")}
                      subtitle={t("hpSectionAgreementsSub")}
                    />
                    <SukoonCard variant="default" padding="md" className="flex flex-1 flex-col">
                      <SukoonFeatureBadge variant="agreementReady">
                        {shellT("shFooterBadgeAgreement")}
                      </SukoonFeatureBadge>
                      <p className="mt-sukoon-3 text-sukoon-body-sm leading-relaxed text-sukoon-graphite-muted">
                        {t("hpAgreementServiceDesc")}
                      </p>
                      <p className="mt-sukoon-2 text-sukoon-caption leading-relaxed text-sukoon-graphite-muted">
                        {t("hpAgreementServiceSub")}
                      </p>
                      <div className="mt-auto pt-sukoon-5">
                        <SukoonButton variant="primary" onClick={() => {}}>
                          {t("hpCreateAgreement")}
                        </SukoonButton>
                      </div>
                    </SukoonCard>
                  </div>
                ) : null}

                {visibility.policeVerification ? (
                  <div className="flex flex-col gap-sukoon-6">
                    <SukoonSectionHeader
                      title={t("hpSectionPolice")}
                      subtitle={t("hpSectionPoliceSub")}
                    />
                    <SukoonCard variant="default" padding="md" className="flex flex-1 flex-col">
                      <SukoonFeatureBadge variant="policeVerified">
                        {shellT("shFooterBadgePolice")}
                      </SukoonFeatureBadge>
                      <p className="mt-sukoon-3 text-sukoon-body-sm leading-relaxed text-sukoon-graphite-muted">
                        {t("hpPoliceServiceDesc")}
                      </p>
                      <p className="mt-sukoon-2 text-sukoon-caption leading-relaxed text-sukoon-graphite-muted">
                        {t("hpPoliceServiceSub")}
                      </p>
                      <div className="mt-auto pt-sukoon-5">
                        <SukoonButton variant="secondary" onClick={() => {}}>
                          {t("hpPoliceLearnMore")}
                        </SukoonButton>
                      </div>
                    </SukoonCard>
                  </div>
                ) : null}
              </div>
            </section>
          ) : null}

          {visibility.whySukoon ? (
            <PreviewSection title={t("hpSectionWhy")} subtitle={t("hpSectionWhySub")}>
              <WhyBenefitsCarousel items={WHY_BENEFITS} t={t} />
            </PreviewSection>
          ) : null}

          {visibility.popularCities ? (
            <PreviewSection title={t("hpSectionCities")} subtitle={t("hpSectionCitiesSub")}>
              <div className="grid gap-sukoon-6 sm:grid-cols-2 lg:grid-cols-4">
                {cities.map((city) => (
                  <SukoonCard key={city.city} variant="default" padding="md" className="flex h-full flex-col justify-between">
                    <div>
                      <h3 className="text-sukoon-h3 text-sukoon-graphite">{city.city}</h3>
                      <p className="mt-sukoon-2 text-sukoon-caption text-sukoon-graphite-muted">{city.listings}</p>
                      <p className="mt-sukoon-2 text-sukoon-body-sm text-sukoon-graphite-muted">{city.description}</p>
                    </div>
                    <SukoonButton variant="secondary" size="sm" className="mt-sukoon-5 w-full sm:w-auto">
                      {t("hpExploreCity")}
                    </SukoonButton>
                  </SukoonCard>
                ))}
              </div>
            </PreviewSection>
          ) : null}

          {visibility.finalCta ? (
            <PreviewSection title={t("hpSectionFinalCta")} subtitle={t("hpSectionFinalCtaSub")}>
              <SukoonCard variant="elevated" padding="lg">
                <p className="text-sukoon-body-sm text-sukoon-graphite-muted">{t("hpFinalCtaTrustLine")}</p>
                <div className="mt-sukoon-6 flex flex-wrap items-center gap-sukoon-4">
                  <SukoonButton variant="primary" size="lg" onClick={onExplore}>
                    {t("hpStartSearch")}
                  </SukoonButton>
                  <SukoonButton variant="secondary" size="lg" onClick={onVerify}>
                    {t("hpGetVerified")}
                  </SukoonButton>
                </div>
              </SukoonCard>
            </PreviewSection>
          ) : null}
        </div>
      </PageContainer>
    </main>
  );
}
