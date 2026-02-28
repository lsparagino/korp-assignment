import type { User } from '@/api/auth'
import { defineQueryOptions } from '@pinia/colada'
import { fetchUser } from '@/api/auth'

export const AUTH_QUERY_KEYS = {
  root: ['auth'] as const,
  user: ['auth', 'user'] as const,
}

export const userQuery = defineQueryOptions({
  key: AUTH_QUERY_KEYS.user,
  query: async (): Promise<User> => {
    const response = await fetchUser()
    return response.data
  },
})
