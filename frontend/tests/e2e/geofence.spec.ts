import { expect, type Page, test } from "@playwright/test";

const geofence = {
  geofence_enabled: true,
  geofence_name: "Madrasah uji",
  geofence_latitude: "-6.7000000",
  geofence_longitude: "108.5000000",
  geofence_radius_meters: 100,
};

async function login(page: Page, username = "operator") {
  await page.goto("/login");
  await page.getByLabel("Username atau email").fill(username);
  await page.getByLabel("Password", { exact: true }).fill("Password12345");
  await page.getByRole("button", { name: "Masuk ke portal" }).click();
  await expect(page).toHaveURL(new RegExp(`/dashboard/${username}$`));
}

test.beforeEach(async ({ page, request }) => {
  await request.post("http://127.0.0.1:43990/__test/reset");
  await page.emulateMedia({ reducedMotion: "reduce" });
  await page.route("https://tile.openstreetmap.org/**", (route) =>
    route.fulfill({
      contentType: "image/png",
      body: Buffer.from(
        "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lSsAAAAASUVORK5CYII=",
        "base64",
      ),
    }),
  );
});

test("policy editor follows institution access returned by the API", async ({
  page,
}) => {
  await page.route("**/api/attendance/policy", async (route) => {
    const response = await route.fetch();
    const json = await response.json();
    json.can_manage = false;
    json.scheduled = [];
    await route.fulfill({ response, json });
  });
  await login(page);
  await page.goto("/absensi/pola-kerja");
  await page.getByRole("tab", { name: "Kebijakan foto/GPS" }).click();
  await expect(
    page.getByRole("heading", { name: "Kebijakan berlaku (versi 1)" }),
  ).toBeVisible();
  await expect(
    page.getByRole("button", { name: "Versi kebijakan baru" }),
  ).toHaveCount(0);
});

test("admin selects a point on the map, saves a scheduled policy and reopens it", async ({
  page,
}) => {
  await login(page);
  await page.goto("/absensi/pola-kerja");
  await page.getByRole("tab", { name: "Kebijakan foto/GPS" }).click();
  await page.getByRole("button", { name: "Versi kebijakan baru" }).click();
  const dialog = page.getByRole("dialog");
  await dialog.getByLabel("Batasi absensi dalam radius lokasi").check();
  await dialog.getByLabel("Nama lokasi absensi").fill("Madrasah uji");
  const map = dialog.getByRole("region", { name: "Peta titik absensi" });
  await expect(map).toHaveAttribute("data-ready", "true");
  await dialog.getByLabel("Latitude titik absensi").fill("-6.7000000");
  await dialog.getByLabel("Longitude titik absensi").fill("108.5000000");
  await expect(dialog.locator(".attendance-map-pin")).toBeVisible();
  await map.click({ position: { x: 120, y: 130 } });
  await expect(dialog.getByLabel("Latitude titik absensi")).not.toHaveValue(
    "-6.7000000",
  );
  const latitude = await dialog
    .getByLabel("Latitude titik absensi")
    .inputValue();
  const longitude = await dialog
    .getByLabel("Longitude titik absensi")
    .inputValue();
  await dialog.getByLabel("Radius absensi (meter)").fill("150");
  await expect(dialog.locator(".attendance-map-radius")).toBeVisible();
  await dialog
    .getByLabel("Alasan / dasar perubahan")
    .fill("Penetapan titik dan radius absensi madrasah");
  const submitted = page.waitForRequest(
    (request) =>
      request.method() === "POST" &&
      request.url().endsWith("/api/attendance/policy/versions"),
  );
  await dialog.getByRole("button", { name: "Simpan", exact: true }).click();
  const payload = (await submitted).postDataJSON();
  expect(payload.geofence_radius_meters).toBe(150);
  expect(payload.geofence_latitude).toBe(latitude);
  expect(payload.geofence_longitude).toBe(longitude);
  await expect(dialog).not.toBeVisible();
  await expect(
    page.getByRole("heading", { name: "Kebijakan terjadwal (versi 2)" }),
  ).toBeVisible();
  await expect(
    page.getByText("Madrasah uji, radius 150 m", { exact: false }),
  ).toBeVisible();
  await expect(
    page.getByText("Perubahan berhasil disimpan.", { exact: true }),
  ).toHaveCount(0);
  await page.setViewportSize({ width: 320, height: 568 });
  await page.getByRole("button", { name: "Versi kebijakan baru" }).click();
  await expect(
    dialog.getByLabel("Batasi absensi dalam radius lokasi"),
  ).toBeChecked();
  await expect(dialog.getByLabel("Latitude titik absensi")).toHaveValue(
    latitude,
  );
  await expect(dialog.getByLabel("Radius absensi (meter)")).toHaveValue("150");
  await expect(
    dialog.getByRole("button", { name: "Simpan", exact: true }),
  ).toBeInViewport();
  await expect
    .poll(() =>
      page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
    )
    .toBe(true);
  await page.screenshot({
    path: "test-results/screenshots/geofence-editor-mobile.png",
    fullPage: true,
  });
});

