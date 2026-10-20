import type { Metadata } from "next";
import { Suspense } from "react";
import { AuthFrame } from "@/components/auth/auth-frame";
import { RenewSession } from "@/components/auth/renew-session";
import { safeReturnTo } from "@/lib/auth/roles";
export const metadata: Metadata = { title: "Memperbarui sesi" };
export default function RenewPage(props: PageProps<"/sesi/perbarui">) {
  return (
    <AuthFrame>
      <Suspense fallback={<output>Menyiapkan ruang kerja…</output>}>
        <RenewContent {...props} />
      </Suspense>
    </AuthFrame>
  );
}
async function RenewContent({ searchParams }: PageProps<"/sesi/perbarui">) {
  const params = await searchParams;
  return (
    <RenewSession
      next={safeReturnTo(
        typeof params.next === "string" ? params.next : undefined,
      )}
    />
  );
}
