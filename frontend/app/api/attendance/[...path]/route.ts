import { type NextRequest, NextResponse } from "next/server";
import { BackendError, backendFetch, backendJson } from "@/lib/auth/backend";
import {
  ACCESS_COOKIE,
  isSameOrigin,
  privateResponse,
} from "@/lib/auth/cookies";

type Context = { params: Promise<{ path: string[] }> };

const id = "[1-9]\\d*";
const GET_JSON = new RegExp(
  `^(?:status|me|policy|records(?:\\/${id})?|recap|corrections|work-patterns|work-assignments|captures\\/${id})$`,
);
const POST_JSON = new RegExp(
  `^(?:records|corrections|corrections\\/${id}\\/decision|policy\\/versions|work-patterns|work-patterns\\/${id}\\/(?:versions|assignments)|work-days\\/generate)$`,
);

async function boundedBody(
  request: NextRequest,
  limit: number,
): Promise<ArrayBuffer> {
  const reader = request.body?.getReader();
  if (!reader) return new ArrayBuffer(0);
  const chunks: Uint8Array[] = [];
  let size = 0;
  try {
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      size += value.byteLength;
      if (size > limit) {
        await reader.cancel();
        throw new BackendError(413, {
          message: "Berkas atau permintaan terlalu besar.",
        });
      }
      chunks.push(value);
    }
  } finally {
    reader.releaseLock();
  }
  const result = new Uint8Array(size);
  let offset = 0;
  for (const chunk of chunks) {
    result.set(chunk, offset);
    offset += chunk.byteLength;
  }
  return result.buffer;
}

async function proxy(
  request: NextRequest,
  context: Context,
): Promise<NextResponse> {
  const path = (await context.params).path.join("/");
  const method = request.method;
  const clock = path === "clock" && method === "POST";
  const photo =
    method === "GET" && new RegExp(`^captures\\/${id}\\/photo$`).test(path);
  const allowed =
    (method === "GET" && (GET_JSON.test(path) || photo)) ||
    (method === "POST" && (POST_JSON.test(path) || clock)) ||
    (method === "PUT" && new RegExp(`^work-patterns\\/${id}$`).test(path));
  if (!allowed)
    return privateResponse(
      NextResponse.json({ message: "Tidak ditemukan." }, { status: 404 }),
    );
  if (
    method !== "GET" &&
    !isSameOrigin(
      request,
      clock ? ["multipart/form-data"] : ["application/json"],
    )
  )
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
    let body: BodyInit | undefined;
    if (clock) {
      const buffer = await boundedBody(request, 11 * 1024 * 1024);
      const form = await new Response(buffer, {
        headers: { "Content-Type": request.headers.get("content-type") ?? "" },
      }).formData();
      const photoFile = form.get("photo");
      if (
        !(photoFile instanceof File) ||
        photoFile.size === 0 ||
        photoFile.size > 10 * 1024 * 1024 ||
        !photoFile.type.startsWith("image/")
      )
        throw new BackendError(422, {
          message:
            "Foto absensi wajib berupa gambar yang diambil saat mencatat.",
        });
      const safe = new FormData();
      for (const key of [
        "action",
        "operation_key",
        "latitude",
        "longitude",
        "accuracy_meters",
        "photo_taken_at",
        "location_captured_at",
      ]) {
        const value = form.get(key);
        if (typeof value !== "string" || value === "")
          throw new BackendError(422, {
            message: "Data absensi belum lengkap.",
          });
        safe.set(key, value);
      }
      safe.set("photo", photoFile, "photo.jpg");
      body = safe;
    } else if (method !== "GET") {
      const data = await request.json();
      if (!data || typeof data !== "object" || Array.isArray(data))
        throw new BackendError(422, {
          message: "Format permintaan tidak valid.",
        });
      body = JSON.stringify(data);
      headers["Content-Type"] = "application/json";
    }
    if (photo) {
      const response = await backendFetch(
        path,
        { method: "GET", headers },
        "attendance",
      );
      if (!response.ok)
        return privateResponse(
          NextResponse.json(
            { message: "Bukti tidak tersedia." },
            { status: response.status },
          ),
        );
      const bytes = await response.arrayBuffer();
      return privateResponse(
        new NextResponse(bytes, {
          status: 200,
          headers: {
            "Content-Type":
              response.headers.get("content-type") ?? "image/webp",
            "Cache-Control": "private, no-store",
          },
        }),
      );
    }
    const params = new URLSearchParams();
    for (const name of [
      "page",
      "per_page",
      "employee_id",
      "date_from",
      "date_to",
      "presence",
      "status",
    ]) {
      const value = request.nextUrl.searchParams.get(name);
      if (value !== null) params.set(name, value);
    }
    const result = await backendJson(
      path + (params.size ? `?${params.toString()}` : ""),
      { method, headers, body: body ?? undefined },
      "attendance",
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
export const PUT = proxy;
