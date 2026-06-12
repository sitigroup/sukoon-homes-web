import MetaData from '@/components/meta/MetaData';
import RentListingCard, { RentFaqBlock, RentLinkBlock, PopularSearches } from '@/plugins/seo-engine/RentPageComponents';

export default function RentPageView({ payload, popularPaths = [], lang = 'en', structuredData = null, robots = 'index, follow' }) {
  const page = payload?.page || {};
  const listings = payload?.listings || [];
  const stats = payload?.locality_stats?.current;

  return (
    <div className="container mx-auto px-4 py-8">
      <MetaData
        title={page.title}
        description={page.meta_description}
        pageName={`${page.path}?lang=${lang}`}
        robots={robots}
        structuredData={structuredData}
      />
      <header className="mb-6">
        <nav aria-label="Breadcrumb" className="mb-2 text-sm text-gray-500">
          {(payload?.breadcrumbs || []).map((c, i) => (
            <span key={c.path}>
              {i > 0 ? ' › ' : ''}
              {c.label}
            </span>
          ))}
        </nav>
        <h1 className="text-3xl font-bold text-gray-900">{page.h1 || page.title}</h1>
        {page.intro_html ? (
          <div className="prose mt-4 max-w-none" dangerouslySetInnerHTML={{ __html: page.intro_html }} />
        ) : (
          <p className="mt-4 text-lg text-gray-700">
            {page.listing_count} verified rental {page.listing_count === 1 ? 'listing' : 'listings'}
            {stats?.avg_rent ? ` — average rent ₹${stats.avg_rent}` : ''}.
          </p>
        )}
      </header>

      {listings.length > 0 ? (
        <section>
          <h2 className="mb-4 text-xl font-semibold">Available rentals</h2>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {listings.map((listing) => (
              <RentListingCard key={listing.id || listing.slug_id} listing={listing} lang={lang} />
            ))}
          </div>
        </section>
      ) : (
        <p className="text-gray-600">No active listings match this search yet. Browse related areas below.</p>
      )}

      <RentLinkBlock title="Related areas" links={payload?.links?.siblings || []} lang={lang} />
      <RentLinkBlock title="More in this area" links={payload?.links?.children || []} lang={lang} />
      <RentLinkBlock title="Budget filters" links={payload?.links?.budget || []} lang={lang} />
      <RentFaqBlock faqJson={page.faq_json} />
      <PopularSearches paths={popularPaths} currentPath={page.path} lang={lang} />
    </div>
  );
}
