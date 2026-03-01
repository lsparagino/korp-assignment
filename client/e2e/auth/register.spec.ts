import { expect, test } from '@playwright/test'

test.describe('Register', () => {
  test.use({ storageState: { cookies: [], origins: [] } })

  test('can register a new account', async ({ page }) => {
    const uniqueEmail = `e2e-${Date.now()}@example.com`

    await page.goto('/auth/register')

    await page.getByTestId('name-input').locator('input').fill('New User')
    await page.getByTestId('email-input').locator('input').fill(uniqueEmail)
    await page.getByTestId('password-input').locator('input').fill('Password123!')
    await page.getByTestId('password-confirm-input').locator('input').fill('Password123!')
    await page.getByTestId('register-btn').click()

    // After successful registration, should navigate away from register page
    await expect(page).not.toHaveURL(/\/auth\/register/, { timeout: 15_000 })
  })

  test('shows error for duplicate email', async ({ page }) => {
    await page.goto('/auth/register')

    await page.getByTestId('name-input').locator('input').fill('Admin Duplicate')
    await page.getByTestId('email-input').locator('input').fill('admin@example.com')
    await page.getByTestId('password-input').locator('input').fill('Password123!')
    await page.getByTestId('password-confirm-input').locator('input').fill('Password123!')
    await page.getByTestId('register-btn').click()

    // Should show an error about duplicate email
    await expect(page.getByTestId('error-alert')).toBeVisible({ timeout: 10_000 })
  })

  test('can navigate to login page', async ({ page }) => {
    await page.goto('/auth/register')

    await page.getByTestId('login-link').click()

    await expect(page).toHaveURL('/auth/login')
  })
})
