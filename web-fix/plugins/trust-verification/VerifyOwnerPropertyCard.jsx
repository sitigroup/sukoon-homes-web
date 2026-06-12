"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { MdOutlineVerifiedUser } from "react-icons/md";
import {
  resolvePropertyCitySlug,
  verificationWizardPath,
} from "@/plugins/trust-verification/trustVerificationUtils";
import { getTrustVerificationCities } from "@/plugins/trust-verification/trustVerificationApi";

const RENT_TYPES = new Set(["rent", "pg", "commercial_rent"]);

function buildListingAddress(property) {
  if (!property) return "";
  const parts = [
    property.address,
    property.area_listing?.sub_area_name,
    property.area_listing?.area_name,
    property.area_listing?.city_name,
  ].filter(Boolean);
  return parts.join(", ") || property.title || "";
}

export default function VerifyOwnerPropertyCard({ propertyDetails, lang = "en", userCurrentId }) {
  const [cities, setCities] = useState([]);

  useEffect(() => {
    getTrustVerificationCities()
      .then((res) => setCities(res?.data || []))
      .catch(() => setCities([]));
  }, []);

  const propertyCitySlug = useMemo(
    () => resolvePropertyCitySlug(propertyDetails),
    [propertyDetails]
  );

  const city = useMemo(
    () => cities.find((c) => c.slug === propertyCitySlug),
    [cities, propertyCitySlug]
  );

  if (!propertyDetails) return null;

  const isRent = RENT_TYPES.has(String(propertyDetails.property_type || "").toLowerCase());
  const isOwnListing = userCurrentId && String(userCurrentId) === String(propertyDetails.added_by);
  if (!isRent || isOwnListing || !city?.has_owner_packages) return null;

  const address = buildListingAddress(propertyDetails);
  const basePath = verificationWizardPath("owner", city.slug, lang || "en");
  const fullHref = `${basePath}&property_id=${encodeURIComponent(String(propertyDetails.id || ""))}&property_address=${encodeURIComponent(address)}&listing_title=${encodeURIComponent(propertyDetails.title || propertyDetails.slug_id || "")}`;

  return (
    <div className="newBorder mb-7 rounded-2xl border bg-white p-4 shadow-sm">
      <div className="flex items-start gap-3">
        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full brandBgLight">
          <MdOutlineVerifiedUser className="h-5 w-5 brandColor" />
        </div>
        <div className="min-w-0 flex-1">
          <h3 className="text-sm font-semibold brandColor">Verify owner before you rent</h3>
          <p className="mt-1 text-xs leadColor">
            Confirm this landlord and property in {city.label} — ID, ownership docs, and a PDF report.
          </p>
          <Link
            href={fullHref}
            className="brandBg mt-3 inline-block rounded-lg px-4 py-2 text-xs font-medium text-white"
          >
            Verify owner
          </Link>
        </div>
      </div>
    </div>
  );
}
