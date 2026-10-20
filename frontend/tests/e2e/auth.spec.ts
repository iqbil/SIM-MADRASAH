import { expect, type Page, test } from "@playwright/test";

async function login(page: Page, username = "guru") {
  await page.goto("/login");
  await page.getByLabel("Username atau email").fill(username);
  await page.getByLabel("Password", { exact: true }).fill("Password12345");
  await page.getByRole("button", { name: "Masuk ke portal" }).click();
}

test.beforeEach(async ({ request, page }) => {
  await page.emulateMedia({ reducedMotion: "reduce" });
  await request.post("http://127.0.0.1:43990/__test/reset");
});

test("mobile login is accessible, has no overflow, and supports help and password visibility", async ({
  page,
}) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto("/login");
  await expect(
    page.getByRole("heading", { name: "Login", exact: true }),
  ).toBeVisible();
  await page.getByRole("button", { name: "Tampilkan password" }).click();
  await expect(page.getByLabel("Password", { exact: true })).toHaveAttribute(
    "type",
    "text",
  );
  await page.getByRole("button", { name: "Lupa password?" }).click();
  await expect(page.getByRole("dialog")).toContainText("operator madrasah");
  await page.keyboard.press("Escape");
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= window.innerWidth,
    ),
  ).toBe(true);
  await expect
    .poll(() =>
      page
        .getByRole("img", { name: /lingkungan sekolah/i })
        .evaluate((image) => (image as HTMLImageElement).naturalWidth),
    )
    .toBeGreaterThan(0);
  await page.screenshot({
    path: "test-results/screenshots/login-mobile.png",
    fullPage: true,
    animations: "disabled",
  });
  await page.setViewportSize({ width: 1440, height: 960 });
  await page.screenshot({
    path: "test-results/screenshots/login-desktop.png",
    fullPage: true,
    animations: "disabled",
  });
});

test("login errors and rate limits are displayed without leaking upstream details", async ({
  page,
}) => {
  await page.goto("/login");
  for (const username of ["salah", "dibatasi", "gangguan"]) {
    await page.getByLabel("Username atau email").fill(username);
    await page.getByLabel("Password", { exact: true }).fill("Password12345");
    await page.getByRole("button", { name: "Masuk ke portal" }).click();
    await expect(page.locator('[data-slot="alert"]')).toBeVisible();
    if (username === "dibatasi")
      await expect(page.locator('[data-slot="alert"]')).toContainText(
        "Tunggu satu menit",
      );
    if (username === "gangguan")
      await expect(page.locator('[data-slot="alert"]')).not.toContainText(
        "secret-database",
      );
  }
});

test("real BFF login uses HttpOnly cookies and blocks unrelated roles and anonymous access", async ({
  page,
  context,
}) => {
  await page.goto("/dashboard/guru");
  await expect(page).toHaveURL(/\/login$/);
  await login(page);
  await expect(page).toHaveURL(/\/dashboard\/guru$/);
  await expect(
    page.getByRole("heading", { name: "Ruang kerja Anda sudah siap" }),
  ).toBeVisible();
  const cookies = await context.cookies();
  expect(
    cookies
      .filter((cookie) => cookie.name.startsWith("madrasah_"))
      .every((cookie) => cookie.httpOnly),
  ).toBe(true);
  expect(await page.evaluate(() => document.cookie)).not.toContain(
    "madrasah_access",
  );
  expect(await page.content()).not.toContain("test-access-");
  await page.goto("/dashboard/bendahara");
  await expect(
    page.getByRole("heading", { name: "Ruang kerja tidak tersedia" }),
  ).toBeVisible();
});

