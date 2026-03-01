import { createRequire } from 'node:module'
import { expect, test } from '@playwright/test'

const require = createRequire(import.meta.url)
const en = require('../src/locales/en.json')

test.describe('Pagination', () => {
  test.describe('Transactions pagination', () => {
    test('shows pagination info and controls', async ({ page }) => {
      await page.goto('/transactions')
      await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

      // Wait for data to load
      await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

      // Pagination should show entry count
      await expect(page.getByTestId('pagination-meta')).toBeVisible({ timeout: 10_000 })

      // Per page selector should be visible
      await expect(page.getByTestId('per-page-select')).toBeVisible()
    })

    test('can change per page count', async ({ page }) => {
      await page.goto('/transactions')
      await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

      await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

      // Wait for data to be loaded
      await expect(page.getByTestId('pagination-meta')).toBeVisible({ timeout: 10_000 })

      // Open per-page dropdown
      await page.getByTestId('per-page-select').click()

      // Wait for dropdown menu and select 5
      const overlay = page.locator('.v-overlay--active .v-list')
      await expect(overlay).toBeVisible({ timeout: 5000 })
      await overlay.locator('.v-list-item').filter({ hasText: '5' }).first().click()

      // URL should contain per_page=5
      await expect(page).toHaveURL(/per_page=5/, { timeout: 10_000 })
    })

    test('can navigate to next page', async ({ page }) => {
      // Start with 5 items per page to ensure multiple pages
      await page.goto('/transactions?per_page=5')
      await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

      await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

      // Pagination controls should be visible
      await expect(page.getByTestId('pagination-nav')).toBeVisible({ timeout: 10_000 })

      // Click page 2 button
      await page.getByTestId('pagination-nav').getByRole('button', { name: '2' }).click()

      // URL should contain page=2
      await expect(page).toHaveURL(/page=2/, { timeout: 10_000 })

      // Pagination meta should update
      await expect(page.getByTestId('pagination-meta')).toContainText('6', { timeout: 10_000 })
    })

    test('page resets to 1 when filter changes', async ({ page }) => {
      // Start on page 2
      await page.goto('/transactions?per_page=5&page=2')
      await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

      // Apply a filter — open type select and pick Debit
      await page.getByTestId('type-select').click()
      await page.getByRole('option', { name: en.transactions.typeDebit }).click()
      await page.getByTestId('filter-btn').click()

      // Page should reset to 1
      await expect(page).toHaveURL(/page=1/, { timeout: 10_000 })
    })
  })

  test.describe('Wallets pagination', () => {
    test('shows pagination info', async ({ page }) => {
      await page.goto('/wallets')
      await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

      await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

      // Pagination should show entry count
      await expect(page.getByTestId('pagination-meta')).toBeVisible({ timeout: 10_000 })
    })

    test('can change per page on wallets', async ({ page }) => {
      await page.goto('/wallets')
      await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

      // Open per-page dropdown
      await page.getByTestId('per-page-select').click()

      // Wait for dropdown and select 5
      const overlay = page.locator('.v-overlay--active .v-list')
      await expect(overlay).toBeVisible({ timeout: 5000 })
      await overlay.locator('.v-list-item').filter({ hasText: '5' }).first().click()

      // URL should contain per_page=5
      await expect(page).toHaveURL(/per_page=5/, { timeout: 10_000 })
    })
  })
})
