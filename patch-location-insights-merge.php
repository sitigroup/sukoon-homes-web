<?php
$p = '/www/wwwroot/homes.sukoon.group/src/components/property-detail/PropertyDetails.jsx';
$t = file_get_contents($p);

$old = <<<'HTML'
              {/* Location Insights — outdoor facilities + nearby places */}
              <div className="location-insights mb-1 space-y-4">
                <FeaturesAmenities
                  data={propertyDetails}
                  DistanceSymbol={DistanceSymbol}
                  themeEnabled={webSettings?.svg_clr === "1"}
                />

                <NearbyPlacesSection
                  propertyId={propertyDetails?.id}
                  areaListing={propertyDetails?.area_listing}
                  city={propertyDetails?.city}
                  state={propertyDetails?.state}
                />
              </div>
HTML;

$new = <<<'HTML'
              <FeaturesAmenities
                data={propertyDetails}
                DistanceSymbol={DistanceSymbol}
                themeEnabled={webSettings?.svg_clr === "1"}
                hideOutdoor
              />

              <LocationInsightsGroup
                propertyDetails={propertyDetails}
                DistanceSymbol={DistanceSymbol}
                themeEnabled={webSettings?.svg_clr === "1"}
              />
HTML;

if (str_contains($t, 'LocationInsightsGroup')) {
    echo "already_patched\n";
    exit(0);
}

if (!str_contains($t, 'import LocationInsightsGroup from "./LocationInsightsGroup";')) {
    $t = str_replace(
        'import FeaturesAmenities from "./FeatureAmenities";',
        "import FeaturesAmenities from \"./FeatureAmenities\";\nimport LocationInsightsGroup from \"./LocationInsightsGroup\";",
        $t
    );
}

if (!str_contains($t, $old)) {
    fwrite(STDERR, "block not found\n");
    exit(1);
}

$t = str_replace($old, $new, $t);
file_put_contents($p, $t);
echo "property_details_updated\n";
