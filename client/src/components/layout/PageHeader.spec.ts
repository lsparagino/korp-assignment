import { createTestingPinia } from '@pinia/testing'
import { describe, expect, it, vi } from 'vitest'
import { mountWithPlugins } from '@/test/setup'
import PageHeader from './PageHeader.vue'

describe('PageHeader.vue', () => {
  it('renders title', () => {
    const wrapper = mountWithPlugins(PageHeader, {
      props: {
        title: 'Dashboard',
      },
    })

    expect(wrapper.find('h1').text()).toContain('Dashboard')
  })

  it('renders slot content', () => {
    const wrapper = mountWithPlugins(PageHeader, {
      props: {
        title: 'Title',
      },
      slots: {
        default: '<button class="test-btn">Action</button>',
      },
    })

    expect(wrapper.find('.test-btn').exists()).toBe(true)
    expect(wrapper.text()).toContain('Action')
  })

  it('renders company name when currentCompany is set', async () => {
    const wrapper = mountWithPlugins(PageHeader, {
      props: {
        title: 'Dashboard',
      },
      global: {
        plugins: [
          createTestingPinia({
            createSpy: vi.fn,
            initialState: {
              company: {
                currentCompany: { id: 1, name: 'ACME Corp' },
                companies: [{ id: 1, name: 'ACME Corp' }],
              },
            },
          }),
        ],
      },
    })

    // Component is already mounted with the desired state
    expect(wrapper.find('h1').text()).toContain('- ACME Corp')
  })
})
