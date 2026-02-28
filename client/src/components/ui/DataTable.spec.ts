import type { PaginationMeta } from '@/api/pagination'
import { describe, expect, it } from 'vitest'
import { mountWithPlugins } from '@/test/setup'
import DataTable from './DataTable.vue'

const mockMeta: PaginationMeta = {
  current_page: 1,
  last_page: 3,
  per_page: 10,
  total: 30,
  from: 1,
  to: 10,
}

describe('DataTable.vue', () => {
  it('renders columns and body slots', () => {
    const wrapper = mountWithPlugins(DataTable, {
      slots: {
        columns: '<th class="test-col">ID</th><th class="test-col">Name</th>',
        body: '<tr class="test-row"><td>1</td><td>Test Name</td></tr>',
      },
    })

    expect(wrapper.findAll('.test-col').length).toBe(2)
    expect(wrapper.find('.test-row').exists()).toBe(true)
    expect(wrapper.text()).toContain('Test Name')
  })

  it('renders footer slot', () => {
    const wrapper = mountWithPlugins(DataTable, {
      slots: {
        footer: '<div class="test-footer">Table Footer</div>',
      },
    })

    expect(wrapper.find('.test-footer').exists()).toBe(true)
    expect(wrapper.text()).toContain('Table Footer')
  })

  it('hides pagination when meta is not provided', () => {
    const wrapper = mountWithPlugins(DataTable)

    const pagination = wrapper.findComponent({ name: 'Pagination' })
    expect(pagination.exists()).toBe(false)
  })

  it('shows pagination when meta is provided and passes events', async () => {
    const wrapper = mountWithPlugins(DataTable, {
      props: {
        meta: mockMeta,
      },
    })

    const pagination = wrapper.findComponent({ name: 'Pagination' })
    expect(pagination.exists()).toBe(true)
    expect(pagination.props('meta')).toEqual(mockMeta)

    // Simulate emitted events from Pagination child component
    await pagination.vm.$emit('update:page', 2)
    await pagination.vm.$emit('update:per-page', 25)

    // Check if DataTable re-emits them
    expect(wrapper.emitted('update:page')).toBeTruthy()
    expect(wrapper.emitted('update:page')?.[0]).toEqual([2])
    expect(wrapper.emitted('update:per-page')).toBeTruthy()
    expect(wrapper.emitted('update:per-page')?.[0]).toEqual([25])
  })

  it('applies loading state to v-card', () => {
    const wrapper = mountWithPlugins(DataTable, {
      props: {
        loading: true,
      },
    })

    const vCard = wrapper.findComponent({ name: 'v-card' })
    expect(vCard.props('loading')).toBe(true)
  })
})
