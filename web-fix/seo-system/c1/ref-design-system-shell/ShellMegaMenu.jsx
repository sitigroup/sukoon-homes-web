import { cn } from "@/lib/utils";
import { SukoonButton } from "@/design-system";
import { MdCheckCircle } from "react-icons/md";
import { getFeaturedTitleKey, getNavSection } from "./shellNavConfig";

function MegaMenuItem({ icon: Icon, label, highlight = false }) {
  return (
    <li>
      <button
        type="button"
        className={cn(
          "group flex w-full items-center gap-sukoon-3 rounded-sukoon-input px-sukoon-2 py-2 text-left transition-colors duration-200 hover:bg-sukoon-page",
          highlight && "bg-sukoon-page/60",
        )}
      >
        <span
          className={cn(
            "flex h-8 w-8 shrink-0 items-center justify-center rounded-sukoon-input border bg-sukoon-background text-sukoon-graphite-muted transition-colors duration-200 group-hover:border-sukoon-gold/35 group-hover:text-sukoon-graphite",
            highlight ? "border-sukoon-gold/40 text-sukoon-gold" : "border-sukoon-border",
          )}
        >
          <Icon className="h-4 w-4" aria-hidden />
        </span>
        <span
          className={cn(
            "text-sukoon-body-sm font-medium transition-colors duration-200 group-hover:text-sukoon-graphite",
            highlight ? "font-semibold text-sukoon-graphite" : "text-sukoon-graphite",
          )}
        >
          {label}
        </span>
      </button>
    </li>
  );
}

function MegaColumn({ title, accent, children }) {
  return (
    <div className="min-w-0 flex-1">
      <p
        className={cn(
          "mb-sukoon-3 border-b border-sukoon-border pb-sukoon-2 text-[0.6875rem] font-semibold uppercase tracking-[0.14em]",
          accent ? "text-sukoon-gold" : "text-sukoon-graphite-muted",
        )}
      >
        {title}
      </p>
      <ul className="space-y-0.5">{children}</ul>
    </div>
  );
}

function FeaturedPanel({ t, variant }) {
  const titleKey = getFeaturedTitleKey(variant);

  return (
    <div className="flex min-w-0 flex-1 flex-col border-t border-sukoon-border pt-sukoon-6 lg:border-l lg:border-t-0 lg:pl-sukoon-5 lg:pt-0">
      <p className="text-[0.6875rem] font-semibold uppercase tracking-[0.12em] text-sukoon-gold">
        {t("shMegaFeaturedLabel")}
      </p>
      <p className="mt-sukoon-2 text-sukoon-body font-semibold text-sukoon-graphite">{t(titleKey)}</p>
      <p className="mt-sukoon-1 text-sukoon-body-sm font-medium text-sukoon-graphite">{t("shMegaFeaturedHeadline")}</p>
      <p className="mt-sukoon-2 text-[1.75rem] font-bold leading-none tabular-nums text-sukoon-gold">₹49</p>
      <ul className="mt-sukoon-4 space-y-sukoon-2">
        {[
          t("shMegaFeaturedBenefit1"),
          t("shMegaFeaturedBenefit2"),
          t("shMegaFeaturedBenefit3"),
          t("shMegaFeaturedBenefit4"),
        ].map((benefit) => (
          <li key={benefit} className="flex items-start gap-sukoon-2 text-sukoon-body-sm text-sukoon-graphite-muted">
            <MdCheckCircle className="mt-0.5 h-4 w-4 shrink-0 text-sukoon-success-text" aria-hidden />
            {benefit}
          </li>
        ))}
      </ul>
      <SukoonButton variant="primary" size="sm" className="mt-sukoon-5 w-full">
        {t("shMegaFeaturedCta")}
      </SukoonButton>
    </div>
  );
}

export default function ShellMegaMenu({ t, open, section = "buy", className }) {
  if (!open) return null;

  const config = getNavSection(section);

  if (config.simple) {
    return (
      <div className={cn("absolute left-0 right-0 top-full z-40 px-sukoon-4 pb-sukoon-4 pt-sukoon-2 sm:px-sukoon-6", className)}>
        <div
          className="mx-auto max-w-md overflow-hidden rounded-sukoon-card border border-sukoon-border-strong bg-sukoon-background shadow-sukoon-md"
          role="region"
          aria-label={t(config.labelKey)}
        >
          <div className="px-sukoon-6 py-sukoon-6">
            <p className="mb-sukoon-4 border-b border-sukoon-border pb-sukoon-2 text-[0.6875rem] font-semibold uppercase tracking-[0.14em] text-sukoon-gold">
              {t(config.labelKey)}
            </p>
            <ul className="grid gap-0.5 sm:grid-cols-2">
              {config.items.map(({ key, icon, highlight }) => (
                <MegaMenuItem key={key} icon={icon} label={t(key)} highlight={highlight} />
              ))}
            </ul>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className={cn("absolute left-0 right-0 top-full z-40 px-sukoon-4 pb-sukoon-4 pt-sukoon-2 sm:px-sukoon-6", className)}>
      <div
        className="mx-auto max-w-6xl overflow-hidden rounded-sukoon-card border border-sukoon-border-strong bg-sukoon-background shadow-sukoon-md"
        role="region"
        aria-label={t(config.labelKey)}
        onMouseEnter={(e) => e.stopPropagation()}
      >
        <div className="px-sukoon-6 py-sukoon-8 lg:px-sukoon-8">
          <div className="grid items-start gap-sukoon-6 lg:grid-cols-4 lg:gap-sukoon-5">
            {config.columns.map((column) => (
              <MegaColumn key={column.titleKey} title={t(column.titleKey)} accent={column.accent}>
                {column.items.map(({ key, icon, highlight }) => (
                  <MegaMenuItem key={key} icon={icon} label={t(key)} highlight={highlight} />
                ))}
              </MegaColumn>
            ))}
            <FeaturedPanel t={t} variant={config.featured} />
          </div>
        </div>
      </div>
    </div>
  );
}
