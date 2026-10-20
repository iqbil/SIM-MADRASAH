import type { Metadata } from "next";
import Link from "next/link";
import { Suspense } from "react";
import { AuthFrame } from "@/components/auth/auth-frame";
import { LogoutButton } from "@/components/auth/logout-button";
import { PasswordForm } from "@/components/auth/password-form";
import { requireUser } from "@/lib/auth/session";
export const metadata: Metadata = { title: "Ganti password" };
export default function ChangePasswordPage() {
  return (
    <AuthFrame>
      <Suspense
        fallback={
          <output className="text-sm text-muted-foreground">
            Memuat keamanan akun…
          </output>
        }
      >
        <PasswordContent />
      </Suspense>
    </AuthFrame>
  );
}
async function PasswordContent() {
  const user = await requireUser("/ganti-password", true);
  return (
    <div className="animate-enter">
      <p className="mb-3 text-[11px] font-semibold tracking-[0.16em] text-primary uppercase">
        Keamanan akun
      </p>
      <h1 className="text-3xl font-semibold tracking-tight">
        {user.must_change_password
          ? "Buat password baru"
          : "Ganti password Anda"}
      </h1>
      <p className="mt-3 mb-7 text-sm leading-7 text-muted-foreground">
        {user.must_change_password
          ? "Anda menggunakan password sementara. Buat password pribadi untuk melanjutkan ke ruang kerja."
          : "Perbarui password untuk menjaga keamanan akun madrasah Anda."}
      </p>
      <PasswordForm />
      <div className="mt-5 flex flex-wrap items-center justify-between gap-2">
        {!user.must_change_password && (
          <Link
            href="/akun"
            className="py-3 text-xs text-primary hover:underline"
          >
            Kembali ke akun saya
          </Link>
        )}
        <LogoutButton />
      </div>
    </div>
  );
}
