import { useSelector } from "react-redux";
import UserPropertyDetails from "@/components/property-detail/UserPropertyDetails";
import CustomPropertyDetailsPage from "./CustomPropertyDetailsPage";
import PropertyDetailsErrorBoundary from "./PropertyDetailsErrorBoundary";
import { useFreshPropertyDetailWebSettings } from "./useFreshPropertyDetailWebSettings";

const MyPropertyDetailsSwitcher = () => {
  useFreshPropertyDetailWebSettings();

  const layout = useSelector((state) => state.WebSetting?.data?.property_detail_layout);
  const officialFallback = <UserPropertyDetails />;

  if (layout !== "custom") {
    return officialFallback;
  }

  return (
    <PropertyDetailsErrorBoundary fallback={officialFallback}>
      <CustomPropertyDetailsPage OfficialFallback={UserPropertyDetails} ownerMode />
    </PropertyDetailsErrorBoundary>
  );
};

export default MyPropertyDetailsSwitcher;
