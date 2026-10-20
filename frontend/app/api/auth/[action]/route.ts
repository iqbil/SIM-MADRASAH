import { type NextRequest, NextResponse } from "next/server";
import { BackendError, backendJson } from "@/lib/auth/backend";
import {
  ACCESS_COOKIE,
  clearAuthCookies,
  isSameOrigin,
  privateResponse,
  REFRESH_COOKIE,
  setAuthCookies,
} from "@/lib/auth/cookies";
import { refreshSession } from "@/lib/auth/refresh";
import { ROLE_COOKIE, userHome } from "@/lib/auth/roles";
import type { AuthUser, TokenResponse } from "@/lib/auth/types";

type Context = { params: Promise<{ action: string }> };

function failure(error: unknown): NextResponse {
  if (error instanceof BackendError) {
    return privateResponse(
      NextResponse.json(error.payload, { status: error.status }),
    );
  }
  return privateResponse(
    NextResponse.json(
      { message: "Permintaan tidak dapat diproses. Silakan coba kembali." },
      { status: 500 },
    ),
  );
}

export async function GET(
  request: NextRequest,
  context: Context,
): Promise<NextResponse> {
  const { action } = await context.params;
  try {
    if (action === "options") {
      return privateResponse(NextResponse.json(await backendJson("options")));
    }
    if (action !== "session")
      return privateResponse(
        NextResponse.json({ message: "Tidak ditemukan." }, { status: 404 }),
      );
    const access = request.cookies.get(ACCESS_COOKIE)?.value;
    if (!access)
      return privateResponse(
        NextResponse.json(
          { message: "Silakan login kembali." },
          { status: 401 },
        ),
      );
    const result = await backendJson<{ data: AuthUser }>("me", {
      headers: { Authorization: `Bearer ${access}` },
    });
    return privateResponse(NextResponse.json(result));
  } catch (error) {
    return failure(error);
  }
}

export async function POST(
  request: NextRequest,
  context: Context,
): Promise<NextResponse> {
  return mutate(request, context, "POST");
}

export async function PUT(
  request: NextRequest,
  context: Context,
): Promise<NextResponse> {
  return mutate(request, context, "PUT");
}

async function mutate(
  request: NextRequest,
  context: Context,
  method: string,
): Promise<NextResponse> {
  if (!isSameOrigin(request))
    return privateResponse(
      NextResponse.json(
        { message: "Permintaan tidak diizinkan." },
        { status: 403 },
      ),
    );
  const { action } = await context.params;
  if (
    !(method === "POST" && ["login", "refresh", "logout"].includes(action)) &&
    !(method === "PUT" && action === "password")
  ) {
    return privateResponse(
      NextResponse.json({ message: "Tidak ditemukan." }, { status: 404 }),
    );
  }
  let body: Record<string, unknown>;
  try {
    body = await request.json();
    if (!body || typeof body !== "object" || Array.isArray(body))
      throw new Error("invalid body");
  } catch {
    return privateResponse(
      NextResponse.json(
        { message: "Format permintaan tidak valid." },
        { status: 422 },
      ),
    );
  }
  try {
    if (action === "login") {
      const result = await backendJson<{ data: TokenResponse }>("login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ login: body.login, password: body.password }),
      });
      const response = privateResponse(
        NextResponse.json({
          redirect: userHome(
            result.data.user,
            request.cookies.get(ROLE_COOKIE)?.value,
          ),
        }),
      );
      setAuthCookies(response, result.data);
      return response;
    }
    if (action === "refresh") {
      const token = request.cookies.get(REFRESH_COOKIE)?.value;
      if (!token)
        throw new BackendError(401, {
          message: "Sesi berakhir. Silakan login kembali.",
        });
      try {
        const result = await refreshSession(token);
        const response = privateResponse(
          NextResponse.json({
            redirect: userHome(
              result.data.user,
              request.cookies.get(ROLE_COOKIE)?.value,
            ),
          }),
        );
        setAuthCookies(response, result.data);
        return response;
      } catch (error) {
        const response = failure(error);
        if (error instanceof BackendError && [401, 403].includes(error.status))
          clearAuthCookies(response);
        return response;
      }
    }
    const access = request.cookies.get(ACCESS_COOKIE)?.value;
    const refresh = request.cookies.get(REFRESH_COOKIE)?.value;
    if (action === "logout") {
      let revoked = !access && !refresh;
      try {
        let bearer = access;
        if (!bearer && refresh)
          bearer = (await refreshSession(refresh)).data.access_token;
        if (bearer) {
          await backendJson("logout", {
            method: "POST",
            headers: { Authorization: `Bearer ${bearer}` },
          });
          revoked = true;
        }
      } catch (error) {
        if (error instanceof BackendError && error.status === 401 && refresh) {
          try {
            const result = await refreshSession(refresh);
            await backendJson("logout", {
              method: "POST",
              headers: { Authorization: `Bearer ${result.data.access_token}` },
            });
            revoked = true;
          } catch {
            revoked = false;
          }
        }
      }
      const response = privateResponse(
        NextResponse.json({ redirect: "/login", server_revoked: revoked }),
      );
      clearAuthCookies(response);
      response.cookies.set(ROLE_COOKIE, "", {
        path: "/",
        maxAge: 0,
        sameSite: "lax",
      });
      return response;
    }
    if (!access)
      throw new BackendError(401, { message: "Silakan login kembali." });
    await backendJson("password", {
      method: "PUT",
      headers: {
        Authorization: `Bearer ${access}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        current_password: body.current_password,
        password: body.password,
        password_confirmation: body.password_confirmation,
      }),
    });
    const response = privateResponse(
      NextResponse.json({ redirect: "/login?notice=password-updated" }),
    );
    clearAuthCookies(response);
    return response;
  } catch (error) {
    return failure(error);
  }
}
