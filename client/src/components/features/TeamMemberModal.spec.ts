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
  }),
}))

describe('TeamMemberModal.vue (invite-only)', () => {
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
    await flushPromises()
    await wrapper.vm.$nextTick()
    await flushPromises()
    await wrapper.vm.$nextTick()
    await flushPromises()
    return wrapper
  }

  it('renders dialog with form fields', async () => {
    await mountModal()

    expect(findByTestId('member-name-input')).not.toBeNull()
    expect(findByTestId('member-email-input')).not.toBeNull()
    expect(findByTestId('member-submit-btn')).not.toBeNull()
  })

  it('shows add member title', async () => {
    await mountModal()

    const title = document.body.querySelector('.v-card-title')
    expect(title).not.toBeNull()
    expect(title!.textContent).toContain(en.teamMembers.addMember)
  })

  it('has submit button disabled when form is empty', async () => {
    await mountModal()

    const submitBtn = findByTestId('member-submit-btn')
    expect(submitBtn).not.toBeNull()
    expect((submitBtn!.element as HTMLButtonElement).disabled).toBe(true)
  })

  it('shows invite member label on submit button', async () => {
    await mountModal()

    const submitBtn = findByTestId('member-submit-btn')
    expect(submitBtn).not.toBeNull()
    expect(submitBtn!.text()).toContain(en.teamMembers.inviteMember)
  })
})
