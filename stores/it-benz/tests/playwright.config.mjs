import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: '.',
  testMatch: /.*\.spec\.mjs/,
  timeout: 90_000,
  workers: 1,
  reporter: [['list']],
  use: {
    baseURL: process.env.BASE_URL || 'http://localhost:8080',
    locale: 'fr-FR',
    launchOptions: process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {},
  },
  projects: [
    { name: 'desktop', use: { ...devices['Desktop Chrome'] }, grepInvert: /@mobile/ },
    { name: 'mobile', use: { ...devices['Pixel 7'] }, grep: /@mobile/ },
  ],
});
