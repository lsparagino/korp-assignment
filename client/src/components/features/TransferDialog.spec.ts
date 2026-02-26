import { DOMWrapper, flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import { mountWithPlugins } from '@/test/setup'
import TransferDialog from './TransferDialog.vue'

vi.mock('@/api/transactions', () => ({
    initiateTransfer: vi.fn().mockResolvedValue({ data: { group_id: 'test-uuid', status: 'completed' } }),
}))

vi.mock('@/stores/auth', () => ({
    useAuthStore: () => ({
        user: { id: 1, name: 'Test User', email: 'test@example.com', role: 'member' },
        isAdmin: false,
    }),
}))

const mockWallets = [
    { id: 1, name: 'Savings', address: 'addr1', balance: 5000, locked_balance: 0, available_balance: 5000, currency: 'USD', status: 'active', can_delete: false },
    { id: 2, name: 'Business', address: 'addr2', balance: 3000, locked_balance: 0, available_balance: 3000, currency: 'USD', status: 'active', can_delete: false },
]

// Mock the wallets query module so useQuery returns our test data
vi.mock('@/queries/wallets', () => ({
    WALLET_QUERY_KEYS: { root: ['wallets'], list: vi.fn(), byId: vi.fn() },
    walletsListQuery: () => ({
        key: ['wallets', { page: 1, perPage: 500 }],
        query: async () => ({ data: mockWallets, meta: { current_page: 1, last_page: 1, per_page: 500, total: 2, from: 1, to: 2 } }),
    }),
    useWalletList: vi.fn(),
    walletByIdQuery: vi.fn(),
}))

describe('TransferDialog.vue', () => {
    let wrapper: ReturnType<typeof mountWithPlugins>

    afterEach(() => {
        wrapper?.unmount()
        document.body.innerHTML = ''
    })

    async function mountDialog(props: Record<string, unknown> = {}) {
        const w = mountWithPlugins(TransferDialog, {
            props: {
                modelValue: true,
                ...props,
            },
            attachTo: document.body,
        })
        await flushPromises()
        await w.vm.$nextTick()
        await flushPromises()
        return w
    }

    function findByTestId(testId: string) {
        const el = document.body.querySelector(`[data-testid="${testId}"]`)
        return el ? new DOMWrapper(el as HTMLElement) : null
    }

    it('renders dialog with all key elements', async () => {
        wrapper = await mountDialog()

        expect(findByTestId('transfer-dialog')).not.toBeNull()
        expect(findByTestId('transfer-type-toggle')).not.toBeNull()
        expect(findByTestId('transfer-amount')).not.toBeNull()
        expect(findByTestId('transfer-reference')).not.toBeNull()
        expect(findByTestId('transfer-submit-btn')).not.toBeNull()
        expect(findByTestId('transfer-cancel-btn')).not.toBeNull()
    })

    it('shows receiver wallet by default (internal mode)', async () => {
        wrapper = await mountDialog()

        expect(findByTestId('transfer-receiver-wallet')).not.toBeNull()
        expect(findByTestId('transfer-external-name')).toBeNull()
        expect(findByTestId('transfer-external-address')).toBeNull()
    })

    it('switches to external mode on toggle click', async () => {
        wrapper = await mountDialog()

        const externalBtn = findByTestId('transfer-type-external')
        expect(externalBtn).not.toBeNull()
        await externalBtn!.trigger('click')
        await flushPromises()
        await wrapper.vm.$nextTick()

        expect(findByTestId('transfer-external-name')).not.toBeNull()
        expect(findByTestId('transfer-external-address')).not.toBeNull()
        expect(findByTestId('transfer-receiver-wallet')).toBeNull()
    })

    it('does not show threshold warning for zero amount', async () => {
        wrapper = await mountDialog()

        expect(findByTestId('transfer-threshold-warning')).toBeNull()
    })

    it('closes dialog on cancel click', async () => {
        wrapper = await mountDialog()

        const cancelBtn = findByTestId('transfer-cancel-btn')
        expect(cancelBtn).not.toBeNull()
        await cancelBtn!.trigger('click')

        expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    })

    it('closes dialog on close icon click', async () => {
        wrapper = await mountDialog()

        const closeBtn = findByTestId('transfer-close-btn')
        expect(closeBtn).not.toBeNull()
        await closeBtn!.trigger('click')

        expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    })
})
