import { expect, test } from '@playwright/test'

test.describe('Authorization - Unauthenticated', () => {
  test.use({ storageState: { cookies: [], origins: [] } })

  test('redirects to login when accessing dashboard', async ({ page }) => {
    await page.goto('/dashboard')

    await expect(page).toHaveURL(/\/auth\/login/, { timeout: 10_000 })
  })

  test('redirects to login when accessing wallets', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page).toHaveURL(/\/auth\/login/, { timeout: 10_000 })
  })

  test('redirects to login when accessing transactions', async ({ page }) => {
    await page.goto('/transactions')

    await expect(page).toHaveURL(/\/auth\/login/, { timeout: 10_000 })
  })

  test('redirects to login when accessing settings', async ({ page }) => {
    await page.goto('/settings/profile')

    await expect(page).toHaveURL(/\/auth\/login/, { timeout: 10_000 })
  })

  test('allows access to login page', async ({ page }) => {
    await page.goto('/auth/login')

    await expect(page.getByTestId('auth-heading')).toBeVisible({ timeout: 10_000 })
  })

  test('allows access to register page', async ({ page }) => {
    await page.goto('/auth/register')

    await expect(page.getByTestId('auth-heading')).toBeVisible({ timeout: 10_000 })
  })
})

test.describe('Authorization - Member Role', () => {
  test.use({ storageState: 'e2e/.auth/member.json' })

  test('cannot see create wallet button on dashboard', async ({ page }) => {
    await page.goto('/dashboard')

    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
    await expect(page.getByTestId('create-wallet-btn')).not.toBeVisible()
  })

  test('cannot see admin actions on wallets page', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 15_000 })
    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

    // Member should not see any edit/delete icons in the table
    await expect(page.getByTestId('edit-btn')).not.toBeVisible()
  })

  test('cannot see add member button on team members page', async ({ page }) => {
    await page.goto('/team-members')

    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
    await expect(page.getByTestId('add-member-btn')).not.toBeVisible()
  })
})
