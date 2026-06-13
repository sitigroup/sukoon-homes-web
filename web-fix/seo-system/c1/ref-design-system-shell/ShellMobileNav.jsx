"use client";

import { useEffect, useState } from "react";
import { cn } from "@/lib/utils";
import { MdClose, MdExpandLess, MdExpandMore } from "react-icons/md";
import ShellLangSwitcher from "./ShellLangSwitcher";
import { ShellLocationPanel, ShellLocationTrigger } from "./ShellLocationPopup";
import ShellSocialIcons from "./ShellSocialIcons";
import ShellWordmark from "./ShellWordmark";
import { NAV_SECTIONS, getSectionDrawerLinks } from "./shellNavConfig";

function DrawerSection({ title, links, t, expanded, onToggle }) {
  return (
    <div className="border-b border-sukoon-border last:border-b-0">
      <button
        type="button"
        className="flex min-h-[48px] w-full items-center justify-between px-sukoon-4 py-sukoon-3 text-left text-sukoon-body-sm font-semibold text-sukoon-graphite transition-colors duration-200 hover:bg-sukoon-page"
        onClick={onToggle}
        aria-expanded={expanded}
      >
        {title}
        {expanded ? (
          <MdExpandLess className="h-5 w-5 shrink-0 text-sukoon-graphite-muted" />
        ) : (
          <MdExpandMore className="h-5 w-5 shrink-0 text-sukoon-graphite-muted" />
        )}
      </button>
      {expanded ? (
        <ul className="space-y-0.5 px-sukoon-3 pb-sukoon-3">
          {links.map((key) => (
            <li key={key}>
              <button
                type="button"
                className="flex min-h-[44px] w-full items-center rounded-sukoon-input px-sukoon-3 text-left text-sukoon-body-sm text-sukoon-graphite-muted transition-colors duration-200 hover:bg-sukoon-page hover:text-sukoon-graphite"
              >
                {t(key)}
              </button>
            </li>
          ))}
        </ul>
      ) : null}
    </div>
  );
}

export default function ShellMobileNav({ t, lang, onLangChange, open, onClose, contained = false }) {
  const [expandedId, setExpandedId] = useState("");
  const [locationOpen, setLocationOpen] = useState(false);

  useEffect(() => {
    if (!open) return undefined;
    const onKeyDown = (event) => {
      if (event.key === "Escape") onClose?.();
    };
    window.addEventListener("keydown", onKeyDown);
    return () => window.removeEventListener("keydown", onKeyDown);
  }, [open, onClose]);

  useEffect(() => {
    if (!open) {
      setLocationOpen(false);
      setExpandedId("");
    }
  }, [open]);

  const positionClass = contained ? "absolute" : "fixed";
  const drawerWidth = contained ? "w-full max-w-[390px]" : "w-[min(100%,390px)]";

  return (
    <>
      <div
        className={cn(
          positionClass,
          "inset-0 z-40 bg-sukoon-graphite/20 backdrop-blur-[2px] transition-opacity duration-300",
          open ? "opacity-100" : "pointer-events-none opacity-0",
        )}
        aria-hidden={!open}
        onClick={onClose}
      />
      <aside
        className={cn(
          positionClass,
          "inset-y-0 right-0 z-50 flex flex-col overflow-hidden border-l border-sukoon-border bg-sukoon-background shadow-sukoon-md transition-transform duration-300 ease-out",
          drawerWidth,
          open ? "translate-x-0" : "pointer-events-none translate-x-full",
        )}
        aria-hidden={!open}
        aria-label={t("shMobileMenu")}
        role="dialog"
        aria-modal={open ? "true" : undefined}
      >
        <div className="flex min-w-0 items-center justify-between gap-sukoon-3 border-b border-sukoon-border px-sukoon-4 py-sukoon-4">
          <ShellWordmark t={t} compact />
          <button
            type="button"
            onClick={onClose}
            className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-sukoon-input text-sukoon-graphite transition-colors duration-200 hover:bg-sukoon-page"
            aria-label={t("shMobileClose")}
          >
            <MdClose className="h-5 w-5" aria-hidden />
          </button>
        </div>

        <div className="flex gap-sukoon-2 border-b border-sukoon-border px-sukoon-4 py-sukoon-3">
          <button
            type="button"
            className="min-h-[44px] min-w-0 flex-1 rounded-sukoon-button border border-sukoon-border bg-sukoon-background text-[0.8125rem] font-semibold text-sukoon-graphite transition-all duration-200 hover:bg-sukoon-page"
          >
            {t("shLoginRegister")}
          </button>
          <button
            type="button"
            className="min-h-[44px] min-w-0 flex-1 rounded-sukoon-button bg-sukoon-gold text-[0.8125rem] font-semibold text-sukoon-graphite shadow-sukoon-sm"
          >
            {t("shGetVerified")}
          </button>
        </div>

        <div className="border-b border-sukoon-border px-sukoon-4 py-sukoon-3">
          <ShellLocationTrigger
            t={t}
            active={locationOpen}
            onClick={() => setLocationOpen((value) => !value)}
            className="w-full justify-center"
          />
          {locationOpen ? (
            <div className="mt-sukoon-3">
              <ShellLocationPanel t={t} variant="sheet" onClose={() => setLocationOpen(false)} />
            </div>
          ) : null}
        </div>

        <nav className="min-h-0 flex-1 overflow-y-auto overflow-x-hidden">
          {NAV_SECTIONS.map((section) => (
            <DrawerSection
              key={section.id}
              title={t(section.labelKey)}
              links={getSectionDrawerLinks(section)}
              t={t}
              expanded={expandedId === section.id}
              onToggle={() => setExpandedId((id) => (id === section.id ? "" : section.id))}
            />
          ))}
        </nav>

        <div className="shrink-0 space-y-sukoon-4 border-t border-sukoon-border px-sukoon-4 py-sukoon-4">
          <div>
            <p className="mb-sukoon-2 text-[0.6875rem] font-semibold uppercase tracking-wide text-sukoon-graphite-subtle">
              {t("shDrawerLanguage")}
            </p>
            <ShellLangSwitcher lang={lang} onLangChange={onLangChange} t={t} />
          </div>

          <div>
            <a
              href={`mailto:${t("shFooterEmail")}`}
              className="block break-all text-sukoon-body-sm font-medium text-sukoon-graphite transition-colors duration-200 hover:text-sukoon-gold"
            >
              {t("shFooterEmail")}
            </a>
          </div>

          <ShellSocialIcons t={t} size="md" />
        </div>
      </aside>
    </>
  );
}
