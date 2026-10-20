import "server-only";

import { createHash } from "node:crypto";
import { backendJson } from "./backend";
import type { TokenResponse } from "./types";

// Deduplicate concurrent refreshes in this server process, including a short retry window.
const pending = new Map<
  string,
  { promise: Promise<{ data: TokenResponse }>; expires: number }
>();

export function refreshSession(
  token: string,
): Promise<{ data: TokenResponse }> {
  const now = Date.now();
  for (const [key, value] of pending) {
    if (value.expires <= now) pending.delete(key);
  }
  const key = createHash("sha256").update(token).digest("hex");
  const existing = pending.get(key);
  if (existing) return existing.promise;
  const promise = backendJson<{ data: TokenResponse }>("refresh", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ refresh_token: token }),
  });
  pending.set(key, { promise, expires: now + 15_000 });
  return promise;
}