test("all nine active SRS roles have separate empty dashboards and can be switched", async ({
  page,
}) => {
  await login(page, "multi");
  await expect(page).toHaveURL(/\/dashboard\/kepala_madrasah$/);
  const roles = [
    ["operator", "Admin/Operator"],
    ["guru", "Guru"],
    ["wali_kelas", "Wali Kelas"],
    ["guru_bk", "Guru BK"],
    ["tenaga_kependidikan", "Tenaga Kependidikan"],
    ["bendahara", "Bendahara"],
    ["petugas_tu", "Petugas TU"],
    ["orang_tua_wali", "Orang Tua/Wali"],
  ];
  for (const [code, name] of roles) {
    await page.getByRole("button", { name: "Pilih ruang kerja" }).click();
    await page.getByRole("menuitem", { name, exact: true }).click();
    await expect(page).toHaveURL(new RegExp(`/dashboard/${code}$`));
    await expect(
      page.getByRole("heading", { name: "Ruang kerja Anda sudah siap" }),
    ).toBeVisible();
  }
  await page.screenshot({
    path: "test-results/screenshots/dashboard-desktop.png",
    fullPage: true,
    animations: "disabled",
  });
  await page.goto("/dashboard");
  await expect(page).toHaveURL(/\/dashboard\/orang_tua_wali$/);
});

test("mobile sidebar opens, navigates and closes, including at 320px", async ({
  page,
}) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await login(page);
  await expect(page).toHaveURL(/\/dashboard\/guru$/);
  await expect(
    page.getByRole("heading", { name: "Ruang kerja Anda sudah siap" }),
  ).toBeVisible();
  await page.screenshot({
    path: "test-results/screenshots/dashboard-mobile.png",
    fullPage: true,
    animations: "disabled",
  });
  await page
    .getByRole("button", { name: "Buka atau tutup menu", exact: true })
    .click();
  await expect(
    page.getByRole("dialog", { name: "Menu navigasi" }),
  ).toBeVisible();
  await page.screenshot({
    path: "test-results/screenshots/sidebar-mobile.png",
    fullPage: true,
    animations: "disabled",
  });
  await page.getByRole("link", { name: "Akun saya", exact: true }).click();
  await expect(page).toHaveURL(/\/akun$/);
  await expect(
    page.getByRole("dialog", { name: "Menu navigasi" }),
  ).not.toBeVisible();
  await page.setViewportSize({ width: 320, height: 740 });
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= window.innerWidth,
    ),
  ).toBe(true);
});

test("desktop sidebar can collapse and remembers its state after reload", async ({
  page,
}) => {
  await login(page);
  await expect(
    page.getByRole("heading", { name: "Ruang kerja Anda sudah siap" }),
  ).toBeVisible();
  await page
    .getByRole("button", { name: "Buka atau tutup menu", exact: true })
    .click();
  await expect(
    page.locator('[data-slot="sidebar"][data-state]'),
  ).toHaveAttribute("data-state", "collapsed");
  await page.reload();
  await expect(
    page.locator('[data-slot="sidebar"][data-state]'),
  ).toHaveAttribute("data-state", "collapsed");
  await expect(
    page.getByRole("heading", { name: "Ruang kerja Anda sudah siap" }),
  ).toBeVisible();
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= window.innerWidth,
    ),
  ).toBe(true);
  await page.screenshot({
    path: "test-results/screenshots/sidebar-desktop-collapsed.png",
    fullPage: true,
    animations: "disabled",
  });
});

test("expired access automatically rotates the refresh token; revoked sessions require login", async ({
  page,
  request,
  context,
}) => {
  await login(page);
  await expect(page).toHaveURL(/\/dashboard\/guru$/);
  const previous = (await context.cookies()).find(
    (cookie) => cookie.name === "madrasah_refresh",
  )?.value;
  await request.post("http://127.0.0.1:43990/__test/expire");
  await page.reload();
  await expect(
    page.getByRole("heading", { name: "Ruang kerja Anda sudah siap" }),
  ).toBeVisible();
  const refreshed = (await context.cookies()).find(
    (cookie) => cookie.name === "madrasah_refresh",
  )?.value;
  expect(refreshed).not.toBe(previous);
  await request.post("http://127.0.0.1:43990/__test/revoke");
  await page.reload();
  await expect(page).toHaveURL(/\/login\?error=session-expired$/);
});

