"use client";

import { LoaderCircle } from "lucide-react";
import {
  type FormEventHandler,
  type ReactNode,
  useEffect,
  useRef,
} from "react";
import { Alert, AlertDescription } from "@/components/ui/alert";
import {
  AlertDialog,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import { useDialogFocus } from "@/hooks/use-dialog-focus";

export function ConfirmationDialog({
  title,
  description,
  children,
  busy,
  error,
  onClose,
  onSubmit,
  destructive = false,
  disabled = false,
  submitLabel = "Konfirmasi tindakan",
}: {
  title: string;
  description: ReactNode;
  children: ReactNode;
  busy: boolean;
  error?: string;
  onClose: () => void;
  onSubmit: FormEventHandler<HTMLFormElement>;
  destructive?: boolean;
  disabled?: boolean;
  submitLabel?: string;
}) {
  const errorRef = useRef<HTMLDivElement>(null);
  const focus = useDialogFocus();
  useEffect(() => {
    if (error) errorRef.current?.focus();
  }, [error]);
  return (
    <AlertDialog
      open
      onOpenChange={(open) => {
        if (!open && !busy) onClose();
      }}
    >
      <AlertDialogContent
        {...focus}
        className="flex max-h-[calc(100dvh-2rem)] max-w-md flex-col gap-0 overflow-hidden p-0"
        onEscapeKeyDown={(event) => {
          if (busy) event.preventDefault();
        }}
      >
        <AlertDialogHeader className="shrink-0 place-items-start border-b p-5 text-left">
          <AlertDialogTitle>{title}</AlertDialogTitle>
          <AlertDialogDescription className="break-words text-sm leading-6">
            {description}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <form
          onSubmit={onSubmit}
          aria-busy={busy}
          className="flex min-h-0 flex-col overflow-hidden"
        >
          <div className="min-h-0 overflow-y-auto overscroll-contain">
            <fieldset disabled={busy} className="min-w-0 space-y-4 p-5">
              {error && (
                <Alert variant="destructive" ref={errorRef} tabIndex={-1}>
                  <AlertDescription>{error}</AlertDescription>
                </Alert>
              )}
              {children}
            </fieldset>
          </div>
          <AlertDialogFooter className="relative z-10 mx-0 mb-0 shrink-0 rounded-none p-4 [&>button]:min-h-11">
            <AlertDialogCancel disabled={busy} className="h-11">
              Batal
            </AlertDialogCancel>
            <Button
              type="submit"
              variant={destructive ? "destructive" : "default"}
              className="h-11"
              disabled={busy || disabled}
            >
              {busy && (
                <LoaderCircle
                  className="size-4 animate-spin"
                  aria-hidden="true"
                />
              )}
              {busy ? "Memproses…" : submitLabel}
            </Button>
          </AlertDialogFooter>
        </form>
      </AlertDialogContent>
    </AlertDialog>
  );
}
