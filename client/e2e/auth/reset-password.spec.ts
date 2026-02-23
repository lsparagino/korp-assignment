import { expect, test } from '@playwright/test'
import { createPasswordResetToken } from '../helpers/api'

test.describe('Reset Password', () => {
    test.use({ storageState: { cookies: [], origins: [] } })

    test('can reset password with valid token', async ({ page }) => {
        // Generate a reset token via the test API
        const { token, email } = await createPasswordResetToken('admin@example.com')

        // Navigate to the reset password page with the token
        await page.goto(`/auth/reset-password?token=${token}&email=${encodeURIComponent(email)}`)

        // Email field should be pre-filled and readonly
        await expect(page.locator('input[name="email"]')).toHaveValue(email, { timeout: 10_000 })

        // Fill in the new password
        await page.locator('input[name="password"]').fill('ResetPassword123!')
        await page.locator('input[name="password_confirmation"]').fill('ResetPassword123!')

        await page.getByRole('button', { name: 'Reset password' }).click()

        // Should show a success alert
        await expect(page.locator('.v-alert').first()).toBeVisible({ timeout: 10_000 })

        // Should eventually redirect to login
        await expect(page).toHaveURL(/\/auth\/login/, { timeout: 10_000 })
    })
})
