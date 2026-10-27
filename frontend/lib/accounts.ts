import { apiRequest, requestError } from "@/lib/api-client";

export interface AccountRole {
  id: number;
  code: string;
  name: string;
  description?: string | null;
  is_active?: boolean;
  is_system?: boolean;
  can_assign?: boolean;
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
  return apiRequest<T>("accounts", path, method, data);
}

export const accountError = requestError;

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
