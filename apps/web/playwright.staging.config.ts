import { defineConfig, devices } from '@playwright/test'
import base from './playwright.config'

export default defineConfig({
  ...base,
  use: {
    ...base.use,
    baseURL: process.env.STAGING_URL ?? 'https://staging.spediamofacile.it',
  },
  timeout: 45_000,
  retries: 2,
  workers: 2,
  reporter: [['html', { outputFolder: 'playwright-report-staging' }], ['list']],
})
