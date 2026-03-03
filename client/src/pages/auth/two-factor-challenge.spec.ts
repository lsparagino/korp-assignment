import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import TwoFactorChallengePage from './two-factor-challenge.vue'

vi.mock('@/api/auth', () => ({
  twoFactorChallenge: vi.fn(),
}))

function makeAuthState () {
  return {
    auth: {
      user: null,
      token: null,
      twoFactorUserId: 42,
    },
    company: {
      currentCompany: null,
      companies: [],
    },
  }
}

describe('two-factor-challenge.vue', () => {
  it('renders OTP input by default', () => {
    const wrapper = mountWithPlugins(TwoFactorChallengePage, {
      piniaOptions: { initialState: makeAuthState() },
    })

    expect(wrapper.find('.v-otp-input').exists()).toBe(true)
  })

  it('renders continue button', () => {
    const wrapper = mountWithPlugins(TwoFactorChallengePage, {
      piniaOptions: { initialState: makeAuthState() },
    })

    const buttons = wrapper.findAll('button[type="submit"]')
    const continueBtn = buttons.find(b => b.text().includes(en.common.continue))
    expect(continueBtn).toBeDefined()
  })

  it('shows recovery code input after toggle', async () => {
    const wrapper = mountWithPlugins(TwoFactorChallengePage, {
      piniaOptions: { initialState: makeAuthState() },
    })

    const toggleBtn = wrapper.findAll('button[type="button"]')
      .find(b => b.text().includes(en.auth.twoFactor.useRecoveryCode))
    expect(toggleBtn).toBeDefined()
    await toggleBtn!.trigger('click')

    expect(wrapper.find('.v-otp-input').exists()).toBe(false)
    expect(wrapper.find('input').exists()).toBe(true)
  })

  it('calls twoFactorChallenge API on submit', async () => {
    const { twoFactorChallenge } = await import('@/api/auth')
    vi.mocked(twoFactorChallenge).mockResolvedValue({
      data: {
        access_token: 'jwt-token',
        user: { id: 1, name: 'User', email: 'u@test.com', email_verified_at: '2024-01-01', role: 'member' },
      },
    } as any)

    const wrapper = mountWithPlugins(TwoFactorChallengePage, {
      piniaOptions: { initialState: makeAuthState() },
    })

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(twoFactorChallenge).toHaveBeenCalled()
  })
})
