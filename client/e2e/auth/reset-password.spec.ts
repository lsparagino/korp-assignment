import { expect, test } from '@playwright/test'
import { createPasswordResetToken, createUser } from '../helpers/api'

test.describe('Reset Password', () => {
  test.use({ storageState: { cookies: [], origins: [] } })

  test('can reset password with valid token', async ({ page }) => {
    // Create a dedicated user so we don't mutate the shared admin password
    const { user } = await createUser({
      name: 'Reset Test User',
      email: `reset-${Date.now()}@example.com`,
    })

    // Generate a reset token via the test API
    const { token, email } = await createPasswordResetToken(user.email as string)

    // Navigate to the reset password page with the token
    await page.goto(`/auth/reset-password?token=${token}&email=${encodeURIComponent(email)}`)

    // Email field should be pre-filled and readonly
    await expect(page.getByTestId('email-input').locator('input')).toHaveValue(email, { timeout: 10_000 })

    // Fill in the new password
    await page.getByTestId('password-input').locator('input').fill('ResetPassword123!')
    await page.getByTestId('password-confirm-input').locator('input').fill('ResetPassword123!')

    await page.getByTestId('submit-btn').click()

    // Should show a success alert
    await expect(page.getByTestId('status-alert')).toBeVisible({ timeout: 10_000 })

    // Should eventually redirect to login
    await expect(page).toHaveURL(/\/auth\/login/, { timeout: 10_000 })
  })
})
