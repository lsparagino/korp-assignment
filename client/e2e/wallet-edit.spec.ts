import { expect, type Page, test } from '@playwright/test'

async function createWallet (page: Page, name: string) {
  await page.goto('/wallets/create')
  const nameField = page.locator('input').first()
  await expect(nameField).toBeVisible({ timeout: 10_000 })
  await nameField.fill(name)

  // Click the Create Wallet submit button (inside the main content, not the sidebar)
  await page.locator('.v-main').getByRole('button', { name: 'Create Wallet' }).click()

  // Wait for redirect to wallets list
  await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })
  await expect(page.getByText(name)).toBeVisible({ timeout: 10_000 })
}

test.describe('Wallet Edit', () => {
  test('admin can navigate to edit page from wallet list', async ({ page }) => {
    const walletName = `Nav Test ${Date.now()}`
    await createWallet(page, walletName)

    // Click the edit icon on the wallet row
    const row = page.locator('tr', { hasText: walletName })
    await row.locator('[class*="mdi-pencil"]').click()

    // Should navigate to the edit page
    await expect(page).toHaveURL(/\/wallets\/\d+\/edit/, { timeout: 10_000 })
    await expect(page.getByText('Edit Wallet')).toBeVisible({ timeout: 10_000 })
  })

  test('admin can rename a wallet', async ({ page }) => {
    const walletName = `Rename Test ${Date.now()}`
    await createWallet(page, walletName)

    const row = page.locator('tr', { hasText: walletName })
    await row.locator('[class*="mdi-pencil"]').click()
    await expect(page).toHaveURL(/\/wallets\/\d+\/edit/, { timeout: 10_000 })

    const nameField = page.locator('input').first()
    await expect(nameField).toBeVisible({ timeout: 10_000 })

    const renamedName = `Renamed ${Date.now()}`
    await nameField.clear()
    await nameField.fill(renamedName)

    await page.getByRole('button', { name: 'Save Changes' }).click()

    // Should redirect back to wallets list with updated name
    await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })
    await expect(page.getByText(renamedName)).toBeVisible({ timeout: 10_000 })
  })

  test('admin can freeze and unfreeze a wallet from list', async ({ page }) => {
    const freezeWallet = `Freeze Test ${Date.now()}`
    await createWallet(page, freezeWallet)

    // Click the freeze (snowflake) icon
    const row = page.locator('tr', { hasText: freezeWallet })
    await row.locator('[class*="mdi-snowflake"]').click()

    // Confirmation dialog should appear
    const dialog = page.getByRole('dialog')
    await expect(dialog).toBeVisible({ timeout: 5000 })

    // Confirm the freeze
    await dialog.getByRole('button', { name: 'Yes, Proceed' }).click()
    await expect(dialog).not.toBeVisible({ timeout: 10_000 })

    // Status should change to frozen
    await expect(row.getByText('frozen')).toBeVisible({ timeout: 10_000 })

    // Now unfreeze — the icon changes to mdi-fire
    await row.locator('[class*="mdi-fire"]').click()

    const unfreezeDialog = page.getByRole('dialog')
    await expect(unfreezeDialog).toBeVisible({ timeout: 5000 })
    await unfreezeDialog.getByRole('button', { name: 'Yes, Proceed' }).click()
    await expect(unfreezeDialog).not.toBeVisible({ timeout: 10_000 })

    // Status should change back to active
    await expect(row.getByText('active')).toBeVisible({ timeout: 10_000 })
  })
})
