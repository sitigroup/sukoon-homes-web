import { useSelector } from 'react-redux';
import OfficialPropertyDetailsPage from '@/components/property-detail/PropertyDetails';
import CustomPropertyDetailsPage from './CustomPropertyDetailsPage';
import PropertyDetailsErrorBoundary from './PropertyDetailsErrorBoundary';
import { useFreshPropertyDetailWebSettings } from './useFreshPropertyDetailWebSettings';

const PropertyDetailsSwitcher = ({ initialPropertyLoad = null }) => {
  useFreshPropertyDetailWebSettings();

  const layout = useSelector((state) => state.WebSetting?.data?.property_detail_layout);
  const officialFallback = (
    <OfficialPropertyDetailsPage initialPropertyLoad={initialPropertyLoad} />
  );

  if (layout !== 'custom') {
    return officialFallback;
  }

  return (
    <PropertyDetailsErrorBoundary fallback={officialFallback}>
      <CustomPropertyDetailsPage
        OfficialFallback={OfficialPropertyDetailsPage}
        initialPropertyLoad={initialPropertyLoad}
      />
    </PropertyDetailsErrorBoundary>
  );
};

export default PropertyDetailsSwitcher;
