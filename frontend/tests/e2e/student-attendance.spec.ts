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

test("homeroom teacher fills daily attendance and sees recap", async ({
  page,
}) => {
  await login(page, "wali_kelas");
  await page.goto("/absensi-siswa/harian");
  await expect(
    page.getByRole("heading", { name: "Absensi harian siswa" }),
  ).toBeVisible();
  await expect(page.getByText("Belum dicatat").first()).toBeVisible();

  // Dua siswa: satu hadir (tombol cepat), satu sakit via pilihan status.
  const satuRow = page.getByRole("row", { name: /Siswa Mutasi Satu/ });
  await satuRow.getByRole("button", { name: "Hadir", exact: true }).click();
  const duaRow = page.getByRole("row", { name: /Siswa Mutasi Dua/ });
  await duaRow.getByRole("combobox").click();
  await page.getByRole("option", { name: "Sakit" }).click();
  await page.getByRole("button", { name: /Simpan 2 siswa/ }).click();
  await expect(page.getByText("2/3 terisi hari ini")).toBeVisible();

  // Siswa ketiga masih belum dicatat; rekap memisahkannya dari alpa.
  await page.goto("/absensi-siswa/rekap");
  await expect(
    page.getByRole("heading", { name: "Rekap kehadiran siswa" }),
  ).toBeVisible();
  await expect(page.getByText("Sakit").first()).toBeVisible();

  // Perwalian menampilkan roster + status hari ini.
  await page.goto("/akademik/perwalian");
  await expect(
    page.getByRole("heading", { name: "Kelas perwalian saya" }),
  ).toBeVisible();
  await expect(page.getByText(/2\/3 terisi hari ini/)).toBeVisible();
});

test("correction requested by teacher and approved by mandated officer", async ({
  page,
}) => {
  await login(page, "wali_kelas");
  await page.goto("/absensi-siswa/harian");
  const satuRow = page.getByRole("row", { name: /Siswa Mutasi Satu/ });
  await satuRow.getByRole("button", { name: "Hadir", exact: true }).click();
  await page.getByRole("button", { name: /Simpan 1 siswa/ }).click();
  await expect(page.getByText(/1\/3 terisi/)).toBeVisible();

  await satuRow.getByRole("button", { name: "Ajukan koreksi" }).click();
  const dialog = page.getByRole("dialog");
  await dialog.getByLabel("Status baru", { exact: true }).click();
  await page.getByRole("option", { name: "Izin" }).click();
  await dialog
    .getByLabel("Alasan / dasar perubahan")
    .fill("Orang tua menghubungi: siswa izin acara keluarga");
  await dialog.getByRole("button", { name: "Simpan", exact: true }).click();
  await expect(page.getByText("Perubahan berhasil disimpan.")).toBeVisible();

  await login(page, "mandat_absensi", "operator");
  await page.goto("/absensi/koreksi");
  await expect(
    page.getByRole("heading", { name: "Persetujuan koreksi absensi" }),
  ).toBeVisible();

  await page.getByRole("tab", { name: "Koreksi siswa", exact: true }).click();
  const correctionRow = page.getByRole("row", { name: /Siswa Mutasi Satu/ });
  await expect(correctionRow).toBeVisible();
  await correctionRow
    .getByRole("button", { name: "Setujui", exact: true })
    .click();
  const confirmDialog = page.getByRole("alertdialog");
  await confirmDialog
    .getByLabel("Alasan / dasar perubahan")
    .fill("Disetujui setelah konfirmasi dengan wali kelas");
  await confirmDialog
    .getByRole("button", { name: "Setujui koreksi", exact: true })
    .click();
  await expect(page.getByText("Perubahan berhasil disimpan.")).toBeVisible();
});

test("access guard rejects roles without duty", async ({ page }) => {
  await login(page, "operator");
  await page.goto("/absensi-siswa/harian");
  await expect(
    page.getByRole("heading", { name: "Ruang kerja tidak tersedia" }),
  ).toBeVisible();

  await login(page, "orang_tua_wali");
  await page.goto("/akademik/perwalian");
  await expect(
    page.getByRole("heading", { name: "Ruang kerja tidak tersedia" }),
  ).toBeVisible();
});
