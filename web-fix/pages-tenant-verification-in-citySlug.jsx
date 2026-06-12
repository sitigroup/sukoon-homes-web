import TrustVerificationPage from "@/plugins/trust-verification/TrustVerificationPage";

export default function TenantVerificationInCityPage({ citySlug }) {
  return <TrustVerificationPage orderType="tenant" citySlug={citySlug} />;
}

export async function getServerSideProps({ params }) {
  const citySlug = String(params?.citySlug || "")
    .toLowerCase()
    .trim();

  if (!citySlug) {
    return { notFound: true };
  }

  return {
    props: { citySlug },
  };
}
