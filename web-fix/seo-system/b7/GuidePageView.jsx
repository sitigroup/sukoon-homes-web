import MetaData from '@/components/meta/MetaData';
import CustomLink from '@/components/context/CustomLink';

export default function GuidePageView({ payload, lang, structuredData, robots }) {
  const { page, breadcrumbs, related_rent_links: relatedLinks = [] } = payload;

  return (
    <>
      <MetaData
        title={page.title}
        description={page.meta_description}
        pageName={`${page.path}?lang=${lang}`}
        structuredData={structuredData}
        robots={robots}
      />
      <div className="container py-4">
        <nav aria-label="breadcrumb" className="mb-3">
          <ol className="breadcrumb">
            {(breadcrumbs || []).map((c) => (
              <li key={c.path} className="breadcrumb-item">
                {c.path === page.path ? (
                  <span>{c.label}</span>
                ) : (
                  <CustomLink href={`${c.path}?lang=${lang}`}>{c.label}</CustomLink>
                )}
              </li>
            ))}
          </ol>
        </nav>

        <h1 className="h2 mb-3">{page.question}</h1>

        {page.direct_answer && (
          <div
            className="p-4 mb-4 rounded border-start border-4 border-primary bg-light"
            role="region"
            aria-label="Direct answer"
          >
            <p className="mb-0 fw-semibold">{page.direct_answer}</p>
          </div>
        )}

        {page.body_html && (
          <div className="guide-body mb-4" dangerouslySetInnerHTML={{ __html: page.body_html }} />
        )}

        {relatedLinks.length > 0 && (
          <section className="mt-4">
            <h2 className="h5">Related rental searches</h2>
            <ul>
              {relatedLinks.map((path) => (
                <li key={path}>
                  <CustomLink href={`${path}?lang=${lang}`}>{path}</CustomLink>
                </li>
              ))}
            </ul>
          </section>
        )}
      </div>
    </>
  );
}
