import { api } from '@/plugins/api'

interface TeamMemberForm {
    name: string
    email: string
    wallets: number[]
}

export function fetchTeamMembers(page = 1) {
    return api.get('/team-members', { params: { page } })
}

export function createTeamMember(form: TeamMemberForm) {
    return api.post('/team-members', form)
}

export function updateTeamMember(id: number, form: TeamMemberForm) {
    return api.put(`/team-members/${id}`, form)
}

export function deleteTeamMember(id: number) {
    return api.delete(`/team-members/${id}`)
}
