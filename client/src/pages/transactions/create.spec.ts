import { flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import TransferCreatePage from '@/pages/transactions/create.vue'
import { findByTestId } from '@/test/helpers'
import { mountWithPlugins } from '@/test/setup'

vi.mock('@/api/transactions', () => ({
  initiateTransfer: vi.fn().mockResolvedValue({ data: { group_id: 'test-uuid', status: 'completed' } }),
}))

vi.mock('@/api/address-book', () => ({
  createAddressBookEntry: vi.fn().mockResolvedValue({
    data: { data: { id: 99, name: 'New Contact', address: 'new-addr-123', created_at: '2026-01-01T00:00:00Z' } },
  }),
  fetchAddressBook: vi.fn().mockResolvedValue({ data: { data: [] } }),
  updateAddressBookEntry: vi.fn(),
  deleteAddressBookEntry: vi.fn(),
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

vi.mock('@/queries/wallets', () => ({
  WALLET_QUERY_KEYS: { root: ['wallets'], list: vi.fn(), byId: vi.fn(), transferTargets: ['wallets', 'transfer-targets'] },
  walletsListQuery: () => ({
    key: ['wallets', { page: 1, perPage: 500 }],
    query: async () => ({ data: mockWallets, meta: { current_page: 1, last_page: 1, per_page: 500, total: 2, from: 1, to: 2 } }),
  }),
  transferTargetsQuery: () => ({
    key: ['wallets', 'transfer-targets'],
    query: async () => ({ data: mockWallets.map(w => ({ ...w, is_own: true })) }),
  }),
  useWalletList: vi.fn(),
  walletByIdQuery: vi.fn(),
}))

vi.mock('@/queries/address-book', () => ({
  ADDRESS_BOOK_QUERY_KEYS: { root: ['address-book'], list: vi.fn() },
  addressBookListQuery: () => ({
    key: ['address-book', 'list'],
    query: async () => [],
  }),
}))

describe('TransferCreatePage', () => {
  let wrapper: ReturnType<typeof mountWithPlugins>

  afterEach(() => {
    wrapper?.unmount()
    document.body.innerHTML = ''
  })

  async function mountPage (props: Record<string, unknown> = {}) {
    const w = mountWithPlugins(TransferCreatePage, {
      props,
      attachTo: document.body,
    })
    await flushPromises()
    await w.vm.$nextTick()
    await flushPromises()
    return w
  }

  it('renders page with all key elements', async () => {
    wrapper = await mountPage()

    expect(findByTestId('transfer-type-toggle')).not.toBeNull()
    expect(findByTestId('transfer-amount')).not.toBeNull()
    expect(findByTestId('transfer-reference')).not.toBeNull()
    expect(findByTestId('transfer-submit-btn')).not.toBeNull()
    expect(findByTestId('transfer-cancel-btn')).not.toBeNull()
  })

  it('shows receiver wallet by default (internal mode)', async () => {
    wrapper = await mountPage()

    expect(findByTestId('transfer-receiver-wallet')).not.toBeNull()
    expect(findByTestId('transfer-external-name')).toBeNull()
    expect(findByTestId('transfer-external-address')).toBeNull()
  })

  it('switches to external mode on toggle click', async () => {
    wrapper = await mountPage()

    const externalBtn = findByTestId('transfer-type-external')
    expect(externalBtn).not.toBeNull()
    await externalBtn!.trigger('click')
    await flushPromises()
    await wrapper.vm.$nextTick()

    expect(findByTestId('transfer-external-name')).not.toBeNull()
    expect(findByTestId('transfer-external-address')).not.toBeNull()
    expect(findByTestId('transfer-receiver-wallet')).toBeNull()
  })

  it('shows address book link in external mode', async () => {
    wrapper = await mountPage()

    const externalBtn = findByTestId('transfer-type-external')
    await externalBtn!.trigger('click')
    await flushPromises()
    await wrapper.vm.$nextTick()

    expect(findByTestId('transfer-address-book-link')).not.toBeNull()
  })

  it('does not show address book link in internal mode', async () => {
    wrapper = await mountPage()

    expect(findByTestId('transfer-address-book-link')).toBeNull()
  })

  it('does not show threshold warning for zero amount', async () => {
    wrapper = await mountPage()

    expect(findByTestId('transfer-threshold-warning')).toBeNull()
  })

  it('has back link to transactions', async () => {
    wrapper = await mountPage()

    expect(findByTestId('transfer-back-link')).not.toBeNull()
  })

  it('submit button is not disabled after 422 error returns to form', async () => {
    const { initiateTransfer } = await import('@/api/transactions')
    vi.mocked(initiateTransfer).mockRejectedValueOnce({
      response: { status: 422, data: { errors: { amount: ['Exceeds daily limit'] } } },
    })

    wrapper = await mountPage()

    const vm = wrapper.vm as any
    vm.step = 'recap'
    await flushPromises()

    await vm.executeTransfer()
    await flushPromises()

    expect(vm.step).toBe('form')
    const submitBtn = findByTestId('transfer-submit-btn')
    expect(submitBtn).not.toBeNull()
    expect((submitBtn!.element as HTMLButtonElement).disabled).toBe(false)
  })

  it('clears api error when user edits a form field', async () => {
    wrapper = await mountPage()

    const vm = wrapper.vm as any
    vm.apiError = 'Something went wrong'
    await flushPromises()

    expect(findByTestId('transfer-api-error')).not.toBeNull()

    // Edit the amount field
    vm.form.amount = 100
    await flushPromises()

    expect(findByTestId('transfer-api-error')).toBeNull()
  })
})
