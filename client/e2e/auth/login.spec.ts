import { expect, test } from '@playwright/test'

test.describe('Login', () => {
  test.use({ storageState: { cookies: [], origins: [] } })

  test('can log in with valid credentials', async ({ page }) => {
    await page.goto('/auth/login')

    await page.getByLabel('Email address').fill('admin@example.com')
    await page.getByPlaceholder('Password').fill('password')
    await page.getByRole('button', { name: 'Log in' }).click()

    // After successful login, should navigate away from login page
    await expect(page).not.toHaveURL(/\/auth\/login/, { timeout: 15_000 })
  })

  test('shows error with invalid credentials', async ({ page }) => {
    await page.goto('/auth/login')

    await page.getByLabel('Email address').fill('wrong@example.com')
    await page.getByPlaceholder('Password').fill('wrongpassword')
    await page.getByRole('button', { name: 'Log in' }).click()

    // Should show an error or stay on login page
    await page.waitForTimeout(3000)
    await expect(page).toHaveURL(/\/auth\/login/)
  })

  test('can navigate to register page', async ({ page }) => {
    await page.goto('/auth/login')

    await page.getByRole('link', { name: 'Sign up' }).click()

    await expect(page).toHaveURL('/auth/register')
  })

  test('can navigate to forgot password page', async ({ page }) => {
    await page.goto('/auth/login')

    await page.getByRole('link', { name: 'Forgot password?' }).click()

    await expect(page).toHaveURL('/auth/forgot-password')
  })
})
