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
    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

    // Find the row and click it to navigate to the detail page
    const row = page.getByTestId('data-table').getByRole('row', { name: user.name as string })
    await expect(row).toBeVisible({ timeout: 10_000 })
    await row.click()

    // Wait for the detail page to load
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Click the delete button on the detail page
    await page.getByTestId('delete-member-btn').click()

    // ConfirmDialog should appear with PIN
    const dialog = page.getByTestId('confirm-dialog')
    await expect(dialog).toBeVisible({ timeout: 5000 })
    await expect(dialog.getByTestId('pin-section')).toBeVisible()

    // Read the PIN and enter it
    const pinText = await dialog.getByTestId('confirm-pin').textContent()
    await dialog.getByTestId('pin-input').locator('input').fill(pinText!.trim())

    // Click confirm
    await dialog.getByTestId('confirm-btn').click()

    // Should be redirected back to the list
    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

    // Member should no longer appear in the list
    await expect(page.getByTestId('data-table').getByRole('row', { name: user.name as string })).not.toBeVisible({ timeout: 10_000 })
  })
})
