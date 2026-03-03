import { flushPromises } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useRoute } from 'vue-router'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import ResetPasswordPage from './reset-password.vue'

vi.mock('@/api/auth', () => ({
    resetPassword: vi.fn(),
}))

beforeEach(() => {
    vi.mocked(useRoute).mockReturnValue({
        query: { token: 'test-token', email: 'user@test.com' },
    } as any)
})

describe('reset-password.vue', () => {
    it('renders email, password, and confirm password fields', () => {
        const wrapper = mountWithPlugins(ResetPasswordPage)

        expect(wrapper.find('[data-testid="email-input"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="password-input"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="password-confirm-input"]').exists()).toBe(true)
    })

    it('renders the submit button with correct label', () => {
        const wrapper = mountWithPlugins(ResetPasswordPage)

        const btn = wrapper.find('[data-testid="submit-btn"]')
        expect(btn.exists()).toBe(true)
        expect(btn.text()).toContain(en.auth.resetPassword.submit)
    })

    it('pre-fills email from route query', async () => {
        const wrapper = mountWithPlugins(ResetPasswordPage)
        await flushPromises()

        const emailInput = wrapper.find('[data-testid="email-input"] input')
        expect((emailInput.element as HTMLInputElement).value).toBe('user@test.com')
    })
})
