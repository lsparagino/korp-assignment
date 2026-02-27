import { expect, test } from '@playwright/test'

test.describe('Forgot Password', () => {
  test.use({ storageState: { cookies: [], origins: [] } })

  test('can submit forgot password form', async ({ page }) => {
    await page.goto('/auth/forgot-password')

    await page.getByTestId('email-input').locator('input').fill('admin@example.com')
    await page.getByTestId('submit-btn').click()

    // Should show a success/info alert
    await expect(page.getByTestId('status-alert')).toBeVisible({ timeout: 10_000 })
  })

  test('shows cooldown after submission', async ({ page }) => {
    await page.goto('/auth/forgot-password')

    // Use a different email to avoid rate limiting from the first test
    await page.getByTestId('email-input').locator('input').fill('member@example.com')
    await page.getByTestId('submit-btn').click()

    // After successful submission, the alert should appear
    await expect(page.getByTestId('status-alert')).toBeVisible({ timeout: 10_000 })

    // Button should be disabled during cooldown
    await expect(page.getByTestId('submit-btn')).toBeDisabled()
  })
})
