"use client";

import { scheduleTrustUiActivation } from "./trustUiHydration";
import { useEffect } from "react";

/** Schedules global trust UI activation after hydration (prevents React #421). */
export default function TrustUiHydrationGate() {
  useEffect(() => {
    scheduleTrustUiActivation();
  }, []);

  return null;
}
