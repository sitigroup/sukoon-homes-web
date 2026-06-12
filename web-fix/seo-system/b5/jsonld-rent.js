function webBase() {
  return (process.env.NEXT_PUBLIC_WEB_URL || 'https://homes.sukoon.group').replace(/\/$/, '');
}

export function rentBreadcrumbList(breadcrumbs = [], lang = 'en') {
  const items = (breadcrumbs || []).map((crumb) => ({
    name: crumb.label,
    url: crumb.url || `${webBase()}${crumb.path}?lang=${lang}`,
  }));
  if (!items.length) return null;
  return {
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.name,
      item: item.url,
    })),
  };
}

export function rentItemList(listings = [], pageTitle = '', lang = 'en') {
  if (!listings?.length) return null;
  const base = webBase();
  return {
    '@type': 'ItemList',
    name: pageTitle,
    numberOfItems: listings.length,
    itemListElement: listings.map((p, i) => ({
      '@type': 'ListItem',
      position: i + 1,
      url: `${base}/property-details/${p.slug_id}/?lang=${lang}`,
      name: p.title,
    })),
  };
}

export function rentFaqPage(faqJson = []) {
  const faqs = Array.isArray(faqJson) ? faqJson : [];
  if (!faqs.length) return null;
  return {
    '@type': 'FAQPage',
    mainEntity: faqs.map((f) => ({
      '@type': 'Question',
      name: f.question || f.q,
      acceptedAnswer: {
        '@type': 'Answer',
        text: f.answer || f.a,
      },
    })),
  };
}

export function mergeRentStructuredData(...blocks) {
  const graph = blocks.filter(Boolean);
  if (!graph.length) return null;
  if (graph.length === 1) {
    return { '@context': 'https://schema.org', ...graph[0] };
  }
  return { '@context': 'https://schema.org', '@graph': graph };
}
