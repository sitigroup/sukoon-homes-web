import { useSelector } from "react-redux";
import OfficialPropertyDetailsPage from "@/components/property-detail/PropertyDetails";
import CustomPropertyDetailsPage from "./CustomPropertyDetailsPage";
import PropertyDetailsErrorBoundary from "./PropertyDetailsErrorBoundary";
import { useFreshPropertyDetailWebSettings } from "./useFreshPropertyDetailWebSettings";

const PropertyDetailsSwitcher = () => {
  useFreshPropertyDetailWebSettings();

  const layout = useSelector((state) => state.WebSetting?.data?.property_detail_layout);
  const officialFallback = <OfficialPropertyDetailsPage />;

  if (layout !== "custom") {
    return officialFallback;
  }

  return (
    <PropertyDetailsErrorBoundary fallback={officialFallback}>
      <CustomPropertyDetailsPage OfficialFallback={OfficialPropertyDetailsPage} />
    </PropertyDetailsErrorBoundary>
  );
};

export default PropertyDetailsSwitcher;
