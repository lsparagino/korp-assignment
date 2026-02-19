import type { TeamMember } from '@/api/team-members'
import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { useQuery, useMutation, useQueryCache } from '@pinia/colada'
import {
    createTeamMember as apiCreateMember,
    deleteTeamMember as apiDeleteMember,
    updateTeamMember as apiUpdateMember,
} from '@/api/team-members'
import { teamMembersListQuery, TEAM_MEMBER_QUERY_KEYS } from '@/queries/team-members'

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

    const members = computed<TeamMember[]>(() => data.value?.members ?? [])
    const pagination = computed(() => ({
        currentPage: data.value?.pagination?.current_page ?? 1,
        lastPage: data.value?.pagination?.last_page ?? 1,
        total: data.value?.pagination?.total ?? 0,
    }))

    function invalidateQueries() {
        queryCache.invalidateQueries({ key: TEAM_MEMBER_QUERY_KEYS.root })
    }

    const { mutateAsync: createMember } = useMutation({
        mutation: (form: TeamMemberForm) => apiCreateMember(form),
        onSettled: invalidateQueries,
    })

    const { mutateAsync: updateMember } = useMutation({
        mutation: ({ id, form }: { id: number, form: TeamMemberForm }) => apiUpdateMember(id, form),
        onSettled: invalidateQueries,
    })

    const { mutateAsync: deleteMember } = useMutation({
        mutation: (id: number) => apiDeleteMember(id),
        onSettled: invalidateQueries,
    })

    return {
        page,
        members,
        pagination,
        listLoading,
        createMember,
        updateMember,
        deleteMember,
    }
})
