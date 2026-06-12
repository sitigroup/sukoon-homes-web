export function guideBreadcrumbList(crumbs, lang = 'en') {
  const web = process.env.NEXT_PUBLIC_WEB_URL || 'https://homes.sukoon.group';
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: (crumbs || []).map((c, i) => ({
      '@type': 'ListItem',
      position: i + 1,
      name: c.label,
      item: c.url || `${web}${c.path}${c.path.includes('?') ? '&' : '?'}lang=${lang}`,
    })),
  };
}

export function guideArticle(page, canonical) {
  return {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: page.question,
    description: page.direct_answer,
    dateModified: page.updated_at,
    mainEntityOfPage: canonical,
    author: { '@type': 'Organization', name: 'Sukoon Homes' },
    publisher: { '@type': 'Organization', name: 'Sukoon Homes' },
  };
}

export function guideFaqPage(page) {
  if (!page?.direct_answer) return null;
  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
      {
        '@type': 'Question',
        name: page.question,
        acceptedAnswer: {
          '@type': 'Answer',
          text: page.direct_answer,
        },
      },
    ],
  };
}

export function mergeGuideStructuredData(...nodes) {
  return nodes.filter(Boolean);
}
