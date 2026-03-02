import { describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import CreateWalletPage from './create.vue'

vi.mock('@/api/wallets', () => ({
  createWallet: vi.fn(),
  fetchWallets: vi.fn(),
  fetchWallet: vi.fn(),
  fetchCurrencies: vi.fn().mockResolvedValue({ data: ['USD', 'EUR', 'GBP'] }),
}))

vi.mock('@/queries/wallets', () => ({
  WALLET_QUERY_KEYS: { root: ['wallets'] },
  walletByIdQuery: vi.fn(),
}))

vi.mock('@/queries/dashboard', () => ({
  DASHBOARD_QUERY_KEYS: { root: ['dashboard'] },
}))

function makeAdminState () {
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

describe('wallets/create.vue', () => {
  it('renders the create wallet page with title', () => {
    const wrapper = mountWithPlugins(CreateWalletPage, {
      piniaOptions: { initialState: makeAdminState() },
    })

    expect(wrapper.text()).toContain(en.wallets.createNewWallet)
  })

  it('renders the submit button', () => {
    const wrapper = mountWithPlugins(CreateWalletPage, {
      piniaOptions: { initialState: makeAdminState() },
    })

    const btn = wrapper.find('[data-testid="wallet-create-btn"]')
    expect(btn.exists()).toBe(true)
    expect(btn.text()).toContain(en.wallets.createWallet)
  })

  it('renders back to wallets link', () => {
    const wrapper = mountWithPlugins(CreateWalletPage, {
      piniaOptions: { initialState: makeAdminState() },
    })

    expect(wrapper.text()).toContain(en.wallets.backToWallets)
  })
})
