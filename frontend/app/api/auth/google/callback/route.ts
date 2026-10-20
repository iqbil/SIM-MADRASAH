import { type NextRequest, NextResponse } from "next/server";
import { backendJson } from "@/lib/auth/backend";
import {
  cookieOptions,
  OAUTH_COOKIE,
  privateResponse,
  setAuthCookies,
} from "@/lib/auth/cookies";
import { ROLE_COOKIE, userHome } from "@/lib/auth/roles";
import type { TokenResponse } from "@/lib/auth/types";

export async function GET(request: NextRequest): Promise<NextResponse> {
  const origin = process.env.APP_URL ?? request.nextUrl.origin;
  let response: NextResponse;
  try {
    const encoded = request.cookies.get(OAUTH_COOKIE)?.value;
    if (!encoded || encoded.length > 3800)
      throw new Error("Missing OAuth transaction");
    const pairs: unknown = JSON.parse(
      Buffer.from(encoded, "base64url").toString(),
    );
    if (
      !Array.isArray(pairs) ||
      pairs.length > 5 ||
      !pairs.every(
        (value) =>
          typeof value === "string" && /^[\w-]+=[^;\r\n]+$/.test(value),
      )
    ) {
      throw new Error("Invalid OAuth transaction");
    }
    const params = new URLSearchParams();
    for (const name of ["state", "code", "error"]) {
      const value = request.nextUrl.searchParams.get(name);
      if (value && value.length <= 4096) params.set(name, value);
    }
    const result = await backendJson<{ data: TokenResponse }>(
      `google/callback?${params.toString()}`,
      {
        headers: { Cookie: pairs.join("; ") },
      },
    );
    response = privateResponse(
      NextResponse.redirect(
        new URL(
          userHome(result.data.user, request.cookies.get(ROLE_COOKIE)?.value),
          origin,
        ),
      ),
    );
    setAuthCookies(response, result.data);
  } catch {
    response = privateResponse(
      NextResponse.redirect(new URL("/login?error=google-failed", origin)),
    );
  }
  response.cookies.set(OAUTH_COOKIE, "", {
    ...cookieOptions,
    path: "/api/auth/google",
    maxAge: 0,
  });
  return response;
}
