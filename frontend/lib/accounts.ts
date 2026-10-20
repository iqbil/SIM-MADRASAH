import { AuthRequestError, renewSession } from "@/lib/auth/client";

export interface AccountRole {
  id: number;
  code: string;
  name: string;
  description?: string | null;
  is_active?: boolean;
  permission_codes?: string[];
}
export interface ManagedUser {
  id: number;
  person_id: number;
  name: string;
  username: string;
  email: string | null;
  status: "active" | "disabled";
  lock_version: number;
  email_verified: boolean;
  google_linked: boolean;
  must_change_password: boolean;
  active_sessions: number;
  last_login_at: string | null;
  created_at: string | null;
  roles: AccountRole[];
  sessions?: {
    id: number;
    login_method: string;
    last_used_at: string;
    expires_at: string;
  }[];
  role_history?: {
    id: number;
    role: string;
    effective_from: string;
    effective_to: string | null;
    granted_by: string;
    revoked_by: string | null;
    reason: string | null;
  }[];
}
export interface PageMeta {
  page: number;
  last_page: number;
  total: number;
}
export interface AuditEntry {
  id: number;
  actor: string;
  event: string;
  object_type: string;
  object_id: string;
  occurred_at: string;
  reason: string | null;
}

export async function accountRequest<T>(
  path: string,
  method = "GET",
  data?: unknown,
): Promise<T> {
  async function send() {
    try {
      return await fetch(`/api/accounts/${path}`, {
        method,
        cache: "no-store",
        headers: { "Content-Type": "application/json" },
        body: data === undefined ? undefined : JSON.stringify(data),
        signal: AbortSignal.timeout(15_000),
      });
    } catch {
      throw new AuthRequestError(503, {
        message: "Layanan madrasah belum dapat dihubungi.",
      });
    }
  }
  let response = await send();
  if (response.status === 401) {
    const result = await renewSession();
    if (result.redirect === "/ganti-password") {
      window.location.assign(result.redirect);
      throw new AuthRequestError(403, {
        message: "Anda wajib mengganti password.",
      });
    }
    response = await send();
  }
  const payload = await response.json();
  if (!response.ok) throw new AuthRequestError(response.status, payload);
  return payload;
}

export function accountError(error: unknown): string {
  if (error instanceof AuthRequestError) {
    const detail = Object.values(error.payload.errors ?? {}).flat()[0];
    return detail ?? error.message;
  }
  return "Permintaan belum berhasil. Silakan coba kembali.";
}

export function formatDate(value: string | null): string {
  return value
    ? new Intl.DateTimeFormat("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        timeZone: "Asia/Jakarta",
      }).format(new Date(value))
    : "—";
}
