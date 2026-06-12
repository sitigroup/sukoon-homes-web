import Layout from "@/components/layout/Layout";
import MetaData from "@/components/meta/MetaData";
import DataDeletionInstructions from "@/components/legal/DataDeletionInstructions";
import { dataDeletionPageContent } from "@/components/legal/dataDeletionContent";

/**
 * Public page for Meta App Review — user data deletion instructions.
 * Route: /data-deletion (no login required)
 */
export default function DataDeletionPage() {
  const { title, description, pagePath } = dataDeletionPageContent;
  const metaTitle = `${title} | Sukoon Homes`;
  const metaDescription =
    description ||
    "Request deletion of your Sukoon Homes account data and Meta or WhatsApp related data. Email hello@sukoon.group with subject Data Deletion Request.";

  return (
    <Layout>
      <MetaData title={metaTitle} description={metaDescription} pageName={pagePath} />
      <DataDeletionInstructions />
    </Layout>
  );
}
