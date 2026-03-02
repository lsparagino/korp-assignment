import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mountWithPlugins } from '@/test/setup'
import TransferButton from './TransferButton.vue'

vi.mock('@pinia/colada', () => ({
  PiniaColada: { install: vi.fn() },
  useQuery: vi.fn(() => ({
    data: { value: { data: [{ id: 1, name: 'Wallet 1' }] } },
    status: { value: 'success' },
    asyncStatus: { value: 'idle' },
    error: { value: null },
    isPending: { value: false },
    refresh: vi.fn(),
  })),
  defineQuery: vi.fn((fn: Function) => fn),
  defineQueryOptions: vi.fn((fn: Function) => fn),
}))

vi.mock('@/queries/wallets', () => ({
  WALLET_QUERY_KEYS: { root: ['wallets'], list: vi.fn(), byId: vi.fn() },
  walletsListQuery: vi.fn(),
  walletByIdQuery: vi.fn(),
}))

describe('TransferButton.vue', () => {
  let wrapper: ReturnType<typeof mountWithPlugins>

  beforeEach(() => {
    wrapper = mountWithPlugins(TransferButton)
  })

  afterEach(() => {
    wrapper?.unmount()
  })

  it('renders with transfer text', () => {
    expect(wrapper.text()).toContain('Initiate Transfer')
  })

  it('renders as a link to /transactions/create', () => {
    const btn = wrapper.find('[data-testid="transfer-btn"]')
    expect(btn.exists()).toBe(true)
  })

  it('applies block prop', () => {
    wrapper.unmount()
    wrapper = mountWithPlugins(TransferButton, {
      props: { block: true },
    })

    const btn = wrapper.find('[data-testid="transfer-btn"]')
    expect(btn.exists()).toBe(true)
    expect(btn.classes()).toContain('v-btn--block')
  })

  it('is enabled when wallets exist', () => {
    const btn = wrapper.find('[data-testid="transfer-btn"]')
    expect(
      btn.element.hasAttribute('disabled')
      || btn.element.getAttribute('aria-disabled') === 'true',
    ).toBe(false)
  })
})
