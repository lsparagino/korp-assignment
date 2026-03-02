import { flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { findByTestId } from '@/test/helpers'
import { mountWithPlugins } from '@/test/setup'
import TeamMemberModal from './TeamMemberModal.vue'

vi.mock('@/queries/wallets', () => ({
  WALLET_QUERY_KEYS: { root: ['wallets'], list: vi.fn(), byId: vi.fn() },
  walletsListQuery: () => ({
    key: ['wallets', { page: 1, perPage: 500 }],
    query: async () => ({
      data: [
        { id: 1, name: 'Savings', address: 'addr1', balance: 5000, locked_balance: 0, available_balance: 5000, currency: 'USD', status: 'active', can_delete: false },
        { id: 2, name: 'Business', address: 'addr2', balance: 3000, locked_balance: 0, available_balance: 3000, currency: 'EUR', status: 'active', can_delete: false },
      ],
      meta: { current_page: 1, last_page: 1, per_page: 500, total: 2, from: 1, to: 2 },
    }),
  }),
  useWalletList: vi.fn(),
  walletByIdQuery: vi.fn(),
}))

vi.mock('@/stores/team-member', () => ({
  useTeamMemberStore: () => ({
    createMember: vi.fn().mockResolvedValue({}),
    updateMember: vi.fn().mockResolvedValue({}),
  }),
}))

describe('TeamMemberModal.vue', () => {
  let wrapper: ReturnType<typeof mountWithPlugins>

  afterEach(() => {
    wrapper?.unmount()
    document.body.innerHTML = ''
  })

  async function mountModal(props: Record<string, unknown> = {}) {
    wrapper = mountWithPlugins(TeamMemberModal, {
      props: {
        modelValue: true,
        ...props,
      },
      attachTo: document.body,
    })
    // Give Vuetify dialog and query time to resolve
    await flushPromises()
    await wrapper.vm.$nextTick()
    await flushPromises()
    await wrapper.vm.$nextTick()
    await flushPromises()
    return wrapper
  }


  it('renders dialog with form fields for new member', async () => {
    await mountModal()

    expect(findByTestId('member-name-input')).not.toBeNull()
    expect(findByTestId('member-email-input')).not.toBeNull()
    expect(findByTestId('member-submit-btn')).not.toBeNull()
  })

  it('shows add member title when no user prop', async () => {
    await mountModal()

    // Dialog title is rendered inside the card
    const title = document.body.querySelector('.v-card-title')
    expect(title).not.toBeNull()
    expect(title!.textContent).toContain(en.teamMembers.addMember)
  })

  it('shows edit member title when user prop is provided', async () => {
    await mountModal({
      user: {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        role: 'member',
        wallet_access: 'limited',
        is_current: false,
        is_pending: false,
        assigned_wallets: [1],
      },
    })

    const title = document.body.querySelector('.v-card-title')
    expect(title).not.toBeNull()
    expect(title!.textContent).toContain(en.teamMembers.editMember)
  })

  it('has submit button disabled when form is empty', async () => {
    await mountModal()

    const submitBtn = findByTestId('member-submit-btn')
    expect(submitBtn).not.toBeNull()
    expect((submitBtn!.element as HTMLButtonElement).disabled).toBe(true)
  })

  it('shows invite member label on submit button for new member', async () => {
    await mountModal()

    const submitBtn = findByTestId('member-submit-btn')
    expect(submitBtn).not.toBeNull()
    expect(submitBtn!.text()).toContain(en.teamMembers.inviteMember)
  })
})
