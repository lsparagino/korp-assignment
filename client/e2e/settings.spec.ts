import { expect, test } from '@playwright/test'

test.describe('Settings - Profile', () => {
  test('shows profile settings page', async ({ page }) => {
    await page.goto('/settings/profile')

    await expect(page.getByText('Profile information')).toBeVisible({ timeout: 10_000 })
    await expect(page.getByLabel('Name')).toBeVisible()
    await expect(page.getByLabel('Email address')).toBeVisible()
  })

  test('shows delete account section', async ({ page }) => {
    await page.goto('/settings/profile')

    await expect(page.getByRole('heading', { name: 'Delete account' })).toBeVisible({ timeout: 10_000 })
  })

  test('can open delete account dialog', async ({ page }) => {
    await page.goto('/settings/profile')

    await expect(page.getByRole('heading', { name: 'Delete account' })).toBeVisible({ timeout: 10_000 })

    await page.getByRole('button', { name: 'Delete Account' }).first().click()

    await expect(page.getByRole('dialog')).toBeVisible({ timeout: 5000 })
  })
})

test.describe('Settings - Password', () => {
  test('shows password settings page', async ({ page }) => {
    await page.goto('/settings/password')

    await expect(page.getByText(/password/i).first()).toBeVisible({ timeout: 10_000 })
  })
})

test.describe('Settings - Two Factor', () => {
  test('shows two factor settings page', async ({ page }) => {
    await page.goto('/settings/two-factor')

    await expect(page.getByText(/two.factor/i).first()).toBeVisible({ timeout: 10_000 })
  })
})
