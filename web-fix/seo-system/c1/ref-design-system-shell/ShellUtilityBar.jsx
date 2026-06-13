"use client";

import { useState } from "react";
import { cn } from "@/lib/utils";
import ShellLangSwitcher from "./ShellLangSwitcher";
import { ShellLocationPanel, ShellLocationTrigger } from "./ShellLocationPopup";
import ShellSocialIcons from "./ShellSocialIcons";

export default function ShellUtilityBar({ t, lang, onLangChange, className }) {
  const [locationOpen, setLocationOpen] = useState(false);

  return (
    <div
      className={cn(
        "hidden border-b border-sukoon-border bg-sukoon-page/80 lg:block",
        className,
      )}
    >
      <div className="mx-auto flex h-8 max-w-6xl min-w-0 items-center justify-between gap-sukoon-3 px-sukoon-4 lg:px-sukoon-6">
        <div className="flex min-w-0 items-center gap-sukoon-4">
          <a
            href={`mailto:${t("shFooterEmail")}`}
            className="truncate text-[0.75rem] font-medium text-sukoon-graphite-muted transition-colors duration-200 hover:text-sukoon-gold"
          >
            {t("shFooterEmail")}
          </a>
        </div>

        <div className="flex shrink-0 items-center gap-sukoon-3">
          <ShellSocialIcons t={t} />
          <div className="h-4 w-px bg-sukoon-border" aria-hidden />
          <ShellLangSwitcher lang={lang} onLangChange={onLangChange} t={t} compact />
          <div className="relative">
            <ShellLocationTrigger
              t={t}
              active={locationOpen}
              onClick={() => setLocationOpen((open) => !open)}
            />
            {locationOpen ? (
              <>
                <button
                  type="button"
                  className="fixed inset-0 z-40 cursor-default"
                  aria-label={t("shMobileClose")}
                  onClick={() => setLocationOpen(false)}
                />
                <div className="absolute right-0 top-[calc(100%+0.375rem)] z-50">
                  <ShellLocationPanel t={t} variant="dropdown" onClose={() => setLocationOpen(false)} />
                </div>
              </>
            ) : null}
          </div>
        </div>
      </div>
    </div>
  );
}
