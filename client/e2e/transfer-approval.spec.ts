import { createRequire } from 'node:module'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { expect, type Page, test } from '@playwright/test'
import { createWallet, loginViaApi } from './helpers/api'
import { authenticatedPage } from './helpers/auth'

const require = createRequire(import.meta.url)
const en = require('../src/locales/en.json')

const __dirname = path.dirname(fileURLToPath(import.meta.url))

// ── Helpers ──────────────────────────────────────────────────────

/**
 * Navigate to the transfer creation page.
 */
async function goToCreateTransfer (page: Page) {
  await page.goto('/transactions/create')
  await expect(page.getByTestId('transfer-type-toggle')).toBeVisible({ timeout: 10_000 })
}

/**
 * Select a wallet from a v-select dropdown.
 *
 * The wallet data loads asynchronously via useQuery, so the dropdown may
 * briefly contain no items. This helper retries up to 3 times to handle that.
 */
async function selectFirstAvailableWallet (page: Page, testId: string) {
  const select = page.getByTestId(testId)

  for (let attempt = 0; attempt < 3; attempt++) {
    await select.click()
    try {
      const option = page.getByRole('option').first()
      await expect(option).toBeVisible({ timeout: 3000 })
      await option.click()
      // Wait for overlay to close
      await expect(page.locator('.v-overlay--active')).not.toBeVisible({ timeout: 3000 })
      return
    } catch {
      // Dropdown opened but no items yet — close and retry.
      await page.keyboard.press('Escape')
      await expect(page.locator('.v-overlay--active')).not.toBeVisible({ timeout: 3000 })
    }
  }

  // Final attempt — fail loudly if no items.
  await select.click()
  const option = page.getByRole('option').first()
  await expect(option).toBeVisible({ timeout: 10_000 })
  await option.click()
  await expect(page.locator('.v-overlay--active')).not.toBeVisible({ timeout: 3000 })
}

/**
 * Fill the internal transfer form (sender, receiver, amount, reference).
 */
async function fillInternalTransfer (page: Page, amount: string, reference: string) {
  await selectFirstAvailableWallet(page, 'transfer-sender-wallet')
  await selectFirstAvailableWallet(page, 'transfer-receiver-wallet')

  await page.getByTestId('transfer-amount').locator('input').fill(amount)
  await page.getByTestId('transfer-reference').locator('input').fill(reference)
}

/**
 * Complete the 2-step transfer flow: review → confirm.
 */
async function submitTransfer (page: Page) {
  await page.getByTestId('transfer-submit-btn').click()
  await expect(page.getByTestId('transfer-recap')).toBeVisible({ timeout: 10_000 })
  await page.getByTestId('transfer-confirm-btn').click()
  // After successful transfer, the page redirects to /transactions
  await expect(page).toHaveURL(/\/transactions\/?$/, { timeout: 15_000 })
}

// ── Tests ────────────────────────────────────────────────────────

