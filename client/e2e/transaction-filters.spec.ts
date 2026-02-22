import { expect, test } from '@playwright/test'

test.describe('Transaction Filters', () => {
  test('can filter by transaction type', async ({ page }) => {
    await page.goto('/transactions')
    await expect(page.getByRole('heading', { name: 'Transactions' })).toBeVisible({ timeout: 10_000 })

    // Open the Type dropdown and select 'Debit'
    const typeSelect = page.locator('.v-select').first()
    await typeSelect.click()
    await page.getByRole('option', { name: 'Debit' }).click()

    // Click Filter button
    await page.getByRole('button', { name: 'Filter', exact: true }).click()

    // URL should contain ?type=debit
    await expect(page).toHaveURL(/type=debit/, { timeout: 10_000 })

    // Active filter count badge should appear
    await expect(page.getByText('1 active')).toBeVisible({ timeout: 5000 })

    // All visible transaction type chips should say Debit
    const table = page.locator('table').first()
    await expect(table).toBeVisible({ timeout: 15_000 })

    // Check that the table has transactions (or is empty)
    const rows = table.locator('tbody tr')
    const rowCount = await rows.count()
    if (rowCount > 0) {
      // Verify all visible type chips say "debit"
      for (let i = 0; i < Math.min(rowCount, 5); i++) {
        const typeCell = rows.nth(i).locator('.v-chip', { hasText: /debit/i })
        await expect(typeCell).toBeVisible()
      }
    }
  })

  test('can clear filters', async ({ page }) => {
    // Start with a filter applied
    await page.goto('/transactions?type=debit')
    await expect(page.getByRole('heading', { name: 'Transactions' })).toBeVisible({ timeout: 10_000 })
    await expect(page.getByText('1 active')).toBeVisible({ timeout: 5000 })

    // Click Clear
    await page.getByRole('button', { name: 'Clear' }).click()

    // URL should no longer contain the filter
    await expect(page).not.toHaveURL(/type=/, { timeout: 10_000 })

    // Active count badge should disappear
    await expect(page.getByText('active')).not.toBeVisible()
  })

  test('can filter by type Credit and see results', async ({ page }) => {
    await page.goto('/transactions')
    await expect(page.getByRole('heading', { name: 'Transactions' })).toBeVisible({ timeout: 10_000 })

    // Select Credit type
    const typeSelect = page.locator('.v-select').first()
    await typeSelect.click()
    await page.getByRole('option', { name: 'Credit' }).click()

    await page.getByRole('button', { name: 'Filter', exact: true }).click()

    await expect(page).toHaveURL(/type=credit/, { timeout: 10_000 })
    await expect(page.getByText('1 active')).toBeVisible({ timeout: 5000 })
  })

  test('advanced filters work with amount range', async ({ page }) => {
    await page.goto('/transactions')
    await expect(page.getByRole('heading', { name: 'Transactions' })).toBeVisible({ timeout: 10_000 })

    // Open advanced filters
    await page.locator('.v-expansion-panel-title').click()
    await expect(page.getByText('Amount Range')).toBeVisible({ timeout: 5000 })

    // Set min amount
    await page.getByPlaceholder('Min').fill('100')

    // Click Filter
    await page.getByRole('button', { name: 'Filter', exact: true }).click()

    // URL should contain amount_min
    await expect(page).toHaveURL(/amount_min=100/, { timeout: 10_000 })
  })

  test('filter persists type in URL', async ({ page }) => {
    // Navigate with type filter already in URL
    await page.goto('/transactions?type=credit')
    await expect(page.getByRole('heading', { name: 'Transactions' })).toBeVisible({ timeout: 10_000 })

    // The type dropdown should reflect the filter
    await expect(page.locator('.v-select').first()).toContainText('Credit')
  })
})
