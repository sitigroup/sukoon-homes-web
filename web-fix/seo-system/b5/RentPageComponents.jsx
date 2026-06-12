import CustomLink from '@/components/context/CustomLink';

function formatInr(n) {
  const v = Number(n);
  if (!Number.isFinite(v)) return '';
  return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(v);
}

export default function RentListingCard({ listing, lang = 'en' }) {
  if (!listing?.slug_id) return null;
  return (
    <article className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md">
      <h3 className="mb-1 text-lg font-semibold text-gray-900">
        <CustomLink href={`/property-details/${listing.slug_id}/?lang=${lang}`}>{listing.title}</CustomLink>
      </h3>
      <p className="text-sm text-gray-600">{listing.city}{listing.state ? `, ${listing.state}` : ''}</p>
      {listing.price ? (
        <p className="mt-2 text-base font-bold text-primary">{formatInr(listing.price)}<span className="text-sm font-normal text-gray-500"> / {listing.rent_duration || 'mo'}</span></p>
      ) : null}
    </article>
  );
}

export function RentLinkBlock({ title, links = [], lang = 'en' }) {
  if (!links?.length) return null;
  return (
    <section className="mt-8">
      <h2 className="mb-3 text-xl font-semibold">{title}</h2>
      <ul className="flex flex-wrap gap-2">
        {links.map((link) => (
          <li key={link.path}>
            <CustomLink
              href={`${link.path}?lang=${lang}`}
              className="inline-block rounded-full border border-gray-200 bg-white px-3 py-1 text-sm hover:border-primary hover:text-primary"
            >
              {link.title || link.path} ({link.listing_count})
            </CustomLink>
          </li>
        ))}
      </ul>
    </section>
  );
}

export function RentFaqBlock({ faqJson = [] }) {
  const faqs = Array.isArray(faqJson) ? faqJson : [];
  if (!faqs.length) return null;
  return (
    <section className="mt-8">
      <h2 className="mb-3 text-xl font-semibold">Frequently asked questions</h2>
      <div className="space-y-3">
        {faqs.map((f, i) => (
          <details key={i} className="rounded-lg border border-gray-200 bg-white p-4">
            <summary className="cursor-pointer font-medium">{f.question || f.q}</summary>
            <p className="mt-2 text-gray-700">{f.answer || f.a}</p>
          </details>
        ))}
      </div>
    </section>
  );
}

export function PopularSearches({ paths = [], lang = 'en' }) {
  if (!paths?.length) return null;
  return (
    <footer className="mt-12 border-t border-gray-200 pt-8">
      <h2 className="mb-3 text-lg font-semibold">Popular rental searches</h2>
      <ul className="flex flex-wrap gap-2">
        {paths.map((p) => (
          <li key={p.path}>
            <CustomLink href={`${p.path}?lang=${lang}`} className="text-sm text-primary underline">
              {p.title || p.path}
            </CustomLink>
          </li>
        ))}
      </ul>
    </footer>
  );
}
