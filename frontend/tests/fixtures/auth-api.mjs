import { randomUUID } from "node:crypto";
import { createServer } from "node:http";

const roles = [
  ["kepala_madrasah", "Kepala Madrasah"],
  ["operator", "Admin/Operator"],
  ["guru", "Guru"],
  ["wali_kelas", "Wali Kelas"],
  ["guru_bk", "Guru BK"],
  ["tenaga_kependidikan", "Tenaga Kependidikan"],
  ["bendahara", "Bendahara"],
  ["petugas_tu", "Petugas TU"],
  ["orang_tua_wali", "Orang Tua/Wali"],
].map(([code, name]) => ({ code, name }));
const accountPermissions = [
  "view",
  "create",
  "update",
  "status",
  "verify",
  "reset",
  "sessions",
  "assign",
  "roles",
  "audit",
].map((code) => `accounts.${code}`);
const managedRoles = [
  {
    id: 1,
    code: "super_admin",
    name: "Super Admin",
    is_active: true,
    permission_codes: accountPermissions,
  },
  ...roles.map((role, index) => ({
    ...role,
    id: index + 2,
    is_active: true,
    permission_codes: [],
  })),
];
let managedUsers = [];
let events = [];
const sessions = new Set();
let refreshCount = 0;

function user(login) {
  return {
    id: 1,
    username: login,
    name:
      login === "nama_panjang" ? "NamaPengguna".repeat(12) : "Ahmad Ramadani",
    email: `${login}@madrasah.test`,
    email_verified: true,
    must_change_password: login === "sementara",
    permissions: login === "super_admin" ? accountPermissions : [],
    roles:
      login === "nama_panjang"
        ? roles.filter((role) => role.code === "tenaga_kependidikan")
        : login === "super_admin"
          ? [{ code: "super_admin", name: "Super Admin" }]
          : login === "multi"
            ? roles
            : roles.filter(
                (role) =>
                  role.code === (login === "sementara" ? "guru" : login),
              ),
  };
}

function issue(account, session) {
  const record = session ?? { user: account, revoked: false };
  record.access = `test-access-${randomUUID()}`;
  record.refresh = `test-refresh-${randomUUID()}`;
  record.expired = false;
  sessions.add(record);
  return {
    access_token: record.access,
    refresh_token: record.refresh,
    token_type: "Bearer",
    expires_in: 900,
    refresh_expires_in: 7200,
    user: record.user,
  };
}

function json(response, status, payload) {
  response.writeHead(status, { "Content-Type": "application/json" });
  response.end(JSON.stringify(payload));
}

