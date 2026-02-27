import { expect, test } from '@playwright/test'

test.describe('Transaction Filters', () => {
  test('can filter by transaction type', async ({ page }) => {
    await page.goto('/transactions')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Open the Type dropdown and select 'Debit'
    await page.getByTestId('type-select').click()
    await page.locator('.v-overlay .v-list-item').filter({ hasText: 'Debit' }).click()

    // Click Filter button
    await page.getByTestId('filter-btn').click()

    // URL should contain ?type=debit
    await expect(page).toHaveURL(/type=debit/, { timeout: 10_000 })

    // Active filter count badge should appear
    await expect(page.getByTestId('active-filters-badge')).toBeVisible({ timeout: 5000 })

    // Table should be visible with results
    const table = page.locator('table').first()
    await expect(table).toBeVisible({ timeout: 15_000 })
  })

  test('can clear filters', async ({ page }) => {
    // Start with a filter applied
    await page.goto('/transactions?type=debit')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
    await expect(page.getByTestId('active-filters-badge')).toBeVisible({ timeout: 5000 })

    // Click Clear
    await page.getByTestId('clear-btn').click()

    // URL should no longer contain the filter
    await expect(page).not.toHaveURL(/type=/, { timeout: 10_000 })

    // Active count badge should disappear
    await expect(page.getByTestId('active-filters-badge')).not.toBeVisible()
  })

  test('can filter by type Credit and see results', async ({ page }) => {
    await page.goto('/transactions')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Select Credit type
    await page.getByTestId('type-select').click()
    await page.locator('.v-overlay .v-list-item').filter({ hasText: 'Credit' }).click()

    await page.getByTestId('filter-btn').click()

    await expect(page).toHaveURL(/type=credit/, { timeout: 10_000 })
    await expect(page.getByTestId('active-filters-badge')).toBeVisible({ timeout: 5000 })
  })

  test('advanced filters work with amount range', async ({ page }) => {
    await page.goto('/transactions')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Open advanced filters
    await page.getByTestId('advanced-filters').locator('.v-expansion-panel-title').click()

    // Wait for expansion to complete
    await expect(page.getByTestId('amount-min-input')).toBeVisible({ timeout: 5000 })

    // Set min amount
    await page.getByTestId('amount-min-input').locator('input').fill('100')

    // Click Filter
    await page.getByTestId('filter-btn').click()

    // URL should contain amount_min
    await expect(page).toHaveURL(/amount_min=100/, { timeout: 10_000 })
  })

  test('filter persists type in URL', async ({ page }) => {
    // Navigate with type filter already in URL
    await page.goto('/transactions?type=credit')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // The type dropdown should reflect the filter
    await expect(page.getByTestId('type-select')).toContainText('Credit')
  })
})
