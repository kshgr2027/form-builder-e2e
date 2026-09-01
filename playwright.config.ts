import { defineConfig, devices } from '@playwright/test';
import { env } from './src/utils/env';

/**
 * The app under test is a stripped-down Laravel "Form Builder" module.
 * Tests provision their own data (slug-prefixed `fb-e2e-`) and clean up after
 * themselves, so the suite is fully parallel-safe. See README for scope/assumptions.
 */
export default defineConfig({
  testDir: './tests',
  outputDir: './test-results',

  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  // The app under test runs on `php artisan serve` (PHP built-in server = single
  // request at a time). Beyond ~2 concurrent workers it saturates and navigations
  // time out. 2 is the supported ceiling for this environment.
  workers: process.env.PW_WORKERS ? Number(process.env.PW_WORKERS) : 2,

  timeout: 60_000,
  expect: { timeout: 10_000 },

  globalSetup: require.resolve('./global-setup'),
  globalTeardown: require.resolve('./global-teardown'),

  reporter: [
    ['list'],
    ['html', { outputFolder: 'playwright-report', open: 'never' }],
    ['junit', { outputFile: 'results/junit.xml' }],
  ],

  use: {
    baseURL: env.baseUrl,
    actionTimeout: 10_000,
    navigationTimeout: 30_000,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    // Date-range tests depend on a stable clock/locale.
    timezoneId: 'Asia/Kolkata',
    locale: 'en-IN',
    // App runs on http with a self-signed-ish setup in some environments.
    ignoreHTTPSErrors: true,
  },

  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    // A second engine is cheap insurance for selector robustness; enable when time allows.
    // { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
  ],

  webServer: env.startApp
    ? {
        command: `${env.phpBin} artisan serve --host=127.0.0.1 --port=8000`,
        cwd: env.appDir,
        url: env.baseUrl,
        reuseExistingServer: !process.env.CI,
        timeout: 60_000,
        stdout: 'pipe',
        stderr: 'pipe',
      }
    : undefined,
});
