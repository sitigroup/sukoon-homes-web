import { cn } from "@/lib/utils";
import { SUKOON_SHELL_LOGO_URL } from "./shellBrand";

export default function ShellWordmark({ t, compact = false, className }) {
  return (
    <div className={cn("flex shrink-0 items-center", className)}>
      <img
        src={SUKOON_SHELL_LOGO_URL}
        alt={t("shLogoAlt")}
        className={cn("w-auto object-contain object-left", compact ? "max-h-11" : "max-h-[64px]")}
        width={compact ? 140 : 200}
        height={compact ? 44 : 64}
        decoding="async"
      />
    </div>
  );
}
