import { type NextRequest, NextResponse } from "next/server";
import { BackendError, backendFetch, backendJson } from "@/lib/auth/backend";
import {
  ACCESS_COOKIE,
  isSameOrigin,
  privateResponse,
} from "@/lib/auth/cookies";

const resource =
  "(?:students|guardians|employees|positions|employee-categories|subjects|student-statuses|attendance-statuses)";
const relations =
  "(?:students/[1-9]\\d*/guardian-links|employees/[1-9]\\d*/(?:categories|positions))";

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
  context: { params: Promise<{ path: string[] }> },
): Promise<NextResponse> {
  const path = (await context.params).path.join("/");
  const method = request.method;
  const allowed =
    method === "GET"
      ? new RegExp(
          `^(?:options|people|institution|${resource}(?:/[1-9]\\d*)?|${relations}|imports(?:/[1-9]\\d*(?:/(?:rows|errors))?|/templates/(?:students|guardians|employees|subjects))?)$`,
        ).test(path)
      : method === "PUT"
        ? new RegExp(`^(?:institution|${resource}/[1-9]\\d*)$`).test(path)
        : method === "POST" &&
          new RegExp(
            `^(?:${resource}(?:/[1-9]\\d*/status)?|${relations}(?:/[1-9]\\d*/(?:verify|revoke|end))?|imports(?:/[1-9]\\d*/confirm)?)$`,
          ).test(path);
  if (!allowed)
    return privateResponse(
      NextResponse.json({ message: "Tidak ditemukan." }, { status: 404 }),
    );
  const upload = path === "imports" && method === "POST";
  if (
    method !== "GET" &&
    !isSameOrigin(
      request,
      upload ? ["multipart/form-data"] : ["application/json"],
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
    if (upload) {
      const buffer = await boundedBody(request, 11 * 1024 * 1024);
      const form = await new Response(buffer, {
        headers: { "Content-Type": request.headers.get("content-type") ?? "" },
      }).formData();
      const file = form.get("file");
      if (
        !(file instanceof File) ||
        file.size > 10 * 1024 * 1024 ||
        !file.name.toLowerCase().endsWith(".csv")
      )
        throw new BackendError(422, {
          message: "Pilih berkas CSV maksimal 10 MB.",
        });
      const safe = new FormData();
      for (const key of [
        "resource",
        "source_system",
        "operation_key",
        "reason",
      ]) {
        const value = form.get(key);
        if (typeof value !== "string")
          throw new BackendError(422, {
            message: "Informasi impor belum lengkap.",
          });
        safe.set(key, value);
      }
      safe.set("file", file);
      body = safe;
    } else if (method !== "GET") {
      const buffer = await boundedBody(request, 50_000);
      const data = JSON.parse(new TextDecoder().decode(buffer));
      if (!data || typeof data !== "object" || Array.isArray(data))
        throw new BackendError(422, {
          message: "Format permintaan tidak valid.",
        });
      headers["Content-Type"] = "application/json";
      body = JSON.stringify(data);
    }
    const params = new URLSearchParams();
    for (const key of [
      "q",
      "page",
      "per_page",
      "is_active",
      "domain",
      "status",
      "birth_date",
    ]) {
      const value = request.nextUrl.searchParams.get(key);
      if (value !== null) params.set(key, value);
    }
    const target = path + (params.size ? `?${params.toString()}` : "");
    if (
      method === "GET" &&
      (path.startsWith("imports/templates/") || path.endsWith("/errors"))
    ) {
      const response = await backendFetch(
        target,
        { method, headers },
        "master",
      );
      if (!response.ok) {
        const payload = await response.json().catch(() => ({}));
        throw new BackendError(response.status, {
          message:
            response.status >= 500
              ? "Unduhan belum tersedia. Silakan coba kembali."
              : payload.message,
        });
      }
      if (!response.headers.get("content-type")?.startsWith("text/csv"))
        throw new BackendError(502, {
          message: "Format unduhan tidak sesuai.",
        });
      const filename = path.startsWith("imports/templates/")
        ? `template-${path.split("/").at(-1)}.csv`
        : `impor-${path.split("/")[1]}-kesalahan.csv`;
      return privateResponse(
        new NextResponse(response.body, {
          headers: {
            "Content-Type": "text/csv; charset=UTF-8",
            "Content-Disposition": `attachment; filename="${filename}"`,
          },
        }),
      );
    }
    const result = await backendJson(
      target,
      { method, headers, body },
      "master",
    );
    return privateResponse(
      NextResponse.json(result, {
        status: method === "POST" && path.startsWith("imports") ? 202 : 200,
      }),
    );
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
              : error instanceof SyntaxError || error instanceof TypeError
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
