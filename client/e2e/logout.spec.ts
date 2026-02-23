import { expect, test } from '@playwright/test'
import { loginViaApi } from './helpers/api'
import { buildStorageState } from './helpers/auth'

/**
 * Logout tests create their own Sanctum token per test so they don't
 * invalidate the shared admin token used by the rest of the suite.
 */
test.describe('Logout', () => {
  test('can logout via user menu', async ({ browser }) => {
    const auth = await loginViaApi('admin@example.com', 'password')
    const context = await browser.newContext({ storageState: buildStorageState(auth) })
    const page = await context.newPage()

    await page.goto('/dashboard')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Open the user avatar menu
    await page.getByTestId('user-menu-btn').click()

    // Click Disconnect
    await page.getByTestId('disconnect-btn').click()

    // Should redirect to login page
    await expect(page).toHaveURL(/\/auth\/login/, { timeout: 10_000 })
  })

  test('after logout, visiting dashboard redirects to login', async ({ browser }) => {
    const auth = await loginViaApi('admin@example.com', 'password')
    const context = await browser.newContext({ storageState: buildStorageState(auth) })
    const page = await context.newPage()

    await page.goto('/dashboard')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Logout
    await page.getByTestId('user-menu-btn').click()
    await page.getByTestId('disconnect-btn').click()
    await expect(page).toHaveURL(/\/auth\/login/, { timeout: 10_000 })

    // Try to visit dashboard again
    await page.goto('/dashboard')

    // Should be redirected back to login
    await expect(page).toHaveURL(/\/auth\/login/, { timeout: 10_000 })
  })
})
