import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import { AcademicWorkspace } from "@/components/academic/academic-workspace";
import { PageSkeleton } from "@/components/layout/page-skeleton";
import { ACADEMIC_SECTIONS, type AcademicSection } from "@/lib/academic";
import { requireUser } from "@/lib/auth/session";

export const metadata: Metadata = { title: "Akademik & kelas" };

export function generateStaticParams() {
  return Object.keys(ACADEMIC_SECTIONS).map((section) => ({ section }));
}

export default function AcademicPage({
  params,
}: {
  params: Promise<{ section: string }>;
}) {
  return (
    <Suspense fallback={<PageSkeleton />}>
      <AcademicPageContent params={params} />
    </Suspense>
  );
}

async function AcademicPageContent({
  params,
}: {
  params: Promise<{ section: string }>;
}) {
  const { section } = await params;
  if (!Object.hasOwn(ACADEMIC_SECTIONS, section)) notFound();
  const user = await requireUser(`/akademik/${section}`);
  const nav = new Set(user.navigation ?? []);
  if (nav.has("academic.view") || nav.has("academic.manage")) {
    for (const view of [
      "academic.years.view",
      "academic.classes.view",
      "academic.enrollments.view",
      "academic.calendar.view",
      "academic.assignments.view",
      "academic.schedules.view",
      "academic.substitutes.view",
    ]) {
      nav.add(view);
    }
  }
  const access = [...new Set([...nav, ...(user.permissions ?? [])])];
  const required = ACADEMIC_SECTIONS[section as AcademicSection].permission;
  const allowed = nav.has("academic.manage") || nav.has(required);
  if (!allowed) notFound();
  return (
    <AcademicWorkspace section={section as AcademicSection} access={access} />
  );
}
