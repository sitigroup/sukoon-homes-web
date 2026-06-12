import React from 'react';
import Layout from '../layout/Layout';
import PropertyDetailsSwitcher from '@/plugins/property-detail-switcher/PropertyDetailsSwitcher';

const PropertyDetailPage = ({ initialPropertyLoad = null }) => {
  return (
    <Layout>
      <PropertyDetailsSwitcher initialPropertyLoad={initialPropertyLoad} />
    </Layout>
  );
};

export default PropertyDetailPage;
