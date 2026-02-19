import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useCompanyStore } from './company'

vi.mock('@/api/companies', () => ({
    fetchCompanies: vi.fn(),
}))

describe('useCompanyStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    describe('initial state', () => {
        it('starts with empty companies and null currentCompany', () => {
            const store = useCompanyStore()
            expect(store.companies).toEqual([])
            expect(store.currentCompany).toBeNull()
        })

        it('hasCompanies is false initially', () => {
            const store = useCompanyStore()
            expect(store.hasCompanies).toBe(false)
        })

        it('companyLabel defaults to "Select company"', () => {
            const store = useCompanyStore()
            expect(store.companyLabel).toBe('Select company')
        })
    })

    describe('setCurrentCompany', () => {
        it('sets the current company', () => {
            const store = useCompanyStore()
            const company = { id: 1, name: 'ACME Corp' }

            store.setCurrentCompany(company as any)

            expect(store.currentCompany).toEqual(company)
            expect(store.companyLabel).toBe('ACME Corp')
        })
    })

    describe('fetchCompanies', () => {
        it('populates companies and auto-selects first', async () => {
            const { fetchCompanies: apiFetchCompanies } = await import('@/api/companies')
            const companies = [
                { id: 1, name: 'Alpha' },
                { id: 2, name: 'Beta' },
            ]
            vi.mocked(apiFetchCompanies).mockResolvedValue({ data: { data: companies } } as any)

            const store = useCompanyStore()
            await store.fetchCompanies()

            expect(store.companies).toEqual(companies)
            expect(store.currentCompany).toEqual(companies[0])
            expect(store.hasCompanies).toBe(true)
        })

        it('does not override currentCompany if already set', async () => {
            const { fetchCompanies: apiFetchCompanies } = await import('@/api/companies')
            const existing = { id: 99, name: 'Existing' }
            const companies = [{ id: 1, name: 'Alpha' }]
            vi.mocked(apiFetchCompanies).mockResolvedValue({ data: { data: companies } } as any)

            const store = useCompanyStore()
            store.setCurrentCompany(existing as any)
            await store.fetchCompanies()

            expect(store.currentCompany).toEqual(existing)
        })

        it('clears companies on fetch failure', async () => {
            const { fetchCompanies: apiFetchCompanies } = await import('@/api/companies')
            vi.mocked(apiFetchCompanies).mockRejectedValue(new Error('Network error'))

            const store = useCompanyStore()
            await store.fetchCompanies()

            expect(store.companies).toEqual([])
            expect(store.hasCompanies).toBe(false)
        })

        it('handles empty response', async () => {
            const { fetchCompanies: apiFetchCompanies } = await import('@/api/companies')
            vi.mocked(apiFetchCompanies).mockResolvedValue({ data: { data: [] } } as any)

            const store = useCompanyStore()
            await store.fetchCompanies()

            expect(store.companies).toEqual([])
            expect(store.currentCompany).toBeNull()
            expect(store.hasCompanies).toBe(false)
        })
    })
})
