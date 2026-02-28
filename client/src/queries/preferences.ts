import type { UserPreferences } from '@/api/settings'
import { defineQueryOptions } from '@pinia/colada'
import { fetchUserPreferences } from '@/api/settings'

export const PREFERENCES_QUERY_KEYS = {
  root: ['preferences'] as const,
}

export const preferencesQuery = defineQueryOptions({
  key: PREFERENCES_QUERY_KEYS.root,
  query: async (): Promise<UserPreferences> => {
    const response = await fetchUserPreferences()
    return response.data.data
  },
})
