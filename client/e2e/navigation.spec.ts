import { expect, test } from '@playwright/test'

test.describe('Navigation', () => {
  test('sidebar links navigate correctly', async ({ page }) => {
    await page.goto('/dashboard')
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible({ timeout: 10_000 })

    const sidebar = page.locator('.v-navigation-drawer')

    // Navigate to Wallets
    await sidebar.getByText('Wallets', { exact: true }).click()
    await expect(page).toHaveURL(/\/wallets/, { timeout: 10_000 })
    await expect(page.getByRole('heading', { name: 'Wallets' })).toBeVisible({ timeout: 10_000 })

    // Navigate to Transactions
    await sidebar.getByText('Transactions').click()
    await expect(page).toHaveURL(/\/transactions/, { timeout: 10_000 })
    await expect(page.getByRole('heading', { name: 'Transactions' })).toBeVisible({ timeout: 10_000 })

    // Navigate to Team Members
    await sidebar.getByText('Team Members').click()
    await expect(page).toHaveURL(/\/team-members/, { timeout: 10_000 })
    await expect(page.getByRole('heading', { name: 'Team Members' })).toBeVisible({ timeout: 10_000 })

    // Navigate back to Dashboard
    await sidebar.getByText('Dashboard').click()
    await expect(page).toHaveURL(/\/dashboard/, { timeout: 10_000 })
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible({ timeout: 10_000 })
  })

  test('user menu navigates to settings', async ({ page }) => {
    await page.goto('/dashboard')
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible({ timeout: 10_000 })

    // Open the user avatar menu
    await page.locator('.v-app-bar .mdi-account').click()

    // Wait for menu to open and click Settings
    await page.getByText('Settings').click()

    await expect(page).toHaveURL(/\/settings\/profile/, { timeout: 10_000 })
  })

  test('sidebar create wallet button works', async ({ page }) => {
    await page.goto('/dashboard')
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible({ timeout: 10_000 })

    // Click the Create Wallet button in the sidebar
    await page.locator('.v-navigation-drawer').getByText('Create Wallet').click()

    await expect(page).toHaveURL(/\/wallets\/create/, { timeout: 10_000 })
  })
})
