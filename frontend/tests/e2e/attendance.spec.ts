import { expect, type Page, test } from "@playwright/test";

async function login(page: Page, username: string) {
  await page.goto("/login");
  await page.getByLabel("Username atau email").fill(username);
  await page.getByLabel("Password", { exact: true }).fill("Password12345");
  await page
    .getByRole("button", { name: "Masuk ke portal", exact: true })
    .click();
  await expect(page).toHaveURL(new RegExp(`/dashboard/${username}$`));
}

/** Pasang kamera & GPS palsu untuk aksi absensi mandiri (FR-05-04). */
async function fakeSensors(page: Page) {
  await page.addInitScript(() => {
    Object.defineProperty(navigator.mediaDevices, "getUserMedia", {
      value: async () => {
        const canvas = document.createElement("canvas");
        canvas.width = 320;
        canvas.height = 240;
        canvas.getContext("2d")?.fillRect(0, 0, 320, 240);
        const stream = canvas.captureStream(1);
        await new Promise((resolve) => setTimeout(resolve, 50));

        return stream;
      },
    });
    navigator.geolocation.getCurrentPosition = (success) =>
      success({
        coords: {
          latitude: -6.7,
          longitude: 108.5,
          accuracy: 12.5,
          altitude: null,
          altitudeAccuracy: null,
          heading: null,
          speed: null,
        },
        timestamp: Date.now(),
      } as GeolocationPosition);
  });
}

test.beforeEach(async ({ request, page }) => {
  await request.post("http://127.0.0.1:43990/__test/reset");
  await page.emulateMedia({ reducedMotion: "reduce" });
});

test("GPS policy explains accuracy and keeps tabs compact on desktop", async ({
  page,
}) => {
  await login(page, "operator");
  await page.goto("/absensi/pola-kerja");
  await page.getByRole("tab", { name: "Kebijakan foto/GPS" }).click();
  await expect(
    page.getByRole("heading", { name: "Kebijakan berlaku (versi 1)" }),
  ).toBeVisible();
  await expect(page.getByText("Tanpa titik pusat atau radius")).toBeVisible();
  await expect(
    page.getByText(/Akurasi GPS adalah perkiraan ketelitian posisi perangkat/),
  ).toBeVisible();
  const tabs = page.getByRole("tablist");
  await expect(
    page
      .getByRole("navigation", { name: "Navigasi portal" })
      .getByText("Pola kerja & kebijakan", { exact: true }),
  ).toBeVisible();
  await page.setViewportSize({ width: 1440, height: 900 });
  const desktop = await tabs.boundingBox();
  const main = await page.getByRole("main").boundingBox();
  expect(desktop?.width).toBeLessThan((main?.width ?? 0) * 0.8);
  await page.screenshot({
    path: "test-results/screenshots/attendance-policy-desktop.png",
    fullPage: true,
  });
  await page.setViewportSize({ width: 320, height: 568 });
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= innerWidth,
    ),
  ).toBe(true);
  for (const tab of await tabs.getByRole("tab").all()) {
    await expect
      .poll(() =>
        tab.evaluate((element) => {
          const bounds = element.getBoundingClientRect();
          const range = document.createRange();
          range.selectNodeContents(element);
          return [...range.getClientRects()].every(
            (rect) =>
              rect.left >= bounds.left - 1 &&
              rect.right <= bounds.right + 1 &&
              rect.top >= bounds.top - 1 &&
              rect.bottom <= bounds.bottom + 1,
          );
        }),
      )
      .toBe(true);
  }
  await page.screenshot({
    path: "test-results/screenshots/attendance-policy-mobile.png",
    fullPage: true,
  });
  await page.getByRole("button", { name: "Versi kebijakan baru" }).click();
  await expect(
    page.getByRole("dialog").getByText(/Batas ketelitian posisi perangkat/),
  ).toBeVisible();
});

test("policy loading and failure can be retried without leaving a blank tab", async ({
  page,
}) => {
  await login(page, "operator");
  let release: () => void = () => {};
  const pending = new Promise<void>((resolve) => {
    release = resolve;
  });
  let attempts = 0;
  await page.route("**/api/attendance/policy", async (route) => {
    if (++attempts > 1) return route.continue();
    await pending;
    await route.fulfill({
      status: 503,
      json: { message: "Kebijakan sementara tidak tersedia." },
    });
  });
  await page.goto("/absensi/pola-kerja");
  await page.getByRole("tab", { name: "Kebijakan foto/GPS" }).click();
  const panel = page.getByRole("tabpanel");
  await expect(panel.getByLabel("Memuat data")).toBeVisible();
  await expect(
    panel.getByRole("button", { name: "Versi kebijakan baru" }),
  ).toHaveCount(0);
  release();
  await expect(panel.getByRole("alert")).toContainText(
    "Kebijakan sementara tidak tersedia.",
  );
  await panel.getByRole("button", { name: "Coba kembali" }).click();
  await expect(
    panel.getByRole("heading", { name: "Kebijakan berlaku (versi 1)" }),
  ).toBeVisible();
  await expect(panel.getByRole("alert")).toHaveCount(0);
});

