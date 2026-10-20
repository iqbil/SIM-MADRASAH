import "server-only";

import type { ApiError } from "./types";

export class BackendError extends Error {
  constructor(
    public status: number,
    public payload: ApiError,
  ) {
    super(
      payload.message ??
        "Layanan madrasah belum dapat dihubungi. Silakan coba kembali.",
    );
  }
}

export function backendUrl(
  path: string,
  area: "auth" | "accounts" | "master" = "auth",
): string {
  const base = process.env.API_BASE_URL ?? "http://127.0.0.1:8000/api/v1";
  return `${base.replace(/\/$/, "")}/${area}/${path}`;
}

export async function backendFetch(
  path: string,
  init: RequestInit = {},
  area: "auth" | "accounts" | "master" = "auth",
): Promise<Response> {
  try {
    return await fetch(backendUrl(path, area), {
      ...init,
      cache: "no-store",
      redirect: "manual",
      signal: AbortSignal.timeout(10_000),
      headers: { Accept: "application/json", ...init.headers },
    });
  } catch {
    throw new BackendError(503, {
      message: "Layanan madrasah belum dapat dihubungi. Silakan coba kembali.",
    });
  }
}

export async function backendJson<T>(
  path: string,
  init: RequestInit = {},
  area: "auth" | "accounts" | "master" = "auth",
): Promise<T> {
  const response = await backendFetch(path, init, area);
  let payload: T & ApiError;
  try {
    payload = await response.json();
  } catch {
    throw new BackendError(502, {
      message:
        "Layanan madrasah sedang mengalami gangguan. Silakan coba kembali.",
    });
  }
  if (!response.ok) {
    const message =
      response.status >= 500
        ? "Layanan madrasah sedang mengalami gangguan. Silakan coba kembali."
        : response.status === 429
          ? "Terlalu banyak percobaan. Tunggu satu menit, lalu coba kembali."
          : payload.message;
    throw new BackendError(response.status, {
      message,
      errors: response.status === 422 ? payload.errors : undefined,
      duplicate_candidates:
        response.status === 422 ? payload.duplicate_candidates : undefined,
    });
  }
  return payload;
}
