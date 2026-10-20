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
async function MasterPageContent({
  params,
}: {
  params: Promise<{ section: string }>;
}) {
  const { section } = await params;
  if (!Object.hasOwn(MASTER_SECTIONS, section)) notFound();
  const user = await requireUser(`/data-master/${section}`);
  if (!user.navigation?.includes("master.manage")) notFound();
  return <MasterWorkspace section={section as MasterSection} />;
}
