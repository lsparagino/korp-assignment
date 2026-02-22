import { test, expect } from '@playwright/test'
import path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const memberState = path.join(__dirname, '.auth', 'member.json')

/**
 * Helper: navigates to the create wallet page, fills the name, and submits the form.
 * Returns the wallet name.
 */
async function createWallet(page: import('@playwright/test').Page, name: string) {
    await page.goto('/wallets/create')
    await expect(page.getByLabel('Wallet Name')).toBeVisible({ timeout: 10_000 })
    await page.getByLabel('Wallet Name').fill(name)

    // Click "Create Wallet" button inside the form (inside main, not sidebar)
    const mainArea = page.locator('.v-main')
    await mainArea.getByRole('button', { name: 'Create Wallet' }).click()

    // Wait for redirect back to wallets list
    await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })
    await expect(page.getByText(name)).toBeVisible({ timeout: 10_000 })
}

test.describe('Admin Wallet Lifecycle', () => {
    test.describe.configure({ mode: 'serial' })
    const walletName = `Lifecycle Wallet ${Date.now()}`

    test('admin creates a new wallet', async ({ page }) => {
        await createWallet(page, walletName)
    })

    test('the new wallet has zero balance', async ({ page }) => {
        await page.goto('/wallets')
        await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })

        const row = page.locator('tr', { hasText: walletName })
        await expect(row).toBeVisible({ timeout: 10_000 })

        // The balance column should show $0.00 (USD default)
        await expect(row.getByText('$0.00')).toBeVisible()
    })

    test('the new wallet can be deleted', async ({ page }) => {
        await page.goto('/wallets')
        await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })

        const row = page.locator('tr', { hasText: walletName })
        await expect(row).toBeVisible({ timeout: 10_000 })

        // Click the delete button
        await row.locator('.mdi-delete').first().click()

        // ConfirmDialog should appear with PIN
        const dialog = page.getByRole('dialog')
        await expect(dialog).toBeVisible({ timeout: 5_000 })
        await expect(dialog.getByText('Verification Required')).toBeVisible()

        // Read the PIN and enter it
        const pinText = await dialog.locator('.text-h4').textContent()
        await dialog.locator('input').fill(pinText!.trim())

        // Click "Yes, Proceed"
        await dialog.getByRole('button', { name: 'Yes, Proceed' }).click()

        // Wallet should disappear from the list
        await expect(row).not.toBeVisible({ timeout: 10_000 })
    })

    test('member cannot see a wallet they have no access to', async ({ page, browser }) => {
        const restrictedWallet = `Restricted ${Date.now()}`
        await createWallet(page, restrictedWallet)

        // Check as member
        const memberContext = await browser.newContext({ storageState: memberState })
        const memberPage = await memberContext.newPage()
        await memberPage.goto('/wallets')
        await expect(memberPage.locator('table')).toBeVisible({ timeout: 15_000 })

        // Member should not see the restricted wallet
        await expect(memberPage.getByText(restrictedWallet)).not.toBeVisible()

        await memberPage.close()
        await memberContext.close()
    })

    test('admin grants wallet access to member, member can now see it', async ({ page, browser }) => {
        const sharedWallet = `Shared ${Date.now()}`
        await createWallet(page, sharedWallet)

        // Admin goes to Team Members and edits the member
        await page.goto('/team-members')
        await expect(page.locator('table')).toBeVisible({ timeout: 15_000 })

        // Find the member row and click edit
        const memberRow = page.locator('tr', { hasText: 'Member User' })
        await expect(memberRow).toBeVisible({ timeout: 10_000 })
        await memberRow.locator('.mdi-pencil').click()

        // Modal should appear
        const modal = page.getByRole('dialog')
        await expect(modal).toBeVisible({ timeout: 5_000 })

        // Check the new wallet checkbox
        await modal.getByLabel(new RegExp(sharedWallet)).check()

        // Click "Update Member"
        const updateBtn = page.getByRole('button', { name: /update member/i })
        await expect(updateBtn).toBeEnabled({ timeout: 5_000 })
        await Promise.all([
            page.waitForResponse(resp => resp.url().includes('/team-members') && resp.status() === 200),
            updateBtn.click(),
        ])
        await expect(page.getByRole('dialog')).not.toBeVisible({ timeout: 10_000 })

        // Switch to member and verify
        const memberContext = await browser.newContext({ storageState: memberState })
        const memberPage = await memberContext.newPage()
        await memberPage.goto('/wallets')
        await expect(memberPage.locator('table')).toBeVisible({ timeout: 15_000 })

        await expect(memberPage.getByText(sharedWallet)).toBeVisible({ timeout: 10_000 })

        await memberPage.close()
        await memberContext.close()
    })
})
