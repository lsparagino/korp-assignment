import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import PreferencesPage from './preferences.vue'

vi.mock('@/api/settings', () => ({
  fetchUserPreferences: vi.fn(),
  updateUserPreferences: vi.fn(),
}))

function makeAdminState () {
  return {
    auth: {
      user: { id: 1, name: 'Admin', email: 'admin@test.com', email_verified_at: '2024-01-01', role: 'admin' },
      token: 'test-token',
    },
    company: {
      currentCompany: { id: 1, name: 'Test' },
      companies: [],
    },
    preferences: {
      dateFormat: 'en-GB',
      numberFormat: 'en-GB',
    },
  }
}

function makePreferencesResponse () {
  return {
    data: {
      data: {
        id: 1,
        notify_money_received: true,
        notify_money_sent: true,
        notify_transaction_approved: true,
        notify_transaction_rejected: true,
        notify_approval_needed: true,
        date_format: 'en-GB',
        number_format: 'en-GB',
        daily_transaction_limit: '10000',
        security_threshold: '5000',
      },
    },
  }
}

describe('preferences.vue', () => {
  it('renders the page after loading', async () => {
    const { fetchUserPreferences } = await import('@/api/settings')
    vi.mocked(fetchUserPreferences).mockResolvedValue(makePreferencesResponse() as any)

    const wrapper = mountWithPlugins(PreferencesPage, {
      piniaOptions: { initialState: makeAdminState() },
    })
    await flushPromises()

    expect(wrapper.text()).toContain(en.settings.preferences.title)
  })

  it('shows validation error when security threshold exceeds daily limit', async () => {
    const { fetchUserPreferences } = await import('@/api/settings')
    vi.mocked(fetchUserPreferences).mockResolvedValue(makePreferencesResponse() as any)

    const wrapper = mountWithPlugins(PreferencesPage, {
      piniaOptions: { initialState: makeAdminState() },
    })
    await flushPromises()

    // Set threshold higher than limit
    const inputs = wrapper.findAll('input[type="number"]')
    // First input is daily_transaction_limit, second is security_threshold
    await inputs[0]!.setValue('1000')
    await inputs[1]!.setValue('5000')
    await flushPromises()

    // Trigger form validation by submitting
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.text()).toContain(en.settings.preferences.thresholdExceedsLimit)
  })

  it('allows security threshold equal to or lower than daily limit', async () => {
    const { fetchUserPreferences } = await import('@/api/settings')
    vi.mocked(fetchUserPreferences).mockResolvedValue(makePreferencesResponse() as any)

    const wrapper = mountWithPlugins(PreferencesPage, {
      piniaOptions: { initialState: makeAdminState() },
    })
    await flushPromises()

    const inputs = wrapper.findAll('input[type="number"]')
    await inputs[0]!.setValue('10000')
    await inputs[1]!.setValue('5000')
    await flushPromises()

    expect(wrapper.text()).not.toContain(en.settings.preferences.thresholdExceedsLimit)
  })

  it('allows empty security threshold when daily limit is set', async () => {
    const { fetchUserPreferences } = await import('@/api/settings')
    vi.mocked(fetchUserPreferences).mockResolvedValue(makePreferencesResponse() as any)

    const wrapper = mountWithPlugins(PreferencesPage, {
      piniaOptions: { initialState: makeAdminState() },
    })
    await flushPromises()

    const inputs = wrapper.findAll('input[type="number"]')
    await inputs[0]!.setValue('10000')
    await inputs[1]!.setValue('')
    await flushPromises()

    expect(wrapper.text()).not.toContain(en.settings.preferences.thresholdExceedsLimit)
  })
})
