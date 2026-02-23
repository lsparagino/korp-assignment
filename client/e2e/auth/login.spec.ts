import { expect, test } from '@playwright/test'

test.describe('Login', () => {
  test.use({ storageState: { cookies: [], origins: [] } })

  test('can log in with valid credentials', async ({ page }) => {
    await page.goto('/auth/login')

    await page.getByTestId('email-input').locator('input').fill('admin@example.com')
    await page.getByTestId('password-input').locator('input').fill('password')
    await page.getByTestId('login-btn').click()

    // After successful login, should navigate away from login page
    await expect(page).not.toHaveURL(/\/auth\/login/, { timeout: 15_000 })
  })

  test('shows error with invalid credentials', async ({ page }) => {
    await page.goto('/auth/login')

    await page.getByTestId('email-input').locator('input').fill('wrong@example.com')
    await page.getByTestId('password-input').locator('input').fill('wrongpassword')
    await page.getByTestId('login-btn').click()

    // Should show an error or stay on login page
    await page.waitForTimeout(3000)
    await expect(page).toHaveURL(/\/auth\/login/)
  })

  test('can navigate to register page', async ({ page }) => {
    await page.goto('/auth/login')

    await page.getByTestId('signup-link').click()

    await expect(page).toHaveURL('/auth/register')
  })

  test('can navigate to forgot password page', async ({ page }) => {
    await page.goto('/auth/login')

    await page.getByTestId('forgot-password-link').click()

    await expect(page).toHaveURL('/auth/forgot-password')
  })
})
