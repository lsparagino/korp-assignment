import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { expect, test } from '@playwright/test'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const memberState = path.join(__dirname, '.auth', 'member.json')

test.describe('Wallets (Admin)', () => {
  test('shows the wallets page', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
    await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })
  })

  test('shows create wallet button for admin', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page.getByTestId('create-wallet-btn')).toBeVisible({ timeout: 10_000 })
  })

  test('can navigate to create wallet page', async ({ page }) => {
    await page.goto('/wallets')

    await page.getByTestId('create-wallet-btn').click()

    await expect(page).toHaveURL('/wallets/create')
  })

  test('shows wallet table with data', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })

    // Table should have rows
    await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 10_000 })
  })
})

test.describe('Wallets (Member)', () => {
  test.use({ storageState: memberState })

  test('does not show create wallet button', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
    await expect(page.getByTestId('create-wallet-btn')).not.toBeVisible()
  })

  test('does not show edit/delete actions', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })

    // Member should not see any action icons
    await expect(page.locator('[class*="mdi-pencil"]')).not.toBeVisible()
    await expect(page.locator('[class*="mdi-delete"]')).not.toBeVisible()
  })
})
