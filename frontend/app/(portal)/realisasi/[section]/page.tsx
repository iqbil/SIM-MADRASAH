import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import { PageSkeleton } from "@/components/layout/page-skeleton";
import { MyMeetings } from "@/components/teaching/my-meetings";
import { MyRealizations } from "@/components/teaching/my-realizations";
import { Verifications } from "@/components/teaching/verifications";
import { requireUser } from "@/lib/auth/session";
import { TEACHING_SECTIONS, type TeachingSection } from "@/lib/teaching";

export const metadata: Metadata = { title: "Realisasi mengajar" };

export function generateStaticParams() {
  return Object.keys(TEACHING_SECTIONS).map((section) => ({ section }));
}

export default function TeachingPage({
  params,
}: {
  params: Promise<{ section: string }>;
}) {
  return (
    <Suspense fallback={<PageSkeleton />}>
      <TeachingPageContent params={params} />
    </Suspense>
  );
}

async function TeachingPageContent({
  params,
}: {
  params: Promise<{ section: string }>;
}) {
  const { section } = await params;
  if (!Object.hasOwn(TEACHING_SECTIONS, section)) notFound();
  const user = await requireUser(`/realisasi/${section}`);
  const nav = new Set(user.navigation ?? []);
  if (!TEACHING_SECTIONS[section as TeachingSection].allow(nav)) notFound();
  switch (section as TeachingSection) {
    case "pertemuan":
      return <MyMeetings />;
    case "riwayat":
      return <MyRealizations />;
    case "verifikasi":
      return <Verifications />;
  }
}
