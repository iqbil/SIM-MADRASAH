import type { ApiError } from "./types";

export class AuthRequestError extends Error {
  constructor(
    public status: number,
    public payload: ApiError,
  ) {
    super(
      payload.message ?? "Permintaan belum berhasil. Silakan coba kembali.",
    );
  }
}

export async function authRequest<T>(
  action: string,
  body?: unknown,
  method = "POST",
): Promise<T> {
  let response: Response;
  try {
    response = await fetch(`/api/auth/${action}`, {
      method,
      headers: { "Content-Type": "application/json" },
      body: body === undefined ? undefined : JSON.stringify(body),
      cache: "no-store",
      signal: AbortSignal.timeout(15_000),
    });
  } catch {
    throw new AuthRequestError(503, {
      message:
        "Koneksi belum tersedia. Periksa jaringan Anda, lalu coba kembali.",
    });
  }
  const payload = await response.json();
  if (!response.ok) throw new AuthRequestError(response.status, payload);
  return payload;
}

let refreshing: Promise<{ redirect: string }> | undefined;

export function renewSession(): Promise<{ redirect: string }> {
  if (!refreshing) {
    refreshing = authRequest<{ redirect: string }>("refresh", {}).finally(
      () => {
        refreshing = undefined;
      },
    );
  }
  return refreshing;
}
