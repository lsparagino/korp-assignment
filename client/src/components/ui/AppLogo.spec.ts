import { describe, expect, it } from 'vitest'
import { mountWithPlugins } from '@/test/setup'
import AppLogo from './AppLogo.vue'

describe('AppLogo.vue', () => {
  it('renders the logo image with correct attributes', () => {
    const wrapper = mountWithPlugins(AppLogo)

    const logo = wrapper.find('[data-testid="app-logo"]')
    expect(logo.exists()).toBe(true)
    expect(logo.attributes('alt')).toBe('SecureWallet')
    expect(logo.attributes('src')).toContain('sw_logo.png')
    expect(logo.classes()).toContain('logo')
  })
})
