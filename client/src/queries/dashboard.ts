import { defineQueryOptions } from '@pinia/colada'
import { fetchDashboard } from '@/api/dashboard'

export const DASHBOARD_QUERY_KEYS = {
  root: ['dashboard'] as const,
  byCompany: (companyId: number) => [...DASHBOARD_QUERY_KEYS.root, companyId] as const,
}

export const dashboardQuery = defineQueryOptions(
  (companyId: number) => ({
    key: DASHBOARD_QUERY_KEYS.byCompany(companyId),
    query: async () => {
      const response = await fetchDashboard()
      return response.data
    },
  }),
)
