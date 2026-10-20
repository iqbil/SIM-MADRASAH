"use client";
import { LoaderCircle, RefreshCw } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import { LogoutButton } from "@/components/auth/logout-button";
import { Button } from "@/components/ui/button";
import { AuthRequestError, renewSession } from "@/lib/auth/client";
export function RenewSession({ next }: { next: string }) {
  const [error, setError] = useState(false);
  const renew = useCallback(async () => {
    setError(false);
    try {
      const result = await renewSession();
      window.location.replace(
        result.redirect === "/ganti-password" ? result.redirect : next,
      );
    } catch (reason) {
      if (
        reason instanceof AuthRequestError &&
        [401, 403].includes(reason.status)
      )
        window.location.replace("/login?error=session-expired");
      else setError(true);
    }
  }, [next]);
  useEffect(() => {
    void renew();
  }, [renew]);
  return (
    <div className="text-center">
      <div className="mx-auto mb-5 flex size-14 items-center justify-center rounded-2xl bg-primary/7">
        {error ? (
          <RefreshCw className="size-6 text-primary" />
        ) : (
          <LoaderCircle className="size-6 animate-spin text-primary" />
        )}
      </div>
      <h1 className="text-xl font-semibold tracking-tight">
        {error ? "Koneksi belum tersedia" : "Menyiapkan ruang kerja Anda"}
      </h1>
      <p className="mt-3 text-sm leading-7 text-muted-foreground">
        {error
          ? "Kami belum dapat menghubungi layanan madrasah. Silakan coba kembali."
          : "Mohon tunggu sebentar. Kami sedang memperbarui sesi Anda."}
      </p>
      {error && (
        <div className="mt-6 flex flex-col items-center gap-2">
          <Button onClick={renew} className="h-11">
            Coba kembali
          </Button>
          <LogoutButton />
        </div>
      )}
    </div>
  );
}
