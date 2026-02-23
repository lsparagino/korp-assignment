import { expect, test } from '@playwright/test'
import { createSecondCompany } from './helpers/api'

test.describe('Company Switcher', () => {
    test('can switch between companies', async ({ page }) => {
        // Ensure a second company exists
        const { company: secondCompany } = await createSecondCompany('admin@example.com', `Company ${Date.now()}`)

        await page.goto('/dashboard')
        await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible({ timeout: 10_000 })

        // The company selector button should be visible in the app bar
        const appBar = page.locator('.v-app-bar')
        const companyButton = appBar.getByText('Acme Corp')
        await expect(companyButton).toBeVisible({ timeout: 10_000 })

        // Click to open the company dropdown
        await companyButton.click()

        // Select the second company from the menu list
        await page.locator('.v-list-item').filter({ hasText: secondCompany.name }).click()

        // The selector label should update to the second company
        await expect(appBar.getByText(secondCompany.name)).toBeVisible({ timeout: 10_000 })

        // Switch back to Acme Corp
        await appBar.getByText(secondCompany.name).click()
        await page.locator('.v-list-item').filter({ hasText: 'Acme Corp' }).click()

        await expect(appBar.getByText('Acme Corp')).toBeVisible({ timeout: 10_000 })
    })
})
