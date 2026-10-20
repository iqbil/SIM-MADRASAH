import { expect, type Locator, type Page, test } from "@playwright/test";

async function signIn(page: Page, username = "super_admin") {
  await page.goto("/login");
  await page.getByLabel("Username atau email").fill(username);
  await page.getByLabel("Password", { exact: true }).fill("Password12345");
  await page.getByRole("button", { name: "Masuk ke portal" }).click();
  await expect(page).toHaveURL(new RegExp(`/dashboard/${username}$`));
  await expect(
    page.getByRole("heading", { name: "Ruang kerja Anda sudah siap" }),
  ).toBeVisible();
}

async function fitsViewport(page: Page, content?: Locator) {
  await expect
    .poll(() =>
      page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
    )
    .toBe(true);
  if (content) {
    const box = await content.boundingBox();
    const viewport = page.viewportSize();
    expect(box).not.toBeNull();
    expect(box?.x).toBeGreaterThanOrEqual(-1);
    expect(box?.y).toBeGreaterThanOrEqual(-1);
    expect((box?.x ?? 0) + (box?.width ?? 0)).toBeLessThanOrEqual(
      (viewport?.width ?? 0) + 1,
    );
    expect((box?.y ?? 0) + (box?.height ?? 0)).toBeLessThanOrEqual(
      (viewport?.height ?? 0) + 1,
    );
  }
}

test.beforeEach(async ({ page, request }) => {
  await page.emulateMedia({ reducedMotion: "reduce" });
  await request.post("http://127.0.0.1:43990/__test/reset");
});

