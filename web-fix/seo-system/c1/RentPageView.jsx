'use client';

import { useEffect, useRef, useState } from 'react';
import { useRouter } from 'next/router';
import MetaData from '@/components/meta/MetaData';
import {
  ShellPreviewFooter,
  ShellPreviewHeader,
  useShellT,
} from '@/design-system';
import { RentShellContainer } from '@/plugins/seo-engine/rentLayout';
import RentGa4Tracker from '@/plugins/seo-engine/RentGa4Tracker';
import {
  PopularSearches,
  RentAlertModal,
  RentContentBlock,
  RentHeroBand,
  RentInsightCard,
  RentLeadForm,
  RentLinkBlock,
  RentListingGrid,
  RentMapSection,
  RentNearbyPlaces,
  RentTrustStrip,
} from '@/plugins/seo-engine/RentPageComponents';

export default function RentPageView({
  payload,
  popularPaths = [],
  lang = 'en',
  structuredData = null,
  robots = 'index, follow',
  ga4 = null,
}) {
  const router = useRouter();
  const [activeLang, setActiveLang] = useState(lang);
  const shellT = useShellT(activeLang);

  const page = payload?.page || {};
  const listings = payload?.listings || [];
  const area = payload?.area || null;
  const thinListings = listings.length === 0;
  const gridRef = useRef(null);
  const [alertOpen, setAlertOpen] = useState(false);

  useEffect(() => {
    if (typeof router.query.lang === 'string') {
      setActiveLang(router.query.lang);
    } else if (lang) {
      setActiveLang(lang);
    }
  }, [router.query.lang, lang]);

  const switchLang = (next) => {
    setActiveLang(next);
    router.replace(
      { pathname: router.pathname, query: { ...router.query, lang: next } },
      undefined,
      { shallow: true },
    );
  };

  const scrollToListings = () => {
    gridRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  return (
    <div className="sukoon-ds min-h-screen overflow-x-hidden bg-sukoon-page">
      {ga4?.enabled && ga4?.measurementId ? (
        <RentGa4Tracker measurementId={ga4.measurementId} pagePath={page.path} />
      ) : null}

      <MetaData
        title={page.title}
        description={page.meta_description}
        pageName={`${page.path}?lang=${activeLang}`}
        robots={robots}
        structuredData={structuredData}
      />

      <div className="hidden lg:block">
        <ShellPreviewHeader
          t={shellT}
          lang={activeLang}
          onLangChange={switchLang}
          sticky
          elevated
          defaultMegaSection="rent"
        />
      </div>
      <div className="lg:hidden">
        <ShellPreviewHeader
          t={shellT}
          lang={activeLang}
          onLangChange={switchLang}
          variant="mobile"
          sticky
          elevated
        />
      </div>

      <main>
        <RentHeroBand
          page={page}
          area={area}
          stats={payload?.locality_stats?.current}
          breadcrumbs={payload?.breadcrumbs}
          onScrollListings={scrollToListings}
          onAlertClick={() => setAlertOpen(true)}
          thinListings={thinListings}
          lang={activeLang}
          t={shellT}
        />

        <div className="space-y-sukoon-12 py-sukoon-8">
          <RentTrustStrip listingCount={page.listing_count || 0} />

          {thinListings ? (
            <RentLeadForm
              sourcePath={page.path}
              area={area}
              formType="lead"
              title="Tell us what you need"
              subtitle="We will contact you when matching verified rentals are listed in this area."
            />
          ) : null}

          <RentInsightCard localityStats={payload?.locality_stats} />

          <RentListingGrid listings={listings} lang={activeLang} gridRef={gridRef} />

          {!thinListings ? null : (
            <RentShellContainer>
              <p className="text-center text-sukoon-body-sm text-sukoon-graphite-muted">
                No active listings match this search yet. Browse related areas below or submit your requirements above.
              </p>
            </RentShellContainer>
          )}

          <RentMapSection mapData={payload?.map} areaName={area?.area_name || 'Barmer'} />

          <RentNearbyPlaces places={payload?.nearby_places} />

          <RentAlertModal
            open={alertOpen}
            onClose={() => setAlertOpen(false)}
            sourcePath={page.path}
            area={area}
          />

          <RentLeadForm
            sourcePath={page.path}
            area={area}
            formType="lead"
            title="Can't find the right home?"
            subtitle="Share your requirements — 3 fields, no spam."
          />

          <RentContentBlock introHtml={page.intro_html} faqJson={page.faq_json} />

          <RentShellContainer className="space-y-sukoon-8">
            <RentLinkBlock title="Related areas" links={payload?.links?.siblings || []} lang={activeLang} />
            <RentLinkBlock title="More in this area" links={payload?.links?.children || []} lang={activeLang} />
            <RentLinkBlock title="Budget filters" links={payload?.links?.budget || []} lang={activeLang} />
          </RentShellContainer>

          <PopularSearches paths={popularPaths} currentPath={page.path} lang={activeLang} />
        </div>
      </main>

      <div className="hidden lg:block">
        <ShellPreviewFooter t={shellT} variant="desktop" />
      </div>
      <div className="lg:hidden">
        <ShellPreviewFooter t={shellT} variant="mobile" />
      </div>
    </div>
  );
}
