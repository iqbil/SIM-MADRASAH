import Link from "next/link";
import { SchoolMark } from "@/components/brand";
import { Button } from "@/components/ui/button";
export default function NotFound() {
  return (
    <main className="flex min-h-svh flex-col items-center justify-center px-6 text-center">
      <SchoolMark />
      <p className="mt-6 text-xs font-medium tracking-widest text-primary">
        404
      </p>
      <h1 className="mt-3 text-2xl font-semibold tracking-tight">
        Halaman tidak ditemukan
      </h1>
      <p className="mt-3 text-sm text-muted-foreground">
        Periksa kembali alamat halaman yang Anda tuju.
      </p>
      <Button asChild className="mt-6 h-11">
        <Link href="/dashboard">Kembali ke portal</Link>
      </Button>
    </main>
  );
}
