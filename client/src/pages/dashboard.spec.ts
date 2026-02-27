import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import DashboardPage from './dashboard.vue'

vi.mock('@/api/dashboard', () => ({
  fetchDashboard: vi.fn(),
}))

vi.mock('@/api/settings', () => ({
  fetchCompanyThresholds: vi.fn(),
}))

function makeDashboardData (overrides = {}) {
  return {
    balances: [],
    top_wallets: [],
    others: { count: 0, totalUSD: 0, totalEUR: 0 },
    transactions: [],
    wallets: [],
    ...overrides,
  }
}

function makeAuthState (role: string) {
  return {
    auth: {
      user: { id: 1, name: 'User', role, email: 'u@test.com', email_verified_at: '2024-01-01' },
      token: 'test-token',
    },
    company: {
      currentCompany: { id: 1, name: 'Test Corp' },
      companies: [{ id: 1, name: 'Test Corp' }],
    },
  }
}

async function mountDashboard (role: string, dashboardOverrides = {}) {
  const { fetchDashboard } = await import('@/api/dashboard')
  vi.mocked(fetchDashboard).mockResolvedValue({
    data: makeDashboardData(dashboardOverrides),
  } as any)

  const wrapper = mountWithPlugins(DashboardPage, {
    piniaOptions: {
      initialState: makeAuthState(role),
    },
  })

  await flushPromises()
  return wrapper
}

describe('dashboard.vue', () => {
  it('shows missing thresholds warning for admin when missing_thresholds is true', async () => {
    const wrapper = await mountDashboard('admin', { missing_thresholds: true })

    const alert = wrapper.find('[data-testid="missing-thresholds-warning"]')
    expect(alert.exists()).toBe(true)
    expect(alert.text()).toContain(en.dashboard.missingThresholds)
  })

  it('does not show missing thresholds warning for non-admin', async () => {
    const wrapper = await mountDashboard('member', { missing_thresholds: true })

    expect(wrapper.find('[data-testid="missing-thresholds-warning"]').exists()).toBe(false)
  })

  it('does not show warning when thresholds are configured', async () => {
    const wrapper = await mountDashboard('admin', { missing_thresholds: false })

    expect(wrapper.find('[data-testid="missing-thresholds-warning"]').exists()).toBe(false)
  })
})
