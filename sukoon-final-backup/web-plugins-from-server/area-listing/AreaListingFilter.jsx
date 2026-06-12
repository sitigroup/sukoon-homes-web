import AreaSubAreaSelector from "./AreaSubAreaSelector";

const AreaListingFilter = ({ locationInput, setLocationInput, stacked = false }) => {
  return (
    <AreaSubAreaSelector
      compactSeparate
      stacked={stacked}
      requiresCity
      showStateCity={false}
      selectedLocationAddress={locationInput}
      setSelectedLocationAddress={setLocationInput}
    />
  );
};

export default AreaListingFilter;