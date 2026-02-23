import { expect, test } from '@playwright/test'

test.describe('Settings - Profile Update', () => {
    test('can update profile name', async ({ page }) => {
        await page.goto('/settings/profile')

        const nameField = page.locator('input[name="name"]')
        await expect(nameField).toBeVisible({ timeout: 10_000 })

        const newName = `Updated Name ${Date.now()}`
        await nameField.clear()
        await nameField.fill(newName)

        await page.getByRole('button', { name: 'Save' }).click()

        // Should show "Saved." confirmation
        await expect(page.getByText('Saved.')).toBeVisible({ timeout: 10_000 })
    })

    test('save button is disabled when form is unchanged', async ({ page }) => {
        await page.goto('/settings/profile')

        const nameField = page.locator('input[name="name"]')
        await expect(nameField).toBeVisible({ timeout: 10_000 })

        // Button should be disabled when nothing has changed
        await expect(page.getByRole('button', { name: 'Save' })).toBeDisabled()
    })
})
