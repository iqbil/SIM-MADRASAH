import "server-only";

import type { NextRequest, NextResponse } from "next/server";
import type { TokenResponse } from "./types";

export const ACCESS_COOKIE = "madrasah_access";
export const REFRESH_COOKIE = "madrasah_refresh";
export const OAUTH_COOKIE = "madrasah_google";

export const cookieOptions = {
  httpOnly: true,
  secure: process.env.NODE_ENV === "production",
  sameSite: "lax" as const,
  path: "/",
};

export function privateResponse(response: NextResponse): NextResponse {
  response.headers.set("Cache-Control", "private, no-store");
  response.headers.set("Referrer-Policy", "no-referrer");
  response.headers.set("X-Content-Type-Options", "nosniff");
  return response;
}

export function setAuthCookies(
  response: NextResponse,
  data: TokenResponse,
): void {
  const maxAge = data.refresh_expires_in;
  response.cookies.set(ACCESS_COOKIE, data.access_token, {
    ...cookieOptions,
    maxAge,
  });
  response.cookies.set(REFRESH_COOKIE, data.refresh_token, {
    ...cookieOptions,
    maxAge,
  });
}

export function clearAuthCookies(response: NextResponse): void {
  for (const name of [ACCESS_COOKIE, REFRESH_COOKIE]) {
    response.cookies.set(name, "", { ...cookieOptions, maxAge: 0 });
  }
}

export function isSameOrigin(
  request: NextRequest,
  contentTypes = ["application/json"],
): boolean {
  const expected = process.env.APP_URL
    ? new URL(process.env.APP_URL).origin
    : request.nextUrl.origin;
  return (
    request.headers.get("origin") === expected &&
    contentTypes.includes(
      request.headers.get("content-type")?.split(";")[0].trim() ?? "",
    )
  );
}
