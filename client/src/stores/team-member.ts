import { useMutation, useQueryCache } from '@pinia/colada'
import { defineStore } from 'pinia'
import {
  createTeamMember as apiCreateMember,
  deleteTeamMember as apiDeleteMember,
  updateTeamMember as apiUpdateMember,
} from '@/api/team-members'
import { TEAM_MEMBER_QUERY_KEYS } from '@/queries/team-members'

interface TeamMemberForm {
  name: string
  email: string
  wallets: number[]
}

export const useTeamMemberStore = defineStore('team-member', () => {
  const queryCache = useQueryCache()

  async function invalidateQueries () {
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
    createMember,
    updateMember,
    deleteMember,
    invalidateQueries,
  }
})
