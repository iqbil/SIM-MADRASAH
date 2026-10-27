"use client";

import { Database } from "lucide-react";
import type { ReactNode } from "react";
import { Button } from "@/components/ui/button";
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty";

export function MasterHeading({
  title,
  description,
  action,
}: {
  title: string;
  description: string;
  action?: ReactNode;
}) {
  return (
    <div className="flex min-w-0 flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div className="min-w-0">
        <p className="mb-2 text-xs text-muted-foreground">
          Administrasi / Data master
        </p>
        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
          {title}
        </h1>
        <p className="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
          {description}
        </p>
      </div>
      {action && (
        <div className="shrink-0 [&>button]:w-full sm:[&>button]:w-auto">
          {action}
        </div>
      )}
    </div>
  );
}
export {
  DataFailure as MasterFailure,
  DataLoading as MasterLoading,
} from "@/components/layout/data-feedback";
export function MasterEmpty({
  search,
  reset,
}: {
  search?: boolean;
  reset?: () => void;
}) {
  return (
    <Empty className="min-h-64">
      <EmptyHeader>
        <EmptyMedia variant="icon">
          <Database aria-hidden="true" />
        </EmptyMedia>
        <EmptyTitle>
          <h2>{search ? "Data tidak ditemukan" : "Belum ada data"}</h2>
        </EmptyTitle>
        <EmptyDescription>
          {search
            ? "Coba kata kunci lain atau hapus filter untuk melihat data yang tersedia."
            : "Tambahkan data pertama untuk melengkapi administrasi madrasah."}
        </EmptyDescription>
      </EmptyHeader>
      {search && reset && (
        <Button variant="outline" className="h-11" onClick={reset}>
          Hapus filter
        </Button>
      )}
    </Empty>
  );
}