test.describe('Transfer Approval Flow', () => {
  test.describe.configure({ mode: 'serial', timeout: 60_000 })

  // ===========================================================
  // 0. Setup: create wallets for the member user
  //    The seeder only creates wallets for admin; members see none.
  // ===========================================================
  test('setup: create wallets for member and set threshold', async ({ page }) => {
    // Create two USD wallets for the member so they can do internal transfers
    await createWallet({ email: 'member@example.com', name: 'Member Savings', currency: 'USD', balance: 25_000 })
    await createWallet({ email: 'member@example.com', name: 'Member Business', currency: 'USD', balance: 25_000 })

    // Admin sets a USD approval threshold of $1,000
    await page.goto('/settings/thresholds')
    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 10_000 })

    await page.getByTestId('add-threshold-btn').click()
    const dialog = page.getByTestId('threshold-dialog')
    await expect(dialog).toBeVisible({ timeout: 5000 })

    await page.getByTestId('threshold-currency-select').click()
    await page.getByRole('option', { name: 'USD' }).click()

    await page.getByTestId('threshold-amount-input').locator('input').fill('1000')

    await page.getByTestId('threshold-save-btn').click()
    await expect(dialog).not.toBeVisible({ timeout: 10_000 })

    // Verify
    const row = page.getByTestId('data-table').getByRole('row', { name: 'USD' })
    await expect(row).toBeVisible({ timeout: 5000 })
    await expect(row).toContainText('1,000')
  })

  // ===========================================================
  // 1. Member internal transfer below threshold → completed
  // ===========================================================
  test('member internal transfer below threshold completes immediately', async ({ browser }) => {
    const member = await loginViaApi('member@example.com', 'password')
    const page = await authenticatedPage(browser, member)

    await goToCreateTransfer(page)
    await fillInternalTransfer(page, '500', 'Below threshold transfer')

    // No threshold warning should appear
    await expect(page.getByTestId('transfer-threshold-warning')).not.toBeVisible()

    await submitTransfer(page)

    // Verify the transaction appears as completed
    await page.goto('/transactions?status=completed')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
    await expect(page.getByTestId('data-table').getByRole('row', { name: '$500.00' })).toBeVisible({ timeout: 10_000 })

    await page.context().close()
  })

  // ===========================================================
  // 2. Member internal transfer above threshold → warning + pending
  // ===========================================================
  test('member internal transfer above threshold shows warning and becomes pending', async ({ browser }) => {
    const member = await loginViaApi('member@example.com', 'password')
    const page = await authenticatedPage(browser, member)

    await goToCreateTransfer(page)
    await fillInternalTransfer(page, '5000', 'Above threshold transfer')

    // Threshold warning should appear
    await expect(page.getByTestId('transfer-threshold-warning')).toBeVisible({ timeout: 5000 })

    await submitTransfer(page)

    // Verify the transaction shows as pending
    await page.goto('/transactions?status=pending_approval')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
    await expect(page.getByTestId('data-table').getByRole('row', { name: '$5,000.00' })).toBeVisible({ timeout: 10_000 })

    await page.context().close()
  })

  // ===========================================================
  // 3. Frozen wallets are disabled in sender dropdown
  // ===========================================================
  test('frozen wallets appear as disabled in the sender dropdown', async ({ page }) => {
    // Freeze a wallet as admin via the wallet edit page
    await page.goto('/wallets/')
    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

    const firstRow = page.getByTestId('data-table').getByRole('row').nth(1)
    const walletName = await firstRow.locator('td').first().textContent()
    await firstRow.getByTestId('edit-btn').click()
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Click freeze
    const freezeBtn = page.getByTestId('freeze-btn')
    await expect(freezeBtn).toBeVisible({ timeout: 5000 })
    await freezeBtn.click()

    // Confirm
    const confirmDialog = page.getByTestId('confirm-dialog')
    await expect(confirmDialog).toBeVisible({ timeout: 5000 })
    await confirmDialog.getByTestId('confirm-btn').click()

    const unfreezeBtn = page.getByTestId('freeze-btn')
    await expect(unfreezeBtn).toContainText(en.wallets.unfreezeWallet, { timeout: 10_000 })

    // Open transfer page and check sender dropdown
    await goToCreateTransfer(page)
    await page.getByTestId('transfer-sender-wallet').click()

    const allOptions = page.getByRole('option')
    await expect(allOptions.first()).toBeVisible({ timeout: 10_000 })

    // The frozen wallet should show "🔒 Frozen" chip text
    const frozenOption = page.getByRole('option').filter({ hasText: 'Frozen' })
    await expect(frozenOption.first()).toBeVisible({ timeout: 5000 })

    // Go back to wallets
    await page.keyboard.press('Escape')

    // Cleanup: unfreeze the wallet
    await page.goto('/wallets/')
    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })
    const row = page.getByTestId('data-table').getByRole('row', { name: walletName!.trim() })
    await row.getByTestId('edit-btn').click()
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
    await page.getByTestId('freeze-btn').click()
    await expect(confirmDialog).toBeVisible({ timeout: 5000 })
    await confirmDialog.getByTestId('confirm-btn').click()
    await expect(page.getByTestId('freeze-btn')).toContainText(en.wallets.freezeWallet, { timeout: 10_000 })
  })

  // ===========================================================
  // 4. Insufficient funds shows validation error
  // ===========================================================
  test('insufficient funds shows validation error', async ({ browser }) => {
    const member = await loginViaApi('member@example.com', 'password')
    const page = await authenticatedPage(browser, member)

    await goToCreateTransfer(page)
    await selectFirstAvailableWallet(page, 'transfer-sender-wallet')

    // Enter an absurdly large amount
    await page.getByTestId('transfer-amount').locator('input').fill('999999999')
    await page.getByTestId('transfer-reference').locator('input').fill('Should fail')

    // Trigger validation by blurring the amount field
    await page.getByTestId('transfer-amount').locator('input').blur()

    // The insufficient funds error should be visible
    await expect(page.getByTestId('transfer-amount')).toContainText(en.validation.insufficientFunds, { timeout: 5000 })

    // Submit button should be disabled
    await expect(page.getByTestId('transfer-submit-btn')).toBeDisabled()

    await page.context().close()
  })

  // ===========================================================
  // 5. Member external transfer above threshold → pending
  // ===========================================================
  test('member external transfer above threshold becomes pending', async ({ browser }) => {
    const member = await loginViaApi('member@example.com', 'password')
    const page = await authenticatedPage(browser, member)

    await goToCreateTransfer(page)

    // Switch to external transfer
    await page.getByTestId('transfer-type-external').click()

    await selectFirstAvailableWallet(page, 'transfer-sender-wallet')

    // Fill external fields
    await page.getByTestId('transfer-external-name').locator('input').fill('John External')
    await page.getByTestId('transfer-external-address').locator('input').fill('bc1qext123456789')

    // Amount above threshold
    await page.getByTestId('transfer-amount').locator('input').fill('5000')
    await page.getByTestId('transfer-reference').locator('input').fill('External above threshold')

    // Should see threshold warning
    await expect(page.getByTestId('transfer-threshold-warning')).toBeVisible({ timeout: 5000 })

    await submitTransfer(page)

    // Verify pending
    await page.goto('/transactions?status=pending_approval')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
    await expect(page.getByTestId('data-table').getByRole('row', { name: 'John External' })).toBeVisible({ timeout: 10_000 })

    await page.context().close()
  })

  // ===========================================================
  // 6. Manager approves a pending transaction
  // ===========================================================
  test('manager can approve a pending transaction', async ({ browser }) => {
    const manager = await loginViaApi('manager@example.com', 'password')
    const page = await authenticatedPage(browser, manager)

    await page.goto('/transactions?status=pending_approval')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 10_000 })
    const firstRow = page.getByTestId('data-table').getByRole('row').nth(1)
    await expect(firstRow).toBeVisible({ timeout: 10_000 })

    // Open detail modal by clicking the row
    await firstRow.click()
    await expect(page.getByTestId('approve-btn')).toBeVisible({ timeout: 5000 })
    await expect(page.getByTestId('reject-btn')).toBeVisible({ timeout: 5000 })

    // Approve
    await page.getByTestId('approve-btn').click()
    await expect(page.getByTestId('approve-btn')).not.toBeVisible({ timeout: 10_000 })

    await page.context().close()
  })

  // ===========================================================
  // 7. Manager rejects a pending transaction with reason
  // ===========================================================
  test('manager can reject a pending transaction with reason', async ({ browser }) => {
    const manager = await loginViaApi('manager@example.com', 'password')
    const page = await authenticatedPage(browser, manager)

    await page.goto('/transactions?status=pending_approval')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 10_000 })
    const firstRow = page.getByTestId('data-table').getByRole('row').nth(1)
    await expect(firstRow).toBeVisible({ timeout: 10_000 })

    // Open detail modal by clicking the row
    await firstRow.click()
    await expect(page.getByTestId('reject-btn')).toBeVisible({ timeout: 5000 })

    // Click reject, enter reason, confirm
    await page.getByTestId('reject-btn').click()
    await expect(page.getByTestId('reject-reason-input')).toBeVisible({ timeout: 5000 })
    const textarea = page.getByTestId('reject-reason-input').locator('textarea').first()
    await textarea.click()
    // Use evaluate to set value + dispatch native input event for Vue v-model
    await textarea.evaluate(el => {
      const t = el as HTMLTextAreaElement
      const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
        globalThis.HTMLTextAreaElement.prototype, 'value',
      )!.set!
      nativeInputValueSetter.call(t, 'Budget exceeded')
      t.dispatchEvent(new Event('input', { bubbles: true }))
    })

    // Wait for confirm button to become enabled after v-model updates
    const confirmRejectBtn = page.getByTestId('confirm-reject-btn')
    await expect(confirmRejectBtn).toBeEnabled({ timeout: 5000 })
    await confirmRejectBtn.click()
    await expect(confirmRejectBtn).not.toBeVisible({ timeout: 10_000 })

    await page.context().close()
  })
})
