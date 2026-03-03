import { describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { makeEmptyAuthState, mountWithPlugins } from '@/test/setup'
import VerifyEmailPage from './verify-email.vue'

vi.mock('@/api/auth', () => ({
  sendVerificationEmail: vi.fn(),
  verifyEmail: vi.fn(),
}))

describe('verify-email.vue', () => {
  it('renders resend verification button', () => {
    const wrapper = mountWithPlugins(VerifyEmailPage, {
      piniaOptions: { initialState: makeEmptyAuthState() },
    })

    const btns = wrapper.findAll('button')
    const resendBtn = btns.find(b => b.text().includes(en.auth.verifyEmail.resendButton)
      || b.text().includes(en.auth.verifyEmail.resendButtonCooldown.replace('{seconds}', '')))
    expect(resendBtn).toBeDefined()
  })

  it('renders logout button', () => {
    const wrapper = mountWithPlugins(VerifyEmailPage, {
      piniaOptions: { initialState: makeEmptyAuthState() },
    })

    const btns = wrapper.findAll('button')
    const logoutBtn = btns.find(b => b.text().includes(en.common.logOut))
    expect(logoutBtn).toBeDefined()
  })
})
