import { expect, test } from '@playwright/test'

test.describe('Navigation', () => {
  test('sidebar links navigate correctly', async ({ page }) => {
    await page.goto('/dashboard')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Navigate to Wallets
    await page.getByTestId('nav-wallets').click()
    await expect(page).toHaveURL(/\/wallets/, { timeout: 10_000 })
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Navigate to Transactions
    await page.getByTestId('nav-transactions').click()
    await expect(page).toHaveURL(/\/transactions/, { timeout: 10_000 })
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Navigate to Team Members
    await page.getByTestId('nav-team-members').click()
    await expect(page).toHaveURL(/\/team-members/, { timeout: 10_000 })
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Navigate back to Dashboard
    await page.getByTestId('nav-dashboard').click()
    await expect(page).toHaveURL(/\/dashboard/, { timeout: 10_000 })
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
  })

  test('user menu navigates to settings', async ({ page }) => {
    await page.goto('/dashboard')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    await page.getByTestId('user-menu-btn').click()
    await page.getByTestId('settings-link').click()

    await expect(page).toHaveURL(/\/settings\/profile/, { timeout: 10_000 })
  })

  test('sidebar create wallet button works', async ({ page }) => {
    await page.goto('/dashboard')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    await page.getByTestId('sidebar-create-wallet-btn').click()

    await expect(page).toHaveURL(/\/wallets\/create/, { timeout: 10_000 })
  })
})
