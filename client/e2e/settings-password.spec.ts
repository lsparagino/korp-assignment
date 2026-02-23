import { expect, test } from '@playwright/test'

test.describe('Settings - Password Update', () => {
  test('can change password and restore it', async ({ page }) => {
    await page.goto('/settings/password')

    // Step 1: Change password from "password" to "NewPassword123!"
    await page.getByTestId('current-password-input').locator('input').fill('password')
    await page.getByTestId('new-password-input').locator('input').fill('NewPassword123!')
    await page.getByTestId('password-confirm-input').locator('input').fill('NewPassword123!')

    await page.getByTestId('save-password-btn').click()

    // Should show saved confirmation
    await expect(page.getByTestId('saved-confirmation')).toBeVisible({ timeout: 10_000 })

    // Wait for the confirmation to disappear before submitting again
    await expect(page.getByTestId('saved-confirmation')).not.toBeVisible({ timeout: 10_000 })

    // Step 2: Change password back from "NewPassword123!" to "password"
    await page.getByTestId('current-password-input').locator('input').fill('NewPassword123!')
    await page.getByTestId('new-password-input').locator('input').fill('password')
    await page.getByTestId('password-confirm-input').locator('input').fill('password')

    await page.getByTestId('save-password-btn').click()

    // Should show saved confirmation again
    await expect(page.getByTestId('saved-confirmation')).toBeVisible({ timeout: 10_000 })
  })
})
