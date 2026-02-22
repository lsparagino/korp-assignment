import { Browser, Page } from '@playwright/test'

interface AuthState {
    token: string
    user: Record<string, unknown>
    company: { id: number; name: string }
}

/**
 * Build the localStorage entries that the Vue app's auth and company stores expect.
 * Keys match: auth store uses 'access_token' and 'user'.
 */
function buildStorageState(auth: AuthState) {
    const origin = 'http://localhost:3001'

    return {
        cookies: [],
        origins: [
            {
                origin,
                localStorage: [
                    { name: 'access_token', value: auth.token },
                    { name: 'user', value: JSON.stringify(auth.user) },
                ],
            },
        ],
    }
}

/**
 * Create an authenticated browser context with the given auth state.
 */
export async function authenticatedContext(browser: Browser, auth: AuthState) {
    const storageState = buildStorageState(auth)
    return browser.newContext({ storageState })
}

/**
 * Create an authenticated page with the given auth state.
 */
export async function authenticatedPage(browser: Browser, auth: AuthState): Promise<Page> {
    const context = await authenticatedContext(browser, auth)
    return context.newPage()
}

export { AuthState, buildStorageState }
