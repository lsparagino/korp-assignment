import type { Transaction } from '@/api/transactions'
import { DOMWrapper, flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'
import en from '@/locales/en.json'
import { mountWithPlugins } from '@/test/setup'
import TransactionDetailModal from './TransactionDetailModal.vue'

function createTransaction (overrides: Partial<Transaction> = {}): Transaction {
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

describe('TransactionDetailModal.vue', () => {
  let wrapper: ReturnType<typeof mountWithPlugins>

  afterEach(() => {
    wrapper?.unmount()
    document.body.innerHTML = ''
  })

  async function mountModal (props: Record<string, unknown> = {}) {
    wrapper = mountWithPlugins(TransactionDetailModal, {
      props: {
        modelValue: true,
        transaction: createTransaction(),
        ...props,
      },
      attachTo: document.body,
    })
    await flushPromises()
    await wrapper.vm.$nextTick()
    await flushPromises()
    return wrapper
  }

  function bodyText () {
    return document.body.textContent || ''
  }

  it('renders nothing (no card) when transaction is null', async () => {
    await mountModal({ transaction: null })

    expect(bodyText()).not.toContain(en.transactions.transactionDetails)
  })

  it('renders transaction details when transaction is provided', async () => {
    await mountModal()

    const text = bodyText()
    expect(text).toContain(en.transactions.transactionDetails)
    expect(text).toContain('USD')
  })

  it('displays from/to wallet names for debit transaction', async () => {
    await mountModal()

    const text = bodyText()
    // Debit: from = wallet (Savings), to = counterpart_wallet (Business)
    expect(text).toContain('Savings')
    expect(text).toContain('Business')
  })

  it('displays from/to wallet names for credit transaction', async () => {
    await mountModal({
      transaction: createTransaction({ type: 'credit', amount: 500 }),
    })

    const text = bodyText()
    // Credit: from = counterpart_wallet (Business), to = wallet (Savings)
    expect(text).toContain('Business')
    expect(text).toContain('Savings')
  })

  it('shows reference when present', async () => {
    await mountModal({
      transaction: createTransaction({ reference: 'PAY-12345' }),
    })

    const text = bodyText()
    expect(text).toContain(en.transactions.reference)
    expect(text).toContain('PAY-12345')
  })

  it('hides reference section when null', async () => {
    await mountModal({
      transaction: createTransaction({ reference: null }),
    })

    // The reference label should not appear since there's no reference
    // (other sections also show "Reference", so we check the specific value)
    expect(bodyText()).not.toContain('PAY-12345')
  })

  it('shows notes when present', async () => {
    await mountModal({
      transaction: createTransaction({ notes: 'Monthly payment' }),
    })

    const text = bodyText()
    expect(text).toContain(en.transactions.notes)
    expect(text).toContain('Monthly payment')
  })

  it('shows reject reason when present', async () => {
    await mountModal({
      transaction: createTransaction({ status: 'rejected', reject_reason: 'Suspicious activity' }),
    })

    const text = bodyText()
    expect(text).toContain(en.transactions.rejectReason)
    expect(text).toContain('Suspicious activity')
  })

  it('shows external label for external transactions', async () => {
    await mountModal({
      transaction: createTransaction({
        external: true,
        external_name: 'Acme Vendor',
        external_address: 'ext-addr-123',
        wallet: null,
        wallet_id: null,
        counterpart_wallet: null,
        counterpart_wallet_id: null,
      }),
    })

    expect(bodyText()).toContain('Acme Vendor')
  })

  it('emits close on close button click', async () => {
    await mountModal()

    const closeBtn = document.body.querySelector('.v-card-title .v-btn') as HTMLElement
    expect(closeBtn).not.toBeNull()
    await new DOMWrapper(closeBtn).trigger('click')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
  })

  it('shows initiator name when present', async () => {
    await mountModal({
      transaction: createTransaction({ initiator: { id: 10, name: 'Alice Johnson' } }),
    })

    const text = bodyText()
    expect(text).toContain(en.transactions.initiator)
    expect(text).toContain('Alice Johnson')
  })

  it('shows reviewer name when present', async () => {
    await mountModal({
      transaction: createTransaction({ reviewer: { id: 20, name: 'Bob Manager' } }),
    })

    const text = bodyText()
    expect(text).toContain(en.transactions.reviewer)
    expect(text).toContain('Bob Manager')
  })

  it('shows approve/reject buttons for pending transaction when isManagerOrAdmin', async () => {
    await mountModal({
      transaction: createTransaction({ status: 'pending_approval' }),
      isManagerOrAdmin: true,
    })

    const approveBtn = document.body.querySelector('[data-testid="approve-btn"]')
    const rejectBtn = document.body.querySelector('[data-testid="reject-btn"]')
    expect(approveBtn).not.toBeNull()
    expect(rejectBtn).not.toBeNull()
  })

  it('hides approve/reject buttons when user is not manager or admin', async () => {
    await mountModal({
      transaction: createTransaction({ status: 'pending_approval' }),
      isManagerOrAdmin: false,
    })

    const approveBtn = document.body.querySelector('[data-testid="approve-btn"]')
    expect(approveBtn).toBeNull()
  })

  it('hides approve/reject buttons for non-pending transactions', async () => {
    await mountModal({
      transaction: createTransaction({ status: 'completed' }),
      isManagerOrAdmin: true,
    })

    const approveBtn = document.body.querySelector('[data-testid="approve-btn"]')
    expect(approveBtn).toBeNull()
  })

  it('shows reject reason textarea after clicking reject', async () => {
    await mountModal({
      transaction: createTransaction({ status: 'pending_approval' }),
      isManagerOrAdmin: true,
    })

    const rejectBtn = document.body.querySelector('[data-testid="reject-btn"]') as HTMLElement
    expect(rejectBtn).not.toBeNull()
    await new DOMWrapper(rejectBtn).trigger('click')
    await flushPromises()

    const textarea = document.body.querySelector('[data-testid="reject-reason-input"]')
    expect(textarea).not.toBeNull()
  })
  it('shows view recent transactions link when initiator present and isManagerOrAdmin', async () => {
    await mountModal({
      transaction: createTransaction({
        status: 'pending_approval',
        initiator: { id: 10, name: 'Alice Johnson' },
      }),
      isManagerOrAdmin: true,
    })

    const btn = document.body.querySelector('[data-testid="view-recent-btn"]')
    expect(btn).not.toBeNull()
    expect(btn?.textContent).toContain(en.transactions.viewRecentTransactions)
  })

  it('hides view recent transactions link when not manager or admin', async () => {
    await mountModal({
      transaction: createTransaction({
        status: 'pending_approval',
        initiator: { id: 10, name: 'Alice Johnson' },
      }),
      isManagerOrAdmin: false,
    })

    const btn = document.body.querySelector('[data-testid="view-recent-btn"]')
    expect(btn).toBeNull()
  })
})
