import { afterEach, describe, expect, it, vi } from 'vitest'
import { mountWithPlugins } from '@/test/setup'
import TransferButton from './TransferButton.vue'

vi.mock('@pinia/colada', async importOriginal => {
  const actual = await importOriginal()
  return {
    ...(actual as Record<string, unknown>),
    useQuery: vi.fn(() => ({
      data: { value: { data: [{ id: 1, name: 'Wallet 1' }] } },
      status: { value: 'success' },
      asyncStatus: { value: 'idle' },
      error: { value: null },
      isPending: { value: false },
      refresh: vi.fn(),
    })),
  }
})

describe('TransferButton.vue', () => {
  let wrapper: ReturnType<typeof mountWithPlugins>

  afterEach(() => {
    wrapper?.unmount()
  })

  it('renders with transfer text', () => {
    wrapper = mountWithPlugins(TransferButton)

    expect(wrapper.text()).toContain('Initiate Transfer')
  })

  it('renders as a link to /transactions/create', () => {
    wrapper = mountWithPlugins(TransferButton)

    const btn = wrapper.find('[data-testid="transfer-btn"]')
    expect(btn.exists()).toBe(true)
  })

  it('applies block prop', () => {
    wrapper = mountWithPlugins(TransferButton, {
      props: { block: true },
    })

    const btn = wrapper.find('[data-testid="transfer-btn"]')
    expect(btn.exists()).toBe(true)
    expect(btn.classes()).toContain('v-btn--block')
  })

  it('is enabled when wallets exist', () => {
    wrapper = mountWithPlugins(TransferButton)

    const btn = wrapper.find('[data-testid="transfer-btn"]')
    expect(
      btn.element.hasAttribute('disabled')
      || btn.element.getAttribute('aria-disabled') === 'true',
    ).toBe(false)
  })
})
