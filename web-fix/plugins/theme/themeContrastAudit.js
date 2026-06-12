/**
 * Sukoon Theme — temporary WCAG contrast audit (plugin-only).
 * Enable: body[data-theme-debug="true"] or ?theme-debug=1 or localStorage.sukoon_theme_debug=1
 */
import { contrastRatio } from "./contrastUtils";

const MIN_RATIO = 4.5;
const FAIL_CLASS = "sukoon-contrast-fail";
const AUDIT_STYLE_ID = "sukoon-theme-audit-css";

export function isThemeDebugEnabled() {
  if (typeof window === "undefined") return false;
  try {
    if (window.location.search.includes("theme-debug=1")) return true;
    if (localStorage.getItem("sukoon_theme_debug") === "1") return true;
  } catch {
    /* ignore */
  }
  return document.body?.dataset?.themeDebug === "true";
}

export function buildThemeAuditCss() {
  return `
    body[data-theme-debug="true"] .${FAIL_CLASS} {
      outline: 2px solid #DC2626 !important;
      outline-offset: 2px !important;
    }
  `;
}

function parseRgb(color) {
  if (!color || color === "transparent") return null;
  const m = color.match(/rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)/i);
  if (!m) return null;
  return [Number(m[1]), Number(m[2]), Number(m[3])];
}

function rgbToHex(r, g, b) {
  const h = (n) => Math.max(0, Math.min(255, Math.round(n))).toString(16).padStart(2, "0");
  return `#${h(r)}${h(g)}${h(b)}`.toUpperCase();
}


function ratioFromComputed(fgColor, bgColor) {
  const fgRgb = parseRgb(fgColor);
  const bgRgb = parseRgb(bgColor);
  if (!fgRgb || !bgRgb) return null;
  const fgHex = rgbToHex(...fgRgb);
  const bgHex = rgbToHex(...bgRgb);
  return contrastRatio(fgHex, bgHex);
}

function getEffectiveBackground(el) {
  let node = el;
  while (node && node !== document.documentElement) {
    const bg = getComputedStyle(node).backgroundColor;
    if (bg && bg !== "transparent" && bg !== "rgba(0, 0, 0, 0)") {
      const alphaMatch = bg.match(/rgba\([\d\s.,]+,\s*([\d.]+)\s*\)/i);
      const alpha = alphaMatch ? parseFloat(alphaMatch[1]) : 1;
      if (alpha > 0.08 && parseRgb(bg)) return bg;
    }
    node = node.parentElement;
  }
  return getComputedStyle(document.body).backgroundColor || "rgb(255, 255, 255)";
}

function cssPath(el) {
  if (!el || el.nodeType !== 1) return "";
  const parts = [];
  let node = el;
  while (node && node.nodeType === 1 && parts.length < 6) {
    let part = node.tagName.toLowerCase();
    if (node.id) {
      part += `#${node.id}`;
      parts.unshift(part);
      break;
    }
    if (node.className && typeof node.className === "string") {
      const cls = node.className.trim().split(/\s+/).slice(0, 2).join(".");
      if (cls) part += `.${cls}`;
    }
    parts.unshift(part);
    node = node.parentElement;
  }
  return parts.join(" > ");
}

function isTextVisible(el) {
  const style = getComputedStyle(el);
  if (style.display === "none" || style.visibility === "hidden") return false;
  if (parseFloat(style.opacity) < 0.05) return false;
  const text = (el.textContent || "").trim();
  if (!text && !["INPUT", "TEXTAREA", "SELECT"].includes(el.tagName)) return false;
  const rect = el.getBoundingClientRect();
  return rect.width > 0 && rect.height > 0;
}

const TEXT_TAGS =
  "p,span,a,li,td,th,label,h1,h2,h3,h4,h5,h6,button,small,strong,em,b,i,figcaption,dt,dd,legend,option";

export function runThemeContrastAudit({ log = true } = {}) {
  if (typeof document === "undefined") return { failures: [], scanned: 0 };

  document.body.dataset.themeDebug = "true";

  let styleEl = document.getElementById(AUDIT_STYLE_ID);
  if (!styleEl) {
    styleEl = document.createElement("style");
    styleEl.id = AUDIT_STYLE_ID;
    document.head.appendChild(styleEl);
  }
  styleEl.textContent = buildThemeAuditCss();

  document.querySelectorAll(`.${FAIL_CLASS}`).forEach((el) => el.classList.remove(FAIL_CLASS));

  const failures = [];
  const nodes = document.querySelectorAll(TEXT_TAGS);

  nodes.forEach((el) => {
    if (!isTextVisible(el)) return;

    const style = getComputedStyle(el);
    const fg = style.color;
    const bg = getEffectiveBackground(el);
    const ratio = ratioFromComputed(fg, bg);

    if (ratio !== null && ratio < MIN_RATIO) {
      el.classList.add(FAIL_CLASS);
      failures.push({
        selector: cssPath(el),
        fg,
        bg,
        ratio: Number(ratio.toFixed(2)),
      });
    }
  });

  if (log) {
    console.group("[Sukoon Theme] Contrast audit — WCAG AA 4.5:1");
    console.log(`Scanned ${nodes.length} text nodes — ${failures.length} failures`);
    failures.forEach((f) => {
      console.warn(
        `[FAIL ${f.ratio}:1] ${f.selector}\n  fg: ${f.fg}\n  bg: ${f.bg}`
      );
    });
    if (failures.length === 0) {
      console.log("All scanned text meets 4.5:1 contrast.");
    }
    console.groupEnd();
  }

  return { failures, scanned: nodes.length };
}

export function disableThemeContrastAudit() {
  if (typeof document === "undefined") return;
  document.body.dataset.themeDebug = "false";
  document.querySelectorAll(`.${FAIL_CLASS}`).forEach((el) => el.classList.remove(FAIL_CLASS));
  const styleEl = document.getElementById(AUDIT_STYLE_ID);
  if (styleEl) styleEl.textContent = "";
}

export function initThemeContrastAuditWatcher() {
  if (typeof window === "undefined" || !isThemeDebugEnabled()) return () => {};

  const run = () => {
    requestAnimationFrame(() => {
      setTimeout(() => runThemeContrastAudit(), 300);
    });
  };

  run();

  const observer = new MutationObserver(() => run());
  observer.observe(document.body, { childList: true, subtree: true, attributes: true });

  window.addEventListener("load", run);
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") run();
  });

  return () => {
    observer.disconnect();
    disableThemeContrastAudit();
  };
}
