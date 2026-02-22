import type { TeamMember } from '@/api/team-members'
import type { PaginationMeta } from '@/composables/useUrlPagination'
import { defineQuery, defineQueryOptions, useQuery } from '@pinia/colada'
import { computed, ref } from 'vue'
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

export const useTeamMemberList = defineQuery(() => {
  const page = ref(1)

  const { data, ...rest } = useQuery(
    teamMembersListQuery,
    () => page.value,
  )

  const members = computed<TeamMember[]>(() => data.value?.data ?? [])
  const meta = computed<PaginationMeta>(() => data.value?.meta ?? {
    current_page: 1, last_page: 1, per_page: 15, total: 0, from: null, to: null,
  })

  return { ...rest, page, members, meta }
})
