import { useEffect } from 'react';
import Script from 'next/script';

export function trackRentEvent(name, params = {}) {
  if (typeof window === 'undefined' || typeof window.gtag !== 'function') return;
  window.gtag('event', name, params);
}

export default function RentGa4Tracker({ measurementId, pagePath }) {
  useEffect(() => {
    if (!measurementId || typeof window === 'undefined') return;
    trackRentEvent('page_view', { page_path: pagePath, page_location: window.location.href });
  }, [measurementId, pagePath]);

  if (!measurementId) return null;

  return (
    <>
      <Script src={`https://www.googletagmanager.com/gtag/js?id=${measurementId}`} strategy="afterInteractive" />
      <Script id="rent-ga4" strategy="afterInteractive">
        {`
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', '${measurementId}', { send_page_view: false });
        `}
      </Script>
    </>
  );
}
