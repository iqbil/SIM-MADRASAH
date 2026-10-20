import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import { AccountWorkspace } from "@/components/accounts/account-workspace";
import { PageSkeleton } from "@/components/layout/page-skeleton";
import { requireUser } from "@/lib/auth/session";

export const metadata: Metadata = { title: "Pengelolaan akun" };
export default function UsersPage() {
  return (
    <Suspense fallback={<PageSkeleton />}>
      <UsersPageContent />
    </Suspense>
  );
}

async function UsersPageContent() {
  const user = await requireUser("/pengguna");
  if (!user.permissions?.includes("accounts.view")) notFound();
  return <AccountWorkspace user={user} />;
}
