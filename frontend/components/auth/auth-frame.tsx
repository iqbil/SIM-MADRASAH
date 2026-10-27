import { BellRing, CalendarCheck, ShieldCheck } from "lucide-react";
import Image from "next/image";
import type { ReactNode } from "react";
import { Brand } from "@/components/brand";
import { Card, CardContent } from "@/components/ui/card";

const PORTAL_POINTS = [
  {
    icon: CalendarCheck,
    text: "Jadwal, absensi, dan realisasi mengajar tercatat rapi.",
  },
  {
    icon: BellRing,
    text: "Pengumuman dan informasi perkembangan siswa untuk wali.",
  },
  {
    icon: ShieldCheck,
    text: "Akun pribadi dengan sesi login yang terlindungi.",
  },
] as const;

export function AuthFrame({ children }: { children: ReactNode }) {
  return (
    <main className="relative isolate flex min-h-svh flex-col bg-white lg:grid lg:grid-cols-[1.1fr_1fr] lg:bg-[#123c32]">
      <div className="absolute inset-x-0 top-0 -z-10 h-[260px] overflow-hidden sm:h-[300px] lg:inset-0 lg:h-full">
        <Image
          src="/images/school-campus.png"
          alt="Lingkungan sekolah"
          fill
          priority
          sizes="100vw"
          className="object-cover object-[53%_45%]"
          quality={75}
        />
        <div className="absolute inset-0 bg-linear-to-b from-[#0b3029]/65 via-[#0b3029]/10 to-[#0b3029]/35 lg:bg-linear-to-r lg:from-[#0b3029]/85 lg:via-[#0b3029]/65 lg:to-[#0b3029]/25" />
      </div>
      <div className="relative flex h-[245px] flex-col justify-between p-6 pb-12 sm:h-[285px] sm:px-10 lg:h-auto lg:min-h-svh lg:p-12 xl:p-16">
        <Brand light />
        <div className="hidden flex-1 items-center lg:flex">
          <div className="max-w-md py-16">
            <p className="text-xs font-semibold tracking-[0.18em] text-white/70 uppercase">
              Portal internal madrasah
            </p>
            <span
              aria-hidden="true"
              className="mt-5 block h-[3px] w-12 rounded-full bg-[#e9d6a3]"
            />
            <h2 className="mt-6 text-[1.9rem] leading-tight font-semibold tracking-tight text-white xl:text-[2.15rem]">
              Administrasi madrasah dalam satu portal.
            </h2>
            <p className="mt-4 max-w-sm text-sm leading-7 text-white/75">
              MTs Al Karimiyyah mengelola data siswa, kehadiran, honor, dan
              komunikasi dengan wali melalui satu sistem terpadu.
            </p>
            <ul className="mt-10 space-y-4">
              {PORTAL_POINTS.map((point) => (
                <li
                  key={point.text}
                  className="flex items-center gap-3 text-sm leading-6 text-white/85"
                >
                  <point.icon
                    className="size-4 shrink-0 text-white/70"
                    aria-hidden="true"
                  />
                  {point.text}
                </li>
              ))}
            </ul>
          </div>
        </div>
        <p className="hidden text-xs tracking-wide text-white/60 lg:block">
          © MTs Al Karimiyyah · Sistem Informasi Madrasah
        </p>
      </div>
      <section className="relative -mt-7 flex min-h-[calc(100svh-218px)] flex-col rounded-t-[2rem] bg-white px-6 pt-7 pb-5 sm:px-10 lg:m-0 lg:min-h-svh lg:justify-center lg:rounded-none lg:bg-transparent lg:px-10 lg:py-10 xl:px-16">
        <Card className="mx-auto w-full max-w-[440px] overflow-visible rounded-none bg-white p-0 shadow-none ring-0 lg:rounded-[1.75rem] lg:p-9 lg:shadow-2xl lg:shadow-black/15 xl:p-10">
          <CardContent className="p-0">{children}</CardContent>
        </Card>
        <footer className="mx-auto mt-auto pt-8 text-center text-xs text-muted-foreground lg:hidden">
          © MTs Al Karimiyyah · Sistem Informasi Madrasah
        </footer>
      </section>
    </main>
  );
}
