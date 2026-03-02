import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import ForgotPasswordPage from './forgot-password.vue'

vi.mock('@/api/auth', () => ({
  forgotPassword: vi.fn(),
}))

describe('forgot-password.vue', () => {
  it('renders email input', () => {
    const wrapper = mountWithPlugins(ForgotPasswordPage)

    expect(wrapper.find('[data-testid="email-input"]').exists()).toBe(true)
  })

  it('renders the submit button with correct label', () => {
    const wrapper = mountWithPlugins(ForgotPasswordPage)

    const btn = wrapper.find('[data-testid="submit-btn"]')
    expect(btn.exists()).toBe(true)
    expect(btn.text()).toContain(en.auth.forgotPassword.submit)
  })

  it('calls forgotPassword API with email on submit', async () => {
    const { forgotPassword } = await import('@/api/auth')
    vi.mocked(forgotPassword).mockResolvedValue({
      data: { message: 'Reset link sent' },
    } as any)

    const wrapper = mountWithPlugins(ForgotPasswordPage)

    await wrapper.find('[data-testid="email-input"] input').setValue('user@example.com')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(forgotPassword).toHaveBeenCalledWith(
      expect.objectContaining({ email: 'user@example.com' }),
    )
  })

  it('disables submit button after successful submission (cooldown)', async () => {
    vi.useFakeTimers()
    const { forgotPassword } = await import('@/api/auth')
    vi.mocked(forgotPassword).mockResolvedValue({
      data: { message: 'Reset link sent' },
    } as any)

    const wrapper = mountWithPlugins(ForgotPasswordPage)

    await wrapper.find('[data-testid="email-input"] input').setValue('user@example.com')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    const btn = wrapper.find('[data-testid="submit-btn"]')
    expect(btn.attributes('disabled')).toBeDefined()

    vi.useRealTimers()
  })
})
