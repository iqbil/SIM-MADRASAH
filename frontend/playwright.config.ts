import { defineConfig, devices } from "@playwright/test";

export default defineConfig({
  testDir: "./tests/e2e",
  fullyParallel: false,
  workers: 1,
  timeout: 30_000,
  expect: { timeout: 10_000 },
  reporter: "list",
  use: {
    ...devices["Desktop Chrome"],
    baseURL: "http://127.0.0.1:3410",
    trace: "retain-on-failure",
    launchOptions: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH
      ? { executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH }
      : {},
  },
  webServer: [
    {
      command: "node tests/fixtures/auth-api.mjs",
      url: "http://127.0.0.1:43990/health",
      reuseExistingServer: false,
    },
    {
      command: `pnpm exec next ${process.env.NEXT_TEST_DEV === "1" ? "dev" : "start"} --hostname 127.0.0.1 --port 3410`,
      url: "http://127.0.0.1:3410/login",
      env: {
        NEXT_TEST_DEV: process.env.NEXT_TEST_DEV ?? "0",
        API_BASE_URL: "http://127.0.0.1:43990/api/v1",
        APP_URL: "http://127.0.0.1:3410",
      },
      reuseExistingServer: false,
      timeout: 90_000,
    },
  ],
});
