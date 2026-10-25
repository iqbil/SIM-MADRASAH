"use client";

import {
  AlertCircle,
  ArrowRight,
  CircleHelp,
  LoaderCircle,
  LockKeyhole,
  Mail,
  ShieldCheck,
} from "lucide-react";
import { type FormEvent, useEffect, useState } from "react";
import { PasswordInput } from "@/components/auth/password-input";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { AuthRequestError, authRequest } from "@/lib/auth/client";

export function LoginForm({
  initialMessage,
  notice,
}: {
  initialMessage?: string;
  notice?: string;
}) {
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState(initialMessage);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [google, setGoogle] = useState(false);
  const [optionsLoaded, setOptionsLoaded] = useState(false);

  useEffect(() => {
    let active = true;
    authRequest<{ data: { google: boolean } }>("options", undefined, "GET")
      .then((result) => {
        if (active) setGoogle(result.data.google);
      })
      .catch(() => {})
      .finally(() => {
        if (active) setOptionsLoaded(true);
      });
    return () => {
      active = false;
    };
  }, []);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy) return;
    const form = new FormData(event.currentTarget);
    setBusy(true);
    setMessage(undefined);
    setErrors({});
    try {
      const result = await authRequest<{ redirect: string }>("login", {
        login: String(form.get("login") ?? "").trim(),
        password: form.get("password"),
      });
      window.location.assign(result.redirect);
    } catch (error) {
      if (error instanceof AuthRequestError) {
        setErrors(error.payload.errors ?? {});
        setMessage(
          error.status === 401
            ? "Username, email, atau password belum sesuai. Periksa kembali akun Anda."
            : error.message,
        );
      } else setMessage("Login belum berhasil. Silakan coba kembali.");
      setBusy(false);
    }
  }

  return (
    <div className="animate-enter">
      <p className="mb-2 text-center text-[10px] font-semibold tracking-[0.16em] text-primary uppercase">
        Portal MTs Al Karimiyyah
      </p>
      <h1 className="text-center text-[28px] leading-tight font-semibold tracking-[-0.045em] sm:text-[34px]">
        Login
      </h1>
      <p className="mt-2 text-center text-[13px] leading-6 text-muted-foreground">
        Akun diberikan oleh madrasah melalui tata usaha. Silakan masuk untuk
        melanjutkan.
      </p>
      {notice && (
        <Alert className="mt-6 border-primary/20 bg-primary/5 text-primary">
          <ShieldCheck />
          <AlertDescription className="text-primary">{notice}</AlertDescription>
        </Alert>
      )}
      <form className="mt-6 space-y-4" onSubmit={submit}>
        {message && (
          <Alert variant="destructive" role="alert">
            <AlertCircle />
            <AlertDescription>{message}</AlertDescription>
          </Alert>
        )}
        <div className="space-y-2.5">
          <Label htmlFor="login" className="text-[13px]">
            Username atau email
          </Label>
          <div className="relative">
            <Mail className="pointer-events-none absolute top-4 left-3.5 z-10 size-4 text-primary/70" />
            <Input
              id="login"
              name="login"
              type="text"
              autoComplete="username"
              autoCapitalize="none"
              spellCheck={false}
              placeholder="Masukkan username atau email"
              maxLength={254}
              required
              disabled={busy}
              aria-invalid={Boolean(errors.login)}
              aria-describedby={errors.login ? "login-error" : undefined}
              className="h-12 border-transparent bg-muted/70 pl-11 text-base placeholder:text-muted-foreground/70 sm:text-sm"
            />
          </div>
          {errors.login && (
            <p id="login-error" className="text-xs text-destructive">
              {errors.login[0]}
            </p>
          )}
        </div>
        <div className="space-y-2.5">
          <Label htmlFor="password" className="text-[13px]">
            Password
          </Label>
          <PasswordInput
            startIcon={<LockKeyhole className="size-4" />}
            className="border-transparent bg-muted/70!"
            id="password"
            name="password"
            autoComplete="current-password"
            placeholder="Masukkan password Anda"
            maxLength={255}
            required
            disabled={busy}
            aria-invalid={Boolean(errors.password)}
            aria-describedby={errors.password ? "password-error" : undefined}
          />
          {errors.password && (
            <p id="password-error" className="text-xs text-destructive">
              {errors.password[0]}
            </p>
          )}
        </div>
        <div className="-mt-1 flex justify-end">
          {" "}
          <Dialog>
            <DialogTrigger asChild>
              <Button
                variant="link"
                className="h-auto gap-1.5 px-0 text-xs text-primary"
              >
                <CircleHelp className="size-3.5" /> Lupa password?
              </Button>
            </DialogTrigger>
            <DialogContent className="max-w-sm">
              <DialogHeader>
                <DialogTitle>Bantuan akses akun</DialogTitle>
                <DialogDescription className="pt-3 leading-7">
                  Gunakan akun yang diberikan oleh madrasah. Jika lupa password,
                  akun belum aktif, atau tidak bisa masuk, hubungi operator
                  madrasah untuk verifikasi dan pemulihan akses.
                </DialogDescription>
              </DialogHeader>
            </DialogContent>
          </Dialog>
        </div>
        <Button
          type="submit"
          disabled={busy}
          className="h-12 w-full justify-center gap-2 text-sm shadow-sm"
        >
          {busy ? (
            <>
              <LoaderCircle className="size-4 animate-spin" /> Sedang masuk…
            </>
          ) : (
            <>
              Masuk ke portal <ArrowRight className="size-4" />
            </>
          )}
        </Button>
      </form>
      <div className="my-5 flex items-center gap-4">
        <div className="h-px flex-1 bg-border" />
        <span className="text-xs text-muted-foreground">
          atau lanjutkan dengan
        </span>
        <div className="h-px flex-1 bg-border" />
      </div>
      <Button
        type="button"
        variant="outline"
        className="h-12 w-full gap-3 bg-white text-sm"
        disabled={busy || !google}
        onClick={() => {
          window.location.assign("/api/auth/google");
        }}
      >
        <svg viewBox="0 0 24 24" className="size-[18px]" aria-hidden="true">
          <path
            fill="#4285F4"
            d="M21.6 12.23c0-.71-.06-1.39-.18-2.05H12v3.88h5.38a4.6 4.6 0 0 1-1.99 3.02v2.5h3.22c1.88-1.73 2.99-4.28 2.99-7.35Z"
          />
          <path
            fill="#34A853"
            d="M12 22c2.7 0 4.96-.9 6.61-2.42l-3.22-2.5c-.9.6-2.04.97-3.39.97-2.6 0-4.81-1.76-5.6-4.12H3.08v2.58A10 10 0 0 0 12 22Z"
          />
          <path
            fill="#FBBC05"
            d="M6.4 13.93a6 6 0 0 1 0-3.86V7.49H3.08a10 10 0 0 0 0 9.02l3.32-2.58Z"
          />
          <path
            fill="#EA4335"
            d="M12 5.95c1.47 0 2.79.51 3.83 1.51l2.87-2.87A9.6 9.6 0 0 0 12 2a10 10 0 0 0-8.92 5.49l3.32 2.58c.79-2.36 3-4.12 5.6-4.12Z"
          />
        </svg>
        Akun Google
      </Button>
      {optionsLoaded && !google && (
        <p className="mt-2 text-center text-[11px] text-muted-foreground">
          Login Google belum tersedia untuk madrasah ini.
        </p>
      )}
      <div className="mt-5 border-t border-border/70 pt-4 text-center">
        <p className="mt-2 text-[11px] leading-5 text-muted-foreground/80">
          Belum memiliki akun?
          <br />
          Hubungi tata usaha atau operator madrasah.
        </p>
      </div>
    </div>
  );
}
