import type { Metadata } from "next";
import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { Suspense } from "react";
import { AuthFrame } from "@/components/auth/auth-frame";
import { LoginForm } from "@/components/auth/login-form";
import { Skeleton } from "@/components/ui/skeleton";
import { ROLE_COOKIE, userHome } from "@/lib/auth/roles";
import { readSession } from "@/lib/auth/session";

export const metadata: Metadata = { title: "Masuk" };
const messages: Record<string, string> = {
  "google-unavailable":
    "Login Google belum tersedia. Gunakan username atau email Anda, atau hubungi operator madrasah.",
  "google-failed":
    "Login Google belum berhasil. Pastikan akun Google Anda sudah terhubung dengan akun madrasah, lalu coba kembali.",
  "session-expired":
    "Sesi Anda telah berakhir. Silakan masuk kembali untuk melanjutkan.",
};

export default function LoginPage(props: PageProps<"/login">) {
  return (
    <AuthFrame>
      <Suspense
        fallback={
          <div className="space-y-5">
            <Skeleton className="h-10 w-3/4" />
            <Skeleton className="h-14 w-full" />
            <Skeleton className="h-12 w-full" />
            <Skeleton className="h-12 w-full" />
          </div>
        }
      >
        <LoginContent {...props} />
      </Suspense>
    </AuthFrame>
  );
}

async function LoginContent({ searchParams }: PageProps<"/login">) {
  const params = await searchParams;
  const session = await readSession();
  if (session.user)
    redirect(userHome(session.user, (await cookies()).get(ROLE_COOKIE)?.value));
  const error =
    typeof params.error === "string" ? messages[params.error] : undefined;
  return (
    <LoginForm
      initialMessage={error}
      notice={
        params.notice === "password-updated"
          ? "Password berhasil diperbarui. Silakan masuk dengan password baru Anda."
          : undefined
      }
    />
  );
}
