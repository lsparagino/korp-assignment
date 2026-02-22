import type { TeamMember } from '@/api/team-members'
import { useMutation, useQuery, useQueryCache } from '@pinia/colada'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import {
  createTeamMember as apiCreateMember,
  deleteTeamMember as apiDeleteMember,
  updateTeamMember as apiUpdateMember,
} from '@/api/team-members'
import { TEAM_MEMBER_QUERY_KEYS, teamMembersListQuery } from '@/queries/team-members'

interface TeamMemberForm {
  name: string
  email: string
  wallets: number[]
}

export const useTeamMemberStore = defineStore('team-member', () => {
  const queryCache = useQueryCache()
  const page = ref(1)

  const { data, isPending: listLoading } = useQuery(
    teamMembersListQuery,
    () => page.value,
  )

  const members = computed<TeamMember[]>(() => data.value?.data ?? [])
  const pagination = computed(() => ({
    currentPage: data.value?.meta?.current_page ?? 1,
    lastPage: data.value?.meta?.last_page ?? 1,
    total: data.value?.meta?.total ?? 0,
  }))

  async function invalidateQueries() {
    await queryCache.invalidateQueries({ key: TEAM_MEMBER_QUERY_KEYS.root })
  }

  const { mutateAsync: createMember } = useMutation({
    mutation: (form: TeamMemberForm) => apiCreateMember(form),
    onSettled: async () => await invalidateQueries(),
  })

  const { mutateAsync: updateMember } = useMutation({
    mutation: ({ id, form }: { id: number, form: TeamMemberForm }) => apiUpdateMember(id, form),
    onSettled: async () => await invalidateQueries(),
  })

  const { mutateAsync: deleteMember } = useMutation({
    mutation: (id: number) => apiDeleteMember(id),
    onSettled: async () => await invalidateQueries(),
  })

  return {
    page,
    members,
    pagination,
    listLoading,
    createMember,
    updateMember,
    deleteMember,
    invalidateQueries,
  }
})

