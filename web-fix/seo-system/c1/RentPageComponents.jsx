'use client';

import { useEffect, useRef, useState } from 'react';
import CustomLink from '@/components/context/CustomLink';
import {
  ShellSearchBar,
  SukoonBadge,
  SukoonButton,
  SukoonCard,
  SukoonInput,
  SukoonSectionHeader,
  SukoonStat,
  buttonVariants,
} from '@/design-system';
import { cn } from '@/lib/utils';
import {
  MdAssignment,
  MdHomeWork,
  MdOutlineVerifiedUser,
  MdPersonSearch,
  MdPlace,
} from 'react-icons/md';
import { RentShellContainer } from '@/plugins/seo-engine/rentLayout';
import { submitRentLead } from '@/plugins/seo-engine/rentLeadApi';
import { trackRentEvent } from '@/plugins/seo-engine/RentGa4Tracker';

export function formatInr(n) {
  const v = Number(n);
  if (!Number.isFinite(v)) return '';
  return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(v);
}

function areaHeadline(area, page) {
  const areaName = area?.area_name || 'Barmer';
  const city = area?.city_name || 'Barmer';
  return page?.h1 || `Verified Rental Homes in ${areaName}, ${city}`;
}

function RentBreadcrumbs({ crumbs = [], lang = 'en' }) {
  if (!crumbs.length) return null;
  return (
    <nav aria-label="Breadcrumb" className="mb-sukoon-4 text-sukoon-caption text-sukoon-graphite-muted">
      {crumbs.map((c, i) => (
        <span key={c.path || i}>
          {i > 0 ? <span className="mx-sukoon-1 text-sukoon-graphite-subtle">›</span> : null}
          {i < crumbs.length - 1 && c.path ? (
            <CustomLink href={`${c.path}?lang=${lang}`} className="transition-colors hover:text-sukoon-gold">
              {c.label}
            </CustomLink>
          ) : (
            <span className="font-medium text-sukoon-graphite">{c.label}</span>
          )}
        </span>
      ))}
    </nav>
  );
}

export function RentHeroBand({ page, area, stats, breadcrumbs = [], onScrollListings, onAlertClick, thinListings, lang = 'en', t }) {
  const count = page?.listing_count || 0;
  const minRent = stats?.min_rent;
  const heroImg = area?.hero_image_url || null;

  return (
    <section className="border-b border-sukoon-border bg-sukoon-page">
      <RentShellContainer className="py-sukoon-10 sm:py-sukoon-12">
        <div className={cn('grid gap-sukoon-8', heroImg ? 'lg:grid-cols-2 lg:items-center' : 'max-w-3xl')}>
          <div>
            <RentBreadcrumbs crumbs={breadcrumbs} lang={lang} />
            <p className="text-sukoon-caption font-semibold uppercase tracking-[0.14em] text-sukoon-gold">Rent</p>
            <h1 className="mt-sukoon-2 text-balance text-sukoon-h2 font-semibold text-sukoon-graphite">{areaHeadline(area, page)}</h1>
            {minRent ? (
              <p className="mt-sukoon-4 text-sukoon-h2 font-semibold tabular-nums text-sukoon-graphite">
                From {formatInr(minRent)}
                <span className="text-sukoon-body font-normal text-sukoon-graphite-muted">/mo</span>
              </p>
            ) : null}
            <p className="mt-sukoon-3 max-w-xl text-sukoon-body-sm leading-relaxed text-sukoon-graphite-muted">
              {thinListings
                ? 'Limited listings in this search — tell us what you need and we will notify you when matching verified homes are listed.'
                : `${count} verified rental ${count === 1 ? 'listing' : 'listings'} with online rent agreement and tenant KYC support.`}
            </p>
            {t ? (
              <div className="mt-sukoon-5 max-w-xl">
                <ShellSearchBar t={t} demoState="idle" interactive size="hero" />
              </div>
            ) : null}
            <div className="mt-sukoon-6 flex flex-wrap gap-sukoon-3">
              {count > 0 && !thinListings ? (
                <SukoonButton type="button" onClick={onScrollListings}>
                  See {count} Homes
                </SukoonButton>
              ) : null}
              <SukoonButton type="button" variant="secondary" onClick={onAlertClick}>
                Get Rental Alerts
              </SukoonButton>
            </div>
          </div>
          {heroImg ? (
            <SukoonCard variant="default" padding="none" className="overflow-hidden shadow-sukoon-card">
              <div className="relative aspect-[16/10]">
                <img src={heroImg} alt="" className="absolute inset-0 h-full w-full object-cover" loading="eager" />
                <div className="absolute left-sukoon-3 top-sukoon-3">
                  <SukoonBadge variant="verified">Verified area</SukoonBadge>
                </div>
              </div>
            </SukoonCard>
          ) : null}
        </div>
      </RentShellContainer>
    </section>
  );
}

