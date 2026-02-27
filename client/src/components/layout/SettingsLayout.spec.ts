import { describe, expect, it } from 'vitest'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import SettingsLayout from './SettingsLayout.vue'

const nav = en.settings.nav

describe('SettingsLayout.vue', () => {
  it('renders navigation items for non-admin users', () => {
    const wrapper = mountWithPlugins(SettingsLayout)

    expect(wrapper.text()).toContain(nav.profile)
    expect(wrapper.text()).toContain(nav.password)
    expect(wrapper.text()).toContain(nav.twoFactor)
    expect(wrapper.text()).toContain(nav.preferences)

    // Thresholds should NOT be visible for non-admin (default mock user)
    expect(wrapper.text()).not.toContain(nav.thresholds)

    const vListItems = wrapper.findAllComponents({ name: 'v-list-item' })
    expect(vListItems.length).toBe(4)
  })

  it('renders thresholds nav item for admin users', () => {
    const wrapper = mountWithPlugins(SettingsLayout, {
      piniaOptions: {
        initialState: {
          auth: {
            user: { id: 1, name: 'Admin', role: 'admin', email: 'a@test.com', email_verified_at: '2024-01-01' },
          },
        },
      },
    })

    expect(wrapper.text()).toContain(nav.thresholds)

    const vListItems = wrapper.findAllComponents({ name: 'v-list-item' })
    expect(vListItems.length).toBe(5)
  })
})
