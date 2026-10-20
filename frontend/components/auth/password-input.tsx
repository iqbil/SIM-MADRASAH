"use client";

import { Eye, EyeOff } from "lucide-react";
import { type ComponentProps, type ReactNode, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

export function PasswordInput({
  startIcon,
  ...props
}: Omit<ComponentProps<typeof Input>, "type"> & { startIcon?: ReactNode }) {
  const [visible, setVisible] = useState(false);
  return (
    <div className="relative">
      {startIcon && (
        <span className="pointer-events-none absolute top-4 left-3.5 z-10 text-primary/70">
          {startIcon}
        </span>
      )}
      <Input
        {...props}
        type={visible ? "text" : "password"}
        className={`h-12 bg-white pr-12 text-base sm:text-sm ${startIcon ? "pl-11" : ""} ${props.className ?? ""}`}
      />
      <Button
        type="button"
        variant="ghost"
        size="icon"
        className="absolute top-1 right-1 size-10 text-muted-foreground"
        onClick={() => setVisible(!visible)}
        aria-label={visible ? "Sembunyikan password" : "Tampilkan password"}
        aria-pressed={visible}
      >
        {visible ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
      </Button>
    </div>
  );
}