const TRUST_ITEMS = [
  { icon: MdOutlineVerifiedUser, label: 'Verified Listings' },
  { icon: MdAssignment, label: 'Online Rent Agreement' },
  { icon: MdPersonSearch, label: 'Tenant KYC' },
  { icon: MdHomeWork, labelKey: 'homes' },
];

export function RentTrustStrip({ listingCount = 0 }) {
  return (
    <RentShellContainer>
      <div className="grid gap-sukoon-3 sm:grid-cols-2 lg:grid-cols-4">
        {TRUST_ITEMS.map(({ icon: Icon, label, labelKey }) => (
          <div
            key={label || labelKey}
            className="flex items-start gap-sukoon-3 rounded-sukoon-card border border-sukoon-border-strong bg-sukoon-background p-sukoon-4 shadow-sukoon-sm"
          >
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-sukoon-input bg-sukoon-graphite text-sukoon-on-primary">
              <Icon className="h-[18px] w-[18px]" aria-hidden />
            </div>
            <p className="text-sukoon-body-sm font-semibold leading-snug text-sukoon-graphite">
              {labelKey === 'homes' ? `${listingCount} Homes` : label}
            </p>
          </div>
        ))}
      </div>
    </RentShellContainer>
  );
}

function Sparkline({ series = [] }) {
  if (!series.length) return null;
  const values = series.map((s) => Number(s.avg_rent) || 0);
  const max = Math.max(...values, 1);
  const min = Math.min(...values);
  const w = 200;
  const h = 48;
  const pts = values
    .map((v, i) => {
      const x = (i / Math.max(values.length - 1, 1)) * w;
      const y = h - ((v - min) / Math.max(max - min, 1)) * (h - 8) - 4;
      return `${x},${y}`;
    })
    .join(' ');

  return (
    <svg viewBox={`0 0 ${w} ${h}`} className="h-12 w-full max-w-[220px]" aria-hidden>
      <polyline fill="none" stroke="currentColor" strokeWidth="2.5" className="text-sukoon-gold" points={pts} />
    </svg>
  );
}

export function RentInsightCard({ localityStats }) {
  const current = localityStats?.current;
  const series = localityStats?.series || [];
  if (!current) return null;

  return (
    <RentShellContainer>
      <section className="space-y-sukoon-6">
        <SukoonSectionHeader
          title="Rent insights"
          subtitle={current.period ? `Updated ${current.period}` : 'Local rent trends for this area'}
        />
        <SukoonCard variant="subtle" className="p-sukoon-6">
          <div className="grid gap-sukoon-6 sm:grid-cols-3">
            <SukoonStat label="Average" value={formatInr(current.avg_rent)} align="center" />
            <SukoonStat label="From" value={formatInr(current.min_rent)} align="center" />
            <SukoonStat label="Up to" value={formatInr(current.max_rent)} align="center" />
          </div>
          {series.length > 1 ? (
            <div className="mt-sukoon-6 border-t border-sukoon-border pt-sukoon-6">
              <p className="mb-sukoon-2 text-sukoon-caption font-medium uppercase tracking-wide text-sukoon-graphite-subtle">
                6-month average rent trend
              </p>
              <Sparkline series={series} />
            </div>
          ) : null}
        </SukoonCard>
      </section>
    </RentShellContainer>
  );
}

