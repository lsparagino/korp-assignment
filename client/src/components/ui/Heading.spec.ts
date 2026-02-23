import { describe, expect, it } from 'vitest'
import { mountWithPlugins } from '@/test/setup'
import Heading from './Heading.vue'

describe('Heading.vue', () => {
  it('renders title and description', () => {
    const wrapper = mountWithPlugins(Heading, {
      props: {
        title: 'Test Title',
        description: 'Test Description',
      },
    })

    const titleEl = wrapper.find('[data-testid="heading-title"]')
    expect(titleEl.text()).toBe('Test Title')
    expect(titleEl.classes()).toContain('text-h5')

    const descEl = wrapper.find('[data-testid="heading-description"]')
    expect(descEl.text()).toBe('Test Description')

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

    const titleEl = wrapper.find('[data-testid="heading-title"]')
    expect(titleEl.classes()).toContain('text-h6')
    expect(titleEl.classes()).not.toContain('text-h5')

    // Root margin should be mb-4 instead of mb-6
    const rootEl = wrapper.find('div.mb-4')
    expect(rootEl.exists()).toBe(true)
  })
})
