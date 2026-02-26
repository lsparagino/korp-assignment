import { createRequire } from 'node:module'
import { expect, test } from '@playwright/test'

const require = createRequire(import.meta.url)
const en = require('../src/locales/en.json')

test.describe('Transactions', () => {
  test('shows the transactions page', async ({ page }) => {
    await page.goto('/transactions')

    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
  })

  test('shows filter options card', async ({ page }) => {
    await page.goto('/transactions')

    await expect(page.getByTestId('filter-options-card')).toBeVisible({ timeout: 10_000 })
  })

  test('shows filter and clear buttons', async ({ page }) => {
    await page.goto('/transactions')

    await expect(page.getByTestId('filter-btn')).toBeVisible({ timeout: 10_000 })

    // Clear button only appears when filters are active
    // Select a type filter to activate filters, then click Filter
    await page.getByTestId('type-select').click()
    await page.locator('.v-overlay .v-list-item').filter({ hasText: en.transactions.typeDebit }).click()
    await page.getByTestId('filter-btn').click()
    await expect(page.getByTestId('clear-btn')).toBeVisible({ timeout: 10_000 })
  })

  test('has advanced filters section', async ({ page }) => {
    await page.goto('/transactions')

    await expect(page.getByTestId('advanced-filters')).toBeVisible({ timeout: 10_000 })
  })
})
