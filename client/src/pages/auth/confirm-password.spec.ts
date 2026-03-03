import { describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import ConfirmPasswordPage from './confirm-password.vue'

vi.mock('@/api/auth', () => ({
    confirmPassword: vi.fn(),
}))

describe('confirm-password.vue', () => {
    it('renders password field', () => {
        const wrapper = mountWithPlugins(ConfirmPasswordPage)

        const input = wrapper.find('input[type="password"]')
        expect(input.exists()).toBe(true)
    })

    it('renders the submit button with correct label', () => {
        const wrapper = mountWithPlugins(ConfirmPasswordPage)

        const btn = wrapper.findAll('button[type="submit"]')
            .find(b => b.text().includes(en.auth.confirmPassword.submit))
        expect(btn).toBeDefined()
    })
})
