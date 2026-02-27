import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { createRequire } from 'node:module'
import { expect, test } from '@playwright/test'
import { loginViaApi } from './helpers/api'
import { authenticatedPage } from './helpers/auth'

const require = createRequire(import.meta.url)
const en = require('../src/locales/en.json')

const __dirname = path.dirname(fileURLToPath(import.meta.url))

test.describe('Approval Flow', () => {
    test('admin can approve a pending transaction from dashboard', async ({ browser }) => {
        // Step 1: Login as member and initiate a transfer above threshold via UI
        const member = await loginViaApi('member@example.com', 'password')
        const memberPage = await authenticatedPage(browser, member)

        await memberPage.goto('/transactions')
        await expect(memberPage.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

        // Click the new transfer button
        await memberPage.getByTestId('new-transfer-btn').click({ timeout: 5_000 })
        await expect(memberPage.getByTestId('transfer-dialog')).toBeVisible({ timeout: 5_000 })

        // Fill in transfer form — use a large amount to trigger pending approval
        await memberPage.getByTestId('sender-wallet-select').click()
        await memberPage.locator('.v-overlay .v-list-item').first().click()
        await memberPage.getByTestId('receiver-wallet-select').click()
        await memberPage.locator('.v-overlay .v-list-item').first().click()
        await memberPage.getByTestId('amount-input').fill('15000')
        await memberPage.getByTestId('reference-input').fill('E2E approval test')

        await memberPage.getByTestId('submit-transfer-btn').click()

        // Wait for success — dialog should close or show success
        await expect(memberPage.getByTestId('transfer-dialog')).not.toBeVisible({ timeout: 10_000 })
        await memberPage.context().close()

        // Step 2: Login as admin and verify pending section on dashboard
        const admin = await loginViaApi('admin@example.com', 'password')
        const adminPage = await authenticatedPage(browser, admin)

        await adminPage.goto('/dashboard')
        await expect(adminPage.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

        // Should see pending transactions section
        await expect(adminPage.getByTestId('pending-transactions-section')).toBeVisible({ timeout: 15_000 })

        // Click on the eye icon to view details
        const pendingRow = adminPage.getByTestId('pending-transactions-section').locator('tbody tr').first()
        await pendingRow.locator('.v-btn').click()

        // Should see approve and reject buttons
        await expect(adminPage.getByTestId('approve-btn')).toBeVisible({ timeout: 5_000 })
        await expect(adminPage.getByTestId('reject-btn')).toBeVisible({ timeout: 5_000 })

        // Approve the transaction
        await adminPage.getByTestId('approve-btn').click()

        // Dialog should close after approval
        await expect(adminPage.getByTestId('approve-btn')).not.toBeVisible({ timeout: 10_000 })

        await adminPage.context().close()
    })

    test('admin can reject a pending transaction with reason', async ({ browser }) => {
        // Step 1: Login as member and initiate a transfer above threshold
        const member = await loginViaApi('member@example.com', 'password')
        const memberPage = await authenticatedPage(browser, member)

        await memberPage.goto('/transactions')
        await expect(memberPage.getByTestId('page-heading')).toBeVisible({ timeout: 10_000 })

        await memberPage.getByTestId('new-transfer-btn').click({ timeout: 5_000 })
        await expect(memberPage.getByTestId('transfer-dialog')).toBeVisible({ timeout: 5_000 })

        await memberPage.getByTestId('sender-wallet-select').click()
        await memberPage.locator('.v-overlay .v-list-item').first().click()
        await memberPage.getByTestId('receiver-wallet-select').click()
        await memberPage.locator('.v-overlay .v-list-item').first().click()
        await memberPage.getByTestId('amount-input').fill('15000')
        await memberPage.getByTestId('reference-input').fill('E2E reject test')

        await memberPage.getByTestId('submit-transfer-btn').click()
        await expect(memberPage.getByTestId('transfer-dialog')).not.toBeVisible({ timeout: 10_000 })
        await memberPage.context().close()

        // Step 2: Login as admin and reject the pending transaction
        const admin = await loginViaApi('admin@example.com', 'password')
        const adminPage = await authenticatedPage(browser, admin)

        await adminPage.goto('/dashboard')
        await expect(adminPage.getByTestId('pending-transactions-section')).toBeVisible({ timeout: 15_000 })

        // Click on pending transaction details
        const pendingRow = adminPage.getByTestId('pending-transactions-section').locator('tbody tr').first()
        await pendingRow.locator('.v-btn').click()

        await expect(adminPage.getByTestId('reject-btn')).toBeVisible({ timeout: 5_000 })

        // Click reject to show the reason input
        await adminPage.getByTestId('reject-btn').click()
        await expect(adminPage.getByTestId('reject-reason-input')).toBeVisible({ timeout: 5_000 })

        // Fill in the rejection reason
        await adminPage.getByTestId('reject-reason-input').locator('textarea').fill('Insufficient documentation provided')

        // Confirm rejection
        await adminPage.getByTestId('confirm-reject-btn').click()

        // Dialog should close after rejection
        await expect(adminPage.getByTestId('confirm-reject-btn')).not.toBeVisible({ timeout: 10_000 })

        await adminPage.context().close()
    })
})
