"use client";

import { LoaderCircle } from "lucide-react";
import {
  type FormEventHandler,
  type ReactNode,
  useEffect,
  useRef,
} from "react";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { useDialogFocus } from "@/hooks/use-dialog-focus";

export function FormDialog({
  title,
  description,
  children,
  busy = false,
  error,
  onClose,
  onCancel,
  onSubmit,
  submitLabel,
  cancelLabel = "Batal",
  submitDisabled = false,
}: {
  title: string;
  description: string;
  children: ReactNode;
  busy?: boolean;
  error?: string;
  onClose: () => void;
  onCancel?: () => void;
  onSubmit?: FormEventHandler<HTMLFormElement>;
  submitLabel?: string;
  cancelLabel?: string;
  submitDisabled?: boolean;
}) {
  const errorRef = useRef<HTMLDivElement>(null);
  const focus = useDialogFocus();
  useEffect(() => {
    if (error) errorRef.current?.focus();
  }, [error]);
  return (
    <Dialog
      open
      onOpenChange={(open) => {
        if (!open && !busy) onClose();
      }}
    >
      <DialogContent
        {...focus}
        showCloseButton={!busy}
        onEscapeKeyDown={(event) => {
          if (busy) event.preventDefault();
        }}
        onInteractOutside={(event) => {
          if (busy) event.preventDefault();
        }}
        className="flex max-h-[calc(100dvh-2rem)] max-w-lg flex-col gap-0 overflow-hidden p-0 max-sm:top-auto max-sm:bottom-0 max-sm:w-full max-sm:translate-y-0 max-sm:rounded-b-none"
      >
        <DialogHeader className="shrink-0 border-b p-5 pr-14">
          <DialogTitle>{title}</DialogTitle>
          <DialogDescription className="break-words text-sm leading-6">
            {description}
          </DialogDescription>
        </DialogHeader>
        <form
          onSubmit={onSubmit ?? ((event) => event.preventDefault())}
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
          <DialogFooter className="relative z-10 mx-0 mb-0 shrink-0 rounded-none p-4 pb-[max(1rem,env(safe-area-inset-bottom))] [&>button]:min-h-11">
            <Button
              type="button"
              variant="outline"
              className="h-11"
              disabled={busy}
              onClick={onCancel ?? onClose}
            >
              {cancelLabel}
            </Button>
            {submitLabel && (
              <Button
                type="submit"
                className="h-11"
                disabled={busy || submitDisabled}
              >
                {busy && (
                  <LoaderCircle
                    className="size-4 animate-spin"
                    aria-hidden="true"
                  />
                )}
                {busy ? "Menyimpan…" : submitLabel}
              </Button>
            )}
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
