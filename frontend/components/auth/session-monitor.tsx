"use client";

import { useEffect } from "react";
import { AuthRequestError, authRequest, renewSession } from "@/lib/auth/client";
import type { AuthUser } from "@/lib/auth/types";

export function SessionMonitor({ user }: { user: AuthUser }) {
  useEffect(() => {
    let checking = false;
    let active = true;
    async function check() {
      if (checking || document.visibilityState !== "visible") return;
      checking = true;
      try {
        const result = await authRequest<{ data: AuthUser }>(
          "session",
          undefined,
          "GET",
        );
        if (!active) return;
        if (result.data.must_change_password)
          window.location.replace("/ganti-password");
        else if (
          JSON.stringify(result.data.roles) !== JSON.stringify(user.roles) ||
          JSON.stringify(result.data.permissions) !==
            JSON.stringify(user.permissions) ||
          JSON.stringify(result.data.navigation) !==
            JSON.stringify(user.navigation)
        )
          window.location.replace("/dashboard");
      } catch (error) {
        if (!(error instanceof AuthRequestError) || error.status !== 401)
          return;
        try {
          const result = await renewSession();
          if (active && result.redirect === "/ganti-password")
            window.location.replace(result.redirect);
        } catch (refreshError) {
          if (
            active &&
            refreshError instanceof AuthRequestError &&
            [401, 403].includes(refreshError.status)
          )
            window.location.replace("/login?error=session-expired");
        }
      } finally {
        checking = false;
      }
    }
    const timer = window.setInterval(check, 60_000);
    document.addEventListener("visibilitychange", check);
    window.addEventListener("pageshow", check);
    return () => {
      active = false;
      window.clearInterval(timer);
      document.removeEventListener("visibilitychange", check);
      window.removeEventListener("pageshow", check);
    };
  }, [user.roles, user.permissions, user.navigation]);
  return null;
}
