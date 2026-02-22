import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { expect, test } from '@playwright/test'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const memberState = path.join(__dirname, '.auth', 'member.json')

test.describe('Wallets (Admin)', () => {
  test('shows the wallets page', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page.getByRole('heading', { name: 'Wallets' })).toBeVisible({ timeout: 10_000 })
    await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })
  })

  test('shows create wallet button for admin', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page.locator('main').getByRole('link', { name: 'Create Wallet' })).toBeVisible({ timeout: 10_000 })
  })

  test('can navigate to create wallet page', async ({ page }) => {
    await page.goto('/wallets')

    await page.locator('main').getByRole('link', { name: 'Create Wallet' }).click()

    await expect(page).toHaveURL('/wallets/create')
  })

  test('shows wallet table headers', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })

    await expect(page.locator('thead').getByText('NAME')).toBeVisible()
    await expect(page.locator('thead').getByText('BALANCE')).toBeVisible()
    await expect(page.locator('thead').getByText('CURRENCY')).toBeVisible()
    await expect(page.locator('thead').getByText('STATUS')).toBeVisible()
    await expect(page.locator('thead').getByText('ACTIONS')).toBeVisible()
  })
})

test.describe('Wallets (Member)', () => {
  test.use({ storageState: memberState })

  test('does not show create wallet button', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page.getByRole('heading', { name: 'Wallets' })).toBeVisible({ timeout: 10_000 })
    await expect(page.locator('main').getByRole('link', { name: 'Create Wallet' })).not.toBeVisible()
  })

  test('does not show actions column', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })

    const actionsHeader = page.locator('thead').getByText('ACTIONS')
    await expect(actionsHeader).not.toBeVisible()
  })
})
