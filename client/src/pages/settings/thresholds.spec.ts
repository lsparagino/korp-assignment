import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import ThresholdsPage from './thresholds.vue'

vi.mock('@/api/settings', () => ({
  fetchCompanyThresholds: vi.fn(),
  upsertCompanyThreshold: vi.fn(),
  deleteCompanyThreshold: vi.fn(),
  fetchUserPreferences: vi.fn().mockResolvedValue({
    data: { data: { date_format: 'en-GB', number_format: 'en-GB' } },
  }),
}))

vi.mock('@/api/wallets', () => ({
  fetchCurrencies: vi.fn(),
}))

const ALL_CURRENCIES = ['USD', 'EUR', 'GBP']

function makeThreshold (id: number, currency: string, amount: number) {
  return { id, currency, approval_threshold: String(amount), company_id: 1 }
}

async function mountPage (thresholds: ReturnType<typeof makeThreshold>[] = []) {
  const { fetchCompanyThresholds } = await import('@/api/settings')
  const { fetchCurrencies } = await import('@/api/wallets')

  vi.mocked(fetchCompanyThresholds).mockResolvedValue({
    data: { data: thresholds },
  } as any)
  vi.mocked(fetchCurrencies).mockResolvedValue({
    data: ALL_CURRENCIES,
  } as any)

  const wrapper = mountWithPlugins(ThresholdsPage, {
    global: {
      stubs: { SettingsLayout: { template: '<div><slot /></div>' } },
    },
  })

  await flushPromises()
  return wrapper
}

function findAddButton (wrapper: any) {
  const buttons = wrapper.findAll('.v-btn')
  return buttons.find((b: any) => b.text().includes(en.settings.thresholds.addThreshold))
}

describe('thresholds.vue', () => {
  it('enables add button when not all currencies have thresholds', async () => {
    const wrapper = await mountPage([
      makeThreshold(1, 'USD', 5000),
    ])

    const btn = findAddButton(wrapper)
    expect(btn?.exists()).toBe(true)
    expect(btn?.attributes('disabled')).toBeUndefined()
  })

  it('disables add button when all currencies have thresholds', async () => {
    const wrapper = await mountPage([
      makeThreshold(1, 'USD', 5000),
      makeThreshold(2, 'EUR', 3000),
      makeThreshold(3, 'GBP', 4000),
    ])

    const btn = findAddButton(wrapper)
    expect(btn?.exists()).toBe(true)
    expect(btn?.attributes('disabled')).toBeDefined()
  })

  it('shows only available currencies in add dialog dropdown', async () => {
    const wrapper = await mountPage([
      makeThreshold(1, 'USD', 5000),
    ])

    // Open the add dialog
    const btn = findAddButton(wrapper)
    await btn?.trigger('click')
    await flushPromises()

    // Find the v-select and check its items
    const select = wrapper.findComponent({ name: 'v-select' })
    expect(select.exists()).toBe(true)

    const items = select.props('items') as string[]
    expect(items).toContain('EUR')
    expect(items).toContain('GBP')
    expect(items).not.toContain('USD')
  })
})
