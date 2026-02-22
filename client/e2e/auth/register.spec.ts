import { expect, test } from '@playwright/test'

test.describe('Register', () => {
  test.use({ storageState: { cookies: [], origins: [] } })

  test('can register a new account', async ({ page }) => {
    const uniqueEmail = `e2e-${Date.now()}@example.com`

    await page.goto('/auth/register')

    await page.getByLabel('Name').fill('New User')
    await page.getByLabel('Email address').fill(uniqueEmail)
    await page.getByLabel('Password', { exact: true }).fill('Password123!')
    await page.getByLabel('Confirm password').fill('Password123!')
    await page.getByRole('button', { name: 'Create account' }).click()

    // After successful registration, should navigate away from register page
    await expect(page).not.toHaveURL(/\/auth\/register/, { timeout: 15_000 })
  })

  test('shows error for duplicate email', async ({ page }) => {
    await page.goto('/auth/register')

    await page.getByLabel('Name').fill('Admin Duplicate')
    await page.getByLabel('Email address').fill('admin@example.com')
    await page.getByLabel('Password', { exact: true }).fill('Password123!')
    await page.getByLabel('Confirm password').fill('Password123!')
    await page.getByRole('button', { name: 'Create account' }).click()

    // Should show an error about duplicate email
    await expect(page.locator('.v-alert, .v-messages').first()).toBeVisible({ timeout: 10_000 })
  })

  test('can navigate to login page', async ({ page }) => {
    await page.goto('/auth/register')

    await page.getByRole('link', { name: 'Log in' }).click()

    await expect(page).toHaveURL('/auth/login')
  })
})
