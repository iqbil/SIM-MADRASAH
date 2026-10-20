"use client";

import {
  CircleCheckIcon,
  InfoIcon,
  Loader2Icon,
  OctagonXIcon,
  TriangleAlertIcon,
} from "lucide-react";
import { Toaster as Sonner, type ToasterProps } from "sonner";

const Toaster = ({ ...props }: ToasterProps) => {
  return (
    <Sonner
      theme="light"
      position="bottom-right"
      closeButton
      richColors
      duration={5000}
      visibleToasts={3}
      offset={24}
      mobileOffset={{
        bottom: "max(16px, env(safe-area-inset-bottom))",
        left: 16,
        right: 16,
      }}
      containerAriaLabel="Notifikasi"
      customAriaLabel="Notifikasi portal madrasah"
      className="toaster group"
      icons={{
        success: <CircleCheckIcon className="size-4" />,
        info: <InfoIcon className="size-4" />,
        warning: <TriangleAlertIcon className="size-4" />,
        error: <OctagonXIcon className="size-4" />,
        loading: <Loader2Icon className="size-4 animate-spin" />,
      }}
      style={
        {
          "--normal-bg": "var(--popover)",
          "--normal-text": "var(--popover-foreground)",
          "--normal-border": "var(--border)",
          "--border-radius": "var(--radius)",
        } as React.CSSProperties
      }
      toastOptions={{
        classNames: {
          toast: "cn-toast font-sans rounded-xl! shadow-lg! text-sm! pr-14!",
          description: "text-xs! leading-relaxed!",
          closeButton:
            "top-1! right-1! left-auto! size-11! transform-none! rounded-full! border-0! bg-transparent! shadow-none!",
        },
        closeButtonAriaLabel: "Tutup notifikasi",
      }}
      {...props}
    />
  );
};

export { Toaster };
