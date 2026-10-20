import { type NextRequest, NextResponse } from "next/server";
import { backendFetch } from "@/lib/auth/backend";
import {
  cookieOptions,
  OAUTH_COOKIE,
  privateResponse,
} from "@/lib/auth/cookies";

export async function GET(request: NextRequest): Promise<NextResponse> {
  const origin = process.env.APP_URL ?? request.nextUrl.origin;
  const unavailable = () =>
    privateResponse(
      NextResponse.redirect(new URL("/login?error=google-unavailable", origin)),
    );
  try {
    const result = await backendFetch("google/redirect");
    const location = result.headers.get("location");
    if (![302, 303].includes(result.status) || !location) return unavailable();
    const destination = new URL(location);
    if (
      destination.protocol !== "https:" ||
      destination.hostname !== "accounts.google.com" ||
      destination.searchParams.get("redirect_uri") !==
        new URL("/api/auth/google/callback", origin).toString()
    ) {
      return unavailable();
    }
    const pairs = result.headers
      .getSetCookie()
      .map((item) => item.split(";")[0])
      .filter((item) => !item.startsWith("XSRF-TOKEN="));
    if (!pairs.length) return unavailable();
    const value = Buffer.from(JSON.stringify(pairs)).toString("base64url");
    if (value.length > 3800) return unavailable();
    const response = privateResponse(NextResponse.redirect(destination));
    response.cookies.set(OAUTH_COOKIE, value, {
      ...cookieOptions,
      path: "/api/auth/google",
      maxAge: 600,
    });
    return response;
  } catch {
    return unavailable();
  }
}
