import { describe, expect, it } from 'vitest'
import Heading from './Heading.vue'
import { mountWithPlugins } from '@/test/setup'

describe('Heading.vue', () => {
    it('renders title and description', () => {
        const wrapper = mountWithPlugins(Heading, {
            props: {
                title: 'Test Title',
                description: 'Test Description',
            },
        })

        expect(wrapper.text()).toContain('Test Title')
        expect(wrapper.text()).toContain('Test Description')

        // Default variant assertions
        const titleEl = wrapper.find('h2')
        expect(titleEl.classes()).toContain('text-h5')

        const rootEl = wrapper.find('div.mb-6')
        expect(rootEl.exists()).toBe(true)
    })

    it('renders small variant correctly', () => {
        const wrapper = mountWithPlugins(Heading, {
            props: {
                title: 'Test Title',
                description: 'Test Description',
                variant: 'small',
            },
        })

        const titleEl = wrapper.find('h2')
        expect(titleEl.classes()).toContain('text-h6')
        expect(titleEl.classes()).not.toContain('text-h5')

        // Root margin should be mb-4 instead of mb-6
        const rootEl = wrapper.find('div.mb-4')
        expect(rootEl.exists()).toBe(true)
    })
})
