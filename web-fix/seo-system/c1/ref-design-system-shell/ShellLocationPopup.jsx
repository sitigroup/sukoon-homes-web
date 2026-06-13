"use client";

import { cn } from "@/lib/utils";
import { MdClose, MdLocationOn, MdMyLocation, MdSearch } from "react-icons/md";
import { POPULAR_AREAS } from "./shellNavConfig";

export function ShellLocationTrigger({ t, onClick, active = false, className }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        "inline-flex min-h-[32px] shrink-0 items-center gap-1.5 rounded-full border px-3 py-1 text-[0.75rem] font-medium transition-colors duration-200",
        active
          ? "border-sukoon-gold/50 bg-sukoon-gold-soft/20 text-sukoon-graphite"
          : "border-sukoon-border bg-sukoon-background text-sukoon-graphite-muted hover:border-sukoon-gold/35 hover:text-sukoon-gold",
        className,
      )}
    >
      <MdLocationOn className="h-3.5 w-3.5 shrink-0" aria-hidden />
      <span>{t("shLocationSelect")}</span>
    </button>
  );
}

export function ShellLocationPanel({ t, variant = "dropdown", onClose, className }) {
  const isSheet = variant === "sheet";

  return (
    <div
      className={cn(
        "overflow-hidden rounded-sukoon-card border border-sukoon-border-strong bg-sukoon-background shadow-sukoon-md",
        isSheet ? "w-full" : "w-[min(100vw-2rem,360px)]",
        className,
      )}
      role="dialog"
      aria-label={t("shLocationSelectTitle")}
    >
      <div className="flex items-start justify-between gap-sukoon-3 border-b border-sukoon-border px-sukoon-4 py-sukoon-4">
        <div className="min-w-0">
          <p className="text-sukoon-body-sm font-semibold text-sukoon-graphite">{t("shLocationSelectTitle")}</p>
          <p className="mt-sukoon-1 text-sukoon-caption leading-relaxed text-sukoon-graphite-muted">
            {t("shLocationSelectSubtitle")}
          </p>
        </div>
        {onClose ? (
          <button
            type="button"
            onClick={onClose}
            className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-sukoon-input text-sukoon-graphite-muted transition-colors duration-200 hover:bg-sukoon-page hover:text-sukoon-graphite"
            aria-label={t("shMobileClose")}
          >
            <MdClose className="h-4 w-4" />
          </button>
        ) : null}
      </div>

      <div className="space-y-sukoon-4 px-sukoon-4 py-sukoon-4">
        <div className="relative">
          <MdSearch
            className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-sukoon-graphite-subtle"
            aria-hidden
          />
          <input
            type="text"
            readOnly
            placeholder={t("shLocationSearchPlaceholder")}
            className="w-full rounded-sukoon-input border border-sukoon-border bg-sukoon-page py-2.5 pl-10 pr-3 text-sukoon-body-sm text-sukoon-graphite placeholder:text-sukoon-graphite-subtle focus:border-sukoon-gold/50 focus:outline-none focus:ring-2 focus:ring-sukoon-gold/20"
          />
        </div>

        <button
          type="button"
          className="flex min-h-[44px] w-full items-center justify-center gap-sukoon-2 rounded-sukoon-button border border-sukoon-border bg-sukoon-background text-sukoon-body-sm font-medium text-sukoon-graphite transition-colors duration-200 hover:border-sukoon-gold/35 hover:text-sukoon-gold"
        >
          <MdMyLocation className="h-4 w-4" aria-hidden />
          {t("shLocationUseCurrent")}
        </button>

        <div>
          <p className="text-[0.6875rem] font-semibold uppercase tracking-[0.12em] text-sukoon-graphite-muted">
            {t("shLocationPopular")}
          </p>
          <ul className="mt-sukoon-2 space-y-0.5">
            {POPULAR_AREAS.map(({ key }) => (
              <li key={key}>
                <button
                  type="button"
                  className="flex min-h-[44px] w-full items-center rounded-sukoon-input px-sukoon-2 text-left text-sukoon-body-sm text-sukoon-graphite transition-colors duration-200 hover:bg-sukoon-page"
                >
                  {t(key)}
                </button>
              </li>
            ))}
          </ul>
        </div>

        <div className="border-t border-sukoon-border pt-sukoon-3">
          <p className="text-[0.6875rem] font-semibold uppercase tracking-[0.12em] text-sukoon-graphite-muted">
            {t("shLocationRecent")}
          </p>
          <div className="mt-sukoon-2 flex flex-wrap gap-sukoon-2">
            {[t("shAreaJodhpur"), t("shAreaBarmer")].map((label) => (
              <span
                key={label}
                className="inline-flex items-center rounded-full border border-sukoon-border bg-sukoon-page px-3 py-1 text-sukoon-caption font-medium text-sukoon-graphite-muted"
              >
                {label}
              </span>
            ))}
          </div>
        </div>

        <button
          type="button"
          className="text-sukoon-caption font-medium text-sukoon-graphite-muted transition-colors duration-200 hover:text-sukoon-gold"
        >
          {t("shLocationClear")}
        </button>
      </div>
    </div>
  );
}

/** Interactive location selector — preview/static only, no GPS or persistence */
export default function ShellLocationSelector({ t, variant = "dropdown", className }) {
  return (
    <div className={cn("relative", className)}>
      <ShellLocationTrigger t={t} active />
      {variant === "dropdown" ? (
        <div className="absolute right-0 top-[calc(100%+0.5rem)] z-50">
          <ShellLocationPanel t={t} variant="dropdown" />
        </div>
      ) : null}
    </div>
  );
}

/** Preview page block: closed trigger + open popup side by side */
export function ShellLocationPreviewBlock({ t, mobile = false }) {
  return (
    <div className={cn("grid gap-sukoon-6", mobile ? "grid-cols-1" : "md:grid-cols-2")}>
      <div className="rounded-sukoon-card border border-sukoon-border bg-sukoon-page p-sukoon-6">
        <p className="mb-sukoon-4 text-sukoon-caption font-semibold uppercase tracking-[0.1em] text-sukoon-graphite-muted">
          {t("shLocationPreviewClosed")}
        </p>
        <ShellLocationTrigger t={t} />
      </div>
      <div className="rounded-sukoon-card border border-sukoon-border bg-sukoon-page p-sukoon-6">
        <p className="mb-sukoon-4 text-sukoon-caption font-semibold uppercase tracking-[0.1em] text-sukoon-graphite-muted">
          {t("shLocationPreviewOpen")}
        </p>
        <ShellLocationPanel t={t} variant={mobile ? "sheet" : "dropdown"} />
      </div>
    </div>
  );
}
