import { cn } from "@/lib/utils";
import { useId } from "react";

import getDesignSystem from "../getDesignSystem";

export default function SukoonInput({
  label,
  hint,
  error,
  className,
  inputClassName,
  id: idProp,
  required,
  disabled,
  ...props
}) {
  const { inputStyles } = getDesignSystem();
  const autoId = useId();
  const id = idProp || autoId;
  const hintId = hint ? `${id}-hint` : undefined;
  const errorId = error ? `${id}-error` : undefined;

  return (
    <div className={cn(inputStyles.wrapper, className)}>
      {label ? (
        <label htmlFor={id} className={inputStyles.label}>
          {label}
          {required ? (
            <span className={inputStyles.required} aria-hidden>
              *
            </span>
          ) : null}
        </label>
      ) : null}
      <input
        id={id}
        className={cn(
          inputStyles.field,
          error && inputStyles.fieldError,
          disabled && inputStyles.fieldDisabled,
          inputClassName,
        )}
        aria-invalid={error ? true : undefined}
        aria-describedby={[hintId, errorId].filter(Boolean).join(" ") || undefined}
        required={required}
        disabled={disabled}
        {...props}
      />
      {hint && !error ? (
        <p id={hintId} className={inputStyles.hint}>
          {hint}
        </p>
      ) : null}
      {error ? (
        <p id={errorId} className={inputStyles.error} role="alert">
          {error}
        </p>
      ) : null}
    </div>
  );
}