test("temporary password requires a change and clears authentication after success", async ({
  page,
  context,
}) => {
  await login(page, "sementara");
  await expect(page).toHaveURL(/\/ganti-password$/);
  await page.goto("/dashboard/guru");
  await expect(page).toHaveURL(/\/ganti-password$/);
  await page
    .getByLabel("Password saat ini", { exact: true })
    .fill("Password12345");
  await page
    .getByLabel("Password baru", { exact: true })
    .fill("NewPassword12345");
  await page
    .getByLabel("Konfirmasi password baru", { exact: true })
    .fill("DifferentPassword12345");
  await page.getByRole("button", { name: "Simpan password baru" }).click();
  await expect(
    page.getByText("Konfirmasi password belum sesuai."),
  ).toBeVisible();
  await page
    .getByLabel("Konfirmasi password baru", { exact: true })
    .fill("NewPassword12345");
  await page.getByRole("button", { name: "Simpan password baru" }).click();
  await expect(page).toHaveURL(/\/login\?notice=password-updated$/);
  expect(
    (await context.cookies()).some(
      (cookie) => cookie.name === "madrasah_access",
    ),
  ).toBe(false);
});

test("logout revokes the backend session and protected pages become inaccessible", async ({
  page,
  request,
  context,
}) => {
  await login(page);
  await expect(page).toHaveURL(/\/dashboard\/guru$/);
  await page.getByRole("button", { name: "Menu akun", exact: true }).click();
  await page.getByRole("menuitem", { name: "Keluar dari akun" }).click();
  await expect(page).toHaveURL(/\/login$/);
  expect(
    (await context.cookies()).some(
      (cookie) => cookie.name === "madrasah_access",
    ),
  ).toBe(false);
  const stats = await (
    await request.get("http://127.0.0.1:43990/__test/stats")
  ).json();
  expect(stats.active).toBe(0);
  await page.goto("/dashboard/guru");
  await expect(page).toHaveURL(/\/login$/);
});

test("accounts without roles receive an honest empty state", async ({
  page,
}) => {
  await login(page, "tanpa_role");
  await expect(page).toHaveURL(/\/dashboard$/);
  await expect(
    page.getByText("Belum ada role yang diberikan pada akun Anda.", {
      exact: false,
    }),
  ).toBeVisible();
});

test("cross-origin mutations are rejected and renew never redirects externally", async ({
  request,
  page,
}) => {
  const response = await request.post("/api/auth/login", {
    headers: { Origin: "https://untrusted.example" },
    data: { login: "guru", password: "Password12345" },
  });
  expect(response.status()).toBe(403);
  await login(page);
  await expect(page).toHaveURL(/\/dashboard\/guru$/);
  await page.goto("/sesi/perbarui?next=https://untrusted.example");
  await expect(page).toHaveURL(/\/dashboard\/guru$/);
});

test("Google handshake stays server-side, returns cookies and handles invalid callbacks", async ({
  page,
  context,
}) => {
  const start = await context.request.get("/api/auth/google", {
    maxRedirects: 0,
  });
  expect(start.status()).toBe(307);
  expect(start.headers().location).toContain("accounts.google.com");
  const callback = await context.request.get(
    "/api/auth/google/callback?state=valid-state&code=valid-code",
    { maxRedirects: 0 },
  );
  expect(callback.headers().location).toBe(
    "http://127.0.0.1:3410/dashboard/guru",
  );
  expect(callback.headers().location).not.toContain("test-access");
  await page.goto("/dashboard/guru");
  await expect(
    page.getByRole("heading", { name: "Ruang kerja Anda sudah siap" }),
  ).toBeVisible();
  const replay = await context.request.get(
    "/api/auth/google/callback?state=valid-state&code=valid-code",
    { maxRedirects: 0 },
  );
  expect(replay.headers().location).toContain("/login?error=google-failed");
});
