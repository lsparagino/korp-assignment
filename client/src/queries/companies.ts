import type { Company } from '@/types'
import { defineQueryOptions } from '@pinia/colada'
import { fetchCompanies } from '@/api/companies'

export const COMPANY_QUERY_KEYS = {
    root: ['companies'] as const,
}

export const companiesQuery = defineQueryOptions({
    key: COMPANY_QUERY_KEYS.root,
    query: async (): Promise<Company[]> => {
        const response = await fetchCompanies()
        return response.data.data
    },
})
