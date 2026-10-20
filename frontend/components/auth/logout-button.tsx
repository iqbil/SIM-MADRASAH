"use client";

import { LoaderCircle, LogOut } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { DropdownMenuItem } from "@/components/ui/dropdown-menu";
import { authRequest } from "@/lib/auth/client";

export function LogoutButton({ compact = false }: { compact?: boolean }) {
  const [busy, setBusy] = useState(false);
  async function logout() {
    if (busy) return;
    setBusy(true);
    try {
      await authRequest("logout", {});
      window.location.replace("/login");
    } catch {
      toast.error("Belum berhasil keluar. Silakan coba kembali.");
      setBusy(false);
    }
  }
  const content = (
    <>
      {busy ? (
        <LoaderCircle className="size-4 animate-spin" />
      ) : (
        <LogOut className="size-4" />
      )}
      {busy ? "Sedang keluar…" : "Keluar dari akun"}
    </>
  );
  if (compact)
    return (
      <DropdownMenuItem
        disabled={busy}
        variant="destructive"
        className="min-h-11 gap-3"
        onSelect={(event) => {
          event.preventDefault();
          void logout();
        }}
      >
        {content}
      </DropdownMenuItem>
    );
  return (
    <Button
      type="button"
      variant="ghost"
      onClick={logout}
      disabled={busy}
      className="h-11 gap-2 text-muted-foreground"
    >
      {content}
    </Button>
  );
}
