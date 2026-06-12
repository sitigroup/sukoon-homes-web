#!/usr/bin/env python3
path = "/www/wwwroot/homes.sukoon.group/src/components/agent/project/EditProject.jsx"
with open(path, "r", encoding="utf-8") as f:
    c = f.read()

old = """                state_id: selectedLocationAddress.state_id,
                city_id: selectedLocationAddress.city_id,
                area_id: selectedLocationAddress.area_id,
                sub_area_id: selectedLocationAddress.sub_area_id,
                area_name: selectedLocationAddress.area_name,
                sub_area_name: selectedLocationAddress.sub_area_name,
                detected_area_name: selectedLocationAddress.detected_area_name,
                detected_sub_area_name: selectedLocationAddress.detected_sub_area_name,
                area_listing_source: selectedLocationAddress.area_listing_source || "google",
                location_is_verified: selectedLocationAddress.location_is_verified || false,
                location: selectedLocationAddress.formattedAddress,
                manual_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                customer_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                client_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                ...(router.asPath?.includes('/user/') ? { area_listing_user_portal: 1 } : {}),"""

new = """                ...buildAreaListingSaveFields(selectedLocationAddress, {
                    isUserPortal: router.asPath?.includes('/user/'),
                }),
                area_listing_source: selectedLocationAddress.area_listing_source || "google",
                location_is_verified: selectedLocationAddress.location_is_verified || false,
                location: selectedLocationAddress.formattedAddress,
                manual_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                customer_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "",
                client_address: selectedLocationAddress.manualAddress || selectedLocationAddress.clientAddress || "","",

if "buildAreaListingSaveFields(selectedLocationAddress" in c:
    print("SKIP: already patched")
elif old in c:
    c = c.replace(old, new)
    with open(path, "w", encoding="utf-8") as f:
        f.write(c)
    print("OK: patched EditProject.jsx")
else:
    print("ERROR: block not found")
    exit(1)
