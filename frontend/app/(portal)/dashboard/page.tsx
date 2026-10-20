import type { Metadata } from "next";
import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { Suspense } from "react";
import { EmptyDashboard } from "@/components/dashboard/empty-dashboard";
import { PageSkeleton } from "@/components/layout/page-skeleton";
import { ROLE_COOKIE, userHome } from "@/lib/auth/roles";
import { requireUser } from "@/lib/auth/session";
export const metadata: Metadata = { title: "Beranda" };
export default function DashboardPage() {
  return (
    <Suspense fallback={<PageSkeleton />}>
      <DashboardPageContent />
    </Suspense>
  );
}

async function DashboardPageContent() {
  const user = await requireUser();
  if (user.roles.length)
    redirect(userHome(user, (await cookies()).get(ROLE_COOKIE)?.value));
  return <EmptyDashboard user={user} />;
}
