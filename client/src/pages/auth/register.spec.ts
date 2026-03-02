import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import RegisterPage from './register.vue'

vi.mock('@/api/auth', () => ({
  register: vi.fn(),
}))

function makeEmptyAuthState () {
  return {
    auth: {
      user: null,
      token: null,
    },
    company: {
      currentCompany: null,
      companies: [],
    },
  }
}

describe('register.vue', () => {
  it('renders all form fields', () => {
    const wrapper = mountWithPlugins(RegisterPage, {
      piniaOptions: { initialState: makeEmptyAuthState() },
    })

    expect(wrapper.find('[data-testid="name-input"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="email-input"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="password-input"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="password-confirm-input"]').exists()).toBe(true)
  })

  it('renders the register button with correct label', () => {
    const wrapper = mountWithPlugins(RegisterPage, {
      piniaOptions: { initialState: makeEmptyAuthState() },
    })

    const btn = wrapper.find('[data-testid="register-btn"]')
    expect(btn.exists()).toBe(true)
    expect(btn.text()).toContain(en.auth.register.submit)
  })

  it('calls register API with form data on submit', async () => {
    const { register } = await import('@/api/auth')
    vi.mocked(register).mockResolvedValue({
      data: {
        access_token: 'jwt-token',
        user: { id: 1, name: 'User', email: 'u@test.com', email_verified_at: null, role: 'member' },
      },
    } as any)

    const wrapper = mountWithPlugins(RegisterPage, {
      piniaOptions: { initialState: makeEmptyAuthState() },
    })

    await wrapper.find('[data-testid="name-input"] input').setValue('Test User')
    await wrapper.find('[data-testid="email-input"] input').setValue('test@example.com')
    await wrapper.find('[data-testid="password-input"] input').setValue('password123')
    await wrapper.find('[data-testid="password-confirm-input"] input').setValue('password123')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(register).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'Test User',
        email: 'test@example.com',
        password: 'password123',
        password_confirmation: 'password123',
      }),
    )
  })
})
