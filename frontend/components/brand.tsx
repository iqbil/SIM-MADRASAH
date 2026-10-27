import { cn } from "@/lib/utils";

export function SchoolMark({ className }: { className?: string }) {
  return (
    <span
      className={cn(
        "inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary text-white",
        className,
      )}
    >
      <svg
        viewBox="0 0 36 36"
        fill="none"
        className="size-8"
        aria-hidden="true"
      >
        <path
          d="M18 6.5 27 12v10.5L18 28l-9-5.5V12L18 6.5Z"
          stroke="currentColor"
          strokeWidth="1.4"
        />
        <path
          d="M18 15.5c-2-1.7-4.3-2-6.5-1.2v8c2.2-.8 4.5-.5 6.5 1.2m0-8c2-1.7 4.3-2 6.5-1.2v8c-2.2-.8-4.5-.5-6.5 1.2m0-8v8M18 9v3m-1.5-1.5h3"
          stroke="currentColor"
          strokeWidth="1.4"
          strokeLinecap="round"
          strokeLinejoin="round"
        />
      </svg>
    </span>
  );
}

export function Brand({
  light = false,
  compact = false,
}: {
  light?: boolean;
  compact?: boolean;
}) {
  return (
    <div className="flex min-w-0 items-center gap-3">
      <SchoolMark
        className={cn(
          light && "bg-white/10 text-white ring-1 ring-white/15",
          compact && "size-10",
        )}
      />
      <div
        className={cn(
          "min-w-0",
          compact && "group-data-[collapsible=icon]:hidden",
        )}
      >
        <p
          className={cn(
            "truncate text-sm font-semibold tracking-tight",
            light && "text-white",
          )}
        >
          MTs Al Karimiyyah
        </p>
        <p
          className={cn(
            "mt-0.5 text-xs tracking-wide text-muted-foreground",
            light && "text-emerald-100/65",
          )}
        >
          Sistem Informasi Madrasah
        </p>
      </div>
    </div>
  );
}
