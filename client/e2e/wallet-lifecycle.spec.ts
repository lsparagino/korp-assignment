import { createRequire } from 'node:module'
import { expect, type Page, test } from '@playwright/test'
import { loginViaApi } from './helpers/api'
import { authenticatedPage } from './helpers/auth'

const require = createRequire(import.meta.url)
const en = require('../src/locales/en.json')

/**
 * Helper: navigates to the create wallet page, fills the name, and submits the form.
 */
async function createWallet(page: Page, name: string) {
  await page.goto('/wallets/create')
  await expect(page.getByTestId('wallet-name-input').locator('input')).toBeVisible({ timeout: 10_000 })
  await page.getByTestId('wallet-name-input').locator('input').fill(name)

  await page.getByTestId('wallet-create-btn').click()

  // Wait for redirect back to wallets list
  await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })
  await expect(page.getByTestId('data-table').getByRole('row', { name })).toBeVisible({ timeout: 10_000 })
}

test.describe('Admin Wallet Lifecycle', () => {
  test.describe.configure({ mode: 'serial' })
  const walletName = `Lifecycle Wallet ${Date.now()}`

  test('admin creates a new wallet', async ({ page }) => {
    await createWallet(page, walletName)
  })

  test('the new wallet has zero balance', async ({ page }) => {
    await page.goto('/wallets')
    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

    const row = page.getByTestId('data-table').getByRole('row', { name: walletName })
    await expect(row).toBeVisible({ timeout: 10_000 })

    // The balance column should show $0.00 (USD default)
    await expect(row).toContainText('$0.00')
  })

  test('the new wallet can be deleted', async ({ page }) => {
    await page.goto('/wallets')
    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

    const row = page.getByTestId('data-table').getByRole('row', { name: walletName })
    await expect(row).toBeVisible({ timeout: 10_000 })

    // Click the delete button
    await row.getByTestId('delete-btn').click()

    // ConfirmDialog should appear with PIN
    const dialog = page.getByTestId('confirm-dialog')
    await expect(dialog).toBeVisible({ timeout: 5000 })
    await expect(dialog.getByTestId('pin-section')).toBeVisible()

    // Read the PIN and enter it
    const pinText = await dialog.getByTestId('confirm-pin').textContent()
    await dialog.getByTestId('pin-input').locator('input').fill(pinText!.trim())

    // Click confirm
    await dialog.getByTestId('confirm-btn').click()

    // Wallet should disappear from the list
    await expect(row).not.toBeVisible({ timeout: 10_000 })
  })

  test('member cannot see a wallet they have no access to', async ({ page, browser }) => {
    const restrictedWallet = `Restricted ${Date.now()}`
    await createWallet(page, restrictedWallet)

    // Check as member
    const member = await loginViaApi('member@example.com', 'password')
    const memberPage = await authenticatedPage(browser, member)
    await memberPage.goto('/wallets')
    await expect(memberPage.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

    // Member should not see the restricted wallet
    await expect(memberPage.getByTestId('data-table').getByRole('row', { name: restrictedWallet })).not.toBeVisible()

    await memberPage.context().close()
  })

  test('admin grants wallet access to member, member can now see it', async ({ page, browser }) => {
    const sharedWallet = `Shared ${Date.now()}`
    await createWallet(page, sharedWallet)

    // Admin goes to Team Members and edits the member
    await page.goto('/team-members')
    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

    // Find the member row and click edit
    const memberRow = page.getByTestId('data-table').getByRole('row', { name: 'Member User' })
    await expect(memberRow).toBeVisible({ timeout: 10_000 })
    await memberRow.getByTestId('edit-btn').click()

    // Modal should appear
    const modal = page.getByTestId('member-dialog')
    await expect(modal).toBeVisible({ timeout: 5000 })

    // Check the new wallet checkbox
    await modal.getByRole('checkbox', { name: new RegExp(sharedWallet) }).check({ force: true })

    // Click submit
    await page.getByTestId('member-submit-btn').click()
    await expect(page.getByTestId('member-dialog')).not.toBeVisible({ timeout: 10_000 })

    // Switch to member and verify
    const member = await loginViaApi('member@example.com', 'password')
    const memberPage = await authenticatedPage(browser, member)
    await memberPage.goto('/wallets')
    await expect(memberPage.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

    await expect(memberPage.getByTestId('data-table').getByRole('row', { name: sharedWallet })).toBeVisible({ timeout: 10_000 })

    await memberPage.context().close()
  })
})
