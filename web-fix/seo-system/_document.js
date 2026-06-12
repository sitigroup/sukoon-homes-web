import Document, { Html, Head, Main, NextScript } from "next/document";
import { shouldNoIndexPath } from "@/utils/seoNoIndexPaths";

class SukoonDocument extends Document {
  static async getInitialProps(ctx) {
    const initialProps = await Document.getInitialProps(ctx);
    return { ...initialProps, pathname: ctx.pathname || "" };
  }

  render() {
    const pathname = this.props.pathname || "";
    const noIndex = shouldNoIndexPath(pathname);
    const is404 = pathname === "/404";

    return (
      <Html lang="en" web-version={process.env.NEXT_PUBLIC_WEB_VERSION} seo={process.env.NEXT_PUBLIC_SEO}>
        <Head>
          <meta charSet="utf-8" />
          {noIndex && <meta name="robots" content="noindex, nofollow" />}
          {is404 && <title>Page Not Found</title>}
          <script
            async
            src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-xxxxxxxxxxxxxxxxx"
            crossOrigin="anonymous"
          />
          <script
            async
            src={`https://maps.googleapis.com/maps/api/js?key=${process.env.NEXT_PUBLIC_GOOGLE_MAPS_API}&libraries=places,marker&loading=async`}
            defer
          />
          <link rel="icon" href="/favicon.ico" />
          {/* data-sukoon-preview-boot */}
          <script
            dangerouslySetInnerHTML={{
              __html: `(function(){try{var p=location.pathname;var routes='/shell-preview,/design-preview,/design-system-preview,/homepage-preview,/property-detail-preview'.split(',');for(var i=0;i<routes.length;i++){if(p.indexOf(routes[i])===0){document.documentElement.setAttribute('data-sukoon-preview','true');document.body&&document.body.classList.add('sukoon-design-preview');break;}}}catch(e){}})();`,
            }}
          />
        </Head>
        <body className="!pointer-events-auto antialiased">
          <Main />
          <NextScript />
        </body>
      </Html>
    );
  }
}

export default SukoonDocument;
