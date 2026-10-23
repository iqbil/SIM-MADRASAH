import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import { PageSkeleton } from "@/components/layout/page-skeleton";
import { MasterWorkspace } from "@/components/master/master-workspace";
import { requireUser } from "@/lib/auth/session";
import { MASTER_SECTIONS, type MasterSection } from "@/lib/master-data";

export const metadata: Metadata = { title: "Data master" };
export default function MasterPage({
  params,
}: {
  params: Promise<{ section: string }>;
}) {
  return (
    <Suspense fallback={<PageSkeleton />}>
      <MasterPageContent params={params} />
    </Suspense>
  );
}
const SECTION_PERMISSIONS: Record<MasterSection, string[]> = {
  institution: ["master.institution.view"],
  students: ["master.students.view"],
  guardians: ["master.guardians.view"],
  employees: ["master.employees.view"],
  positions: ["master.positions.view", "master.employee-categories.view"],
  subjects: ["master.subjects.view"],
  references: [
    "master.student-statuses.view",
    "master.attendance-statuses.view",
  ],
  imports: ["master.imports"],
};

async function MasterPageContent({
  params,
}: {
  params: Promise<{ section: string }>;
}) {
  const { section } = await params;
  if (!Object.hasOwn(MASTER_SECTIONS, section)) notFound();
  const user = await requireUser(`/data-master/${section}`);
  const nav = new Set(user.navigation ?? []);
  const allowed =
    nav.has("master.manage") ||
    SECTION_PERMISSIONS[section as MasterSection]?.some((perm) =>
      nav.has(perm),
    );
  if (!allowed) notFound();
  return <MasterWorkspace section={section as MasterSection} />;
}
