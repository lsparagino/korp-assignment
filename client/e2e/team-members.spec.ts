import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { expect, test } from '@playwright/test'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const memberState = path.join(__dirname, '.auth', 'member.json')

test.describe('Team Members (Admin)', () => {
  test('shows the team members page with table', async ({ page }) => {
    await page.goto('/team-members')

    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
    await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })

    // Table should have rows
    await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 10_000 })
  })

  test('shows add member button for admin', async ({ page }) => {
    await page.goto('/team-members')

    await expect(page.getByTestId('add-member-btn')).toBeVisible({ timeout: 10_000 })
  })

  test('can open add member modal', async ({ page }) => {
    await page.goto('/team-members')

    await page.getByTestId('add-member-btn').click()

    // Modal should appear
    await expect(page.getByTestId('member-dialog')).toBeVisible({ timeout: 5000 })
  })
})

test.describe('Team Members (Member)', () => {
  test.use({ storageState: memberState })

  test('does not show add member button', async ({ page }) => {
    await page.goto('/team-members')

    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    await expect(page.getByTestId('add-member-btn')).not.toBeVisible()
  })

  test('member can see team members table', async ({ page }) => {
    await page.goto('/team-members')

    await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })
    await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 10_000 })
  })
})
