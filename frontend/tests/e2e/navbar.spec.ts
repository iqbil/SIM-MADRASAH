import { expect, type Locator, type Page, test } from "@playwright/test";

async function login(page: Page, username = "tenaga_kependidikan") {
  await page.goto("/login");
  await page.getByLabel("Username atau email").fill(username);
  await page.getByLabel("Password", { exact: true }).fill("Password12345");
  await page.getByRole("button", { name: "Masuk ke portal" }).click();
  await expect(page).toHaveURL(/\/dashboard\/tenaga_kependidikan$/);
  await expect(
    page.getByRole("button", { name: "Menu akun", exact: true }),
  ).toBeEnabled();
}

async function fitsScreen(page: Page, element: Locator) {
  await expect
    .poll(() =>
      page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
    )
    .toBe(true);
  const box = await element.boundingBox();
  expect(box).not.toBeNull();
  expect(box?.x).toBeGreaterThanOrEqual(-1);
  expect((box?.x ?? 0) + (box?.width ?? 0)).toBeLessThanOrEqual(
    (page.viewportSize()?.width ?? 0) + 1,
  );
}

test.beforeEach(async ({ request, page }) => {
  await request.post("http://127.0.0.1:43990/__test/reset");
  await page.emulateMedia({ reducedMotion: "reduce" });
});

test("navbar controls fit every breakpoint and expose the correct menu state", async ({
  page,
}) => {
  await login(page);
  const nav = page.getByRole("navigation", {
    name: "Navigasi portal",
    includeHidden: true,
  });
  for (const width of [320, 390, 640, 768, 1024, 1440, 1920]) {
    await page.setViewportSize({ width, height: 900 });
    await expect(
      nav.getByText("MTs Al Karimiyyah", { exact: true }),
    ).toBeVisible();
    await fitsScreen(page, nav);
    for (const name of ["Menu akun", "Buka atau tutup menu"]) {
      const control = nav.getByRole("button", { name, exact: true });
      const box = await control.boundingBox();
      expect(box?.width).toBeGreaterThanOrEqual(44);
      expect(box?.height).toBeGreaterThanOrEqual(44);
    }
    const trigger = nav.getByRole("button", {
      name: "Buka atau tutup menu",
      exact: true,
      includeHidden: true,
    });
    await expect(trigger).toHaveAttribute(
      "aria-expanded",
      width < 1024 ? "false" : "true",
    );
    const original = await trigger.getAttribute("aria-expanded");
    await trigger.click();
    await expect(trigger).toHaveAttribute(
      "aria-expanded",
      original === "true" ? "false" : "true",
    );
    if (width < 1024) {
      const drawer = page.getByRole("dialog", { name: "Menu navigasi" });
      await expect(drawer).toBeVisible();
      await drawer
        .getByRole("button", { name: "Tutup menu", exact: true })
        .click();
      await expect(drawer).not.toBeVisible();
    } else {
      await trigger.click();
    }
    if (width >= 768) {
      const text = nav.getByText("Tenaga Kependidikan", { exact: true });
      await expect(text).toBeVisible();
      expect(
        await text.evaluate((element) => {
          const label = element.getBoundingClientRect();
          const badge = element.parentElement?.getBoundingClientRect();
          return (
            !!badge && label.top >= badge.top && label.bottom <= badge.bottom
          );
        }),
      ).toBe(true);
    }
    await nav.screenshot({
      path: `test-results/screenshots/navbar-${width}.png`,
    });
    await nav.getByRole("button", { name: "Menu akun", exact: true }).click();
    const menu = page.getByRole("menu");
    await expect(
      menu.getByRole("menuitem", { name: "Akun saya", exact: true }),
    ).toBeVisible();
    await fitsScreen(page, menu);
    await page.keyboard.press("Escape");
    await expect(menu).not.toBeVisible();
    await expect(
      nav.getByRole("button", { name: "Menu akun", exact: true }),
    ).toBeFocused();
  }
});

test("navbar updates its page context and stays reachable while scrolling", async ({
  page,
}) => {
  await page.setViewportSize({ width: 320, height: 568 });
  await login(page);
  const nav = page.getByRole("navigation", {
    name: "Navigasi portal",
    includeHidden: true,
  });
  await nav.getByRole("button", { name: "Menu akun", exact: true }).click();
  await page.getByRole("menuitem", { name: "Akun saya", exact: true }).click();
  await expect(page).toHaveURL(/\/akun$/);
  await expect(nav.getByText("Akun saya", { exact: true })).toBeVisible();
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
  await expect.poll(async () => (await nav.boundingBox())?.y).toBe(0);
  await nav.getByRole("button", { name: "Menu akun", exact: true }).click();
  await expect(
    page.getByRole("menuitem", { name: "Ganti password", exact: true }),
  ).toBeVisible();
});

test("long names remain readable in the account menu without stretching the navbar", async ({
  page,
}) => {
  await login(page, "nama_panjang");
  for (const width of [320, 1024, 1920]) {
    await page.setViewportSize({ width, height: 900 });
    const nav = page.getByRole("navigation", {
      name: "Navigasi portal",
      includeHidden: true,
    });
    await fitsScreen(page, nav);
    await nav.getByRole("button", { name: "Menu akun", exact: true }).click();
    const menu = page.getByRole("menu");
    await fitsScreen(page, menu);
    const name = menu.getByText("NamaPengguna".repeat(12), { exact: true });
    await expect(name).toBeVisible();
    expect(
      await name.evaluate(
        (element) => element.scrollWidth <= element.clientWidth,
      ),
    ).toBe(true);
    await page.screenshot({
      path: `test-results/screenshots/account-menu-${width}.png`,
      fullPage: false,
    });
    await page.keyboard.press("Escape");
  }
});
