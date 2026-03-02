import { flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { findByTestId } from '@/test/helpers'
import { mountWithPlugins } from '@/test/setup'
import TeamMemberDetailPage from './[memberId].vue'

vi.mock('vue-router', async () => {
  const actual = await vi.importActual('vue-router')
  return {
    ...actual,
    useRoute: () => ({ params: { memberId: '5' } }),
    useRouter: () => ({ push: vi.fn() }),
  }
})

vi.mock('@/api/team-members', () => ({
  fetchTeamMembers: vi.fn(),
  fetchTeamMember: vi.fn(),
  createTeamMember: vi.fn(),
  updateTeamMember: vi.fn(),
  deleteTeamMember: vi.fn(),
  promoteTeamMember: vi.fn(),
}))

vi.mock('@/api/transactions', () => ({
  fetchTransactions: vi.fn().mockResolvedValue({ data: { data: [] } }),
}))

vi.mock('@/queries/team-members', () => ({
  TEAM_MEMBER_QUERY_KEYS: { root: ['team-members'], byId: vi.fn() },
  teamMemberByIdQuery: () => ({
    key: ['team-members', 'detail', 5],
    query: async () => ({
      id: 5,
      name: 'John Doe',
      email: 'john@example.com',
      role: 'Member',
      wallet_access: 'limited',
      is_current: false,
      is_pending: false,
      assigned_wallets: [1],
    }),
  }),
}))

vi.mock('@/queries/wallets', () => ({
  WALLET_QUERY_KEYS: { root: ['wallets'], list: vi.fn(), byId: vi.fn() },
  walletsListQuery: () => ({
    key: ['wallets', { page: 1, perPage: 500 }],
    query: async () => ({
      data: [
        { id: 1, name: 'Savings', address: 'addr1', balance: 5000, locked_balance: 0, available_balance: 5000, currency: 'USD', status: 'active', can_delete: false },
      ],
      meta: { current_page: 1, last_page: 1, per_page: 500, total: 1, from: 1, to: 1 },
    }),
  }),
  useWalletList: vi.fn(),
  walletByIdQuery: vi.fn(),
}))

function makeAuthState (role: string, userId = 99) {
  return {
    auth: {
      user: { id: userId, name: 'Test Admin', role, email: 'admin@test.com', email_verified_at: '2024-01-01' },
      token: 'test-token',
    },
    company: {
      currentCompany: { id: 1, name: 'Test Corp' },
      companies: [{ id: 1, name: 'Test Corp' }],
    },
  }
}

describe('team-members/[memberId].vue', () => {
  let wrapper: ReturnType<typeof mountWithPlugins>

  afterEach(() => {
    wrapper?.unmount()
    document.body.innerHTML = ''
  })

  async function mountPage (authRole = 'admin', authUserId = 99) {
    wrapper = mountWithPlugins(TeamMemberDetailPage, {
      piniaOptions: { initialState: makeAuthState(authRole, authUserId) },
      attachTo: document.body,
    })
    await flushPromises()
    await wrapper.vm.$nextTick()
    await flushPromises()
    await wrapper.vm.$nextTick()
    await flushPromises()
    return wrapper
  }

  it('renders page heading', async () => {
    await mountPage()
    const heading = findByTestId('page-heading')
    expect(heading).not.toBeNull()
    expect(heading!.text()).toContain(en.teamMembers.memberDetails)
  })

  it('shows edit form fields', async () => {
    await mountPage()
    expect(findByTestId('member-name-input')).not.toBeNull()
    expect(findByTestId('member-email-input')).not.toBeNull()
    expect(findByTestId('member-save-btn')).not.toBeNull()
  })

  it('shows promote button for admin viewing a Member', async () => {
    await mountPage('admin')
    const btn = findByTestId('promote-demote-btn')
    expect(btn).not.toBeNull()
  })

  it('shows delete button for admin', async () => {
    await mountPage('admin')
    const btn = findByTestId('delete-member-btn')
    expect(btn).not.toBeNull()
  })

  it('hides management actions for non-admin users', async () => {
    await mountPage('member')
    expect(findByTestId('promote-demote-btn')).toBeNull()
    expect(findByTestId('delete-member-btn')).toBeNull()
  })
})