export default function RentListingCard({ listing, lang = 'en' }) {
  if (!listing?.slug_id) return null;
  const href = `/property-details/${listing.slug_id}/?lang=${lang}`;

  return (
    <SukoonCard variant="default" padding="none" className="h-full overflow-hidden">
      <div className="relative aspect-[16/10]">
        {listing.title_image ? (
          <CustomLink href={href} onClick={() => trackRentEvent('listing_click', { listing_id: listing.id })}>
            <img src={listing.title_image} alt="" className="absolute inset-0 h-full w-full object-cover" loading="lazy" />
          </CustomLink>
        ) : (
          <div className="absolute inset-0 bg-sukoon-page">
            <div className="absolute inset-0 bg-sukoon-gold-soft/25" aria-hidden />
          </div>
        )}
        {listing.verified ? (
          <div className="absolute left-sukoon-3 top-sukoon-3">
            <SukoonBadge variant="verified">Verified</SukoonBadge>
          </div>
        ) : null}
      </div>
      <div className="space-y-sukoon-3 p-sukoon-6">
        <h3 className="text-sukoon-h3 text-sukoon-graphite">
          <CustomLink href={href} onClick={() => trackRentEvent('listing_click', { listing_id: listing.id })} className="hover:text-sukoon-gold">
            {listing.title}
          </CustomLink>
        </h3>
        <p className="text-sukoon-body-sm text-sukoon-graphite-muted">
          {[listing.area, listing.city].filter(Boolean).join(' · ')}
          {listing.state ? ` · ${listing.state}` : ''}
        </p>
        <div className="flex items-end justify-between gap-sukoon-3 border-t border-sukoon-border pt-sukoon-4">
          {listing.price ? (
            <span className="text-sukoon-h3 font-semibold tabular-nums text-sukoon-graphite">
              {formatInr(listing.price)}
              <span className="text-sukoon-caption font-normal text-sukoon-graphite-muted"> / {listing.rent_duration || 'mo'}</span>
            </span>
          ) : (
            <span />
          )}
          <CustomLink href={href} className={buttonVariants({ variant: 'ghost', size: 'sm' })}>
            View details
          </CustomLink>
        </div>
      </div>
    </SukoonCard>
  );
}

export function RentListingGrid({ listings = [], lang = 'en', gridRef }) {
  if (!listings.length) return null;
  return (
    <RentShellContainer>
      <section ref={gridRef} className="scroll-mt-24 space-y-sukoon-6">
        <SukoonSectionHeader title="Available rentals" subtitle="Verified listings with photos and transparent rent." />
        <div className="grid gap-sukoon-4 sm:grid-cols-2 lg:grid-cols-3">
          {listings.map((listing) => (
            <RentListingCard key={listing.id || listing.slug_id} listing={listing} lang={lang} />
          ))}
        </div>
      </section>
    </RentShellContainer>
  );
}

export function RentMapSection({ mapData, areaName }) {
  const ref = useRef(null);
  const [visible, setVisible] = useState(false);
  const center = mapData?.center;

  useEffect(() => {
    const el = ref.current;
    if (!el || !center) return undefined;
    const obs = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setVisible(true);
          obs.disconnect();
        }
      },
      { rootMargin: '200px' },
    );
    obs.observe(el);
    return () => obs.disconnect();
  }, [center]);

  if (!center) return null;

  const { lat, lng } = center;
  const delta = 0.02;
  const bbox = `${lng - delta},${lat - delta},${lng + delta},${lat + delta}`;
  const embed = `https://www.openstreetmap.org/export/embed.html?bbox=${encodeURIComponent(bbox)}&layer=mapnik&marker=${lat}%2C${lng}`;

  return (
    <RentShellContainer>
      <section ref={ref} className="space-y-sukoon-6">
        <SukoonSectionHeader title={`Map — ${areaName || 'Area'}`} subtitle="Approximate area location — exact addresses on listing pages only." />
        <SukoonCard variant="default" padding="none" className="overflow-hidden">
          {visible ? (
            <iframe title="Area map" src={embed} className="h-72 w-full border-0 md:h-96" loading="lazy" />
          ) : (
            <div className="flex h-72 items-center justify-center bg-sukoon-page text-sukoon-body-sm text-sukoon-graphite-muted md:h-96">
              Loading map…
            </div>
          )}
        </SukoonCard>
      </section>
    </RentShellContainer>
  );
}

