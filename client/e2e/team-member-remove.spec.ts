import { expect, test } from '@playwright/test'
import { createUser } from './helpers/api'

test.describe('Team Member Remove', () => {
    test('admin can remove a team member', async ({ page }) => {
        // Create a user to delete
        const { user } = await createUser({
            name: 'Removable User',
            email: `removable-${Date.now()}@example.com`,
            role: 'member',
        })

        await page.goto('/team-members')
        await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })

        // Find the row
        const row = page.locator('tr', { hasText: user.name as string })
        await expect(row).toBeVisible({ timeout: 10_000 })

        // Click the delete icon
        await row.locator('[class*="mdi-delete"]').click()

        // ConfirmDialog should appear with PIN
        const dialog = page.getByRole('dialog')
        await expect(dialog).toBeVisible({ timeout: 5000 })
        await expect(dialog.getByText('Verification Required')).toBeVisible()

        // Read the PIN and enter it
        const pinText = await dialog.locator('.text-h4').textContent()
        await dialog.locator('input').fill(pinText!.trim())

        // Click confirm
        await dialog.getByRole('button', { name: 'Yes, Proceed' }).click()

        // Member should disappear from the list
        await expect(row).not.toBeVisible({ timeout: 10_000 })
    })
})
