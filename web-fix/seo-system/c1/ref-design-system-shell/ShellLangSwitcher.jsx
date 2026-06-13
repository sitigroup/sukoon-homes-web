import { cn } from "@/lib/utils";

export default function ShellLangSwitcher({ lang, onLangChange, t, compact = false, className }) {
  return (
    <div
      className={cn(
        "inline-flex shrink-0 rounded-sukoon-input border border-sukoon-border bg-sukoon-background p-px",
        className,
      )}
      role="group"
      aria-label={t("shDrawerLanguage")}
    >
      {[
        { code: "en", label: compact ? t("shLangEnShort") : t("shLangEn") },
        { code: "hi", label: compact ? t("shLangHiShort") : t("shLangHi") },
      ].map(({ code, label }) => (
        <button
          key={code}
          type="button"
          onClick={() => onLangChange?.(code)}
          className={cn(
            "rounded-[10px] font-medium transition-all duration-200",
            compact ? "px-2 py-0.5 text-[0.6875rem]" : "px-2.5 py-1 text-[0.75rem]",
            lang === code
              ? "bg-sukoon-page text-sukoon-graphite shadow-sukoon-sm"
              : "text-sukoon-graphite-subtle hover:text-sukoon-gold",
          )}
        >
          {label}
        </button>
      ))}
    </div>
  );
}
