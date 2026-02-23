import { expect, test } from '@playwright/test'

test.describe('Settings - Password Change', () => {
    test('can change password successfully', async ({ page }) => {
        await page.goto('/settings/password')

        const currentPassword = page.locator('input[name="current_password"]')
        await expect(currentPassword).toBeVisible({ timeout: 10_000 })

        await currentPassword.fill('password')
        await page.locator('input[name="password"]').fill('NewPassword123!')
        await page.locator('input[name="password_confirmation"]').fill('NewPassword123!')

        await page.getByRole('button', { name: 'Save password' }).click()

        // Should show "Saved." confirmation
        await expect(page.getByText('Saved.')).toBeVisible({ timeout: 10_000 })

        // Fields should be cleared after successful save
        await expect(currentPassword).toHaveValue('')
    })

    test('shows error with wrong current password', async ({ page }) => {
        await page.goto('/settings/password')

        const currentPassword = page.locator('input[name="current_password"]')
        await expect(currentPassword).toBeVisible({ timeout: 10_000 })

        await currentPassword.fill('wrongpassword')
        await page.locator('input[name="password"]').fill('NewPassword123!')
        await page.locator('input[name="password_confirmation"]').fill('NewPassword123!')

        await page.getByRole('button', { name: 'Save password' }).click()

        // Should show a validation error
        await expect(page.locator('.v-messages').first()).toBeVisible({ timeout: 10_000 })
    })
})
