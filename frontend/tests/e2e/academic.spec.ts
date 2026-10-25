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

async function openAcademicMenu(page: Page) {
  await page
    .getByRole("button", { name: "Akademik & kelas", exact: true })
    .click();
}

test.beforeEach(async ({ request, page }) => {
  await request.post("http://127.0.0.1:43990/__test/reset");
  await page.emulateMedia({ reducedMotion: "reduce" });
});

test("operator manages periods, classes, and homeroom through the academic module", async ({
  page,
}) => {
  await login(page, "operator");
  await openAcademicMenu(page);
  await page.getByRole("link", { name: "Tahun ajaran & semester" }).click();
  await expect(page).toHaveURL(/\/akademik\/tahun-ajaran$/);
  await expect(
    page.getByRole("heading", { name: "Tahun ajaran & semester" }),
  ).toBeVisible();
  await expect(page.getByText("2026/2027").first()).toBeVisible();
  await expect(page.getByText("Periode aktif").first()).toBeVisible();

  await page.getByRole("button", { name: "Tambah tahun ajaran" }).click();
  await expect(page.getByRole("dialog")).toContainText("Tambah tahun ajaran");
  await page.keyboard.press("Escape");

  await page.getByRole("link", { name: "Rombongan belajar" }).click();
  await expect(page).toHaveURL(/\/akademik\/rombel$/);
  await expect(page.getByText("Kelas 7A").first()).toBeVisible();
  await expect(page.getByText("VII-A 2026/2027").first()).toBeVisible();

  await page.getByRole("link", { name: "Penugasan mengajar" }).click();
  await expect(page).toHaveURL(/\/akademik\/penugasan$/);
  await expect(page.getByText("Guru Ahmad").first()).toBeVisible();
  await expect(
    page.getByRole("button", { name: "Tambah penugasan" }),
  ).toBeVisible();
});

test("transitions preview and confirm move students with a recorded reason", async ({
  page,
}) => {
  await login(page, "operator");
  await page.goto("/akademik/mutasi");
  await expect(
    page.getByRole("heading", { name: "Kenaikan & mutasi siswa" }),
  ).toBeVisible();

  await page.getByRole("combobox", { name: "Rombel asal" }).click();
  await page.getByRole("option", { name: "VII-A 2026/2027" }).click();
  await expect(page.getByText("Siswa Mutasi Satu")).toBeVisible();

  await page.getByRole("checkbox", { name: "Pilih Siswa Mutasi Satu" }).check();
  await page.getByRole("combobox", { name: "Rombel tujuan" }).click();
  await page.getByRole("option", { name: "VII-B 2026/2027" }).click();
  await page.getByRole("button", { name: /Pratinjau 1 siswa/ }).click();

  await expect(
    page.getByRole("heading", { name: "Pratinjau mutasi" }),
  ).toBeVisible();
  await expect(page.getByText("Siap dikonfirmasi")).toBeVisible();
  await page.getByRole("button", { name: "Konfirmasi mutasi 1 siswa" }).click();
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Kenaikan kelas reguler semester genap 2026/2027");
  await page.getByRole("button", { name: "Proses mutasi" }).click();
  await expect(page.getByText("Mutasi siswa berhasil diproses.")).toBeVisible();
});

test("teacher reads the academic module without manage actions and stays scoped", async ({
  page,
}) => {
  await login(page, "guru");
  await openAcademicMenu(page);
  await page.getByRole("link", { name: "Tahun ajaran & semester" }).click();
  await expect(
    page.getByRole("button", { name: "Tambah tahun ajaran" }),
  ).toHaveCount(0);
  await expect(page.getByText("2026/2027").first()).toBeVisible();

  await page.goto("/akademik/rombel");
  await expect(page.getByText("VII-A 2026/2027").first()).toBeVisible();
  await expect(page.getByText("VII-B 2026/2027")).toHaveCount(0);
  await expect(page.getByRole("button", { name: "Tambah rombel" })).toHaveCount(
    0,
  );

  await page.goto("/akademik/penugasan");
  await expect(page.getByText("Guru Ahmad").first()).toBeVisible();
  await expect(
    page.getByRole("button", { name: "Tambah penugasan" }),
  ).toHaveCount(0);
});

test("pages outside permission render not found instead of leaking access", async ({
  page,
}) => {
  await login(page, "guru");
  await page.goto("/akademik/tidak-ada");
  await expect(page.getByText(/404|tidak ditemukan/i).first()).toBeVisible();
});
