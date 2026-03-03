import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { makeEmptyAuthState, mountWithPlugins } from '@/test/setup'
import LoginPage from './login.vue'

vi.mock('@/api/auth', () => ({
  login: vi.fn(),
}))


describe('login.vue', () => {
  it('renders email and password fields', () => {
    const wrapper = mountWithPlugins(LoginPage, {
      piniaOptions: { initialState: makeEmptyAuthState() },
    })

    expect(wrapper.find('[data-testid="email-input"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="password-input"]').exists()).toBe(true)
  })

  it('renders the login button', () => {
    const wrapper = mountWithPlugins(LoginPage, {
      piniaOptions: { initialState: makeEmptyAuthState() },
    })

    const btn = wrapper.find('[data-testid="login-btn"]')
    expect(btn.exists()).toBe(true)
    expect(btn.text()).toContain(en.auth.login.submit)
  })

  it('renders the forgot-password link', () => {
    const wrapper = mountWithPlugins(LoginPage, {
      piniaOptions: { initialState: makeEmptyAuthState() },
    })

    const link = wrapper.find('[data-testid="forgot-password-link"]')
    expect(link.exists()).toBe(true)
  })

  it('calls login API with form data on submit', async () => {
    const { login } = await import('@/api/auth')
    vi.mocked(login).mockResolvedValue({
      data: {
        access_token: 'jwt-token',
        user: { id: 1, name: 'User', email: 'u@test.com', email_verified_at: '2024-01-01', role: 'admin' },
      },
    } as any)

    const wrapper = mountWithPlugins(LoginPage, {
      piniaOptions: { initialState: makeEmptyAuthState() },
    })

    await wrapper.find('[data-testid="email-input"] input').setValue('user@example.com')
    await wrapper.find('[data-testid="password-input"] input').setValue('password123')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(login).toHaveBeenCalledWith(
      expect.objectContaining({ email: 'user@example.com', password: 'password123' }),
    )
  })

  it('redirects to two-factor challenge when API returns two_factor', async () => {
    const { login } = await import('@/api/auth')
    vi.mocked(login).mockResolvedValue({
      data: { two_factor: true, user_id: 42 },
    } as any)

    const { useRouter } = await import('vue-router')
    const mockPush = vi.fn()
    vi.mocked(useRouter).mockReturnValue({ push: mockPush, replace: vi.fn() } as any)

    const wrapper = mountWithPlugins(LoginPage, {
      piniaOptions: { initialState: makeEmptyAuthState() },
    })

    await wrapper.find('[data-testid="email-input"] input').setValue('u@test.com')
    await wrapper.find('[data-testid="password-input"] input').setValue('password')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(mockPush).toHaveBeenCalledWith('/auth/two-factor-challenge')
  })
})
