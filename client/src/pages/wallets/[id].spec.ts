import { flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { findByTestId } from '@/test/helpers'
import { mountWithPlugins } from '@/test/setup'
import WalletDetailPage from './[id].vue'

vi.mock('vue-router', async () => {
    const actual = await vi.importActual('vue-router')
    return {
        ...actual,
        useRoute: () => ({ params: { id: '1' } }),
        useRouter: () => ({ push: vi.fn() }),
    }
})

vi.mock('@/api/wallets', () => ({
    fetchWallets: vi.fn(),
    fetchWallet: vi.fn(),
    createWallet: vi.fn(),
    updateWallet: vi.fn(),
    toggleWalletFreeze: vi.fn(),
    deleteWallet: vi.fn(),
}))

vi.mock('@/api/transactions', () => ({
    fetchTransactions: vi.fn().mockResolvedValue({ data: { data: [] } }),
}))

vi.mock('@/queries/wallets', () => ({
    WALLET_QUERY_KEYS: { root: ['wallets'], list: vi.fn(), byId: vi.fn() },
    walletsListQuery: () => ({
        key: ['wallets', { page: 1, perPage: 500 }],
        query: async () => ({
            data: [],
            meta: { current_page: 1, last_page: 1, per_page: 500, total: 0, from: 1, to: 0 },
        }),
    }),
    useWalletList: vi.fn(),
    walletByIdQuery: () => ({
        key: ['wallets', '1'],
        query: async () => ({
            data: {
                id: 1,
                name: 'Main Wallet',
                address: '0xabc123def456',
                balance: 10000,
                locked_balance: 500,
                available_balance: 9500,
                currency: 'USD',
                status: 'active',
                can_delete: false,
            },
        }),
    }),
}))

vi.mock('@/queries/dashboard', () => ({
    DASHBOARD_QUERY_KEYS: { root: ['dashboard'] },
}))

function makeAuthState(role: string) {
    return {
        auth: {
            user: { id: 99, name: 'Test User', role, email: 'test@test.com', email_verified_at: '2024-01-01' },
            token: 'test-token',
        },
        company: {
            currentCompany: { id: 1, name: 'Test Corp' },
            companies: [{ id: 1, name: 'Test Corp' }],
        },
    }
}

describe('wallets/[id].vue', () => {
    let wrapper: ReturnType<typeof mountWithPlugins>

    afterEach(() => {
        wrapper?.unmount()
        document.body.innerHTML = ''
    })

    async function mountPage(authRole = 'admin') {
        wrapper = mountWithPlugins(WalletDetailPage, {
            piniaOptions: { initialState: makeAuthState(authRole) },
            attachTo: document.body,
        })
        await flushPromises()
        await wrapper.vm.$nextTick()
        await flushPromises()
        await wrapper.vm.$nextTick()
        await flushPromises()
        return wrapper
    }

    it('renders page heading', async () => {
        await mountPage()
        const heading = findByTestId('page-heading')
        expect(heading).not.toBeNull()
        expect(heading!.text()).toContain(en.wallets.walletDetails)
    })

    it('shows edit form for admin', async () => {
        await mountPage('admin')
        expect(findByTestId('wallet-name-input')).not.toBeNull()
        expect(findByTestId('wallet-currency-select')).not.toBeNull()
        expect(findByTestId('wallet-save-btn')).not.toBeNull()
    })

    it('shows management actions for admin', async () => {
        await mountPage('admin')
        expect(findByTestId('freeze-btn')).not.toBeNull()
        expect(findByTestId('delete-wallet-btn')).not.toBeNull()
    })

    it('hides edit form for non-admin users', async () => {
        await mountPage('member')
        expect(findByTestId('wallet-name-input')).toBeNull()
        expect(findByTestId('wallet-currency-select')).toBeNull()
        expect(findByTestId('wallet-save-btn')).toBeNull()
    })

    it('hides management actions for non-admin users', async () => {
        await mountPage('member')
        expect(findByTestId('freeze-btn')).toBeNull()
        expect(findByTestId('delete-wallet-btn')).toBeNull()
    })

    it('shows read-only wallet info for non-admin users', async () => {
        await mountPage('member')
        const text = document.body.textContent || ''
        expect(text).toContain('Main Wallet')
        expect(text).toContain('0xabc123def456')
        expect(text).toContain('USD')
    })
})
