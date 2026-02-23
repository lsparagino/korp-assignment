import { describe, expect, it } from 'vitest'
import SettingsLayout from './SettingsLayout.vue'
import { mountWithPlugins } from '@/test/setup'

describe('SettingsLayout.vue', () => {
    it('renders navigation items', () => {
        const wrapper = mountWithPlugins(SettingsLayout, {
            global: {
                stubs: {
                    RouterLink: true,
                },
            },
        })

        // With our i18n mock setup or real en.json, the titles should be present
        expect(wrapper.text()).toContain('Profile')
        expect(wrapper.text()).toContain('Password')
        expect(wrapper.text()).toContain('Two-Factor')

        // Uses v-list-item components
        const vListItems = wrapper.findAllComponents({ name: 'v-list-item' })
        expect(vListItems.length).toBe(3)
    })

    it('renders slot content', () => {
        const wrapper = mountWithPlugins(SettingsLayout, {
            slots: {
                default: '<div class="settings-content">Settings Form</div>',
            },
        })

        expect(wrapper.find('.settings-content').exists()).toBe(true)
        expect(wrapper.text()).toContain('Settings Form')
    })
})
