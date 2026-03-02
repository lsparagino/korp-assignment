import { computed, ref } from 'vue'
import { describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import TransactionsPage from './index.vue'

vi.mock('@/api/transactions', () => ({
    fetchTransactions: vi.fn(),
}))

vi.mock('@/api/wallets', () => ({
    fetchWallets: vi.fn(),
}))

// Mock the composable that powers the page
vi.mock('@/composables/useTransactionFilters', () => {
    return {
        useTransactionFilters: vi.fn(() => ({
            transactions: ref([]),
            meta: ref({ current_page: 1, last_page: 1, per_page: 10, total: 0, from: null, to: null }),
            processing: ref(false),
            filterForm: ref({ type: '', status: '', date_from: '', date_to: '', amount_min: '', amount_max: '', reference: '', wallet_id: '', counterpart_wallet_id: '' }),
            activeFiltersCount: computed(() => 0),
            activeAdvancedFiltersCount: computed(() => 0),
            advancedPanel: ref(null),
            dateFromMenu: ref(false),
            dateToMenu: ref(false),
            dateFromValue: ref(null),
            dateToValue: ref(null),
            types: ['credit', 'debit', 'transfer'],
            statuses: ['completed', 'pending', 'failed'],
            walletOptions: [],
            onDateSelected: vi.fn(),
            handlePageChange: vi.fn(),
            handlePerPageChange: vi.fn(),
            handleFilter: vi.fn(),
            clearFilters: vi.fn(),
            invalidateQueries: vi.fn(),
        })),
    }
})

function makeAuthState() {
    return {
        auth: {
            user: { id: 1, name: 'Admin', role: 'admin', email: 'a@test.com', email_verified_at: '2024-01-01' },
            token: 'test-token',
        },
        company: {
            currentCompany: { id: 1, name: 'Test Corp' },
            companies: [{ id: 1, name: 'Test Corp' }],
        },
    }
}

describe('transactions/index.vue', () => {
    it('renders the page title', () => {
        const wrapper = mountWithPlugins(TransactionsPage, {
            piniaOptions: { initialState: makeAuthState() },
        })

        expect(wrapper.text()).toContain(en.transactions.title)
    })

    it('renders filter options card', () => {
        const wrapper = mountWithPlugins(TransactionsPage, {
            piniaOptions: { initialState: makeAuthState() },
        })

        expect(wrapper.find('[data-testid="filter-options-card"]').exists()).toBe(true)
    })

    it('renders type and status filter selects', () => {
        const wrapper = mountWithPlugins(TransactionsPage, {
            piniaOptions: { initialState: makeAuthState() },
        })

        expect(wrapper.find('[data-testid="type-select"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="status-select"]').exists()).toBe(true)
    })

    it('renders the advanced filters panel', () => {
        const wrapper = mountWithPlugins(TransactionsPage, {
            piniaOptions: { initialState: makeAuthState() },
        })

        expect(wrapper.find('[data-testid="advanced-filters"]').exists()).toBe(true)
    })
})
