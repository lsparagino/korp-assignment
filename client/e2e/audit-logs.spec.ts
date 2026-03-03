import { createRequire } from 'node:module'
import { expect, test } from '@playwright/test'

const require = createRequire(import.meta.url)
const en = require('../src/locales/en.json')

test.describe('Audit Logs', () => {
    test('shows the audit logs page with heading and filter card', async ({ page }) => {
        await page.goto('/audit-logs')

        await expect(page.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })
        await expect(page.getByTestId('audit-filter-card')).toBeVisible({ timeout: 10_000 })
    })

    test('shows data table', async ({ page }) => {
        await page.goto('/audit-logs')

        await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })
    })

    test('can filter by category', async ({ page }) => {
        await page.goto('/audit-logs')

        await expect(page.getByTestId('audit-category-select')).toBeVisible({ timeout: 10_000 })

        // Select "Auth" category
        await page.getByTestId('audit-category-select').click()
        await page.getByRole('option', { name: en.auditLogs.categories.auth }).click()

        // Click Filter
        await page.getByTestId('audit-filter-btn').click()

        // Wait for results to refresh
        await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })
    })

    test('can filter by severity', async ({ page }) => {
        await page.goto('/audit-logs')

        await expect(page.getByTestId('audit-severity-select')).toBeVisible({ timeout: 10_000 })

        // Select "Normal" severity
        await page.getByTestId('audit-severity-select').click()
        await page.getByRole('option', { name: en.auditLogs.severities.normal }).click()

        // Click Filter
        await page.getByTestId('audit-filter-btn').click()

        // Wait for results to refresh
        await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })
    })

    test('can clear filters', async ({ page }) => {
        await page.goto('/audit-logs')

        // Apply a category filter first
        await page.getByTestId('audit-category-select').click()
        await page.getByRole('option', { name: en.auditLogs.categories.auth }).click()
        await page.getByTestId('audit-filter-btn').click()
        await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

        // Clear filters
        await page.getByTestId('audit-clear-btn').click()

        // Table should still be visible after clearing
        await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })
    })

    test('load more button fetches additional data', async ({ page }) => {
        await page.goto('/audit-logs')

        await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

        // Count initial rows
        const initialRows = await page.getByTestId('data-table').locator('tbody tr').count()

        // If load more button is visible, click it and verify more rows appear
        const loadMoreBtn = page.getByTestId('audit-load-more')
        if (await loadMoreBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
            await loadMoreBtn.click()

            // Wait for loading to finish
            await expect(loadMoreBtn).not.toHaveAttribute('loading', { timeout: 10_000 })

            const newRows = await page.getByTestId('data-table').locator('tbody tr').count()
            expect(newRows).toBeGreaterThan(initialRows)
        }
    })

    test('can open log detail modal when entries exist', async ({ page }) => {
        await page.goto('/audit-logs')

        await expect(page.getByTestId('data-table')).toBeVisible({ timeout: 15_000 })

        // Only test if there are entries with a detail button
        const detailBtn = page.getByTestId('audit-detail-btn').first()
        if (await detailBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
            await detailBtn.click()

            // Modal should appear
            await expect(page.locator('.v-dialog')).toBeVisible({ timeout: 5000 })
        }
    })
})
