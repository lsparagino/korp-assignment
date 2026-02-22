import { defineConfig, devices } from '@playwright/test'

const API_PORT = 8001
const CLIENT_PORT = 3001

export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  reporter: process.env.CI ? 'github' : 'html',
  timeout: 30_000,

  use: {
    baseURL: `http://localhost:${CLIENT_PORT}`,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },

  projects: [
    {
      name: 'setup',
      testMatch: /global-setup\.ts/,
      teardown: 'teardown',
    },
    {
      name: 'teardown',
      testMatch: /global-teardown\.ts/,
    },
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        storageState: 'e2e/.auth/admin.json',
      },
      dependencies: ['setup'],
    },
  ],

  webServer: [
    {
      command: `php artisan serve --port=${API_PORT} --env=e2e`,
      cwd: '../',
      port: API_PORT,
      reuseExistingServer: !process.env.CI,
      timeout: 30_000,
    },
    {
      command: `npx vite --port ${CLIENT_PORT} --mode e2e`,
      port: CLIENT_PORT,
      reuseExistingServer: !process.env.CI,
      timeout: 30_000,
    },
  ],
})
