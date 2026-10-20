"use client";
import { RefreshCw } from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
export default function ErrorPage({
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return (
    <main className="flex min-h-svh flex-col items-center justify-center px-6 text-center">
      <RefreshCw className="mb-5 size-9 text-primary/70" />
      <h1 className="text-xl font-semibold">Halaman belum dapat dimuat</h1>
      <p className="mt-3 max-w-sm text-sm leading-7 text-muted-foreground">
        Layanan madrasah sedang tidak tersedia. Silakan tunggu sebentar dan coba
        kembali.
      </p>
      <div className="mt-6 flex flex-wrap justify-center gap-3">
        <Button onClick={reset} className="h-11">
          Coba kembali
        </Button>
        <Button asChild variant="outline" className="h-11">
          <Link href="/login">Ke halaman masuk</Link>
        </Button>
      </div>
    </main>
  );
}
