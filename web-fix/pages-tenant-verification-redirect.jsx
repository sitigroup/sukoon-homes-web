import { TRUST_VERIFICATION_DEFAULT_CITY } from "@/plugins/trust-verification/trustVerificationUtils";

export async function getServerSideProps({ query }) {
  const lang = query.lang ? `?lang=${query.lang}` : "";
  return {
    redirect: {
      destination: `/tenant-verification-in-${TRUST_VERIFICATION_DEFAULT_CITY.slug}${lang}`,
      permanent: false,
    },
  };
}

export default function TenantVerificationRedirect() {
  return null;
}
