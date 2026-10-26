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
