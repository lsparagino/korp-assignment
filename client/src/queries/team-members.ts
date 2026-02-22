import { defineQueryOptions } from '@pinia/colada'
import { fetchTeamMembers } from '@/api/team-members'

export const TEAM_MEMBER_QUERY_KEYS = {
  root: ['team-members'] as const,
  list: (page: number) => [...TEAM_MEMBER_QUERY_KEYS.root, page] as const,
}

export const teamMembersListQuery = defineQueryOptions(
  (page: number) => ({
    key: TEAM_MEMBER_QUERY_KEYS.list(page),
    query: async () => {
      const response = await fetchTeamMembers(page)
      return response.data
    },
  }),
)
