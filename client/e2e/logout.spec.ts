import { expect, test } from '@playwright/test'

test.describe('Logout', () => {
    test('can logout via user menu', async ({ page }) => {
        await page.goto('/dashboard')
        await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible({ timeout: 10_000 })

        // Open the user avatar menu
        await page.locator('.v-app-bar .mdi-account').click()

        // Click Disconnect
        await page.getByText('Disconnect').click()

        // Should redirect to login page
        await expect(page).toHaveURL(/\/auth\/login/, { timeout: 10_000 })
    })

    test('after logout, visiting dashboard redirects to login', async ({ page }) => {
        await page.goto('/dashboard')
        await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible({ timeout: 10_000 })

        // Logout
        await page.locator('.v-app-bar .mdi-account').click()
        await page.getByText('Disconnect').click()
        await expect(page).toHaveURL(/\/auth\/login/, { timeout: 10_000 })

        // Try to visit dashboard again
        await page.goto('/dashboard')

        // Should be redirected back to login
        await expect(page).toHaveURL(/\/auth\/login/, { timeout: 10_000 })
    })
})
