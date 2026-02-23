import { expect, test } from '@playwright/test'

test.describe('Team Member Invite', () => {
    test('admin can invite a new member', async ({ page }) => {
        await page.goto('/team-members')
        await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })

        // Click Add Member button
        await page.getByRole('button', { name: 'Add Member' }).click()

        // Modal should appear
        const dialog = page.getByRole('dialog')
        await expect(dialog).toBeVisible({ timeout: 5000 })

        // Fill the form
        const uniqueEmail = `invited-${Date.now()}@example.com`
        await dialog.locator('input').nth(0).fill('Invited User')
        await dialog.locator('input').nth(1).fill(uniqueEmail)

        // Select at least one wallet if available
        const checkbox = dialog.locator('.v-checkbox').first()
        if (await checkbox.isVisible()) {
            await checkbox.click()
        }

        // Submit the invitation
        await dialog.getByRole('button', { name: 'Invite Member' }).click()

        // Dialog should close
        await expect(dialog).not.toBeVisible({ timeout: 10_000 })

        // The new member should appear in the table with a pending badge
        await expect(page.getByText('Invited User')).toBeVisible({ timeout: 10_000 })
        await expect(page.getByText('Pending Invitation')).toBeVisible()
    })

    test('shows error for duplicate email invitation', async ({ page }) => {
        await page.goto('/team-members')
        await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })

        await page.getByRole('button', { name: 'Add Member' }).click()

        const dialog = page.getByRole('dialog')
        await expect(dialog).toBeVisible({ timeout: 5000 })

        // Use the existing member email
        await dialog.locator('input').nth(0).fill('Duplicate Test')
        await dialog.locator('input').nth(1).fill('member@example.com')

        await dialog.getByRole('button', { name: 'Invite Member' }).click()

        // Should show a validation error
        await expect(dialog.locator('.v-messages').first()).toBeVisible({ timeout: 10_000 })
    })
})
