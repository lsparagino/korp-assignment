import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { computed, ref } from 'vue'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import WalletsPage from './index.vue'

vi.mock('@/api/wallets', () => ({
  fetchWallets: vi.fn(),
  deleteWallet: vi.fn(),
}))

// Mock queries module
vi.mock('@/queries/wallets', () => {
  return {
    WALLET_QUERY_KEYS: { root: ['wallets'] },
    walletByIdQuery: vi.fn(),
    walletsListQuery: vi.fn(),
    useWalletList: vi.fn(() => ({
      wallets: computed(() => [
        { id: 1, name: 'Main Wallet', balance: '1000.00', available_balance: '800.00', currency: 'USD', status: 'active', can_delete: true },
        { id: 2, name: 'Savings', balance: '5000.00', available_balance: '5000.00', currency: 'EUR', status: 'active', can_delete: false },
      ]),
      meta: computed(() => ({ current_page: 1, last_page: 1, per_page: 10, total: 2, from: 1, to: 2 })),
      isPending: ref(false),
      refetch: vi.fn(),
      page: ref(1),
      perPage: ref(10),
    })),
  }
})

vi.mock('@/queries/dashboard', () => ({
  DASHBOARD_QUERY_KEYS: { root: ['dashboard'] },
}))

function makeAuthState (role: string) {
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

describe('wallets/index.vue', () => {
  it('renders wallet rows from mocked data', async () => {
    const wrapper = mountWithPlugins(WalletsPage, {
      piniaOptions: { initialState: makeAuthState('admin') },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Main Wallet')
    expect(wrapper.text()).toContain('Savings')
  })

  it('shows create-wallet button for admin', async () => {
    const wrapper = mountWithPlugins(WalletsPage, {
      piniaOptions: { initialState: makeAuthState('admin') },
    })
    await flushPromises()

    expect(wrapper.find('[data-testid="create-wallet-btn"]').exists()).toBe(true)
  })

  it('hides create-wallet button for member', async () => {
    const wrapper = mountWithPlugins(WalletsPage, {
      piniaOptions: { initialState: makeAuthState('member') },
    })
    await flushPromises()

    expect(wrapper.find('[data-testid="create-wallet-btn"]').exists()).toBe(false)
  })

  it('has clickable rows with data-testid', async () => {
    const wrapper = mountWithPlugins(WalletsPage, {
      piniaOptions: { initialState: makeAuthState('admin') },
    })
    await flushPromises()

    expect(wrapper.find('[data-testid="wallet-row-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="wallet-row-2"]').exists()).toBe(true)
  })

  it('does not show action buttons in the table', async () => {
    const wrapper = mountWithPlugins(WalletsPage, {
      piniaOptions: { initialState: makeAuthState('admin') },
    })
    await flushPromises()

    expect(wrapper.find('[data-testid="edit-btn"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="delete-btn"]').exists()).toBe(false)
  })

  it('renders the page title', async () => {
    const wrapper = mountWithPlugins(WalletsPage, {
      piniaOptions: { initialState: makeAuthState('admin') },
    })
    await flushPromises()

    expect(wrapper.text()).toContain(en.wallets.title)
  })
})
