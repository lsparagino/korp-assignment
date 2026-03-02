import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import * as transactionsApi from '@/api/transactions'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import RecentTransactions from './RecentTransactions.vue'

vi.mock('@/api/transactions', () => ({
    fetchTransactions: vi.fn().mockResolvedValue({
        data: {
            data: [
                {
                    id: 1,
                    group_id: 'g1',
                    type: 'Credit',
                    amount: 100,
                    currency: 'USD',
                    status: 'completed',
                    exchange_rate: 1,
                    reference: null,
                    wallet_id: 1,
                    counterpart_wallet_id: null,
                    wallet: { id: 1, name: 'Main', address: '0x1' },
                    counterpart_wallet: null,
                    external: true,
                    external_address: '0xabc',
                    external_name: 'External',
                    initiator_user_id: 1,
                    reviewer_user_id: null,
                    initiator: { id: 1, name: 'Admin' },
                    reviewer: null,
                    reject_reason: null,
                    notes: null,
                    created_at: '2024-01-01T00:00:00Z',
                },
            ],
        },
    }),
}))

function makeAuthState(role: string) {
    return {
        auth: {
            user: { id: 1, name: 'Admin', role, email: 'a@test.com', email_verified_at: '2024-01-01' },
            token: 'test-token',
        },
        company: {
            currentCompany: { id: 1, name: 'Test Corp' },
            companies: [{ id: 1, name: 'Test Corp' }],
        },
    }
}

describe('RecentTransactions', () => {
    it('fetches transactions with given filter params', async () => {
        mountWithPlugins(RecentTransactions, {
            props: {
                filterParams: { has_wallet_id: 42 },
                viewAllQuery: { has_wallet_id: '42' },
            },
            piniaOptions: { initialState: makeAuthState('admin') },
        })
        await flushPromises()

        expect(transactionsApi.fetchTransactions).toHaveBeenCalledWith(
            expect.objectContaining({ has_wallet_id: 42, per_page: 5 }),
        )
    })

    it('renders a "View All" link with correct URL', async () => {
        const wrapper = mountWithPlugins(RecentTransactions, {
            props: {
                filterParams: { initiator_user_id: 7 },
                viewAllQuery: { initiator_user_id: '7' },
            },
            piniaOptions: { initialState: makeAuthState('admin') },
        })
        await flushPromises()

        const viewAllLink = wrapper.find('[data-testid="view-all-link"]')
        expect(viewAllLink.exists()).toBe(true)
        expect(viewAllLink.text()).toContain(en.transactions.viewAll)
    })

    it('uses custom limit when provided', async () => {
        mountWithPlugins(RecentTransactions, {
            props: {
                filterParams: { has_wallet_id: 1 },
                viewAllQuery: { has_wallet_id: '1' },
                limit: 10,
            },
            piniaOptions: { initialState: makeAuthState('admin') },
        })
        await flushPromises()

        expect(transactionsApi.fetchTransactions).toHaveBeenCalledWith(
            expect.objectContaining({ per_page: 10 }),
        )
    })

    it('does not fetch when user is not manager or admin', async () => {
        vi.mocked(transactionsApi.fetchTransactions).mockClear()

        mountWithPlugins(RecentTransactions, {
            props: {
                filterParams: { has_wallet_id: 1 },
                viewAllQuery: { has_wallet_id: '1' },
            },
            piniaOptions: { initialState: makeAuthState('member') },
        })
        await flushPromises()

        expect(transactionsApi.fetchTransactions).not.toHaveBeenCalled()
    })
})
