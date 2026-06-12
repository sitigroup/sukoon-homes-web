"use client";

import { useEffect, useRef } from "react";
import { useGoogleMap } from "@react-google-maps/api";

/** Cloud map ID — set NEXT_PUBLIC_GOOGLE_MAP_ID in production; DEMO_MAP_ID works for dev. */
export const getGoogleMapId = () =>
  process.env.NEXT_PUBLIC_GOOGLE_MAP_ID || "DEMO_MAP_ID";

export const withGoogleMapId = (options = {}) => ({
  mapId: getGoogleMapId(),
  ...options,
});

const readLatLng = (position) => {
  if (!position) return null;
  const lat = typeof position.lat === "function" ? position.lat() : position.lat;
  const lng = typeof position.lng === "function" ? position.lng() : position.lng;
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
  return { lat, lng };
};

/**
 * Replaces deprecated google.maps.Marker with AdvancedMarkerElement.
 */
const AdvancedMapMarker = ({
  position,
  draggable = false,
  onDragEnd,
  onClick,
  title = "",
  icon,
  zIndex,
}) => {
  const map = useGoogleMap();
  const markerRef = useRef(null);
  const listenersRef = useRef([]);

  useEffect(() => {
    if (!map || !position || typeof window === "undefined") return undefined;

    const lat = Number(position.lat);
    const lng = Number(position.lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return undefined;

    let cancelled = false;

    const setup = async () => {
      try {
        const { AdvancedMarkerElement, PinElement } = await google.maps.importLibrary("marker");
        if (cancelled) return;

        listenersRef.current.forEach((listener) => listener.remove?.());
        listenersRef.current = [];

        if (markerRef.current) {
          markerRef.current.map = null;
          markerRef.current = null;
        }

        let content = null;
        if (icon?.url) {
          const img = document.createElement("img");
          img.src = icon.url;
          img.alt = title || "Map marker";
          const size = icon.scaledSize || icon.size;
          const width = typeof size?.width === "function" ? size.width() : size?.width;
          const height = typeof size?.height === "function" ? size.height() : size?.height;
          if (width || height) {
            img.style.width = `${width || 32}px`;
            img.style.height = `${height || 32}px`;
          } else {
            img.style.width = "32px";
            img.style.height = "32px";
          }
          img.style.display = "block";
          content = img;
        } else if (!icon) {
          content = new PinElement({
            background: "#111827",
            borderColor: "#ffffff",
            glyphColor: "#ffffff",
          });
        }

        const marker = new AdvancedMarkerElement({
          map,
          position: { lat, lng },
          title,
          gmpDraggable: draggable,
          zIndex: zIndex ?? undefined,
          ...(content ? { content } : {}),
        });

        if (draggable && onDragEnd) {
          const dragListener = marker.addListener("dragend", () => {
            const coords = readLatLng(marker.position);
            if (!coords) return;
            onDragEnd({
              latLng: {
                lat: () => coords.lat,
                lng: () => coords.lng,
              },
            });
          });
          listenersRef.current.push(dragListener);
        }

        if (onClick) {
          const clickListener = marker.addListener("gmp-click", onClick);
          listenersRef.current.push(clickListener);
        }

        markerRef.current = marker;
      } catch (error) {
        console.error("AdvancedMarkerElement failed to load:", error);
      }
    };

    setup();

    return () => {
      cancelled = true;
      listenersRef.current.forEach((listener) => listener.remove?.());
      listenersRef.current = [];
      if (markerRef.current) {
        markerRef.current.map = null;
        markerRef.current = null;
      }
    };
  }, [
    map,
    position?.lat,
    position?.lng,
    draggable,
    onDragEnd,
    onClick,
    title,
    icon?.url,
    zIndex,
  ]);

  return null;
};

export default AdvancedMapMarker;
