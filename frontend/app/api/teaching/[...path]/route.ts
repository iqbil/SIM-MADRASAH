import { type NextRequest, NextResponse } from "next/server";
import { BackendError, backendJson } from "@/lib/auth/backend";
import {
  ACCESS_COOKIE,
  isSameOrigin,
  privateResponse,
} from "@/lib/auth/cookies";

type Context = { params: Promise<{ path: string[] }> };

const id = "[1-9]\\d*";
const GET_JSON = new RegExp(
  `^(?:meetings(?:\\/${id})?|realizations|verifications|revisions\\/${id})$`,
);
const POST_JSON = new RegExp(
  `^(?:meetings\\/${id}\\/realizations|revisions\\/${id}\\/(?:submit|decision))$`,
);

async function proxy(
  request: NextRequest,
  context: Context,
): Promise<NextResponse> {
  const path = (await context.params).path.join("/");
  const method = request.method;
  const allowed =
    (method === "GET" && GET_JSON.test(path)) ||
    (method === "POST" && POST_JSON.test(path));
  if (!allowed)
    return privateResponse(
      NextResponse.json({ message: "Tidak ditemukan." }, { status: 404 }),
    );
  if (method !== "GET" && !isSameOrigin(request, ["application/json"]))
    return privateResponse(
      NextResponse.json(
        { message: "Permintaan tidak diizinkan." },
        { status: 403 },
      ),
    );
  const access = request.cookies.get(ACCESS_COOKIE)?.value;
  if (!access)
    return privateResponse(
      NextResponse.json({ message: "Silakan login kembali." }, { status: 401 }),
    );
  try {
    const headers: Record<string, string> = {
      Authorization: `Bearer ${access}`,
    };
    let body: string | undefined;
    if (method !== "GET") {
      const data = await request.json();
      if (!data || typeof data !== "object" || Array.isArray(data))
        throw new BackendError(422, {
          message: "Format permintaan tidak valid.",
        });
      body = JSON.stringify(data);
      headers["Content-Type"] = "application/json";
    }
    const params = new URLSearchParams();
    for (const name of [
      "page",
      "per_page",
      "date_from",
      "date_to",
      "semester_id",
      "verification_status",
    ]) {
      const value = request.nextUrl.searchParams.get(name);
      if (value !== null) params.set(name, value);
    }
    const result = await backendJson(
      path + (params.size ? `?${params.toString()}` : ""),
      { method, headers, body },
      "teaching",
    );

    return privateResponse(NextResponse.json(result));
  } catch (error) {
    return privateResponse(
      NextResponse.json(
        error instanceof BackendError
          ? error.payload
          : { message: "Permintaan belum berhasil. Silakan coba kembali." },
        {
          status:
            error instanceof BackendError
              ? error.status
              : error instanceof SyntaxError
                ? 422
                : 500,
        },
      ),
    );
  }
}

export const GET = proxy;
export const POST = proxy;
