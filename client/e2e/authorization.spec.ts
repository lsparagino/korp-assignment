import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { expect, test } from '@playwright/test'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const memberState = path.join(__dirname, '.auth', 'member.json')

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

    await expect(page.getByRole('heading', { name: /log in/i })).toBeVisible({ timeout: 10_000 })
  })

  test('allows access to register page', async ({ page }) => {
    await page.goto('/auth/register')

    await expect(page.getByRole('heading', { name: /create an account/i })).toBeVisible({ timeout: 10_000 })
  })
})

test.describe('Authorization - Member Role', () => {
  test.use({ storageState: memberState })

  test('cannot see create wallet button on dashboard', async ({ page }) => {
    await page.goto('/dashboard')

    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible({ timeout: 10_000 })
    await expect(page.locator('main').getByRole('link', { name: 'Create Wallet' })).not.toBeVisible()
  })

  test('cannot see admin actions on wallets page', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page.getByRole('heading', { name: 'Wallets' })).toBeVisible({ timeout: 15_000 })

    const actionsHeader = page.locator('thead').getByText('Actions')
    await expect(actionsHeader).not.toBeVisible()
  })

  test('cannot see add member button on team members page', async ({ page }) => {
    await page.goto('/team-members')

    await expect(page.getByRole('heading', { name: 'Team Members' })).toBeVisible({ timeout: 10_000 })
    await expect(page.getByRole('button', { name: 'Add Member' })).not.toBeVisible()
  })
})
