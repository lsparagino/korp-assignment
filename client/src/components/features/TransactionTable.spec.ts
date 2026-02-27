import type { Transaction } from '@/api/transactions'
import { flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import TransactionTable from './TransactionTable.vue'

const mockWallets = [
  { id: 1, name: 'Savings', address: 'addr1', balance: 5000, locked_balance: 0, available_balance: 5000, currency: 'USD', status: 'active' as const, can_delete: false },
  { id: 2, name: 'Business', address: 'addr2', balance: 3000, locked_balance: 0, available_balance: 3000, currency: 'USD', status: 'active' as const, can_delete: false },
]

function createTransaction(overrides: Partial<Transaction> = {}): Transaction {
  return {
    id: 1,
    group_id: 'grp-1',
    type: 'debit',
    amount: -500,
    currency: 'USD',
    status: 'completed',
    exchange_rate: 1,
    reference: 'REF-001',
    wallet_id: 1,
    counterpart_wallet_id: 2,
    wallet: { id: 1, name: 'Savings', address: 'addr-1' },
    counterpart_wallet: { id: 2, name: 'Business', address: 'addr-2' },
    external: false,
    external_address: null,
    external_name: null,
    initiator_user_id: 1,
    reviewer_user_id: null,
    initiator: null,
    reviewer: null,
    reject_reason: null,
    notes: null,
    created_at: '2026-02-20T10:00:00.000Z',
    ...overrides,
  }
}

describe('TransactionTable.vue', () => {
  let wrapper: ReturnType<typeof mountWithPlugins>

  afterEach(() => {
    wrapper?.unmount()
    document.body.innerHTML = ''
  })

  async function mountTable(props: Record<string, unknown> = {}) {
    wrapper = mountWithPlugins(TransactionTable, {
      props: {
        items: [],
        wallets: mockWallets,
        ...props,
      },
      attachTo: document.body,
    })
    await flushPromises()
    return wrapper
  }

  function bodyText() {
    return document.body.textContent || ''
  }

  it('renders column headers', async () => {
    await mountTable()

    const text = bodyText()
    const headers = en.transactions.tableHeaders
    expect(text).toContain(headers.transaction)
    expect(text).toContain(headers.date)
    expect(text).toContain(headers.type)
    expect(text).toContain(headers.status)
    expect(text).toContain(headers.amount)
    expect(text).toContain(headers.fromWallet)
    expect(text).toContain(headers.toWallet)
    expect(text).toContain(headers.actions)
  })

  it('shows empty state when no items', async () => {
    await mountTable({ items: [] })

    expect(bodyText()).toContain(en.transactions.noTransactions)
  })

  it('renders rows for each transaction', async () => {
    await mountTable({
      items: [
        createTransaction({ id: 1 }),
        createTransaction({ id: 2 }),
      ],
    })

    const rows = document.body.querySelectorAll('tbody tr')
    expect(rows.length).toBe(2)
  })

  it('labels internal transfer as "transfer"', async () => {
    // Internal transfer: both wallet_id & counterpart_wallet_id assigned, not external
    await mountTable({
      items: [createTransaction({
        type: 'debit',
        wallet_id: 1,
        counterpart_wallet_id: 2,
        external: false,
      })],
    })

    // The chip renders the label — check the DOM for the text content
    const chips = document.body.querySelectorAll('.v-chip')
    const chipTexts = Array.from(chips).map(c => c.textContent?.trim().toLowerCase())
    expect(chipTexts).toContain('transfer')
  })

  it('displays absolute amount for transfers', async () => {
    await mountTable({
      items: [createTransaction({
        type: 'debit',
        amount: -1000,
        wallet_id: 1,
        counterpart_wallet_id: 2,
        external: false,
      })],
    })

    // Should show $1,000.00 (absolute value), not -$1,000.00
    expect(bodyText()).toContain('$1,000.00')
    expect(bodyText()).not.toContain('-$1,000.00')
  })

  it('maps from/to wallets correctly for external debit', async () => {
    await mountTable({
      items: [createTransaction({
        type: 'debit',
        external: true,
        external_name: 'External Vendor',
        counterpart_wallet_id: null,
        counterpart_wallet: null,
      })],
    })

    const text = bodyText()
    // Debit: from = wallet (Savings), to = external
    expect(text).toContain('Savings')
    expect(text).toContain('External Vendor')
  })

  it('shows pending instead of pending_approval', async () => {
    await mountTable({
      items: [createTransaction({ status: 'pending_approval' })],
    })

    const chips = document.body.querySelectorAll('.v-chip')
    const chipTexts = Array.from(chips).map(c => c.textContent?.trim().toLowerCase())
    expect(chipTexts).toContain('pending')
  })

})
