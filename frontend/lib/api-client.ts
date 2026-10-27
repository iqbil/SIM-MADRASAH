import { AuthRequestError, renewSession } from "@/lib/auth/client";

export async function apiResponse(
  area:
    | "accounts"
    | "master"
    | "academic"
    | "attendance"
    | "teaching"
    | "student-attendance",
  path: string,
  method = "GET",
  data?: unknown,
): Promise<Response> {
  async function send() {
    try {
      const multipart = data instanceof FormData;
      return await fetch(`/api/${area}/${path}`, {
        method,
        cache: "no-store",
        headers: multipart ? undefined : { "Content-Type": "application/json" },
        body:
          data === undefined
            ? undefined
            : multipart
              ? data
              : JSON.stringify(data),
        signal: AbortSignal.timeout(30_000),
      });
    } catch {
      throw new AuthRequestError(503, {
        message:
          "Layanan madrasah belum dapat dihubungi. Periksa koneksi dan coba kembali.",
      });
    }
  }
  let response = await send();
  if (response.status === 401) {
    try {
      const result = await renewSession();
      if (result.redirect === "/ganti-password") {
        window.location.assign(result.redirect);
        throw new AuthRequestError(403, {
          message: "Anda wajib mengganti password.",
        });
      }
    } catch (error) {
      if (error instanceof AuthRequestError && error.status === 401)
        window.location.assign("/login?notice=session-expired");
      throw error;
    }
    response = await send();
  }
  if (!response.ok) {
    const payload = await response.json().catch(() => ({
      message: "Permintaan belum berhasil. Silakan coba kembali.",
    }));
    throw new AuthRequestError(response.status, payload);
  }
  return response;
}

export async function apiRequest<T>(
  area:
    | "accounts"
    | "master"
    | "academic"
    | "attendance"
    | "teaching"
    | "student-attendance",
  path: string,
  method = "GET",
  data?: unknown,
): Promise<T> {
  const response = await apiResponse(area, path, method, data);
  try {
    return await response.json();
  } catch {
    throw new AuthRequestError(502, {
      message: "Respons layanan belum dapat dibaca. Silakan coba kembali.",
    });
  }
}

export function requestError(error: unknown): string {
  if (error instanceof AuthRequestError)
    return Object.values(error.payload.errors ?? {}).flat()[0] ?? error.message;
  if (error instanceof Error && error.message) return error.message;
  return "Permintaan belum berhasil. Silakan coba kembali.";
}
