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

test("daily attendance keeps the latest filter when an earlier request finishes late", async ({
  page,
}) => {
  await login(page, "wali_kelas");
  const initialResponse = page.waitForResponse(
    (response) =>
      response.url().includes("/api/student-attendance/roster?") &&
      response.status() === 200,
  );
  await page.goto("/absensi-siswa/harian");
  const initial = await (await initialResponse).json();
  await expect(page.getByText("Siswa Mutasi Satu").first()).toBeVisible();
  let release: () => void = () => {};
  let started: () => void = () => {};
  const delayed = new Promise<void>((resolve) => {
    release = resolve;
  });
  const requested = new Promise<void>((resolve) => {
    started = resolve;
  });
  await page.route("**/api/student-attendance/roster?**", async (route) => {
    const date = new URL(route.request().url()).searchParams.get("date");
    const body = structuredClone(initial);
    body.data.date = date;
    body.data.rows[0].student.person.full_name =
      date === "2026-10-24" ? "Roster lama" : "Roster terbaru";
    if (date === "2026-10-24") {
      started();
      await delayed;
    }
    await route.fulfill({ json: body });
  });
  await page.getByLabel("Tanggal", { exact: true }).fill("2026-10-24");
  await requested;
  await page.getByLabel("Tanggal", { exact: true }).fill("2026-10-25");
  await expect(page.getByText("Roster terbaru", { exact: true })).toBeVisible();
  const oldResponse = page.waitForResponse((response) =>
    response.url().includes("date=2026-10-24"),
  );
  release();
  await (await oldResponse).finished();
  await page.evaluate(
    () =>
      new Promise<void>((resolve) =>
        requestAnimationFrame(() => requestAnimationFrame(() => resolve())),
      ),
  );
  await expect(page.getByText("Roster terbaru", { exact: true })).toBeVisible();
  await expect(page.getByText("Roster lama", { exact: true })).toHaveCount(0);
  await expect(page.getByLabel("Tanggal", { exact: true })).toHaveValue(
    "2026-10-25",
  );
});

test("class loading and errors do not claim that the teacher has no classes", async ({
  page,
}) => {
  await login(page, "wali_kelas");
  let release: () => void = () => {};
  const pending = new Promise<void>((resolve) => {
    release = resolve;
  });
  await page.route("**/api/student-attendance/classes", async (route) => {
    await pending;
    await route.fulfill({
      status: 503,
      json: { message: "Daftar kelas sementara tidak tersedia." },
    });
  });
  await page.goto("/absensi-siswa/harian");
  await expect(
    page.getByLabel("Memuat data", { exact: true }).first(),
  ).toBeVisible();
  await expect(page.getByText(/Belum ada kelas penugasan Anda/)).toHaveCount(0);
  release();
  await expect(page.getByRole("main").getByRole("alert")).toContainText(
    "Daftar kelas sementara tidak tersedia.",
  );
  await expect(page.getByText(/Belum ada kelas penugasan Anda/)).toHaveCount(0);
});
