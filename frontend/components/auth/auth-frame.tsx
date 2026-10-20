import { BookOpen, GraduationCap, ShieldCheck } from "lucide-react";
import Image from "next/image";
import type { ReactNode } from "react";
import { Brand } from "@/components/brand";
import { Card, CardContent } from "@/components/ui/card";

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
        <div className="hidden max-w-xl py-20 lg:block">
          <div className="mb-7 inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-4 py-2 text-[11px] font-medium tracking-[0.14em] text-white backdrop-blur-sm">
            <GraduationCap className="size-4" /> PORTAL MADRASAH
          </div>
          <h2 className="text-[clamp(2.8rem,4.3vw,4.5rem)] leading-[1.12] font-semibold tracking-[-0.045em] text-white">
            Tempat belajar.
            <br />
            Ruang bertumbuh.
            <br />
            <span className="text-[#e9d6a3]">Masa depan bersama.</span>
          </h2>
          <p className="mt-7 max-w-sm text-sm leading-7 text-white/80">
            Menghubungkan keluarga besar madrasah dalam satu portal untuk
            mendukung pendidikan yang lebih baik.
          </p>
          <div className="mt-9 flex items-center gap-5 text-xs text-white/80">
            <span className="flex items-center gap-2">
              <BookOpen className="size-4" /> Ilmu & akhlak
            </span>
            <span className="flex items-center gap-2">
              <ShieldCheck className="size-4" /> Akses pribadi
            </span>
          </div>
        </div>
      </div>
      <section className="relative -mt-7 flex min-h-[calc(100svh-218px)] flex-col rounded-t-[2rem] bg-white px-6 pt-7 pb-5 sm:px-10 lg:m-0 lg:min-h-svh lg:justify-center lg:rounded-none lg:bg-transparent lg:px-10 lg:py-10 xl:px-16">
        <Card className="mx-auto w-full max-w-[440px] overflow-visible rounded-none bg-white p-0 shadow-none ring-0 lg:rounded-[1.75rem] lg:p-9 lg:shadow-2xl lg:shadow-black/15 xl:p-10">
          <CardContent className="p-0">{children}</CardContent>
        </Card>
        <footer className="mx-auto mt-auto pt-8 text-center text-[10px] text-muted-foreground lg:mt-0 lg:pt-6 lg:text-white/75">
          © MTs Al Karimiyyah · Sistem Informasi Madrasah
        </footer>
      </section>
    </main>
  );
}
