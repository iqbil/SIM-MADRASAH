import { expect, type Page, test } from "@playwright/test";

async function login(page: Page, username: string) {
  await page.goto("/login");
  await page.getByLabel("Username atau email").fill(username);
  await page.getByLabel("Password", { exact: true }).fill("Password12345");
  await page.getByRole("button", { name: "Masuk ke portal" }).click();
  await expect(page).toHaveURL(/\/dashboard(?:\/[^/]+)?$/);
  await expect(
    page.getByRole("button", { name: "Buka atau tutup menu", exact: true }),
  ).toBeEnabled();
}

const sections = [
  "Data master",
  "Akademik & kelas",
  "Absensi pegawai",
  "Realisasi mengajar",
  "Absensi siswa",
  "Honor & keuangan",
  "Bank soal & paket",
  "Bimbingan & konseling",
  "Tata usaha & arsip",
  "Portal orang tua / wali",
  "Website madrasah",
  "Laporan & ekspor",
  "Pengaturan sistem",
];
const cases: [string, string[]][] = [
  [
    "super_admin",
    [
      "Data master",
      "Akademik & kelas",
      "Absensi pegawai",
      "Website madrasah",
      "Laporan & ekspor",
      "Pengaturan sistem",
    ],
  ],
  [
    "operator",
    [
      "Data master",
      "Akademik & kelas",
      "Absensi pegawai",
      "Website madrasah",
      "Laporan & ekspor",
      "Pengaturan sistem",
    ],
  ],
  [
    "kepala_madrasah",
    [
      "Akademik & kelas",
      "Absensi pegawai",
      "Honor & keuangan",
      "Laporan & ekspor",
    ],
  ],
  [
    "guru",
    [
      "Akademik & kelas",
      "Absensi pegawai",
      "Realisasi mengajar",
      "Absensi siswa",
      "Honor & keuangan",
      "Bank soal & paket",
    ],
  ],
  [
    "wali_kelas",
    [
      "Akademik & kelas",
      "Absensi pegawai",
      "Absensi siswa",
      "Honor & keuangan",
      "Bimbingan & konseling",
    ],
  ],
  ["guru_bk", ["Absensi pegawai", "Honor & keuangan"]],
  ["tenaga_kependidikan", ["Absensi pegawai", "Honor & keuangan"]],
  ["bendahara", ["Absensi pegawai", "Honor & keuangan"]],
  [
    "petugas_tu",
    [
      "Absensi pegawai",
      "Honor & keuangan",
      "Tata usaha & arsip",
      "Website madrasah",
    ],
  ],
  ["orang_tua_wali", []],
  ["tanpa_role", []],
  ["akses_dicabut", []],
];

test.beforeEach(async ({ request, page }) => {
  await request.post("http://127.0.0.1:43990/__test/reset");
  await page.emulateMedia({ reducedMotion: "reduce" });
});

for (const [username, allowed] of cases) {
  test(`sidebar only shows server-authorized sections for ${username}`, async ({
    page,
  }) => {
    await login(page, username);
    const nav = page.getByRole("navigation", { name: "Menu utama madrasah" });
    await expect(
      nav.getByRole("link", { name: "Pengelolaan akun", exact: true }),
    ).toHaveCount(username === "super_admin" ? 1 : 0);
    for (const section of sections) {
      await expect(
        nav.getByRole("button", { name: section, exact: true }),
      ).toHaveCount(allowed.includes(section) ? 1 : 0);
    }
    await expect(
      nav.getByRole("link", { name: "Beranda", exact: true }),
    ).toBeVisible();
    await expect(
      nav.getByRole("link", { name: "Akun saya", exact: true }),
    ).toBeVisible();
  });
}

for (const [username, section, item] of [
  ["bk_berizin", "Bimbingan & konseling", "Kasus & catatan internal"],
  ["bendahara_bermandat", "Honor & keuangan", "Pemeriksaan honor"],
  ["kepala_bermandat", "Honor & keuangan", "Pengesahan & penguncian honor"],
  ["wali_terverifikasi", "Portal orang tua / wali", "Data & kelas anak"],
]) {
  test(`scoped access makes the corresponding menu visible for ${username}`, async ({
    page,
    request,
  }) => {
    await login(page, username);
    const nav = page.getByRole("navigation", { name: "Menu utama madrasah" });
    await nav.getByRole("button", { name: section, exact: true }).click();
    const planned = nav.getByRole("button", {
      name: `${item} — Segera hadir`,
      exact: true,
    });
    await expect(planned).toBeVisible();
    await expect(planned).toBeDisabled();
    expect(await planned.getAttribute("href")).toBeNull();
    await request.post("http://127.0.0.1:43990/__test/revoke-navigation");
    await page.reload();
    await expect(
      nav.getByRole("button", { name: section, exact: true }),
    ).toHaveCount(0);
  });
}

