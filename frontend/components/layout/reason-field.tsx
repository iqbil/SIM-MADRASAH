"use client";

import { useId } from "react";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

export function ReasonField({
  disabled,
  error,
}: {
  disabled: boolean;
  error?: string;
}) {
  const id = useId();
  return (
    <div className="space-y-2">
      <Label htmlFor={id}>Alasan / dasar perubahan</Label>
      <Textarea
        id={id}
        aria-invalid={Boolean(error)}
        aria-describedby={error ? `${id}-error` : `${id}-hint`}
        name="reason"
        placeholder="Tuliskan dasar perubahan, minimal 10 karakter…"
        minLength={10}
        maxLength={1000}
        required
        disabled={disabled}
        className="min-h-20"
      />
      <p id={`${id}-hint`} className="text-xs leading-5 text-muted-foreground">
        Dicatat dalam audit. Jangan memasukkan password atau data konseling.
      </p>
      {error && (
        <p id={`${id}-error`} className="text-sm text-destructive">
          {error}
        </p>
      )}
    </div>
  );
}