export function RentNearbyPlaces({ places = [] }) {
  if (!places.length) return null;
  return (
    <RentShellContainer>
      <section className="space-y-sukoon-6">
        <SukoonSectionHeader title="Nearby landmarks" subtitle="Schools, hospitals, and markets with distances." />
        <div className="grid gap-sukoon-3 sm:grid-cols-2 lg:grid-cols-3">
          {places.map((place, i) => (
            <div
              key={place.id || i}
              className="flex items-start gap-sukoon-3 rounded-sukoon-card border border-sukoon-border-strong bg-sukoon-background p-sukoon-4 shadow-sukoon-sm"
            >
              <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-sukoon-input bg-sukoon-page text-sukoon-graphite">
                <MdPlace className="h-[18px] w-[18px]" aria-hidden />
              </div>
              <div className="min-w-0">
                <p className="text-sukoon-body-sm font-semibold text-sukoon-graphite">{place.name || place.title}</p>
                <p className="mt-sukoon-1 text-sukoon-caption text-sukoon-graphite-muted">
                  {[place.category || place.type, place.distance].filter(Boolean).join(' · ')}
                </p>
              </div>
            </div>
          ))}
        </div>
      </section>
    </RentShellContainer>
  );
}

export function RentLeadForm({
  sourcePath,
  area,
  formType = 'lead',
  title,
  subtitle,
  compact = false,
  onSuccess,
}) {
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [requirement, setRequirement] = useState('');
  const [status, setStatus] = useState('idle');
  const [error, setError] = useState('');

  async function handleSubmit(e) {
    e.preventDefault();
    setStatus('loading');
    setError('');
    try {
      await submitRentLead({
        name: name.trim(),
        phone: phone.trim(),
        requirement: requirement.trim(),
        source_path: sourcePath,
        area_id: area?.area_id || null,
        sub_area_id: area?.sub_area_id || null,
        form_type: formType,
        website: '',
      });
      setStatus('done');
      trackRentEvent('lead_submit', { form_type: formType, source_path: sourcePath });
      setName('');
      setPhone('');
      setRequirement('');
      onSuccess?.();
    } catch (err) {
      setStatus('idle');
      setError(err.message || 'Something went wrong');
    }
  }

  const inner = (
    <>
      <SukoonSectionHeader title={title || "Can't find the right home?"} subtitle={subtitle} />
      {status === 'done' ? (
        <SukoonCard variant="subtle" className="mt-sukoon-4 border border-sukoon-success-border bg-sukoon-success-bg p-sukoon-4 text-sukoon-body-sm text-sukoon-graphite">
          Thank you — our team will contact you shortly.
        </SukoonCard>
      ) : (
        <form onSubmit={handleSubmit} className="mt-sukoon-4 grid gap-sukoon-3 md:grid-cols-3">
          <input type="text" name="website" className="hidden" tabIndex={-1} autoComplete="off" aria-hidden />
          <SukoonInput required value={name} onChange={(e) => setName(e.target.value)} placeholder="Your name" />
          <SukoonInput required value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="Phone / WhatsApp" type="tel" />
          <SukoonInput value={requirement} onChange={(e) => setRequirement(e.target.value)} placeholder="BHK, budget, area…" />
          <div className="md:col-span-3">
            <SukoonButton type="submit" loading={status === 'loading'}>
              Submit
            </SukoonButton>
          </div>
          {error ? <p className="text-sukoon-caption text-sukoon-error md:col-span-3">{error}</p> : null}
        </form>
      )}
    </>
  );

  if (compact) {
    return <div className="p-sukoon-2">{inner}</div>;
  }

  return (
    <RentShellContainer>
      <SukoonCard variant="default" className="p-sukoon-6 shadow-sukoon-card">
        {inner}
      </SukoonCard>
    </RentShellContainer>
  );
}

export function RentContentBlock({ introHtml, faqJson }) {
  return (
    <RentShellContainer className="space-y-sukoon-10">
      {introHtml ? (
        <SukoonCard variant="subtle" className="prose prose-sukoon max-w-none p-sukoon-6">
          <div dangerouslySetInnerHTML={{ __html: introHtml }} />
        </SukoonCard>
      ) : null}
      <RentFaqBlock faqJson={faqJson} embedded />
    </RentShellContainer>
  );
}

