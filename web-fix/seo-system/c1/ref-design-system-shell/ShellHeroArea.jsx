import { cn } from "@/lib/utils";
import ShellSearchBar from "./ShellSearchBar";
import { ShellLocationTrigger } from "./ShellLocationPopup";

const HERO_CITY_KEYS = ["shAreaBarmer", "shAreaJodhpur", "shAreaJaipur", "shAreaUdaipur"];

export default function ShellHeroArea({ t, className }) {
  return (
    <div className={cn("border-b border-sukoon-border bg-sukoon-page px-sukoon-4 py-sukoon-10 sm:px-sukoon-8 sm:py-sukoon-12", className)}>
      <div className="mx-auto w-full max-w-[760px] text-center">
        <h2 className="text-balance text-[1.375rem] font-semibold leading-tight tracking-tight text-sukoon-graphite sm:text-[1.625rem]">
          {t("shHeroHeadline")}
        </h2>
        <p className="mx-auto mt-sukoon-3 max-w-xl text-balance text-sukoon-body-sm font-medium leading-relaxed text-sukoon-graphite-muted sm:text-[0.9375rem]">
          {t("shHeroSubheadline")}
        </p>

        <div className="mt-sukoon-5 flex justify-center">
          <ShellLocationTrigger t={t} />
        </div>

        <div className="mt-sukoon-5">
          <ShellSearchBar t={t} demoState="idle" interactive size="hero" />
        </div>

        <div className="mt-sukoon-5">
          <p className="text-[0.6875rem] font-semibold uppercase tracking-[0.12em] text-sukoon-graphite-subtle">
            {t("shHeroPopularCities")}
          </p>
          <div className="mt-sukoon-3 flex flex-wrap items-center justify-center gap-sukoon-2">
            {HERO_CITY_KEYS.map((key) => (
              <button
                key={key}
                type="button"
                className="inline-flex min-h-[36px] items-center rounded-full border border-sukoon-border bg-sukoon-background px-sukoon-4 text-sukoon-caption font-medium text-sukoon-graphite-muted transition-colors duration-200 hover:border-sukoon-gold/40 hover:text-sukoon-gold"
              >
                {t(key)}
              </button>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
