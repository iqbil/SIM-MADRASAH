import { ShieldAlert } from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
export default function NotFound() {
  return (
    <div className="flex flex-1 flex-col items-center justify-center px-4 py-24 text-center">
      <ShieldAlert className="mb-5 size-10 text-primary/70" />
      <h1 className="text-xl font-semibold">Ruang kerja tidak tersedia</h1>
      <p className="mt-3 max-w-sm text-sm leading-7 text-muted-foreground">
        Halaman ini tidak ditemukan atau tidak termasuk akses akun Anda.
      </p>
      <Button asChild className="mt-6 h-11">
        <Link href="/dashboard">Kembali ke beranda</Link>
      </Button>
    </div>
  );
}