createServer(async (request, response) => {
  const url = new URL(request.url, "http://127.0.0.1:43990");
  let raw = "";
  for await (const chunk of request) raw += chunk;
  const body = raw ? JSON.parse(raw) : {};
  if (url.pathname === "/health") return json(response, 200, { ok: true });
  if (url.pathname === "/__test/reset") {
    managedUsers = [
      {
        id: 1,
        person_id: 1,
        name: "Pengelola Utama",
        username: "superadmin",
        email: "admin@madrasah.test",
        status: "active",
        lock_version: 0,
        email_verified: true,
        google_linked: false,
        must_change_password: false,
        active_sessions: 1,
        last_login_at: new Date().toISOString(),
        created_at: new Date().toISOString(),
        roles: [managedRoles[0]],
        sessions: [
          {
            id: 1,
            login_method: "password",
            last_used_at: new Date().toISOString(),
            expires_at: new Date(Date.now() + 7200000).toISOString(),
          },
        ],
        role_history: [],
      },
    ];
    events = [];
    sessions.clear();
    refreshCount = 0;
    return json(response, 200, {});
  }
  if (url.pathname === "/__test/expire") {
    for (const session of sessions) session.expired = true;
    return json(response, 200, {});
  }
  if (url.pathname === "/__test/revoke") {
    for (const session of sessions) session.revoked = true;
    return json(response, 200, {});
  }
  if (url.pathname === "/__test/stats")
    return json(response, 200, {
      refreshCount,
      active: [...sessions].filter((record) => !record.revoked).length,
    });

  if (url.pathname.startsWith("/api/v1/accounts/")) {
    const token = request.headers.authorization?.replace("Bearer ", "");
    const session = [...sessions].find(
      (record) => record.access === token && !record.expired && !record.revoked,
    );
    if (!session) return json(response, 401, { message: "Unauthenticated." });
    if (session.user.username !== "super_admin")
      return json(response, 403, { message: "Anda tidak memiliki izin." });
    const route = url.pathname.replace("/api/v1/accounts/", "");
    const id = Number(route.split("/")[1]);
    const selected = managedUsers.find((item) => item.id === id);
    if (route === "roles" && request.method === "GET")
      return json(response, 200, {
        data: managedRoles,
        permissions: accountPermissions.map((code) => ({
          code,
          name: code.replace("accounts.", "Izin "),
        })),
      });
    if (route === "people") return json(response, 200, { data: [] });
    if (route === "audit")
      return json(response, 200, {
        data: events,
        meta: { page: 1, last_page: 1, total: events.length },
      });
    if (route === "users" && request.method === "GET") {
      let data = managedUsers.filter((item) =>
        [item.name, item.username, item.email ?? ""].some((text) =>
          text
            .toLowerCase()
            .includes((url.searchParams.get("q") ?? "").toLowerCase()),
        ),
      );
      const filter = url.searchParams.get("status");
      if (filter) data = data.filter((item) => item.status === filter);
      return json(response, 200, {
        data,
        meta: { page: 1, last_page: 1, total: data.length },
        stats: {
          total: managedUsers.length,
          active: managedUsers.filter((item) => item.status === "active")
            .length,
          disabled: managedUsers.filter((item) => item.status === "disabled")
            .length,
        },
      });
    }
    if (route === "users" && request.method === "POST") {
      const item = {
        ...body,
        id: managedUsers.length + 1,
        person_id: managedUsers.length + 1,
        lock_version: 1,
        status: "active",
        email_verified: false,
        google_linked: false,
        must_change_password: true,
        active_sessions: 0,
        last_login_at: null,
        created_at: new Date().toISOString(),
        roles: managedRoles.filter((role) => body.role_ids?.includes(role.id)),
        sessions: [],
        role_history: [],
      };
      managedUsers.push(item);
      events.push({
        id: events.length + 1,
        actor: "Pengelola Utama",
        event: "accounts.user_created",
        object_type: "user",
        object_id: String(item.id),
        occurred_at: new Date().toISOString(),
        reason: body.reason,
      });
      return json(response, 201, {
        data: item,
        temporary_password: "Aa1!TemporaryPassword123",
      });
    }
    if (selected && request.method === "GET")
      return json(response, 200, { data: selected });
    if (selected && request.method === "PUT") {
      Object.assign(selected, {
        ...body,
        roles: managedRoles.filter((role) => body.role_ids?.includes(role.id)),
        lock_version: selected.lock_version + 1,
      });
      return json(response, 200, { data: selected });
    }
    if (selected && request.method === "POST") {
      const action = route.split("/")[2];
      selected.lock_version++;
      if (action === "status") selected.status = body.status;
      if (action === "verify-email") selected.email_verified = true;
      if (action === "revoke-sessions") {
        selected.sessions = [];
        selected.active_sessions = 0;
      }
      if (action === "unlink-google") {
        selected.google_linked = false;
        selected.email_verified = false;
      }
      events.push({
        id: events.length + 1,
        actor: "Pengelola Utama",
        event: `accounts.${action}`,
        object_type: "user",
        object_id: String(selected.id),
        occurred_at: new Date().toISOString(),
        reason: body.reason,
      });
      return json(response, 200, {
        data: selected,
        temporary_password:
          action === "reset-password" ? "Aa1!ResetPassword123" : null,
      });
    }
    if (route === "roles" && request.method === "POST") {
      const role = { ...body, id: managedRoles.length + 1 };
      managedRoles.push(role);
      return json(response, 201, { data: role });
    }
    if (route.startsWith("roles/") && request.method === "PUT") {
      const role = managedRoles.find((role) => role.id === id);
      Object.assign(role, body);
      return json(response, 200, { data: role });
    }
    return json(response, 404, {});
  }
  const action = url.pathname.replace("/api/v1/auth/", "");
  if (action === "options")
    return json(response, 200, { data: { password: true, google: true } });
  if (action === "login") {
    if (body.login === "dibatasi")
      return json(response, 429, { message: "Too many attempts" });
    if (body.login === "gangguan")
      return json(response, 500, { message: "secret-database-credentials" });
    const login = String(body.login ?? "").split("@")[0];
    if (
      body.password !== "Password12345" ||
      ![
        ...roles.map((role) => role.code),
        "multi",
        "tanpa_role",
        "nama_panjang",
        "sementara",
        "super_admin",
      ].includes(login)
    )
      return json(response, 401, { message: "Kredensial tidak valid." });
    return json(response, 200, { data: issue(user(login)) });
  }
  if (action === "refresh") {
    refreshCount++;
    const session = [...sessions].find(
      (record) => record.refresh === body.refresh_token && !record.revoked,
    );
    if (!session)
      return json(response, 401, { message: "Refresh token tidak valid." });
    if (session.user.must_change_password)
      return json(response, 403, {
        message: "Ganti password terlebih dahulu.",
      });
    return json(response, 200, { data: issue(session.user, session) });
  }
  if (action === "google/redirect") {
    const destination = new URL("https://accounts.google.com/o/oauth2/auth");
    destination.searchParams.set(
      "redirect_uri",
      "http://127.0.0.1:3410/api/auth/google/callback",
    );
    destination.searchParams.set("state", "valid-state");
    response.writeHead(302, {
      Location: destination.toString(),
      "Set-Cookie":
        "sim_session=google-test-session; Path=/; HttpOnly; SameSite=Lax",
    });
    return response.end();
  }
  if (action === "google/callback") {
    if (
      !request.headers.cookie?.includes("sim_session=google-test-session") ||
      url.searchParams.get("state") !== "valid-state" ||
      url.searchParams.get("code") !== "valid-code"
    )
      return json(response, 401, { message: "Invalid state" });
    return json(response, 200, { data: issue(user("guru")) });
  }
  const token = request.headers.authorization?.replace("Bearer ", "");
  const session = [...sessions].find(
    (record) => record.access === token && !record.expired && !record.revoked,
  );
  if (!session) return json(response, 401, { message: "Unauthenticated." });
  if (action === "me") return json(response, 200, { data: session.user });
  if (action === "logout") {
    session.revoked = true;
    return json(response, 200, { message: "Logout berhasil." });
  }
  if (action === "password") {
    if (body.current_password !== "Password12345")
      return json(response, 422, {
        message: "Sandi saat ini tidak sesuai.",
        errors: { current_password: ["Sandi saat ini tidak sesuai."] },
      });
    for (const record of sessions) record.revoked = true;
    return json(response, 200, { message: "Password diubah." });
  }
  return json(response, 404, {});
}).listen(43990, "127.0.0.1");
