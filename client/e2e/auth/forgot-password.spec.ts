import { expect, test } from '@playwright/test'

test.describe('Forgot Password', () => {
  test.use({ storageState: { cookies: [], origins: [] } })

  test('can submit forgot password form', async ({ page }) => {
    await page.goto('/auth/forgot-password')

    await page.locator('input[name="email"]').fill('admin@example.com')
    await page.getByRole('button', { name: 'Email password reset link' }).click()

    // Should show a success/info alert
    await expect(page.locator('.v-alert').first()).toBeVisible({ timeout: 10_000 })
  })

  test('shows cooldown after submission', async ({ page }) => {
    await page.goto('/auth/forgot-password')

    await page.locator('input[name="email"]').fill('admin@example.com')
    await page.getByRole('button', { name: 'Email password reset link' }).click()

    // After successful submission, the alert should appear
    await expect(page.locator('.v-alert').first()).toBeVisible({ timeout: 10_000 })

    // Button should be disabled during cooldown and show "Wait Xs to resend"
    const submitBtn = page.locator('button[type="submit"]')
    await expect(submitBtn).toBeDisabled()
  })
})
