"use client";

import { ArrowLeft, ArrowRight } from "lucide-react";
import { Button } from "@/components/ui/button";

export function Pager({
  meta,
  setPage,
  busy = false,
}: {
  meta: { page: number; last_page: number; total: number };
  setPage: (page: number) => void;
  busy?: boolean;
}) {
  return (
    <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t pt-4">
      <p className="text-xs text-muted-foreground">
        {meta.total} hasil · Halaman {meta.page} dari {meta.last_page}
      </p>
      <div className="flex gap-2">
        <Button
          type="button"
          variant="outline"
          size="icon"
          className="size-11"
          aria-label="Halaman sebelumnya"
          disabled={busy || meta.page <= 1}
          onClick={() => setPage(meta.page - 1)}
        >
          <ArrowLeft className="size-3.5" />
        </Button>
        <Button
          type="button"
          variant="outline"
          size="icon"
          className="size-11"
          aria-label="Halaman berikutnya"
          disabled={busy || meta.page >= meta.last_page}
          onClick={() => setPage(meta.page + 1)}
        >
          <ArrowRight className="size-3.5" />
        </Button>
      </div>
    </div>
  );
}
