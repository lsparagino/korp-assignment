import { test, expect } from '@playwright/test'

test.describe('Dashboard', () => {
    test('shows the dashboard page heading', async ({ page }) => {
        await page.goto('/dashboard')

        await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible({ timeout: 10_000 })
    })

    test('shows top performing wallets section', async ({ page }) => {
        await page.goto('/dashboard')

        await expect(page.getByText('Top Performing Wallets')).toBeVisible({ timeout: 15_000 })
    })

    test('shows recent transactions section', async ({ page }) => {
        await page.goto('/dashboard')

        await expect(page.getByText('Recent Transactions')).toBeVisible({ timeout: 15_000 })
    })

    test('admin sees create wallet button', async ({ page }) => {
        await page.goto('/dashboard')

        // The "Create Wallet" button in the main content area (not sidebar)
        await expect(page.locator('main').getByRole('link', { name: 'Create Wallet' })).toBeVisible({ timeout: 10_000 })
    })
})
