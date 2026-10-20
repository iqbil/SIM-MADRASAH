"use client";

import { AlertCircle, ArrowRight, LoaderCircle } from "lucide-react";
import { type FormEvent, useState } from "react";
import { PasswordInput } from "@/components/auth/password-input";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { AuthRequestError, authRequest, renewSession } from "@/lib/auth/client";

export function PasswordForm() {
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState<string>();
  const [errors, setErrors] = useState<Record<string, string[]>>({});

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy) return;
    const form = new FormData(event.currentTarget);
    const body = Object.fromEntries(form.entries());
    setErrors({});
    setMessage(undefined);
    if (body.password !== body.password_confirmation) {
      setErrors({
        password_confirmation: ["Konfirmasi password belum sesuai."],
      });
      return;
    }
    if (new TextEncoder().encode(String(body.password)).length > 72) {
      setErrors({
        password: ["Password terlalu panjang. Gunakan maksimal 72 byte."],
      });
      return;
    }
    setBusy(true);
    try {
      let result: { redirect: string };
      try {
        result = await authRequest<{ redirect: string }>(
          "password",
          body,
          "PUT",
        );
      } catch (error) {
        if (!(error instanceof AuthRequestError) || error.status !== 401)
          throw error;
        await renewSession();
        result = await authRequest<{ redirect: string }>(
          "password",
          body,
          "PUT",
        );
      }
      window.location.replace(result.redirect);
    } catch (error) {
      if (error instanceof AuthRequestError) {
        if ([401, 403].includes(error.status)) {
          window.location.replace("/login?error=session-expired");
          return;
        }
        setErrors(error.payload.errors ?? {});
        setMessage(
          error.status === 422
            ? "Periksa kembali password yang Anda masukkan."
            : error.message,
        );
      } else
        setMessage("Password belum berhasil diubah. Silakan coba kembali.");
      setBusy(false);
    }
  }

  const fields = [
    {
      name: "current_password",
      label: "Password saat ini",
      autocomplete: "current-password",
      placeholder: "Masukkan password saat ini",
    },
    {
      name: "password",
      label: "Password baru",
      autocomplete: "new-password",
      placeholder: "Buat password baru",
    },
    {
      name: "password_confirmation",
      label: "Konfirmasi password baru",
      autocomplete: "new-password",
      placeholder: "Ulangi password baru",
    },
  ];

  return (
    <form onSubmit={submit} className="space-y-5">
      {message && (
        <Alert variant="destructive" role="alert">
          <AlertCircle />
          <AlertDescription>{message}</AlertDescription>
        </Alert>
      )}
      {fields.map((field) => (
        <div key={field.name} className="space-y-2.5">
          <Label htmlFor={field.name}>{field.label}</Label>
          <PasswordInput
            id={field.name}
            name={field.name}
            autoComplete={field.autocomplete}
            placeholder={field.placeholder}
            required
            disabled={busy}
            minLength={field.name === "current_password" ? 1 : 12}
            maxLength={field.name === "current_password" ? 255 : 72}
            aria-invalid={Boolean(errors[field.name])}
            aria-describedby={
              errors[field.name]
                ? `${field.name}-error`
                : field.name === "password"
                  ? "password-hint"
                  : undefined
            }
          />
          {field.name === "password" && (
            <p
              id="password-hint"
              className="text-xs leading-5 text-muted-foreground"
            >
              Minimal 12 karakter, dengan huruf besar, huruf kecil, dan angka.
            </p>
          )}
          {errors[field.name] && (
            <p id={`${field.name}-error`} className="text-xs text-destructive">
              {errors[field.name][0]}
            </p>
          )}
        </div>
      ))}
      <p className="text-xs leading-6 text-muted-foreground">
        Setelah password diubah, Anda akan keluar dari semua perangkat dan perlu
        masuk kembali.
      </p>
      <Button type="submit" className="h-12 w-full gap-2" disabled={busy}>
        {busy ? (
          <>
            <LoaderCircle className="size-4 animate-spin" /> Menyimpan…
          </>
        ) : (
          <>
            Simpan password baru <ArrowRight className="size-4" />
          </>
        )}
      </Button>
    </form>
  );
}
