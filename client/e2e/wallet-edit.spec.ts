import { createRequire } from 'node:module'
import { expect, type Page, test } from '@playwright/test'
import { createWallet as apiCreateWallet } from './helpers/api'

const require = createRequire(import.meta.url)
const en = require('../src/locales/en.json')

async function createWallet (page: Page, name: string) {
  // Create wallet via API to avoid client-side caching/navigation issues
  await apiCreateWallet({ email: 'admin@example.com', name })

  // Navigate to wallets list and verify the wallet shows up
  await page.goto('/wallets')
  await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })
  await expect(page.getByTestId('data-table').getByRole('row', { name })).toBeVisible({ timeout: 10_000 })
}

test.describe('Wallet Detail', () => {
  test('admin can navigate to detail page from wallet list', async ({ page }) => {
    const walletName = `Nav Test ${Date.now()}`
    await createWallet(page, walletName)

    // Click the wallet row to navigate
    const row = page.getByTestId('data-table').getByRole('row', { name: walletName })
    await row.click()

    // Should navigate to the detail page
    await expect(page).toHaveURL(/\/wallets\/\d+$/, { timeout: 10_000 })
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
  })

  test('admin can rename a wallet', async ({ page }) => {
    const walletName = `Rename Test ${Date.now()}`
    await createWallet(page, walletName)

    const row = page.getByTestId('data-table').getByRole('row', { name: walletName })
    await row.click()
    await expect(page).toHaveURL(/\/wallets\/\d+$/, { timeout: 10_000 })

    const nameField = page.getByTestId('wallet-name-input').locator('input')
    await expect(nameField).toBeVisible({ timeout: 10_000 })

    // Wait for wallet data to load from API (name field is populated with original name)
    await expect(nameField).toHaveValue(walletName, { timeout: 15_000 })

    const renamedName = `Renamed ${Date.now()}`
    await nameField.clear()
    await nameField.fill(renamedName)

    await page.getByTestId('wallet-save-btn').click()

    // Should stay on the detail page and show success
    await expect(page).toHaveURL(/\/wallets\/\d+$/, { timeout: 10_000 })
  })

  test('admin can freeze and unfreeze a wallet via detail page', async ({ page }) => {
    const freezeWallet = `Freeze Test ${Date.now()}`
    await createWallet(page, freezeWallet)

    // Navigate to the detail page for the wallet
    const row = page.getByTestId('data-table').getByRole('row', { name: freezeWallet })
    await row.click()
    await expect(page).toHaveURL(/\/wallets\/\d+$/, { timeout: 10_000 })
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Click the freeze button
    const freezeBtn = page.getByTestId('freeze-btn')
    await expect(freezeBtn).toBeVisible({ timeout: 5000 })
    await expect(freezeBtn).toContainText(en.wallets.freezeWallet)
    await freezeBtn.click()

    // Confirmation dialog should appear
    const dialog = page.getByTestId('confirm-dialog')
    await expect(dialog).toBeVisible({ timeout: 5000 })
    await dialog.getByTestId('confirm-btn').click()
    await expect(dialog).not.toBeVisible({ timeout: 10_000 })

    // Button should now say "Unfreeze Wallet"
    await expect(freezeBtn).toContainText(en.wallets.unfreezeWallet, { timeout: 10_000 })

    // Unfreeze
    await freezeBtn.click()
    await expect(dialog).toBeVisible({ timeout: 5000 })
    await dialog.getByTestId('confirm-btn').click()
    await expect(dialog).not.toBeVisible({ timeout: 10_000 })

    // Button should be back to "Freeze Wallet"
    await expect(freezeBtn).toContainText(en.wallets.freezeWallet, { timeout: 10_000 })
  })
})
