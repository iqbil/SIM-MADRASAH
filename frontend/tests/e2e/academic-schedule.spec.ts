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

test.beforeEach(async ({ request, page }) => {
  await request.post("http://127.0.0.1:43990/__test/reset");
  await page.emulateMedia({ reducedMotion: "reduce" });
});

test("operator drafts entries, publishes a version, and generates meetings", async ({
  page,
}) => {
  await login(page, "operator");
  await page.goto("/akademik/jadwal");
  await expect(
    page.getByRole("heading", { name: "Jadwal pelajaran" }),
  ).toBeVisible();
  await expect(page.getByRole("tab", { name: "Versi jadwal" })).toBeVisible();
  await expect(page.getByText("Versi 2").first()).toBeVisible();
  await expect(page.getByText("Digantikan").first()).toBeVisible();

  // Draf baru menyalin entri versi terbit.
  await page.getByRole("button", { name: "Draf versi baru" }).click();
  await page.getByLabel("Berlaku mulai").fill("2026-11-02");
  await page.getByLabel("Salin entri dari versi terbit saat ini").check();
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Penyesuaian jadwal setelah evaluasi tengah semester");
  await page.getByRole("button", { name: "Simpan", exact: true }).click();
  await expect(page.getByText("Versi 4").first()).toBeVisible();

  // Entri benturan pada draf ditolak.
  await page.getByRole("tab", { name: "Entri mingguan" }).click();
  await expect(page.getByText("Senin").first()).toBeVisible();
  await page.getByRole("button", { name: "Tambah entri" }).click();
  await page.getByLabel("Penugasan mengajar").click();
  await page.getByRole("option", { name: /Guru Ahmad — Matematika/ }).click();
  await page.getByLabel("Hari").click();
  await page.getByRole("option", { name: "Selasa" }).click();
  await page.getByLabel("Jam mulai").fill("07:30");
  await page.getByLabel("Jam selesai").fill("08:50");
  await page.getByLabel("JP terencana").fill("2");
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Menambah sesi latihan mandiri pekan kedua");
  await page.getByRole("button", { name: "Simpan", exact: true }).click();
  await expect(page.getByText("Selasa").first()).toBeVisible();

  // Terbitkan draf menutup versi terbit sebelumnya.
  await page.getByRole("tab", { name: "Versi jadwal" }).click();
  await page
    .getByRole("button", { name: "Terbitkan", exact: true })
    .first()
    .click();
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Penerbitan hasil evaluasi jadwal November");
  await page.getByRole("button", { name: "Terbitkan versi" }).click();
  await expect(page.getByText("Terbit", { exact: true }).first()).toBeVisible();

  // Generator pertemuan membuat baris pertemuan dari versi terbit.
  await page.getByRole("tab", { name: "Pertemuan" }).click();
  await page.getByRole("button", { name: "Buat pertemuan" }).click();
  const generateDialog = page.getByRole("dialog");
  await generateDialog.getByLabel("Dari tanggal").fill("2026-11-02");
  await generateDialog.getByLabel("Sampai tanggal").fill("2026-11-08");
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Pembuatan pertemuan pekan pertama November");
  await page.getByRole("button", { name: "Buat pertemuan" }).click();
  await expect(page.getByText("2026-11-02").first()).toBeVisible();
});

test("operator assigns and revokes a substitute teacher", async ({ page }) => {
  await login(page, "operator");
  await page.goto("/akademik/pengganti");
  await expect(
    page.getByRole("heading", { name: "Guru pengganti" }),
  ).toBeVisible();
  await expect(page.getByText("Guru Ahmad").first()).toBeVisible();

  await page.getByRole("button", { name: "Tunjuk pengganti" }).first().click();
  const assignDialog = page.getByRole("dialog");
  await assignDialog.getByLabel("Guru pengganti", { exact: true }).click();
  await page.getByRole("option", { name: "Guru Siti" }).click();
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Guru Ahmad mengikuti diklat daerah pada tanggal tersebut");
  await page.getByRole("button", { name: "Simpan", exact: true }).click();
  await expect(page.getByText("Digantikan").first()).toBeVisible();

  await page.getByRole("tab", { name: "Riwayat pengganti" }).click();
  await expect(page.getByText("Guru Siti").first()).toBeVisible();
  await page
    .getByRole("button", { name: "Cabut", exact: true })
    .first()
    .click();
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Guru Ahmad kembali tersedia dan akan mengajar sendiri");
  await page.getByRole("button", { name: "Cabut penunjukan" }).click();
  await page.getByRole("tab", { name: "Riwayat pengganti" }).click();
  await expect(page.getByText("Guru Siti").first()).toBeHidden();
  await page.getByRole("combobox", { name: "Status penunjukan" }).click();
  await page.getByRole("option", { name: "Semua" }).last().click();
  await expect(page.getByText(/Dicabut/).first()).toBeVisible();
});

test("guru reads schedules without management actions", async ({ page }) => {
  await login(page, "guru");
  await page.goto("/akademik/jadwal");
  await expect(
    page.getByRole("heading", { name: "Jadwal pelajaran" }),
  ).toBeVisible();
  await expect(page.getByText("Versi 2").first()).toBeVisible();
  await page.getByRole("tab", { name: "Entri mingguan" }).click();
  await expect(page.getByText("Guru Ahmad").first()).toBeVisible();
  await expect(
    page.getByRole("button", { name: "Draf versi baru" }),
  ).toHaveCount(0);
  await expect(
    page.getByRole("button", { name: "Buat pertemuan" }),
  ).toHaveCount(0);

  await page.goto("/akademik/pengganti");
  await expect(
    page.getByRole("heading", { name: "Guru pengganti" }),
  ).toBeVisible();
  await expect(
    page.getByRole("button", { name: "Tunjuk pengganti" }),
  ).toHaveCount(0);
});

test("schedule BFF rejects unknown academic paths and cross-origin writes", async ({
  request,
}) => {
  const foreign = await request.post("/api/academic/timetables/301/versions", {
    headers: { Origin: "https://evil.example" },
    data: { effective_from: "2026-11-02", reason: "Coba tulis lintas origin" },
  });
  expect(foreign.status()).toBe(403);
});
