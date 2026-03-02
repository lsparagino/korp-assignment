import { flushPromises } from '@vue/test-utils'
import { computed, ref } from 'vue'
import { describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import TeamMembersPage from './index.vue'

vi.mock('@/api/team-members', () => ({
    fetchTeamMembers: vi.fn(),
    fetchTeamMember: vi.fn(),
    createTeamMember: vi.fn(),
    updateTeamMember: vi.fn(),
    deleteTeamMember: vi.fn(),
    promoteTeamMember: vi.fn(),
}))

vi.mock('@/api/wallets', () => ({
    fetchWallets: vi.fn(),
}))

vi.mock('@/queries/team-members', () => {
    return {
        TEAM_MEMBER_QUERY_KEYS: { root: ['team-members'] },
        useTeamMemberList: vi.fn(() => ({
            members: computed(() => [
                { id: 1, name: 'Admin User', email: 'admin@test.com', role: 'Admin', wallet_access: 'All', is_pending: false },
                { id: 2, name: 'Member User', email: 'member@test.com', role: 'Member', wallet_access: '2 wallets', is_pending: false },
                { id: 3, name: 'Pending User', email: 'pending@test.com', role: 'Member', wallet_access: 'None', is_pending: true },
            ]),
            meta: computed(() => ({ current_page: 1, last_page: 1, per_page: 10, total: 3, from: 1, to: 3 })),
            isPending: ref(false),
            refetch: vi.fn(),
            page: ref(1),
        })),
    }
})

function makeAuthState(role: string) {
    return {
        auth: {
            user: { id: 1, name: 'Admin User', role, email: 'admin@test.com', email_verified_at: '2024-01-01' },
            token: 'test-token',
        },
        company: {
            currentCompany: { id: 1, name: 'Test Corp' },
            companies: [{ id: 1, name: 'Test Corp' }],
        },
    }
}

describe('team-members/index.vue', () => {
    it('renders member rows', async () => {
        const wrapper = mountWithPlugins(TeamMembersPage, {
            piniaOptions: { initialState: makeAuthState('admin') },
        })
        await flushPromises()

        expect(wrapper.text()).toContain('Admin User')
        expect(wrapper.text()).toContain('Member User')
        expect(wrapper.text()).toContain('Pending User')
    })

    it('shows pending invitation badge', async () => {
        const wrapper = mountWithPlugins(TeamMembersPage, {
            piniaOptions: { initialState: makeAuthState('admin') },
        })
        await flushPromises()

        expect(wrapper.text()).toContain(en.teamMembers.pendingInvitation)
    })

    it('shows add member button for admin', async () => {
        const wrapper = mountWithPlugins(TeamMembersPage, {
            piniaOptions: { initialState: makeAuthState('admin') },
        })
        await flushPromises()

        expect(wrapper.find('[data-testid="add-member-btn"]').exists()).toBe(true)
    })

    it('hides add member button for member', async () => {
        const wrapper = mountWithPlugins(TeamMembersPage, {
            piniaOptions: { initialState: makeAuthState('member') },
        })
        await flushPromises()

        expect(wrapper.find('[data-testid="add-member-btn"]').exists()).toBe(false)
    })

    it('has clickable rows with data-testid', async () => {
        const wrapper = mountWithPlugins(TeamMembersPage, {
            piniaOptions: { initialState: makeAuthState('admin') },
        })
        await flushPromises()

        expect(wrapper.find('[data-testid="member-row-1"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="member-row-2"]').exists()).toBe(true)
    })

    it('does not show action buttons in the table', async () => {
        const wrapper = mountWithPlugins(TeamMembersPage, {
            piniaOptions: { initialState: makeAuthState('admin') },
        })
        await flushPromises()

        expect(wrapper.find('[data-testid="edit-btn"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="delete-btn"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="promote-btn-2"]').exists()).toBe(false)
    })
})