export function RentLinkBlock({ title, links = [], lang = 'en' }) {
  if (!links?.length) return null;
  return (
    <div>
      <p className="mb-sukoon-3 text-[0.6875rem] font-semibold uppercase tracking-[0.12em] text-sukoon-graphite-subtle">{title}</p>
      <ul className="flex flex-wrap gap-sukoon-2">
        {links.map((link) => (
          <li key={link.path}>
            <CustomLink
              href={`${link.path}?lang=${lang}`}
              className="inline-flex min-h-[36px] items-center rounded-full border border-sukoon-border bg-sukoon-background px-sukoon-4 text-sukoon-caption font-medium text-sukoon-graphite-muted transition-colors duration-200 hover:border-sukoon-gold/40 hover:text-sukoon-gold"
            >
              {link.title || link.path} ({link.listing_count})
            </CustomLink>
          </li>
        ))}
      </ul>
    </div>
  );
}

export function RentFaqBlock({ faqJson = [], embedded = false }) {
  const faqs = Array.isArray(faqJson) ? faqJson : [];
  if (!faqs.length) return null;

  const content = (
    <section className={embedded ? '' : 'space-y-sukoon-6'}>
      {!embedded ? (
        <SukoonSectionHeader title="Frequently asked questions" subtitle="Common questions about renting in this area." />
      ) : null}
      <div className="space-y-sukoon-3">
        {faqs.map((f, i) => (
          <details key={i} className="group rounded-sukoon-card border border-sukoon-border-strong bg-sukoon-background shadow-sukoon-sm">
            <summary className="cursor-pointer list-none px-sukoon-4 py-sukoon-4 text-sukoon-body-sm font-semibold text-sukoon-graphite marker:content-none [&::-webkit-details-marker]:hidden">
              {f.question || f.q}
            </summary>
            <p className="border-t border-sukoon-border px-sukoon-4 py-sukoon-4 text-sukoon-body-sm text-sukoon-graphite-muted">{f.answer || f.a}</p>
          </details>
        ))}
      </div>
    </section>
  );

  if (embedded) return content;
  return <RentShellContainer>{content}</RentShellContainer>;
}

export function PopularSearches({ paths = [], currentPath = '', lang = 'en' }) {
  const filtered = paths.filter((p) => p.path && p.path !== currentPath);
  if (!filtered.length) return null;
  return (
    <RentShellContainer>
      <footer className="border-t border-sukoon-border pt-sukoon-8">
        <p className="mb-sukoon-3 text-[0.6875rem] font-semibold uppercase tracking-[0.12em] text-sukoon-graphite-subtle">
          Popular rental searches
        </p>
        <ul className="flex flex-wrap gap-sukoon-2">
          {filtered.map((p) => (
            <li key={p.path}>
              <CustomLink
                href={`${p.path}?lang=${lang}`}
                className="text-sukoon-body-sm font-medium text-sukoon-gold underline-offset-2 hover:underline"
              >
                {p.title || p.path}
              </CustomLink>
            </li>
          ))}
        </ul>
      </footer>
    </RentShellContainer>
  );
}

export function RentAlertModal({ open, onClose, sourcePath, area }) {
  if (!open) return null;
  return (
    <div className="fixed inset-0 z-50 flex items-end justify-center bg-sukoon-graphite/40 p-sukoon-4 sm:items-center">
      <SukoonCard variant="default" className="w-full max-w-lg p-sukoon-2 shadow-sukoon-lg">
        <RentLeadForm
          sourcePath={sourcePath}
          area={area}
          formType="alert"
          title="Get rental alerts"
          subtitle="We will contact you when new verified homes match your requirements."
          compact
          onSuccess={() => setTimeout(onClose, 1500)}
        />
        <div className="px-sukoon-4 pb-sukoon-2">
          <SukoonButton type="button" variant="ghost" size="sm" className="w-full" onClick={onClose}>
            Close
          </SukoonButton>
        </div>
      </SukoonCard>
    </div>
  );
}
