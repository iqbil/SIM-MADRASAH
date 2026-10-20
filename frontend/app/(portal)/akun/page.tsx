import { LockKeyhole, Mail, ShieldCheck, UserRound } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { Suspense } from "react";
import { PageSkeleton } from "@/components/layout/page-skeleton";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { requireUser } from "@/lib/auth/session";
export const metadata: Metadata = { title: "Akun saya" };
export default function AccountPage() {
  return (
    <Suspense fallback={<PageSkeleton />}>
      <AccountPageContent />
    </Suspense>
  );
}

async function AccountPageContent() {
  const user = await requireUser("/akun");
  return (
    <div className="animate-enter max-w-3xl">
      <p className="mb-3 text-[11px] text-muted-foreground">
        Portal madrasah / Akun saya
      </p>
      <h1 className="text-[28px] font-semibold tracking-tight">Akun saya</h1>
      <p className="mt-2 text-sm leading-6 text-muted-foreground">
        Informasi akun dan akses Anda di MTs Al Karimiyyah.
      </p>
      <Card className="mt-7 rounded-2xl p-2">
        <CardHeader className="border-b pb-5">
          <CardTitle className="flex items-center gap-2 text-sm">
            <UserRound className="size-4 text-primary" /> Informasi pribadi
          </CardTitle>
        </CardHeader>
        <CardContent>
          <dl className="divide-y divide-border/65 text-sm">
            {[
              ["Nama lengkap", user.name],
              ["Username", user.username],
              ["Email", user.email ?? "Belum ditambahkan"],
            ].map(([label, value]) => (
              <div
                key={label}
                className="grid gap-2 py-4 sm:grid-cols-[160px_1fr]"
              >
                <dt className="text-xs text-muted-foreground">{label}</dt>
                <dd className="break-words text-[13px] font-medium">{value}</dd>
              </div>
            ))}
            <div className="grid gap-3 py-4 sm:grid-cols-[160px_1fr]">
              <dt className="text-xs text-muted-foreground">Role akun</dt>
              <dd className="flex flex-wrap gap-2">
                {user.roles.length ? (
                  user.roles.map((role) => (
                    <Badge
                      key={role.code}
                      variant="secondary"
                      className="bg-primary/7 text-primary"
                    >
                      {role.name}
                    </Badge>
                  ))
                ) : (
                  <span className="text-xs text-muted-foreground">
                    Belum ada role yang diberikan
                  </span>
                )}
              </dd>
            </div>
            <div className="grid gap-2 py-4 sm:grid-cols-[160px_1fr]">
              <dt className="text-xs text-muted-foreground">Status email</dt>
              <dd className="flex items-center gap-2 text-xs">
                <Mail className="size-3.5 text-primary" />
                {user.email_verified ? "Terverifikasi" : "Belum terverifikasi"}
              </dd>
            </div>
          </dl>
        </CardContent>
      </Card>
      <Card className="mt-5 rounded-2xl p-2">
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-sm">
            <ShieldCheck className="size-4 text-primary" /> Keamanan akun
          </CardTitle>
        </CardHeader>
        <CardContent>
          <p className="max-w-lg text-xs leading-6 text-muted-foreground">
            Gunakan password yang unik dan jangan membagikannya kepada orang
            lain. Perubahan password akan mengakhiri semua sesi akun.
          </p>
          <Button asChild variant="outline" className="mt-4 h-11 gap-2 text-xs">
            <Link href="/ganti-password">
              <LockKeyhole className="size-4" /> Ganti password
            </Link>
          </Button>
        </CardContent>
      </Card>
      <p className="mt-5 text-xs leading-6 text-muted-foreground">
        Untuk perubahan informasi akun atau penyesuaian role, hubungi operator
        madrasah.
      </p>
    </div>
  );
}
