import { expect, test } from '@playwright/test'

test.describe('Pagination', () => {
  test.describe('Transactions pagination', () => {
    test('shows pagination info and controls', async ({ page }) => {
      await page.goto('/transactions')
      await expect(page.getByRole('heading', { name: 'Transactions' })).toBeVisible({ timeout: 10_000 })

      // Wait for data to load
      const table = page.locator('table').first()
      await expect(table).toBeVisible({ timeout: 15_000 })

      // Pagination should show entry count
      await expect(page.getByText(/Showing \d+ to \d+ of \d+ entries/)).toBeVisible({ timeout: 10_000 })

      // Per page selector should be visible
      await expect(page.getByText('Per page:')).toBeVisible()
    })

    test('can change per page count', async ({ page }) => {
      await page.goto('/transactions')
      await expect(page.getByRole('heading', { name: 'Transactions' })).toBeVisible({ timeout: 10_000 })

      const table = page.locator('table').first()
      await expect(table).toBeVisible({ timeout: 15_000 })

      // Wait for data to be loaded
      await expect(page.getByText(/Showing \d+ to \d+ of \d+ entries/)).toBeVisible({ timeout: 10_000 })

      // Open per-page dropdown
      const perPageContainer = page.locator('.page-size-selector')
      await perPageContainer.click()

      // Wait for dropdown menu and select 5
      const listbox = page.getByRole('listbox')
      await expect(listbox).toBeVisible({ timeout: 5000 })
      await listbox.getByRole('option', { name: '5', exact: true }).click()

      // URL should contain per_page=5
      await expect(page).toHaveURL(/per_page=5/, { timeout: 10_000 })
    })

    test('can navigate to next page', async ({ page }) => {
      // Start with 5 items per page to ensure multiple pages
      await page.goto('/transactions?per_page=5')
      await expect(page.getByRole('heading', { name: 'Transactions' })).toBeVisible({ timeout: 10_000 })

      const table = page.locator('table').first()
      await expect(table).toBeVisible({ timeout: 15_000 })

      // Pagination controls should be visible
      const pagination = page.locator('.v-pagination')
      await expect(pagination).toBeVisible({ timeout: 10_000 })

      // Click page 2 button
      await pagination.getByRole('button', { name: '2' }).click()

      // URL should contain page=2
      await expect(page).toHaveURL(/page=2/, { timeout: 10_000 })

      // Should show "Showing 6 to"
      await expect(page.getByText(/Showing 6 to/)).toBeVisible({ timeout: 10_000 })
    })

    test('page resets to 1 when filter changes', async ({ page }) => {
      // Start on page 2
      await page.goto('/transactions?per_page=5&page=2')
      await expect(page.getByRole('heading', { name: 'Transactions' })).toBeVisible({ timeout: 10_000 })

      // Apply a filter — the Type select is the last v-select in the filter row
      const typeSelect = page.locator('.v-card-text .v-select').first()
      await typeSelect.click()
      await page.getByRole('option', { name: 'Debit' }).click()
      await page.getByRole('button', { name: 'Filter', exact: true }).click()

      // Page should reset to 1
      await expect(page).toHaveURL(/page=1/, { timeout: 10_000 })
    })
  })

  test.describe('Wallets pagination', () => {
    test('shows pagination info', async ({ page }) => {
      await page.goto('/wallets')
      await expect(page.getByRole('heading', { name: 'Wallets' })).toBeVisible({ timeout: 10_000 })

      await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })

      // Pagination should show entry count
      await expect(page.getByText(/Showing \d+ to \d+ of \d+ entries/)).toBeVisible({ timeout: 10_000 })
    })

    test('can change per page on wallets', async ({ page }) => {
      await page.goto('/wallets')
      await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })

      // Open per-page dropdown
      const perPageContainer = page.locator('.page-size-selector')
      await perPageContainer.click()

      // Wait for dropdown and select 5
      const listbox = page.getByRole('listbox')
      await expect(listbox).toBeVisible({ timeout: 5000 })
      await listbox.getByRole('option', { name: '5', exact: true }).click()

      // URL should contain per_page=5
      await expect(page).toHaveURL(/per_page=5/, { timeout: 10_000 })
    })
  })
})
