"use client";

import { tv, cn } from "./trustVerificationTheme";

export default function TrustCard({
  children,
  className = "",
  padding = "p-5 sm:p-6",
  hover = false,
  accent = false,
  as: Component = "div",
  ...props
}) {
  return (
    <Component
      className={cn(
        tv.card,
        padding,
        hover && tv.cardHover,
        accent && "border-[rgba(184,154,74,0.22)] shadow-[0_8px_40px_rgba(184,154,74,0.06)]",
        className
      )}
      {...props}
    >
      {children}
    </Component>
  );
}
