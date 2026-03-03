import { createRequire } from 'node:module'
import { expect, test } from '@playwright/test'

const require = createRequire(import.meta.url)
const en = require('../src/locales/en.json')

test.describe('Settings - Preferences', () => {
  test('shows the preferences page with all sections', async ({ page }) => {
    await page.goto('/settings/preferences')

    // Wait for preferences to load (skeleton is replaced by form)
    await expect(page.getByTestId('daily-limit-input')).toBeVisible({ timeout: 15_000 })
    await expect(page.getByTestId('security-threshold-input')).toBeVisible()
    await expect(page.getByTestId('preferences-save-btn')).toBeVisible()
  })

  test('shows limit input fields', async ({ page }) => {
    await page.goto('/settings/preferences')

    await expect(page.getByTestId('daily-limit-input')).toBeVisible({ timeout: 10_000 })
    await expect(page.getByTestId('security-threshold-input')).toBeVisible()
  })

  test('shows validation error when security threshold exceeds daily limit', async ({ page }) => {
    await page.goto('/settings/preferences')

    await expect(page.getByTestId('daily-limit-input')).toBeVisible({ timeout: 10_000 })

    // Set daily limit to 1000
    const dailyLimitInput = page.getByTestId('daily-limit-input').locator('input')
    await dailyLimitInput.clear()
    await dailyLimitInput.fill('1000')

    // Set security threshold to 2000 (above daily limit)
    const thresholdInput = page.getByTestId('security-threshold-input').locator('input')
    await thresholdInput.clear()
    await thresholdInput.fill('2000')

    // Blur the threshold field to trigger Vuetify validation
    await dailyLimitInput.focus()

    // Should show the threshold validation error inline
    await expect(page.getByText(en.settings.preferences.thresholdExceedsLimit)).toBeVisible({ timeout: 5000 })
  })

  test('no validation error when threshold is within limit', async ({ page }) => {
    await page.goto('/settings/preferences')

    await expect(page.getByTestId('daily-limit-input')).toBeVisible({ timeout: 10_000 })

    // Set daily limit to 1000
    const dailyLimitInput = page.getByTestId('daily-limit-input').locator('input')
    await dailyLimitInput.clear()
    await dailyLimitInput.fill('1000')

    // Set security threshold to 500 (below daily limit)
    const thresholdInput = page.getByTestId('security-threshold-input').locator('input')
    await thresholdInput.clear()
    await thresholdInput.fill('500')

    // Blur to trigger validation
    await dailyLimitInput.focus()

    // Should NOT show the threshold validation error
    await expect(page.getByText(en.settings.preferences.thresholdExceedsLimit)).not.toBeVisible()
  })
})
