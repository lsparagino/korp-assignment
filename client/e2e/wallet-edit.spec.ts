import { expect, type Page, test } from '@playwright/test'

async function createWallet (page: Page, name: string) {
  await page.goto('/wallets/create')
  const nameField = page.getByTestId('wallet-name-input').locator('input')
  await expect(nameField).toBeVisible({ timeout: 10_000 })
  await nameField.fill(name)

  await page.getByTestId('wallet-create-btn').click()

  // Wait for redirect to wallets list
  await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })
  await expect(page.locator('tr').filter({ hasText: name })).toBeVisible({ timeout: 10_000 })
}

test.describe('Wallet Edit', () => {
  test('admin can navigate to edit page from wallet list', async ({ page }) => {
    const walletName = `Nav Test ${Date.now()}`
    await createWallet(page, walletName)

    // Click the edit icon on the wallet row
    const row = page.locator('tr').filter({ hasText: walletName })
    await row.locator('[class*="mdi-pencil"]').click()

    // Should navigate to the edit page
    await expect(page).toHaveURL(/\/wallets\/\d+\/edit/, { timeout: 10_000 })
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
  })

  test('admin can rename a wallet', async ({ page }) => {
    const walletName = `Rename Test ${Date.now()}`
    await createWallet(page, walletName)

    const row = page.locator('tr').filter({ hasText: walletName })
    await row.locator('[class*="mdi-pencil"]').click()
    await expect(page).toHaveURL(/\/wallets\/\d+\/edit/, { timeout: 10_000 })

    const nameField = page.getByTestId('wallet-name-input').locator('input')
    await expect(nameField).toBeVisible({ timeout: 10_000 })

    // Wait for wallet data to load from API (name field is populated with original name)
    await expect(nameField).toHaveValue(walletName, { timeout: 15_000 })

    const renamedName = `Renamed ${Date.now()}`
    await nameField.clear()
    await nameField.fill(renamedName)

    await page.getByTestId('wallet-save-btn').click()

    // Should redirect back to wallets list with updated name
    await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })
    await expect(page.locator('tr').filter({ hasText: renamedName })).toBeVisible({ timeout: 10_000 })
  })

  test('admin can freeze and unfreeze a wallet via edit page', async ({ page }) => {
    const freezeWallet = `Freeze Test ${Date.now()}`
    await createWallet(page, freezeWallet)

    // Navigate to the edit page for the wallet
    const row = page.locator('tr').filter({ hasText: freezeWallet })
    await row.locator('[class*="mdi-pencil"]').click()
    await expect(page).toHaveURL(/\/wallets\/\d+\/edit/, { timeout: 10_000 })
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Click the freeze button
    const freezeBtn = page.locator('button').filter({ hasText: 'Freeze Wallet' })
    await expect(freezeBtn).toBeVisible({ timeout: 5000 })
    await freezeBtn.click()

    // Confirmation dialog should appear
    const dialog = page.getByTestId('confirm-dialog')
    await expect(dialog).toBeVisible({ timeout: 5000 })
    await dialog.getByTestId('confirm-btn').click()
    await expect(dialog).not.toBeVisible({ timeout: 10_000 })

    // Button should now say "Unfreeze Wallet"
    const unfreezeBtn = page.locator('button').filter({ hasText: 'Unfreeze Wallet' })
    await expect(unfreezeBtn).toBeVisible({ timeout: 10_000 })

    // Unfreeze
    await unfreezeBtn.click()
    const unfreezeDialog = page.getByTestId('confirm-dialog')
    await expect(unfreezeDialog).toBeVisible({ timeout: 5000 })
    await unfreezeDialog.getByTestId('confirm-btn').click()
    await expect(unfreezeDialog).not.toBeVisible({ timeout: 10_000 })

    // Button should be back to "Freeze Wallet"
    await expect(freezeBtn).toBeVisible({ timeout: 10_000 })
  })
})
