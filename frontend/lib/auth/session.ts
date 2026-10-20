import "server-only";

import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { connection } from "next/server";
import { cache } from "react";
import { BackendError, backendJson } from "./backend";
import { ACCESS_COOKIE, REFRESH_COOKIE } from "./cookies";
import { safeReturnTo } from "./roles";
import type { AuthUser } from "./types";

const readRequestSession = cache(
  async (): Promise<{ user: AuthUser | null; renewable: boolean }> => {
    const store = await cookies();
    const access = store.get(ACCESS_COOKIE)?.value;
    const renewable = Boolean(store.get(REFRESH_COOKIE)?.value);
    if (!access) return { user: null, renewable };
    try {
      const result = await backendJson<{ data: AuthUser }>("me", {
        headers: { Authorization: `Bearer ${access}` },
      });
      return { user: result.data, renewable };
    } catch (error) {
      if (error instanceof BackendError && error.status === 401)
        return { user: null, renewable };
      throw error;
    }
  },
);

export async function readSession() {
  // Validate authentication against a real request. Keep this boundary outside
  // React's request memoization so every caller suspends before reusing a read.
  await connection();
  return readRequestSession();
}

export async function requireUser(
  returnTo = "/dashboard",
  allowPasswordChange = false,
): Promise<AuthUser> {
  const session = await readSession();
  if (!session.user) {
    if (session.renewable)
      redirect(
        `/sesi/perbarui?next=${encodeURIComponent(safeReturnTo(returnTo))}`,
      );
    redirect("/login");
  }
  if (session.user.must_change_password && !allowPasswordChange)
    redirect("/ganti-password");
  return session.user;
}
