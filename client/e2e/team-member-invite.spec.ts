import { expect, test } from '@playwright/test'

test.describe('Team Member Invite', () => {
  test('admin can invite a new member', async ({ page }) => {
    await page.goto('/team-members')
    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

    // Click Add Member button
    await page.getByTestId('add-member-btn').click()

    // Modal should appear
    const dialog = page.getByTestId('member-dialog')
    await expect(dialog).toBeVisible({ timeout: 5000 })

    // Fill the form
    const uniqueEmail = `invited-${Date.now()}@example.com`
    await dialog.getByTestId('member-name-input').locator('input').fill('Invited User')
    await dialog.getByTestId('member-email-input').locator('input').fill(uniqueEmail)

    // Select at least one wallet using the checkbox
    const checkbox = dialog.getByRole('checkbox').first()
    await expect(checkbox).toBeVisible({ timeout: 5000 })
    await checkbox.check({ force: true })

    // Submit the invitation
    await dialog.getByTestId('member-submit-btn').click()

    // Dialog should close
    await expect(dialog).not.toBeVisible({ timeout: 10_000 })

    // The new member should appear in the table
    await expect(page.getByTestId('data-table').getByRole('row', { name: 'Invited User' })).toBeVisible({ timeout: 10_000 })
  })

  test('shows error for duplicate email invitation', async ({ page }) => {
    await page.goto('/team-members')
    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

    await page.getByTestId('add-member-btn').click()

    const dialog = page.getByTestId('member-dialog')
    await expect(dialog).toBeVisible({ timeout: 5000 })

    // Use the existing member email
    await dialog.getByTestId('member-name-input').locator('input').fill('Duplicate Test')
    await dialog.getByTestId('member-email-input').locator('input').fill('member@example.com')

    await dialog.getByTestId('member-submit-btn').click()

    // Should show a validation error (Vuetify renders error messages in the input wrapper)
    await expect(dialog.getByTestId('member-email-input').locator('.v-messages')).toBeVisible({ timeout: 10_000 })
  })
})
