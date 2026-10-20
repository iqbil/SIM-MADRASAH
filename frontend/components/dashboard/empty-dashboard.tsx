import {
  ArrowUpRight,
  BookOpen,
  CalendarDays,
  GraduationCap,
  LockKeyhole,
  ShieldCheck,
  UserRound,
  UsersRound,
} from "lucide-react";
import { io } from "next/cache";
import Link from "next/link";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty";
import { roleDescription } from "@/lib/auth/roles";
import type { AuthRole, AuthUser } from "@/lib/auth/types";

export async function EmptyDashboard({
  user,
  role,
}: {
  user: AuthUser;
  role?: AuthRole;
}) {
  // Read the clock after Next's prerender boundary has suspended.
  await io();
  const date = new Intl.DateTimeFormat("id-ID", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
    timeZone: "Asia/Jakarta",
  }).format(new Date());
  const shortcuts = [
    ...(user.permissions?.includes("accounts.view")
      ? [
          {
            href: "/pengguna",
            title: "Pengelolaan akun",
            description: "Pengguna, role, dan izin akses",
            icon: UsersRound,
          },
        ]
      : []),
    {
      href: "/akun",
      title: "Akun saya",
      description: "Profil dan akses ruang kerja",
      icon: UserRound,
    },
    {
      href: "/ganti-password",
      title: "Keamanan akun",
      description: "Perbarui password Anda",
      icon: LockKeyhole,
    },
  ];
  return (
    <div className="animate-enter space-y-6">
      <Card className="relative rounded-2xl border border-primary/10 bg-primary/5 py-0 shadow-none ring-0">
        <CardContent className="relative flex items-center justify-between gap-5 p-5 sm:p-7">
          <div className="min-w-0">
            <div className="mb-4 flex flex-wrap items-center gap-2">
              <span className="text-xs font-medium text-primary">
                Portal madrasah
              </span>
              {role && (
                <Badge
                  variant="outline"
                  className="max-w-full whitespace-normal border-primary/15 bg-card text-primary"
                >
                  {role.name}
                </Badge>
              )}
            </div>
            <h1 className="break-words text-2xl leading-tight font-semibold tracking-tight sm:text-3xl">
              Selamat datang, {user.name.split(" ")[0]}.
            </h1>
            <p className="mt-2 max-w-xl text-sm leading-6 text-muted-foreground">
              {role
                ? roleDescription(role)
                : "Selamat datang di portal internal MTs Al Karimiyyah."}
            </p>
            <p className="mt-5 flex items-start gap-2 text-xs leading-5 text-muted-foreground">
              <CalendarDays className="mt-0.5 size-4 shrink-0" />
              <time>{date}</time>
            </p>
          </div>
          <div
            aria-hidden="true"
            className="hidden size-24 shrink-0 items-center justify-center rounded-3xl border border-primary/10 bg-card/80 text-primary sm:flex lg:size-28"
          >
            <GraduationCap className="size-12" strokeWidth={1.25} />
          </div>
        </CardContent>
      </Card>
      <div className="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)]">
        <Card className="min-w-0 rounded-2xl border border-border/80 py-0 shadow-none ring-0">
          <CardHeader className="border-b p-5">
            <CardTitle>
              <h2 className="text-sm font-semibold">Ringkasan ruang kerja</h2>
            </CardTitle>
            <p className="mt-1 text-xs leading-5 text-muted-foreground">
              Informasi dan aktivitas madrasah Anda.
            </p>
          </CardHeader>
          <Empty className="min-h-72 gap-5 px-5 py-10 sm:min-h-80">
            <EmptyHeader>
              <EmptyMedia
                variant="icon"
                className="mb-3 size-14 rounded-2xl bg-primary/7 text-primary"
              >
                <BookOpen className="size-7" strokeWidth={1.4} />
              </EmptyMedia>
              <EmptyTitle>
                <h3 className="text-base font-semibold">
                  {role
                    ? "Ruang kerja Anda sudah siap"
                    : "Akun Anda sudah terhubung"}
                </h3>
              </EmptyTitle>
              <EmptyDescription className="max-w-xs text-xs leading-6">
                {role
                  ? "Belum ada informasi untuk ditampilkan. Ringkasan dan aktivitas madrasah akan tersedia di halaman ini."
                  : "Belum ada role yang diberikan pada akun Anda. Hubungi operator madrasah untuk mendapatkan akses ruang kerja."}
              </EmptyDescription>
            </EmptyHeader>
            <EmptyContent>
              <Button asChild variant="outline" className="h-11 gap-2 text-xs">
                <Link href="/akun">
                  Lihat akun saya <ArrowUpRight className="size-4" />
                </Link>
              </Button>
            </EmptyContent>
          </Empty>
        </Card>
        <div className="min-w-0 space-y-5">
          <Card className="rounded-2xl border border-border/80 py-0 shadow-none ring-0">
            <CardHeader className="p-5 pb-0">
              <CardTitle>
                <h2 className="text-sm font-semibold">Akses cepat</h2>
              </CardTitle>
            </CardHeader>
            <CardContent className="grid gap-2 p-3 pt-2 sm:grid-cols-2 xl:grid-cols-1">
              {shortcuts.map(({ href, title, description, icon: Icon }) => (
                <Button
                  key={href}
                  variant="ghost"
                  asChild
                  className="h-auto min-h-20 min-w-0 justify-start gap-3 rounded-xl p-3 text-left whitespace-normal"
                >
                  <Link href={href}>
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-xl border bg-background text-primary">
                      <Icon className="size-[18px]" />
                    </span>
                    <span className="min-w-0 flex-1">
                      <span className="block text-xs font-medium">{title}</span>
                      <span className="mt-1 block text-[11px] leading-5 font-normal text-muted-foreground">
                        {description}
                      </span>
                    </span>
                    <ArrowUpRight className="size-4 shrink-0 text-muted-foreground" />
                  </Link>
                </Button>
              ))}
            </CardContent>
          </Card>
          <Alert className="rounded-2xl border-primary/10 bg-primary/4 p-5">
            <ShieldCheck className="text-primary!" />
            <AlertDescription className="text-xs leading-6 text-muted-foreground">
              <span className="font-medium text-foreground">
                Akses sesuai peran Anda
              </span>
              <p>
                Anda masuk sebagai {role?.name ?? "pengguna madrasah"}. Gunakan
                menu ruang kerja untuk berpindah di antara peran yang diberikan
                kepada Anda.
              </p>
            </AlertDescription>
          </Alert>
        </div>
      </div>
    </div>
  );
}
