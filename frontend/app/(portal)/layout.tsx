import { cookies } from "next/headers";
import { Suspense } from "react";
import { AppShell } from "@/components/layout/app-shell";
import { ShellSkeleton } from "@/components/layout/shell-skeleton";
import { ROLE_COOKIE } from "@/lib/auth/roles";
import { readSession } from "@/lib/auth/session";
export default function PortalLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <Suspense fallback={<ShellSkeleton />}>
      <ProtectedShell>{children}</ProtectedShell>
    </Suspense>
  );
}
async function ProtectedShell({ children }: { children: React.ReactNode }) {
  const { user } = await readSession();
  // Each page validates access in its own Suspense boundary. Let that page
  // handle redirects; a redirect here also prevents its loading UI rendering.
  if (!user || user.must_change_password) return children;
  const store = await cookies();
  return (
    <AppShell
      user={user}
      preferredRole={store.get(ROLE_COOKIE)?.value}
      sidebarOpen={store.get("sidebar_state")?.value !== "false"}
    >
      {children}
    </AppShell>
  );
}
