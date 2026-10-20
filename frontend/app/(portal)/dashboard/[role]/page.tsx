import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import { EmptyDashboard } from "@/components/dashboard/empty-dashboard";
import { PageSkeleton } from "@/components/layout/page-skeleton";
import { requireUser } from "@/lib/auth/session";
export const metadata: Metadata = { title: "Ruang kerja" };
export default function RoleDashboardPage(
  props: PageProps<"/dashboard/[role]">,
) {
  return (
    <Suspense fallback={<PageSkeleton />}>
      <RoleDashboardPageContent {...props} />
    </Suspense>
  );
}

async function RoleDashboardPageContent({
  params,
}: PageProps<"/dashboard/[role]">) {
  const { role: code } = await params;
  const user = await requireUser(`/dashboard/${encodeURIComponent(code)}`);
  const role = user.roles.find((item) => item.code === code);
  if (!role) notFound();
  return <EmptyDashboard user={user} role={role} />;
}
