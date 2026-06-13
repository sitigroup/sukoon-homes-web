import { cn } from "@/lib/utils";

export default function SukoonStat({
  label,
  value,
  hint,
  className,
  align = "left",
}) {
  return (
    <div
      className={cn(
        "flex flex-col gap-sukoon-1",
        align === "center" && "items-center text-center",
        className,
      )}
    >
      {value != null ? (
        <div className="text-sukoon-stat tabular-nums text-sukoon-graphite">{value}</div>
      ) : null}
      {label ? (
        <div className="text-sukoon-caption font-medium uppercase tracking-wide text-sukoon-graphite-subtle">
          {label}
        </div>
      ) : null}
      {hint ? (
        <p className="text-sukoon-caption text-sukoon-graphite-muted">{hint}</p>
      ) : null}
    </div>
  );
}
