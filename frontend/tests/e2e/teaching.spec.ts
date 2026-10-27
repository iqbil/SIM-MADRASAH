import { expect, type Page, test } from "@playwright/test";

async function login(page: Page, username: string, landing = username) {
  await page.context().clearCookies();
  await page.goto("/login");
  await page.getByLabel("Username atau email").fill(username);
  await page.getByLabel("Password", { exact: true }).fill("Password12345");
  await page
    .getByRole("button", { name: "Masuk ke portal", exact: true })
    .click();
  await expect(page).toHaveURL(new RegExp(`/dashboard/${landing}$`));
}

test.beforeEach(async ({ request, page }) => {
  await request.post("http://127.0.0.1:43990/__test/reset");
  await page.emulateMedia({ reducedMotion: "reduce" });
});

test("teacher records, revises nothing, and submits a realization", async ({
  page,
}) => {
  await login(page, "guru");
  await page.goto("/realisasi/pertemuan");
  await expect(
    page.getByRole("heading", { name: "Pertemuan mengajar saya" }),
  ).toBeVisible();
  await expect(page.getByText("Matematika").first()).toBeVisible();
  await expect(page.getByText("Belum dicatat").first()).toBeVisible();

  const row = page.getByRole("row", { name: /Matematika/ }).first();
  await row.getByRole("button", { name: "Catat realisasi" }).click();
  const dialog = page.getByRole("dialog");
  await dialog.getByLabel("Pelaksanaan").click();
  await page.getByRole("option", { name: "Terlaksana", exact: true }).click();
  await dialog.getByLabel("Materi").fill("Aljabar dasar dan latihan soal");
  await dialog.getByLabel("JP aktual").fill("2");
  await dialog
    .getByLabel("Alasan / dasar perubahan")
    .fill("Pencatatan realisasi pertemuan hari ini");
  await dialog.getByRole("button", { name: "Simpan", exact: true }).click();
  await expect(page.getByText("Perubahan berhasil disimpan.")).toBeVisible();
  await expect(page.getByText("Draf", { exact: true }).first()).toBeVisible();

  await row.getByRole("button", { name: "Kirim", exact: true }).click();
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Mengajukan realisasi untuk diverifikasi petugas");
  await page.getByRole("button", { name: "Kirim realisasi" }).click();
  await expect(page.getByText("Menunggu verifikasi").first()).toBeVisible();

  await page.goto("/realisasi/riwayat");
  await expect(
    page.getByRole("heading", { name: "Realisasi & riwayat JP saya" }),
  ).toBeVisible();
  await expect(page.getByText("Menunggu verifikasi").first()).toBeVisible();
  await expect(
    page.getByRole("row", { name: /VII-A 2026\/2027/ }).first(),
  ).toBeVisible();
});

test("mandated verifier approves the pending realization", async ({ page }) => {
  await login(page, "guru");
  await page.goto("/realisasi/pertemuan");
  const row = page.getByRole("row", { name: /Matematika/ }).first();
  await row.getByRole("button", { name: "Catat realisasi" }).click();
  const dialog = page.getByRole("dialog");
  await dialog.getByLabel("Pelaksanaan").click();
  await page.getByRole("option", { name: "Terlaksana", exact: true }).click();
  await dialog.getByLabel("Materi").fill("Geometri dasar");
  await dialog.getByLabel("JP aktual").fill("2");
  await dialog
    .getByLabel("Alasan / dasar perubahan")
    .fill("Realisasi pertemuan kedua hari ini");
  await dialog.getByRole("button", { name: "Simpan", exact: true }).click();
  await row.getByRole("button", { name: "Kirim", exact: true }).click();
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Pengajuan verifikasi realisasi");
  await page.getByRole("button", { name: "Kirim realisasi" }).click();
  await expect(page.getByText("Menunggu verifikasi").first()).toBeVisible();

  await login(page, "verifikator", "operator");
  await page.goto("/realisasi/verifikasi");
  await expect(
    page.getByRole("heading", { name: "Verifikasi realisasi" }),
  ).toBeVisible();
  await expect(page.getByText("Guru Ahmad").first()).toBeVisible();
  await page.getByRole("button", { name: "Setujui" }).first().click();
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Materi dan JP sesuai jadwal; disetujui");
  await page.getByRole("button", { name: "Setujui realisasi" }).click();
  await expect(page.getByText("Perubahan berhasil disimpan.")).toBeVisible();

  await login(page, "guru");
  await page.goto("/realisasi/riwayat");
  await expect(page.getByText("Disetujui").first()).toBeVisible();
  await expect(page.getByText("2", { exact: true }).first()).toBeVisible();
  await page.getByLabel("Dari tanggal", { exact: true }).fill("2027-01-01");
  await page.getByLabel("Sampai", { exact: true }).fill("2027-01-31");
  await expect(
    page.getByText("Belum ada realisasi pada filter ini"),
  ).toBeVisible();
  await expect(page.getByText("0", { exact: true }).first()).toBeVisible();
});

test("pages follow navigation tokens", async ({ page }) => {
  await login(page, "operator");
  await page.goto("/realisasi/pertemuan");
  await expect(
    page.getByRole("heading", { name: "Ruang kerja tidak tersedia" }),
  ).toBeVisible();

  await login(page, "verifikator", "operator");
  await page.goto("/realisasi/verifikasi");
  await expect(
    page.getByRole("heading", { name: "Verifikasi realisasi" }),
  ).toBeVisible();
  await page.goto("/realisasi/pertemuan");
  await expect(
    page.getByRole("heading", { name: "Ruang kerja tidak tersedia" }),
  ).toBeVisible();
});
