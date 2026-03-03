import { expect, test } from '@playwright/test'
import { createWallet } from './helpers/api'

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

    // Member should not see create wallet button
    await expect(page.getByTestId('create-wallet-btn')).not.toBeVisible()
  })

  test('cannot see add member button on team members page', async ({ page }) => {
    await page.goto('/team-members')

    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
    await expect(page.getByTestId('add-member-btn')).not.toBeVisible()
  })

  test('sees read-only view on team member detail page', async ({ page }) => {
    await page.goto('/team-members')
    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

    // Click on any row to navigate to the detail page
    const row = page.getByTestId('data-table').getByRole('row').nth(1)
    await row.click()

    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Should NOT see edit form inputs or management actions
    await expect(page.getByTestId('member-name-input')).not.toBeVisible()
    await expect(page.getByTestId('member-email-input')).not.toBeVisible()
    await expect(page.getByTestId('member-save-btn')).not.toBeVisible()
    await expect(page.getByTestId('promote-demote-btn')).not.toBeVisible()
    await expect(page.getByTestId('delete-member-btn')).not.toBeVisible()
  })

  test('sees read-only view on wallet detail page', async ({ page }) => {
    // Create a wallet assigned to the member for this test
    const { wallet } = await createWallet({ email: 'member@example.com', name: 'Member Test Wallet' })

    await page.goto(`/wallets/${wallet.id}`)
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Should NOT see edit form inputs or management actions
    await expect(page.getByTestId('wallet-name-input')).not.toBeVisible()
    await expect(page.getByTestId('wallet-currency-select')).not.toBeVisible()
    await expect(page.getByTestId('wallet-save-btn')).not.toBeVisible()
    await expect(page.getByTestId('freeze-btn')).not.toBeVisible()
    await expect(page.getByTestId('delete-wallet-btn')).not.toBeVisible()
  })
})

test.describe('Authorization - Manager Role', () => {
  test.use({ storageState: 'e2e/.auth/manager.json' })

  test('sees read-only view on wallet detail page', async ({ page }) => {
    await page.goto('/wallets')
    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

    // Click on any row to navigate to the detail page
    const row = page.getByTestId('data-table').getByRole('row').nth(1)
    await row.click()

    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Should NOT see edit form inputs or management actions
    await expect(page.getByTestId('wallet-name-input')).not.toBeVisible()
    await expect(page.getByTestId('wallet-currency-select')).not.toBeVisible()
    await expect(page.getByTestId('wallet-save-btn')).not.toBeVisible()
    await expect(page.getByTestId('freeze-btn')).not.toBeVisible()
    await expect(page.getByTestId('delete-wallet-btn')).not.toBeVisible()
  })
})
