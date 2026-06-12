/**
 * Next.js rewrites: proxy Nearby Places API on the public web host to the Laravel admin API.
 * Uses NEXT_PUBLIC_API_URL from env (defaults to admin-homes) as the upstream base.
 */

function nearbyPlacesProxyRewrites() {
  const adminBase = (process.env.NEXT_PUBLIC_API_URL || "https://admin-homes.sukoon.group").replace(
    /\/$/,
    "",
  );

  return [
    {
      source: "/api/nearby-places/:path*",
      destination: `${adminBase}/api/nearby-places/:path*`,
    },
  ];
}

module.exports = { nearbyPlacesProxyRewrites };
