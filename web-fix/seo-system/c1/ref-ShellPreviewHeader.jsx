"use client";

import { useState } from "react";
import { cn } from "@/lib/utils";
import { MdClose, MdMenu } from "react-icons/md";
import { SukoonButton } from "@/design-system";
import ShellMegaMenu from "./ShellMegaMenu";
import ShellMobileNav from "./ShellMobileNav";
import ShellUtilityBar from "./ShellUtilityBar";
import ShellWordmark from "./ShellWordmark";
import { NAV_SECTIONS } from "./shellNavConfig";

function NavLink({ label, expanded, onMouseEnter }) {
  return (
    <div className="relative shrink-0" onMouseEnter={onMouseEnter}>
      <button
        type="button"
        className={cn(
          "group relative whitespace-nowrap px-sukoon-2.5 py-2 text-[0.8125rem] font-medium tracking-tight transition-colors duration-200 lg:px-sukoon-3",
          expanded ? "text-sukoon-graphite" : "text-sukoon-graphite-muted hover:text-sukoon-graphite",
        )}
        aria-expanded={expanded}
      >
        {label}
        <span
          className={cn(
            "absolute bottom-0 left-sukoon-2.5 right-sukoon-2.5 h-px origin-left rounded-full bg-sukoon-gold transition-transform duration-200 lg:left-sukoon-3 lg:right-sukoon-3",
            expanded ? "scale-x-100" : "scale-x-0 group-hover:scale-x-100",
          )}
          aria-hidden
        />
      </button>
    </div>
  );
}

function VerifyCta({ t, className }) {
  return (
    <button
      type="button"
      className={cn(
        "inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-sukoon-button bg-sukoon-gold px-3 py-2 text-[0.75rem] font-semibold text-sukoon-graphite shadow-sukoon-sm transition-all duration-200 hover:bg-sukoon-gold/90 lg:px-sukoon-4 lg:text-[0.8125rem]",
        className,
      )}
    >
      {t("shGetVerified")}
    </button>
  );
}

export default function ShellPreviewHeader({
  t,
  lang = "en",
  onLangChange,
  variant = "desktop",
  megaOpen: megaOpenProp,
  defaultMegaSection = "buy",
  mobileNavOpen: mobileNavOpenProp,
  defaultMobileNavOpen = false,
  contained = false,
  sticky = false,
  elevated = false,
  className,
}) {
  const [megaHover, setMegaHover] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(defaultMobileNavOpen);
  const [activeSection, setActiveSection] = useState(defaultMegaSection);

  const megaOpen = megaOpenProp ?? megaHover;
  const isMobileNavControlled = mobileNavOpenProp !== undefined;
  const mobileNavOpen = isMobileNavControlled ? mobileNavOpenProp : mobileOpen;
  const closeMobileNav = () => {
    if (!isMobileNavControlled) setMobileOpen(false);
  };
  const toggleMobileNav = () => {
    if (!isMobileNavControlled) setMobileOpen((value) => !value);
  };

  if (variant === "mobile") {
    return (
      <>
        <header
          className={cn(
            "relative z-30 overflow-hidden border-b border-sukoon-border bg-sukoon-background shadow-sukoon-sm",
            sticky && "sticky top-0",
            elevated && "shadow-sukoon-card",
            className,
          )}
        >
          <div className="flex min-h-[60px] min-w-0 items-center justify-between gap-sukoon-3 px-sukoon-4 py-2">
            <ShellWordmark t={t} compact />
            <button
              type="button"
              className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-sukoon-input text-sukoon-graphite transition-all duration-200 hover:bg-sukoon-page"
              aria-label={mobileNavOpen ? t("shMobileClose") : t("shMobileMenu")}
              onClick={toggleMobileNav}
            >
              {mobileNavOpen ? <MdClose className="h-5 w-5" /> : <MdMenu className="h-5 w-5" />}
            </button>
          </div>
        </header>
        <ShellMobileNav
          t={t}
          lang={lang}
          onLangChange={onLangChange}
          open={mobileNavOpen}
          onClose={closeMobileNav}
          contained={contained}
        />
      </>
    );
  }

  return (
    <div
      className={cn(
        "relative overflow-hidden",
        sticky && "sticky top-0 z-50",
        className,
      )}
      onMouseLeave={() => setMegaHover(false)}
    >
      <ShellUtilityBar t={t} lang={lang} onLangChange={onLangChange} />

      <header
        className={cn(
          "border-b border-sukoon-border bg-sukoon-background",
          elevated ? "shadow-sukoon-card" : "shadow-sukoon-sm",
        )}
      >
        <div className="mx-auto flex min-h-[64px] max-w-6xl min-w-0 items-center gap-sukoon-2 px-sukoon-4 py-0 lg:gap-sukoon-3 lg:px-sukoon-6">
          <ShellWordmark t={t} />

          <nav
            className="hidden min-w-0 flex-1 items-center justify-center gap-0.5 lg:flex lg:gap-1"
            aria-label="Main"
          >
            {NAV_SECTIONS.map((section) => (
              <NavLink
                key={section.id}
                label={t(section.labelKey)}
                expanded={megaOpen && activeSection === section.id}
                onMouseEnter={() => {
                  setMegaHover(true);
                  setActiveSection(section.id);
                }}
              />
            ))}
          </nav>

          <div className="ml-auto flex shrink-0 items-center gap-sukoon-2">
            <SukoonButton variant="ghost" size="sm" className="hidden whitespace-nowrap md:inline-flex">
              {t("shLoginRegister")}
            </SukoonButton>
            <VerifyCta t={t} />
          </div>
        </div>
      </header>

      <ShellMegaMenu t={t} open={megaOpen} section={activeSection} />
    </div>
  );
}
