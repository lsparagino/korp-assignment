import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { mountWithPlugins } from '@/test/setup'
import PasswordPage from './password.vue'

vi.mock('@/api/settings', () => ({
  updatePassword: vi.fn(),
}))

function makeAuthState () {
  return {
    auth: {
      user: { id: 1, name: 'User', email: 'u@test.com', email_verified_at: '2024-01-01', role: 'admin' },
      token: 'test-token',
    },
    company: {
      currentCompany: { id: 1, name: 'Test Corp' },
      companies: [{ id: 1, name: 'Test Corp' }],
    },
  }
}

describe('settings/password.vue', () => {
  it('renders all three password fields', () => {
    const wrapper = mountWithPlugins(PasswordPage, {
      piniaOptions: { initialState: makeAuthState() },
    })

    expect(wrapper.find('[data-testid="current-password-input"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="new-password-input"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="password-confirm-input"]').exists()).toBe(true)
  })

  it('renders the save button', () => {
    const wrapper = mountWithPlugins(PasswordPage, {
      piniaOptions: { initialState: makeAuthState() },
    })

    const btn = wrapper.find('[data-testid="save-password-btn"]')
    expect(btn.exists()).toBe(true)
  })

  it('calls updatePassword API on submit', async () => {
    const { updatePassword } = await import('@/api/settings')
    vi.mocked(updatePassword).mockResolvedValue({} as any)

    const wrapper = mountWithPlugins(PasswordPage, {
      piniaOptions: { initialState: makeAuthState() },
    })

    await wrapper.find('[data-testid="current-password-input"] input').setValue('old-password')
    await wrapper.find('[data-testid="new-password-input"] input').setValue('new-password')
    await wrapper.find('[data-testid="password-confirm-input"] input').setValue('new-password')

    const form = wrapper.find('form')
    expect(form.exists()).toBe(true)
    await form.trigger('submit.prevent')
    await flushPromises()

    expect(updatePassword).toHaveBeenCalled()
  })
})