test("map tile failure leaves manual coordinate entry usable", async ({
  page,
}) => {
  await page.unroute("https://tile.openstreetmap.org/**");
  await page.route("https://tile.openstreetmap.org/**", (route) =>
    route.abort(),
  );
  await login(page);
  await page.goto("/absensi/pola-kerja");
  await page.getByRole("tab", { name: "Kebijakan foto/GPS" }).click();
  await page.getByRole("button", { name: "Versi kebijakan baru" }).click();
  const dialog = page.getByRole("dialog");
  await dialog.getByLabel("Batasi absensi dalam radius lokasi").check();
  await expect(dialog.getByText(/Latar peta belum dapat dimuat/)).toBeVisible();
  await dialog.getByLabel("Nama lokasi absensi").fill("Madrasah manual");
  await dialog.getByLabel("Latitude titik absensi").fill("-6.7");
  await dialog.getByLabel("Longitude titik absensi").fill("108.5");
  await dialog
    .getByLabel("Alasan / dasar perubahan")
    .fill("Penetapan koordinat manual saat peta tidak tersedia");
  await dialog.getByRole("button", { name: "Simpan", exact: true }).click();
  await expect(dialog).not.toBeVisible();
  await expect(
    page.getByRole("heading", { name: "Kebijakan terjadwal (versi 2)" }),
  ).toBeVisible();
});

test("outside and uncertain positions block the UI and submission refreshes GPS", async ({
  page,
}) => {
  await page.addInitScript(() => {
    navigator.geolocation.getCurrentPosition = (success) =>
      success({
        coords: { latitude: -6.7, longitude: 108.5, accuracy: 10 },
        timestamp: Date.now(),
      } as GeolocationPosition);
    Object.defineProperty(navigator.mediaDevices, "getUserMedia", {
      value: async () => {
        const canvas = document.createElement("canvas");
        canvas.width = 320;
        canvas.height = 240;
        canvas.getContext("2d")?.fillRect(0, 0, 320, 240);
        return canvas.captureStream(1);
      },
    });
  });
  await page.route("**/api/attendance/status", async (route) => {
    const response = await route.fetch();
    const json = await response.json();
    json.data.policy = { ...json.data.policy, ...geofence };
    await route.fulfill({ response, json });
  });
  let requests = 0;
  await page.route("**/api/attendance/clock", async (route) => {
    requests++;
    await route.continue();
  });
  await login(page, "guru");
  await page.goto("/absensi/masuk-pulang");
  const setPosition = async (latitude: number) =>
    page.evaluate((value) => {
      navigator.geolocation.getCurrentPosition = (success) =>
        success({
          coords: { latitude: value, longitude: 108.5, accuracy: 12.5 },
          timestamp: Date.now(),
        } as GeolocationPosition);
    }, latitude);
  const submit = page.getByRole("button", {
    name: "Absen masuk dengan foto + GPS",
  });
  await setPosition(-6.72);
  await page.getByRole("button", { name: "Muat ulang", exact: true }).click();
  await expect(page.getByRole("main").getByRole("alert")).toContainText(
    "di luar radius absensi",
  );
  await expect(submit).toBeDisabled();
  await setPosition(-6.70085);
  await page.getByRole("button", { name: "Muat ulang", exact: true }).click();
  await expect(page.getByRole("main").getByRole("alert")).toContainText(
    "belum pasti di dalam radius",
  );
  await expect(submit).toBeDisabled();
  await setPosition(-6.7);
  await page.getByRole("button", { name: "Muat ulang", exact: true }).click();
  await expect(submit).toBeEnabled();
  await page.getByRole("button", { name: "Nyalakan kamera" }).click();
  await setPosition(-6.72);
  await submit.click();
  await expect(submit).toBeDisabled();
  expect(requests).toBe(0);
  await setPosition(-6.7);
  await page.getByRole("button", { name: "Muat ulang", exact: true }).click();
  await submit.click();
  await expect(
    page.getByText("Absen masuk tercatat dengan foto dan GPS."),
  ).toBeVisible();
  expect(requests).toBe(1);
});
