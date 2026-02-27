import { expect, test } from '@playwright/test'

test.describe('Dashboard', () => {
  test('shows the dashboard page heading', async ({ page }) => {
    await page.goto('/dashboard')

    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
  })

  test('shows top performing wallets section', async ({ page }) => {
    await page.goto('/dashboard')

    await expect(page.getByTestId('top-wallets-section')).toBeVisible({ timeout: 15_000 })
  })

  test('shows recent transactions section', async ({ page }) => {
    await page.goto('/dashboard')

    await expect(page.getByTestId('recent-transactions-section')).toBeVisible({ timeout: 15_000 })
  })
})
