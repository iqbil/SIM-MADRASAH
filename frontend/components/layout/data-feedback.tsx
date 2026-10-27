"use client";

import { AlertCircle, RefreshCw } from "lucide-react";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";

export function DataFailure({
  message,
  retry,
}: {
  message: string;
  retry?: () => void;
}) {
  return (
    <Alert variant="destructive">
      <AlertCircle aria-hidden="true" />
      <AlertDescription className="space-y-3">
        <p>{message}</p>
        {retry && (
          <Button variant="outline" className="h-11" onClick={retry}>
            <RefreshCw className="size-4" aria-hidden="true" />
            Coba kembali
          </Button>
        )}
      </AlertDescription>
    </Alert>
  );
}

export function DataLoading() {
  return (
    <output className="block space-y-3" aria-label="Memuat data">
      {[1, 2, 3].map((key) => (
        <Skeleton key={key} className="h-24 w-full rounded-xl" />
      ))}
    </output>
  );
}
