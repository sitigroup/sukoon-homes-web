import { cn } from "@/lib/utils";
import { FaFacebookF, FaInstagram, FaLinkedinIn, FaWhatsapp } from "react-icons/fa";

const SOCIAL_ITEMS = [
  { id: "facebook", icon: FaFacebookF, labelKey: "shSocialFacebook" },
  { id: "instagram", icon: FaInstagram, labelKey: "shSocialInstagram" },
  { id: "whatsapp", icon: FaWhatsapp, labelKey: "shSocialWhatsApp" },
  { id: "linkedin", icon: FaLinkedinIn, labelKey: "shSocialLinkedIn" },
];

export default function ShellSocialIcons({ t, size = "sm", className }) {
  const iconSize = size === "md" ? "h-4 w-4" : "h-3.5 w-3.5";
  const buttonSize = size === "md" ? "h-11 w-11" : "h-8 w-8";

  return (
    <div className={cn("flex items-center gap-1", className)}>
      {SOCIAL_ITEMS.map(({ id, icon: Icon, labelKey }) => (
        <button
          key={id}
          type="button"
          aria-label={t(labelKey)}
          className={cn(
            "inline-flex items-center justify-center rounded-full border border-transparent text-sukoon-graphite-muted transition-colors duration-200 hover:border-sukoon-gold/30 hover:text-sukoon-gold",
            buttonSize,
          )}
        >
          <Icon className={iconSize} aria-hidden />
        </button>
      ))}
    </div>
  );
}
