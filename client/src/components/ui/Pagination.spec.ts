import type { PaginationMeta } from '@/api/pagination'
import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { mountWithPlugins } from '@/test/setup'
import Pagination from './Pagination.vue'

const defaultMeta: PaginationMeta = {
  current_page: 2,
  last_page: 5,
  per_page: 25,
  total: 120,
  from: 26,
  to: 50,
}

describe('Pagination.vue', () => {
  let wrapper: ReturnType<typeof mountWithPlugins>

  beforeEach(() => {
    wrapper = mountWithPlugins(Pagination, {
      props: {
        meta: defaultMeta,
      },
    })
  })

  afterEach(() => {
    wrapper?.unmount()
  })

  it('renders pagination meta text properly', () => {
    // Uses i18n key 'pagination.showing' -> 'Showing {from} to {to} of {total} entries'
    const metaText = wrapper.find('[data-testid="pagination-meta"]').text()
    expect(metaText).toContain('26')
    expect(metaText).toContain('50')
    expect(metaText).toContain('120')
  })

  it('binds current page and last page to v-pagination', () => {
    const vPagination = wrapper.findComponent({ name: 'v-pagination' })
    expect(vPagination.exists()).toBe(true)
    expect(vPagination.props('length')).toBe(5)
    expect(vPagination.props('modelValue')).toBe(2)
  })

  it('emits update:page when v-pagination value changes', async () => {
    const vPagination = wrapper.findComponent({ name: 'v-pagination' })
    await vPagination.vm.$emit('update:modelValue', 3)

    expect(wrapper.emitted('update:page')).toBeTruthy()
    expect(wrapper.emitted('update:page')?.[0]).toEqual([3])
  })

  it('emits update:per-page when v-select value changes', async () => {
    const vSelect = wrapper.findComponent({ name: 'v-select' })
    await vSelect.vm.$emit('update:modelValue', 50)

    expect(wrapper.emitted('update:per-page')).toBeTruthy()
    expect(wrapper.emitted('update:per-page')?.[0]).toEqual([50])
  })
})
