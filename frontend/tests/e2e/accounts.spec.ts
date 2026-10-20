import { expect, type Page, test } from "@playwright/test";

async function admin(page: Page) {
  await page.goto("/login");
  await page.getByLabel("Username atau email").fill("super_admin");
  await page.getByLabel("Password", { exact: true }).fill("Password12345");
  await page.getByRole("button", { name: "Masuk ke portal" }).click();
  await expect(page).toHaveURL(/\/dashboard\/super_admin$/);
  await page.goto("/pengguna");
  await expect(
    page.getByRole("heading", { name: "Pengelolaan akun", exact: true }),
  ).toBeVisible();
  await expect(
    page.getByRole("button", { name: "Kelola Pengelola Utama", exact: true }),
  ).toBeVisible();
}

test.beforeEach(async ({ request, page }) => {
  await page.emulateMedia({ reducedMotion: "reduce" });
  await request.post("http://127.0.0.1:43990/__test/reset");
});

test("admin creates and edits a user, verifies email, resets password and disables with an audit trail", async ({
  page,
}) => {
  await admin(page);
  await page
    .getByRole("button", { name: "Tambah pengguna", exact: true })
    .click();
  await page.getByLabel("Nama tampilan", { exact: true }).fill("Guru Baru");
  await page.getByLabel("Username", { exact: true }).fill("guru.baru");
  await page
    .getByLabel("Email (opsional)", { exact: true })
    .fill("guru.baru@madrasah.test");
  await page.getByRole("checkbox", { name: "Guru", exact: true }).check();
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Pembuatan akun guru berdasarkan data terverifikasi");
  await page.getByRole("button", { name: "Buat akun", exact: true }).click();
  await expect(
    page.getByRole("dialog", { name: "Password sementara siap" }),
  ).toBeVisible();
  await expect(page.getByRole("dialog")).toContainText(
    "Aa1!TemporaryPassword123",
  );
  await page.getByRole("button", { name: "Sudah saya simpan" }).click();
  await page
    .getByRole("button", { name: "Kelola Guru Baru", exact: true })
    .click();
  await page.getByRole("button", { name: "Ubah akun", exact: true }).click();
  await page
    .getByLabel("Nama tampilan", { exact: true })
    .fill("Guru Diperbarui");
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Perbaikan nama tampilan sesuai identitas pengguna");
  await page
    .getByRole("button", { name: "Simpan perubahan", exact: true })
    .click();
  await page
    .getByRole("button", { name: "Kelola Guru Diperbarui", exact: true })
    .click();
  await page
    .getByRole("button", { name: "Verifikasi email", exact: true })
    .click();
  await page.getByRole("checkbox").check();
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Bukti kepemilikan email telah diperiksa langsung");
  await page.getByRole("button", { name: "Konfirmasi tindakan" }).click();
  await page
    .getByRole("button", { name: "Kelola Guru Diperbarui", exact: true })
    .click();
  await page
    .getByRole("button", { name: "Reset password", exact: true })
    .click();
  await expect(
    page.getByRole("button", { name: "Konfirmasi tindakan" }),
  ).toBeDisabled();
  await page.getByRole("checkbox").check();
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Reset melalui pemeriksaan identitas langsung");
  await page.getByRole("button", { name: "Konfirmasi tindakan" }).click();
  await expect(
    page.getByRole("dialog", { name: "Password sementara siap" }),
  ).toContainText("Aa1!ResetPassword123");
  await page.getByRole("button", { name: "Sudah saya simpan" }).click();
  await page
    .getByRole("button", { name: "Kelola Guru Diperbarui", exact: true })
    .click();
  await page
    .getByRole("button", { name: "Nonaktifkan akun", exact: true })
    .click();
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Akun dinonaktifkan karena masa tugas berakhir");
  await page.getByRole("button", { name: "Konfirmasi tindakan" }).click();
  await expect(
    page
      .getByRole("row")
      .filter({ hasText: "Guru Diperbarui" })
      .getByText("Nonaktif", { exact: true }),
  ).toBeVisible();
  await page.getByRole("tab", { name: "Audit", exact: true }).click();
  await expect(
    page.getByText("accounts.user_created", { exact: true }),
  ).toBeVisible();
  await expect(
    page.getByText("accounts.status", { exact: true }),
  ).toBeVisible();
});

test("role and permission settings work while Super Admin remains protected", async ({
  page,
}) => {
  await admin(page);
  await page.getByRole("tab", { name: "Role & izin", exact: true }).click();
  await expect(
    page.getByText(
      "Pengelola utama akun dan akses madrasah. Role ini dilindungi.",
    ),
  ).toBeVisible();
  await page.getByRole("button", { name: "Tambah role", exact: true }).click();
  await page.getByLabel("Kode role", { exact: true }).fill("petugas_baru");
  await page.getByLabel("Nama role", { exact: true }).fill("Petugas Baru");
  await page.getByRole("checkbox", { name: "Izin view", exact: true }).check();
  await page
    .getByLabel("Alasan / dasar perubahan")
    .fill("Penetapan role petugas pengelolaan akun");
  await page.getByRole("button", { name: "Simpan role", exact: true }).click();
  await expect(page.getByText("Petugas Baru", { exact: true })).toBeVisible();
});

test("account management remains usable on narrow mobile screens", async ({
  page,
}) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await admin(page);
  await page.screenshot({
    path: "test-results/screenshots/accounts-mobile.png",
    fullPage: true,
    animations: "disabled",
  });
  await page
    .getByRole("button", { name: "Tambah pengguna", exact: true })
    .click();
  await expect(
    page.getByRole("dialog", { name: "Tambah pengguna" }),
  ).toBeVisible();
  await expect
    .poll(() =>
      page.evaluate(
        () => document.documentElement.scrollWidth <= window.innerWidth,
      ),
    )
    .toBe(true);
  await page.screenshot({
    path: "test-results/screenshots/user-editor-mobile.png",
    fullPage: true,
    animations: "disabled",
  });
  await page.getByRole("button", { name: "Batal", exact: true }).click();
  await page.setViewportSize({ width: 320, height: 740 });
  await expect
    .poll(() =>
      page.evaluate(
        () => document.documentElement.scrollWidth <= window.innerWidth,
      ),
    )
    .toBe(true);
});

test("ordinary users cannot open the account page or its BFF endpoints", async ({
  page,
  context,
}) => {
  await page.goto("/login");
  await page.getByLabel("Username atau email").fill("guru");
  await page.getByLabel("Password", { exact: true }).fill("Password12345");
  await page.getByRole("button", { name: "Masuk ke portal" }).click();
  await expect(page).toHaveURL(/\/dashboard\/guru$/);
  await page.goto("/pengguna");
  await expect(
    page.getByRole("heading", { name: "Ruang kerja tidak tersedia" }),
  ).toBeVisible();
  const response = await context.request.get("/api/accounts/users");
  expect(response.status()).toBe(403);
  const csrf = await context.request.post("/api/accounts/users", {
    headers: { Origin: "https://untrusted.example" },
    data: { name: "Injected" },
  });
  expect(csrf.status()).toBe(403);
});
