import { expect, test } from '@playwright/test'
import { createSecondCompany, loginViaApi } from './helpers/api'

test.describe('Company Switcher', () => {
  test('can switch between companies', async ({ page }) => {
    // Get the first company name dynamically via login API
    const { company: firstCompany } = await loginViaApi('admin@example.com', 'password')

    // Create a second company
    const { company: secondCompany } = await createSecondCompany('admin@example.com', `Company ${Date.now()}`)

    // Navigate to dashboard and force reload to ensure the company store refetches
    await page.goto('/dashboard')
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
    await page.reload()
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // There are two CompanySelector instances (desktop app bar + mobile sidebar),
    // so we scope to the first visible one.
    const selectorBtn = page.getByTestId('company-selector-btn').first()
    await expect(selectorBtn).toBeVisible({ timeout: 15_000 })

    // Click to open the company dropdown
    await selectorBtn.click()

    // Wait for the overlay list items to appear (Vuetify teleports menu content)
    const secondCompanyItem = page.getByTestId('company-list-item').filter({ hasText: secondCompany.name })
    await expect(secondCompanyItem).toBeVisible({ timeout: 5000 })
    await secondCompanyItem.click()

    // Wait for the switch to complete — the selector should now show the second company
    await expect(selectorBtn).toContainText(secondCompany.name, { timeout: 10_000 })

    // Wait for the page to stabilize after company switch (re-render settles)
    await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

    // Switch back to first company
    await selectorBtn.click()
    const firstCompanyItem = page.getByTestId('company-list-item').filter({ hasText: firstCompany.name })
    await expect(firstCompanyItem).toBeVisible({ timeout: 5000 })
    await firstCompanyItem.click()

    await expect(selectorBtn).toContainText(firstCompany.name, { timeout: 10_000 })
  })
})