test("dashboard navigations render without prerender errors and fit mobile, tablet and desktop", async ({
  page,
}) => {
  const errors: string[] = [];
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("console", (message) => {
    if (
      message.type() === "error" &&
      /unstable value|prerender|hydration|uncached data|instant UI/i.test(
        message.text(),
      )
    )
      errors.push(message.text());
  });
  await page.goto("/dashboard/guru");
  await expect(page).toHaveURL(/\/login$/);
  await signIn(page);
  for (const width of [320, 390, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    await fitsViewport(page);
    await expect(page.getByRole("main")).toHaveCount(1);
    await page.screenshot({
      path: `test-results/screenshots/dashboard-${width}.png`,
      fullPage: true,
    });
    if (width < 1024) {
      await page
        .getByRole("button", { name: "Buka atau tutup menu", exact: true })
        .click();
      const drawer = page.getByRole("dialog", { name: "Menu navigasi" });
      await expect(drawer).toBeVisible();
      await fitsViewport(page, drawer);
      await drawer
        .getByRole("link", { name: "Akun saya", exact: true })
        .click();
      await expect(page).toHaveURL(/\/akun$/);
      await expect(drawer).not.toBeVisible();
      await page
        .getByRole("button", { name: "Buka atau tutup menu", exact: true })
        .click();
      await drawer.getByRole("link", { name: "Beranda", exact: true }).click();
    } else {
      await page
        .getByRole("link", { name: "Lihat akun saya", exact: true })
        .click();
      await expect(page).toHaveURL(/\/akun$/);
      await page.getByRole("link", { name: "Beranda", exact: true }).click();
    }
    await expect(
      page.getByRole("heading", { name: "Ruang kerja Anda sudah siap" }),
    ).toBeVisible();
    await page.reload();
    await expect(
      page.getByRole("heading", { name: "Ruang kerja Anda sudah siap" }),
    ).toBeVisible();
  }
  expect(errors).toEqual([]);
});

test("account forms keep their actions visible on small and short screens", async ({
  page,
}) => {
  await signIn(page);
  await page.goto("/pengguna");
  for (const viewport of [
    { width: 320, height: 568 },
    { width: 390, height: 844 },
    { width: 768, height: 600 },
    { width: 1024, height: 768 },
    { width: 1440, height: 900 },
    { width: 740, height: 360 },
  ]) {
    await page.setViewportSize(viewport);
    await page
      .getByRole("button", { name: "Tambah pengguna", exact: true })
      .click();
    const dialog = page.getByRole("dialog", { name: "Tambah pengguna" });
    await expect(dialog).toBeVisible();
    await fitsViewport(page, dialog);
    await expect(
      dialog.getByRole("button", { name: "Buat akun", exact: true }),
    ).toBeInViewport();
    await dialog
      .getByLabel("Nama tampilan", { exact: true })
      .fill("Pengguna layar kecil");
    await dialog
      .getByLabel("Alasan / dasar perubahan")
      .fill("Pemeriksaan tampilan formulir pada ukuran perangkat");
    await expect(
      dialog.getByRole("button", { name: "Batal", exact: true }),
    ).toBeInViewport();
    await page.screenshot({
      path: `test-results/screenshots/form-${viewport.width}-${viewport.height}.png`,
      fullPage: true,
    });
    await dialog.getByRole("button", { name: "Batal", exact: true }).click();
    await expect(dialog).not.toBeVisible();
  }
});

test("sensitive actions use one shadcn alert dialog and cancel without a mutation", async ({
  page,
  request,
}) => {
  await request.post("http://127.0.0.1:43990/__test/reset");
  await page.setViewportSize({ width: 320, height: 568 });
  await signIn(page);
  await page.goto("/pengguna");
  await page
    .getByRole("button", { name: "Kelola Pengelola Utama", exact: true })
    .click();
  const sheet = page.getByRole("dialog", { name: "Detail pengguna" });
  await sheet
    .getByRole("button", { name: "Cabut semua sesi", exact: true })
    .click();
  const confirmation = page.getByRole("alertdialog", {
    name: "Cabut semua sesi",
  });
  await expect(confirmation).toHaveAttribute(
    "data-slot",
    "alert-dialog-content",
  );
  await expect(sheet).not.toBeVisible();
  await fitsViewport(page, confirmation);
  await expect(
    confirmation.getByRole("button", { name: "Batal" }),
  ).toBeFocused();
  await confirmation.getByRole("button", { name: "Batal" }).click();
  await expect(confirmation).not.toBeVisible();
  await page.reload();
  await expect(
    page.getByRole("heading", { name: "Pengelolaan akun", exact: true }),
  ).toBeVisible();
});

test("pending forms stay open and shadcn notifications survive page navigation", async ({
  page,
}) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await signIn(page);
  await page.goto("/pengguna");
  await page.getByRole("tab", { name: "Role & izin", exact: true }).click();
  await page.getByRole("button", { name: "Tambah role", exact: true }).click();
  const dialog = page.getByRole("dialog", { name: "Tambah role" });
  await dialog
    .getByLabel("Kode role", { exact: true })
    .fill("petugas_responsif");
  await dialog
    .getByLabel("Nama role", { exact: true })
    .fill("Petugas Responsif");
  await dialog
    .getByLabel("Alasan / dasar perubahan")
    .fill("Pengaturan akses petugas madrasah yang disetujui");
  let release: () => void = () => {};
  const pending = new Promise<void>((resolve) => {
    release = resolve;
  });
  await page.route("**/api/accounts/roles", async (route) => {
    if (route.request().method() === "POST") await pending;
    await route.continue();
  });
  await dialog
    .getByRole("button", { name: "Simpan role", exact: true })
    .click();
  await expect(
    dialog.getByRole("button", { name: "Simpan role", exact: true }),
  ).toBeDisabled();
  await page.keyboard.press("Escape");
  await expect(dialog).toBeVisible();
  release();
  await expect(dialog).not.toBeVisible();
  const notification = page
    .locator("[data-sonner-toast]")
    .filter({ hasText: "Role dan izin berhasil disimpan." });
  await expect(notification).toBeVisible();
  await expect(page.locator("[data-sonner-toaster]")).toHaveCount(1);
  await fitsViewport(page, notification);
  await page.screenshot({
    path: "test-results/screenshots/notification-mobile.png",
    fullPage: true,
  });
  await page
    .getByRole("button", { name: "Buka atau tutup menu", exact: true })
    .click();
  await page
    .getByRole("dialog", { name: "Menu navigasi" })
    .getByRole("link", { name: "Akun saya", exact: true })
    .click();
  await expect(page).toHaveURL(/\/akun$/);
  await expect(notification).toBeVisible();
  await notification.getByRole("button", { name: "Tutup notifikasi" }).click();
  await expect(notification).not.toBeVisible();
});