test("menu groups remain usable on mobile and tablet, and planned items never link to missing pages", async ({
  page,
}) => {
  await page.setViewportSize({ width: 320, height: 568 });
  await login(page, "super_admin");
  for (const width of [320, 390, 768, 1024]) {
    await page.setViewportSize({ width, height: 740 });
    if (width < 1024) {
      await page
        .getByRole("button", { name: "Buka atau tutup menu", exact: true })
        .click();
      await expect(
        page.getByRole("dialog", { name: "Menu navigasi" }),
      ).toBeVisible();
    } else {
      await expect(
        page.locator('[data-slot="sidebar-container"]'),
      ).toBeVisible();
    }
    const nav = page.getByRole("navigation", { name: "Menu utama madrasah" });
    const group = nav.getByRole("button", { name: "Data master", exact: true });
    if ((await group.getAttribute("aria-expanded")) !== "true")
      await group.click();
    await expect(
      nav.getByRole("link", {
        name: "Data siswa",
        exact: true,
      }),
    ).toHaveAttribute("href", "/data-master/students");
    const box = await group.boundingBox();
    expect(box?.height).toBeGreaterThanOrEqual(44);
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
    ).toBe(true);
    const links = await nav
      .getByRole("link")
      .evaluateAll((elements) =>
        elements.map((element) => element.getAttribute("href")),
      );
    expect(links).toContain("/data-master/students");
    expect(links).toContain("/dashboard/super_admin");
    expect(links).not.toContain("#");
    await page.screenshot({
      path: `test-results/screenshots/sidebar-${width}.png`,
    });
    if (width < 1024) {
      await page
        .getByRole("button", { name: "Tutup menu", exact: true })
        .click();
    }
  }
});

test("role switching and a forged preference do not grant additional navigation", async ({
  page,
}) => {
  await login(page, "multi");
  const nav = page.getByRole("navigation", { name: "Menu utama madrasah" });
  await expect(
    nav.getByRole("button", { name: "Data master", exact: true }),
  ).toBeVisible();
  await page
    .getByRole("button", { name: "Pilih ruang kerja", exact: true })
    .click();
  await page.getByRole("menuitem", { name: "Guru", exact: true }).click();
  await expect(page).toHaveURL(/\/dashboard\/guru$/);
  await expect(
    nav.getByRole("button", { name: "Data master", exact: true }),
  ).toBeVisible();
  await nav
    .getByRole("button", { name: "Honor & keuangan", exact: true })
    .click();
  await expect(
    nav.getByRole("button", {
      name: "Pemeriksaan honor — Segera hadir",
      exact: true,
    }),
  ).toHaveCount(0);
  await page.context().clearCookies();
  await login(page, "guru");
  await page.context().addCookies([
    {
      name: "madrasah_role",
      value: "super_admin",
      url: "http://127.0.0.1:3410",
    },
  ]);
  await page.reload();
  await expect(
    nav.getByRole("button", { name: "Data master", exact: true }),
  ).toHaveCount(0);
  await expect(
    nav.getByRole("link", { name: "Pengelolaan akun", exact: true }),
  ).toHaveCount(0);
});

test("collapsed sidebar groups keep accessible names and support keyboard expansion", async ({
  page,
}) => {
  await login(page, "super_admin");
  const toggle = page.getByRole("button", {
    name: "Buka atau tutup menu",
    exact: true,
  });
  await toggle.click();
  await expect(toggle).toHaveAttribute("aria-expanded", "false");
  const nav = page.getByRole("navigation", { name: "Menu utama madrasah" });
  const group = nav.getByRole("button", { name: "Data master", exact: true });
  await group.focus();
  await page.keyboard.press("Enter");
  await expect(toggle).toHaveAttribute("aria-expanded", "true");
  await expect(group).toHaveAttribute("aria-expanded", "true");
  await expect(
    nav.getByRole("link", { name: "Data siswa", exact: true }),
  ).toBeVisible();
  await group.focus();
  await page.keyboard.press("Space");
  await expect(group).toHaveAttribute("aria-expanded", "false");
});

test("session checks refresh the sidebar when scoped access changes without changing roles", async ({
  page,
  request,
}) => {
  await login(page, "bk_berizin");
  const nav = page.getByRole("navigation", { name: "Menu utama madrasah" });
  await expect(
    nav.getByRole("button", { name: "Bimbingan & konseling", exact: true }),
  ).toBeVisible();
  await request.post("http://127.0.0.1:43990/__test/revoke-navigation");
  await page.evaluate(() => window.dispatchEvent(new Event("pageshow")));
  await expect(
    nav.getByRole("button", { name: "Bimbingan & konseling", exact: true }),
  ).toHaveCount(0);
  await expect(
    nav.getByRole("link", { name: "Akun saya", exact: true }),
  ).toBeVisible();
});
