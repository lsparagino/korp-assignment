import { expect, test } from '@playwright/test'

test.describe('Transactions', () => {
  test('shows the transactions page', async ({ page }) => {
    await page.goto('/transactions')

    await expect(page.getByRole('heading', { name: 'Transactions' })).toBeVisible({ timeout: 10_000 })
  })

  test('shows filter options', async ({ page }) => {
    await page.goto('/transactions')

    await expect(page.getByText('Filter Options')).toBeVisible({ timeout: 10_000 })
  })

  test('shows filter and clear buttons', async ({ page }) => {
    await page.goto('/transactions')

    await expect(page.getByRole('button', { name: 'Filter', exact: true })).toBeVisible({ timeout: 10_000 })
    await expect(page.getByRole('button', { name: 'Clear' })).toBeVisible()
  })

  test('has advanced filters section', async ({ page }) => {
    await page.goto('/transactions')

    // The expansion panel for advanced filters should be present
    await expect(page.locator('.v-expansion-panel')).toBeVisible({ timeout: 10_000 })
  })
})
