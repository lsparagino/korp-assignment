import { expect, test } from '@playwright/test'

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
    await expect(page.getByTestId('clear-btn')).toBeVisible()
  })

  test('has advanced filters section', async ({ page }) => {
    await page.goto('/transactions')

    await expect(page.getByTestId('advanced-filters')).toBeVisible({ timeout: 10_000 })
  })
})
