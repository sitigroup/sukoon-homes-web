import { cn } from "@/lib/utils";

import getDesignSystem from "../getDesignSystem";

export default function SukoonButton({
  children,
  className,
  variant,
  size,
  loading = false,
  disabled,
  type = "button",
  ...props
}) {
  const { buttonVariants, buttonSpinnerClasses } = getDesignSystem();
  const resolvedVariant = variant ?? "primary";

  return (
    <button
      type={type}
      className={cn(buttonVariants({ variant: resolvedVariant, size }), className)}
      disabled={disabled || loading}
      aria-busy={loading || undefined}
      {...props}
    >
      {loading ? (
        <span
          className={cn(
            "h-4 w-4 animate-spin rounded-full border-2 border-t-transparent",
            buttonSpinnerClasses[resolvedVariant] ?? buttonSpinnerClasses.primary,
          )}
          aria-hidden
        />
      ) : null}
      {children}
    </button>
  );
}

/** Mode-aware button variant resolver — delegates to active UI mode pack. */
export function buttonVariants(props) {
  return getDesignSystem().buttonVariants(props);
}
