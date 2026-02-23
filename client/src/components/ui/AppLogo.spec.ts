import { describe, expect, it } from 'vitest'
import AppLogo from './AppLogo.vue'
import { mountWithPlugins } from '@/test/setup'

describe('AppLogo.vue', () => {
    it('renders the logo image with correct attributes', () => {
        const wrapper = mountWithPlugins(AppLogo)

        const img = wrapper.find('img')
        expect(img.exists()).toBe(true)
        expect(img.attributes('alt')).toBe('SecureWallet')
        expect(img.attributes('src')).toContain('sw_logo.png')
        expect(img.classes()).toContain('logo')
    })
})
