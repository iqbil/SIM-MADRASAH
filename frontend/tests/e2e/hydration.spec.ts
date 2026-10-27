import { expect, test } from "@playwright/test";

// A fresh Playwright context has no browser extensions. Keep this separate
// from a user's browser profile when diagnosing externally modified HTML.
test.use({ locale: "en-US", timezoneId: "UTC", colorScheme: "dark" });

const scenarios = [
  { name: "normal mobile login", url: "/login", width: 390 },
  {
    name: "Google callback error on narrow mobile",
    url: "/login?error=google-failed",
    width: 320,
    message: "Login Google belum berhasil.",
  },
  {
    name: "Google callback error on tablet",
    url: "/login?error=google-failed",
    width: 768,
    message: "Login Google belum berhasil.",
  },
  {
    name: "password change notice on desktop",
    url: "/login?notice=password-updated",
    width: 1440,
    message: "Password berhasil diperbarui.",
  },
];

for (const scenario of scenarios) {
  test(`${scenario.name} hydrates and remains interactive`, async ({
    page,
    request,
  }) => {
    await request.post("http://127.0.0.1:43990/__test/reset");
    await page.setViewportSize({ width: scenario.width, height: 900 });
    const errors: string[] = [];
    page.on("pageerror", (error) => errors.push(error.message));
    page.on("console", (message) => {
      if (
        message.type() === "error" &&
        /hydration|hydrated|server.rendered HTML|Minified React error #(418|425)/i.test(
          message.text(),
        )
      )
        errors.push(message.text());
    });

    const response = await page.goto(scenario.url);
    expect(response?.ok()).toBe(true);
    const html = await response?.text();
    expect(html).not.toMatch(/bis_skin_checked|bis_register|__processed_/);
    if (scenario.message) expect(html).toContain(scenario.message);

    // Options are fetched in useEffect; this waits for actual hydration rather
    // than treating the visibility of server HTML as proof of interactivity.
    await expect(
      page.getByRole("button", { name: "Akun Google" }),
    ).toBeEnabled();
    if (scenario.message)
      await expect(
        page.locator('[data-slot="alert-description"]'),
      ).toContainText(scenario.message);
    await page.getByRole("button", { name: "Tampilkan password" }).click();
    await expect(page.getByLabel("Password", { exact: true })).toHaveAttribute(
      "type",
      "text",
    );
    await page.getByRole("button", { name: "Lupa password?" }).click();
    await expect(
      page.getByRole("dialog", { name: "Bantuan akses akun" }),
    ).toBeVisible();
    await page.keyboard.press("Escape");
    await expect(page.getByRole("dialog")).not.toBeVisible();
    await page.reload();
    await expect(
      page.getByRole("button", { name: "Akun Google" }),
    ).toBeEnabled();
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
    ).toBe(true);
    expect(
      await page
        .locator("html")
        .evaluate((element) => getComputedStyle(element).colorScheme),
    ).toBe("light");
    expect(errors).toEqual([]);
  });
}

for (const width of [390, 1440]) {
  test(`portal shell and attendance policy hydrate without injected attributes at ${width}px`, async ({
    page,
    request,
  }) => {
    await request.post("http://127.0.0.1:43990/__test/reset");
    await page.setViewportSize({ width, height: 900 });
    const errors: string[] = [];
    page.on("pageerror", (error) => errors.push(error.message));
    page.on("console", (message) => {
      if (
        message.type() === "error" &&
        /hydration|hydrated|server.rendered HTML|Minified React error #(418|425)/i.test(
          message.text(),
        )
      )
        errors.push(message.text());
    });
    await page.goto("/login");
    await page.getByLabel("Username atau email").fill("operator");
    await page.getByLabel("Password", { exact: true }).fill("Password12345");
    await page.getByRole("button", { name: "Masuk ke portal" }).click();
    await expect(page).toHaveURL(/\/dashboard\/operator$/);

    for (const path of [
      "/dashboard/operator",
      "/absensi/pola-kerja",
      "/akun",
    ]) {
      const response = await page.goto(path);
      expect(response?.ok()).toBe(true);
      expect(await response?.text()).not.toMatch(
        /bis_skin_checked|bis_register|__processed_/,
      );
      const toggle = page.getByRole("button", {
        name: "Buka atau tutup menu",
        exact: true,
      });
      await expect(toggle).toBeEnabled();
      await toggle.click();
      if (width < 1024) {
        const drawer = page.getByRole("dialog", { name: "Menu navigasi" });
        await expect(drawer).toBeVisible();
        await drawer
          .getByRole("button", { name: "Tutup menu", exact: true })
          .click();
      } else {
        await expect(toggle).toHaveAttribute("aria-expanded", "false");
        await toggle.click();
      }
      if (path === "/absensi/pola-kerja") {
        await expect(
          page
            .getByRole("navigation", { name: "Navigasi portal" })
            .getByText("Pola kerja & kebijakan", { exact: true }),
        ).toBeVisible();
        await page.getByRole("tab", { name: "Kebijakan foto/GPS" }).click();
        await expect(
          page.getByText("Tanpa titik pusat atau radius"),
        ).toBeVisible();
      }
      expect(
        await page.evaluate(() =>
          [...document.querySelectorAll("*")].some((element) =>
            element
              .getAttributeNames()
              .some(
                (name) =>
                  name.startsWith("bis_") || name.startsWith("__processed_"),
              ),
          ),
        ),
      ).toBe(false);
    }
    expect(errors).toEqual([]);
  });
}
