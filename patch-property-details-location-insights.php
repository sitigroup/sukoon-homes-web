<?php
$p = '/www/wwwroot/homes.sukoon.group/src/components/property-detail/PropertyDetails.jsx';
$t = file_get_contents($p);

$old = <<<'HTML'
              {/* Features & Amenities */}
              <FeaturesAmenities
                data={propertyDetails}
                DistanceSymbol={DistanceSymbol}
                themeEnabled={webSettings?.svg_clr === "1"}
              />

              <NearbyPlacesSection propertyId={propertyDetails?.id} areaListing={propertyDetails?.area_listing} city={propertyDetails?.city} state={propertyDetails?.state} />
HTML;

$new = <<<'HTML'
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

if (!str_contains($t, $old)) {
    if (str_contains($t, 'location-insights')) {
        echo "already_patched\n";
        exit(0);
    }
    fwrite(STDERR, "pattern not found\n");
    exit(1);
}

$t = str_replace($old, $new, $t);
file_put_contents($p, $t);
echo "property_details_updated\n";