test("employee lookup failure is visible and blocks assignment until retry succeeds", async ({
  page,
}) => {
  await login(page, "operator");
  let attempts = 0;
  await page.route("**/api/master/employees?*", async (route) => {
    if (++attempts > 1) return route.continue();
    await route.fulfill({
      status: 503,
      json: { message: "Layanan pegawai sedang tidak tersedia." },
    });
  });
  await page.goto("/absensi/pola-kerja");
  const main = page.getByRole("main");
  await expect(main.getByRole("alert")).toContainText(
    "Daftar pegawai: Layanan pegawai sedang tidak tersedia.",
  );
  await expect(
    main.getByRole("button", { name: "Tetapkan", exact: true }),
  ).toBeDisabled();
  await main.getByRole("button", { name: "Coba kembali" }).click();
  await expect(main.getByRole("alert")).toHaveCount(0);
  await main.getByRole("button", { name: "Tetapkan", exact: true }).click();
  await page.getByRole("dialog").getByLabel("Pegawai", { exact: true }).click();
  await expect(page.getByRole("option", { name: "Guru Ahmad" })).toBeVisible();
});

test("employee clocks in and out with camera photo and GPS", async ({
  page,
}) => {
  await fakeSensors(page);
  await login(page, "tenaga_kependidikan");
  await page.goto("/absensi/masuk-pulang");
  await expect(
    page.getByRole("heading", { name: "Masuk / pulang" }),
  ).toBeVisible();
  await expect(page.getByText(/Lokasi siap/)).toBeVisible();
  await page.getByRole("button", { name: "Nyalakan kamera" }).click();
  await expect(
    page.getByRole("button", { name: "Matikan kamera" }),
  ).toBeVisible();

  await page
    .getByRole("button", { name: "Absen masuk dengan foto + GPS" })
    .click();
  await expect(
    page.getByText("Absen masuk tercatat dengan foto dan GPS."),
  ).toBeVisible();
  await expect(page.getByText(/Kehadiran hari ini sudah lengkap/)).toHaveCount(
    0,
  );

  await page
    .getByRole("button", { name: "Absen pulang dengan foto + GPS" })
    .click();
  await expect(
    page.getByText("Absen pulang tercatat dengan foto dan GPS."),
  ).toBeVisible();
  await expect(
    page.getByText("Kehadiran hari ini sudah lengkap. Terima kasih."),
  ).toBeVisible();

  await page.goto("/absensi/riwayat");
  await expect(
    page.getByRole("heading", { name: "Riwayat absensi saya" }),
  ).toBeVisible();
  await expect(page.getByText("Hadir").first()).toBeVisible();
});

test("operator records leave and requests a correction", async ({ page }) => {
  await login(page, "operator");
  await page.goto("/absensi/pegawai");
  await expect(
    page.getByRole("heading", { name: "Absensi & izin pegawai" }),
  ).toBeVisible();
  await expect(page.getByText("Guru Ahmad").first()).toBeVisible();

  await page.getByRole("button", { name: "Catat status pegawai" }).click();
  const dialog = page.getByRole("dialog");
  await dialog.getByLabel("Pegawai", { exact: true }).click();
  await page.getByRole("option", { name: "Guru Siti" }).click();
  await dialog.getByLabel("Status kehadiran", { exact: true }).click();
  await page.getByRole("option", { name: "Izin" }).click();
  await dialog
    .getByLabel("Alasan / dasar perubahan")
    .fill("Pencatatan izin acara keluarga melalui petugas");
  await dialog.getByRole("button", { name: "Simpan", exact: true }).click();
  await expect(page.getByText("Perubahan berhasil disimpan.")).toBeVisible();
  await page
    .locator("[data-sonner-toast]")
    .filter({ hasText: "Perubahan berhasil disimpan." })
    .getByRole("button", { name: "Tutup notifikasi" })
    .click();
  await expect(page.getByText("Perubahan berhasil disimpan.")).toHaveCount(0);

  // Koreksi status melalui dialog ajukan koreksi.
  const sitiRow = page.getByRole("row", { name: /Guru Siti/ }).first();
  await sitiRow.getByRole("button", { name: "Ajukan koreksi" }).click();
  await dialog.getByLabel("Status baru").click();
  await page.getByRole("option", { name: "Dinas luar" }).click();
  await dialog
    .getByLabel("Alasan / dasar perubahan")
    .fill("Ternyata guru mengikuti dinas luar daerah hari itu");
  await dialog.getByRole("button", { name: "Simpan", exact: true }).click();
  await expect(page.getByText("Perubahan berhasil disimpan.")).toBeVisible();

  await page.goto("/absensi/koreksi");
  await expect(
    page.getByRole("row", { name: /Guru Siti/ }).first(),
  ).toBeVisible();
  await expect(page.getByText("Menunggu").first()).toBeVisible();
  await expect(page.getByText("Dinas luar").first()).toBeVisible();
  // Operator tanpa mandat tidak melihat tombol keputusan.
  await expect(page.getByRole("button", { name: "Setujui" })).toHaveCount(0);
});

test("head of school reads the recap without management pages", async ({
  page,
}) => {
  await login(page, "kepala_madrasah");
  await page.goto("/absensi/rekap");
  await expect(
    page.getByRole("heading", { name: "Rekap kehadiran pegawai" }),
  ).toBeVisible();
  await expect(page.getByText("Guru Ahmad").first()).toBeVisible();

  await page.goto("/absensi/pegawai");
  await expect(
    page.getByRole("heading", { name: "Ruang kerja tidak tersedia" }),
  ).toBeVisible();
});

test("guardian without employee link cannot open self-service pages", async ({
  page,
}) => {
  await login(page, "orang_tua_wali");
  await page.goto("/absensi/masuk-pulang");
  await expect(
    page.getByRole("heading", { name: "Ruang kerja tidak tersedia" }),
  ).toBeVisible();
});
