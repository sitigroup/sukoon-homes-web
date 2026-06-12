"use client";

import { startTransition, useSyncExternalStore } from "react";

let trustUiReady = false;
const listeners = new Set();
let bootScheduled = false;

function notify() {
  listeners.forEach((listener) => listener());
}

export function activateTrustUi() {
  if (trustUiReady || typeof window === "undefined") {
    return;
  }
  startTransition(() => {
    trustUiReady = true;
    notify();
  });
}

/**
 * Call once from app shell (e.g. SukoonThemeProvider) after mount.
 */
export function scheduleTrustUiActivation() {
  if (bootScheduled || typeof window === "undefined") {
    return;
  }
  bootScheduled = true;

  const run = () => {
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        setTimeout(activateTrustUi, 400);
      });
    });
  };

  if (document.readyState === "complete") {
    run();
  } else {
    window.addEventListener("load", run, { once: true });
    setTimeout(run, 800);
  }
}

export function useClientTrustReady() {
  return useSyncExternalStore(
    (listener) => {
      listeners.add(listener);
      return () => listeners.delete(listener);
    },
    () => trustUiReady,
    () => false,
  );
}
