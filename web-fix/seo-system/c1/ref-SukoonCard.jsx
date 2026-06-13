import { cn } from "@/lib/utils";

import getDesignSystem from "../getDesignSystem";

export default function SukoonCard({
  children,
  className,
  padding = "md",
  variant,
  soft = false,
  as: Tag = "div",
  ...props
}) {
  const { cardVariants, cardPaddingMap } = getDesignSystem();
  const resolvedVariant = variant ?? (soft ? "subtle" : "default");

  return (
    <Tag
      className={cn(
        cardVariants({ variant: resolvedVariant }),
        cardPaddingMap[padding],
        className,
      )}
      {...props}
    >
      {children}
    </Tag>
  );
}

/** Mode-aware card variant resolver — delegates to active UI mode pack. */
export function cardVariants(props) {
  return getDesignSystem().cardVariants(props);
}
