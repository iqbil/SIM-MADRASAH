"use client";

import { useRef } from "react";

export function useDialogFocus() {
  const previous = useRef<HTMLElement | null>(null);
  return {
    onOpenAutoFocus: () => {
      previous.current =
        document.activeElement instanceof HTMLElement
          ? document.activeElement
          : null;
    },
    onCloseAutoFocus: (event: Event) => {
      event.preventDefault();
      window.requestAnimationFrame(() => {
        if (document.querySelector('[role="dialog"], [role="alertdialog"]'))
          return;
        const target = previous.current?.isConnected
          ? previous.current
          : document.querySelector<HTMLElement>("main button:not(:disabled)");
        target?.focus();
      });
    },
  };
}
