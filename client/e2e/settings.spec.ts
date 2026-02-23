import { expect, test } from '@playwright/test'

test.describe('Settings - Profile', () => {
  test('shows profile settings page', async ({ page }) => {
    await page.goto('/settings/profile')

    await expect(page.getByTestId('heading-title').first()).toBeVisible({ timeout: 10_000 })
    await expect(page.getByTestId('profile-name-input')).toBeVisible()
  })

  test('shows delete account section', async ({ page }) => {
    await page.goto('/settings/profile')

    await expect(page.getByTestId('delete-account-btn')).toBeVisible({ timeout: 10_000 })
  })

  test('can open delete account dialog', async ({ page }) => {
    await page.goto('/settings/profile')

    await expect(page.getByTestId('delete-account-btn')).toBeVisible({ timeout: 10_000 })

    await page.getByTestId('delete-account-btn').click()

    await expect(page.getByTestId('delete-account-dialog')).toBeVisible({ timeout: 5000 })
  })
})

test.describe('Settings - Password', () => {
  test('shows password settings page', async ({ page }) => {
    await page.goto('/settings/password')

    await expect(page.getByTestId('current-password-input')).toBeVisible({ timeout: 10_000 })
  })
})

test.describe('Settings - Two Factor', () => {
  test('shows two factor settings page', async ({ page }) => {
    await page.goto('/settings/two-factor')

    await expect(page.getByTestId('two-factor-section')).toBeVisible({ timeout: 10_000 })
  })
})
