import { describe, expect, it } from 'vitest'
import AuthCard from './AuthCard.vue'
import { mountWithPlugins } from '@/test/setup'

describe('AuthCard.vue', () => {
    it('renders default slot', () => {
        const wrapper = mountWithPlugins(AuthCard, {
            slots: {
                default: '<div class="test-content">Auth Form Content</div>',
            },
        })

        expect(wrapper.find('.test-content').exists()).toBe(true)
        expect(wrapper.text()).toContain('Auth Form Content')

        // Alert should not exist if status not provided
        expect(wrapper.findComponent({ name: 'v-alert' }).exists()).toBe(false)
    })

    it('renders status alert when provided', () => {
        const wrapper = mountWithPlugins(AuthCard, {
            props: {
                status: 'Login successful',
            },
        })

        const alert = wrapper.findComponent({ name: 'v-alert' })
        expect(alert.exists()).toBe(true)
        expect(alert.text()).toContain('Login successful')
        // Default alert type is 'success' when not specified
        expect(alert.props('type')).toBe('success')
    })

    it('renders alert with custom type', () => {
        const wrapper = mountWithPlugins(AuthCard, {
            props: {
                status: 'Invalid credentials',
                alertType: 'error',
            },
        })

        const alert = wrapper.findComponent({ name: 'v-alert' })
        expect(alert.exists()).toBe(true)
        expect(alert.props('type')).toBe('error')
    })

    it('renders footer slot when provided', () => {
        const wrapper = mountWithPlugins(AuthCard, {
            slots: {
                footer: '<div class="footer-content">Back to login</div>',
            },
        })

        expect(wrapper.find('.footer-content').exists()).toBe(true)
    })
})
