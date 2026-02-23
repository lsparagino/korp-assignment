import { expect, test } from '@playwright/test'

test.describe('Settings - Profile Update', () => {
  test('can update profile name', async ({ page }) => {
    await page.goto('/settings/profile')

    const nameField = page.getByTestId('profile-name-input').locator('input')
    await expect(nameField).toBeVisible({ timeout: 10_000 })

    const newName = `Updated Name ${Date.now()}`
    await nameField.clear()
    await nameField.fill(newName)

    await page.getByTestId('profile-save-btn').click()

    // Should show saved confirmation
    await expect(page.getByTestId('saved-confirmation')).toBeVisible({ timeout: 10_000 })
  })

  test('save button is disabled when form is unchanged', async ({ page }) => {
    await page.goto('/settings/profile')

    const nameField = page.getByTestId('profile-name-input').locator('input')
    await expect(nameField).toBeVisible({ timeout: 10_000 })

    // Button should be disabled when nothing has changed
    await expect(page.getByTestId('profile-save-btn')).toBeDisabled()
  })
})
