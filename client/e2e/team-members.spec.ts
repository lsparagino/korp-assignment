import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { expect, test } from '@playwright/test'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const memberState = path.join(__dirname, '.auth', 'member.json')

test.describe('Team Members (Admin)', () => {
  test('shows the team members page', async ({ page }) => {
    await page.goto('/team-members')

    await expect(page.getByRole('heading', { name: 'Team Members' })).toBeVisible({ timeout: 10_000 })
    await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })

    // Should show table headers
    await expect(page.locator('thead').getByText('NAME')).toBeVisible()
    await expect(page.locator('thead').getByText('EMAIL')).toBeVisible()
    await expect(page.locator('thead').getByText('ROLE')).toBeVisible()
    await expect(page.locator('thead').getByText('WALLET ACCESS')).toBeVisible()
    await expect(page.locator('thead').getByText('ACTIONS')).toBeVisible()
  })

  test('shows add member button for admin', async ({ page }) => {
    await page.goto('/team-members')

    await expect(page.getByRole('button', { name: 'Add Member' })).toBeVisible({ timeout: 10_000 })
  })

  test('can open add member modal', async ({ page }) => {
    await page.goto('/team-members')

    await page.getByRole('button', { name: 'Add Member' }).click()

    // Modal should appear
    await expect(page.getByRole('dialog')).toBeVisible({ timeout: 5000 })
  })
})

test.describe('Team Members (Member)', () => {
  test.use({ storageState: memberState })

  test('does not show add member button', async ({ page }) => {
    await page.goto('/team-members')

    await expect(page.getByRole('heading', { name: 'Team Members' })).toBeVisible({ timeout: 10_000 })

    await expect(page.getByRole('button', { name: 'Add Member' })).not.toBeVisible()
  })

  test('does not show actions column', async ({ page }) => {
    await page.goto('/team-members')

    await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })

    // Member should NOT see Actions column
    const actionsHeader = page.locator('thead').getByText('ACTIONS')
    await expect(actionsHeader).not.toBeVisible()
  })
})
