import { flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { useCompanyStore } from '@/stores/company'
import { findAllByTestId, findByTestId } from '@/test/helpers'
import { mountWithPlugins } from '@/test/setup'

import CompanySelector from './CompanySelector.vue'

const mockCompanies = [
  { id: 1, name: 'Acme Corp' },
  { id: 2, name: 'Globex Inc' },
]

vi.mock('@/stores/company', () => ({
  useCompanyStore: vi.fn(),
}))

function mockStore (overrides: Record<string, unknown> = {}) {
  ; (useCompanyStore as unknown as ReturnType<typeof vi.fn>).mockReturnValue({
    companies: mockCompanies,
    currentCompany: mockCompanies[0],
    hasCompanies: true,
    companyLabel: 'Acme Corp',
    setCurrentCompany: vi.fn(),
    ...overrides,
  })
}

describe('CompanySelector.vue', () => {
  let wrapper: ReturnType<typeof mountWithPlugins>

  afterEach(() => {
    wrapper?.unmount()
    document.body.innerHTML = ''
  })

  it('renders selector button when companies exist', () => {
    mockStore()
    wrapper = mountWithPlugins(CompanySelector, { attachTo: document.body })

    expect(findByTestId('company-selector-btn')).not.toBeNull()
  })

  it('hides selector when no companies exist', () => {
    mockStore({ hasCompanies: false, companies: [] })
    wrapper = mountWithPlugins(CompanySelector, { attachTo: document.body })

    expect(findByTestId('company-selector-btn')).toBeNull()
  })

  it('displays the company label on the button', () => {
    mockStore({ companyLabel: 'Acme Corp' })
    wrapper = mountWithPlugins(CompanySelector, { attachTo: document.body })

    const btn = findByTestId('company-selector-btn')
    expect(btn).not.toBeNull()
    expect(btn!.text()).toContain('Acme Corp')
  })

  it('shows select company label when no current company', () => {
    mockStore({ companyLabel: en.company.selectCompany, currentCompany: null })
    wrapper = mountWithPlugins(CompanySelector, { attachTo: document.body })

    const btn = findByTestId('company-selector-btn')
    expect(btn).not.toBeNull()
    expect(btn!.text()).toContain(en.company.selectCompany)
  })

  it('renders company list items on menu open', async () => {
    mockStore()
    wrapper = mountWithPlugins(CompanySelector, { attachTo: document.body })

    const btn = findByTestId('company-selector-btn')
    await btn!.trigger('click')
    await flushPromises()

    const items = findAllByTestId('company-list-item')
    expect(items.length).toBe(2)
  })

  it('calls setCurrentCompany when a list item is clicked', async () => {
    const setCurrentCompany = vi.fn()
    mockStore({ setCurrentCompany })
    wrapper = mountWithPlugins(CompanySelector, { attachTo: document.body })

    const btn = findByTestId('company-selector-btn')
    await btn!.trigger('click')
    await flushPromises()

    const items = findAllByTestId('company-list-item')
    const secondItem = items[1]!
    await secondItem.trigger('click')
    await flushPromises()

    expect(setCurrentCompany).toHaveBeenCalledWith(mockCompanies[1])
  })
})
