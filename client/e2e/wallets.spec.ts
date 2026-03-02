import { expect, test } from '@playwright/test'

test.describe('Wallets (Admin)', () => {
  test('shows the wallets page', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })
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

    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

    // Table should have rows
    await expect(page.getByTestId('data-table').getByRole('row').nth(1)).toBeVisible({ timeout: 10_000 })
  })

  test('can navigate to wallet detail page by clicking a row', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

    const firstRow = page.getByTestId('data-table').getByRole('row').nth(1)
    await firstRow.click()

    await expect(page).toHaveURL(/\/wallets\/\d+$/, { timeout: 10_000 })
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
  })
})

test.describe('Wallets (Member)', () => {
  test.use({ storageState: 'e2e/.auth/member.json' })

  test('does not show create wallet button', async ({ page }) => {
    await page.goto('/wallets')

    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
    await expect(page.getByTestId('create-wallet-btn')).not.toBeVisible()
  })
})
