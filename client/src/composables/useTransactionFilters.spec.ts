import { beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick, reactive, ref } from 'vue'

// Mock vue-router
const mockRoute = reactive({ query: {} as Record<string, string>, fullPath: '/' })
const mockRouter = { push: vi.fn() }

vi.mock('vue-router', () => ({
  useRoute: () => mockRoute,
  useRouter: () => mockRouter,
}))

// Mock vue-i18n
vi.mock('vue-i18n', () => ({
  useI18n: () => ({
    t: (key: string) => key,
  }),
}))

// Mock @pinia/colada
const mockInvalidateQueries = vi.fn()
vi.mock('@pinia/colada', () => ({
  useQuery: vi.fn(() => ({
    data: ref(null),
    isPending: ref(false),
  })),
  useQueryCache: vi.fn(() => ({
    invalidateQueries: mockInvalidateQueries,
  })),
  defineQueryOptions: vi.fn((fn: Function) => fn),
}))

// Mock query modules
vi.mock('@/queries/transactions', () => ({
  TRANSACTION_QUERY_KEYS: { root: ['transactions'] },
  transactionsListQuery: vi.fn(),
}))
vi.mock('@/queries/wallets', () => ({
  WALLET_QUERY_KEYS: { root: ['wallets'] },
  walletsListQuery: vi.fn(),
}))

describe('useTransactionFilters', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mockRoute.query = {}
    mockRoute.fullPath = '/'
    mockRouter.push.mockClear()
  })

  it('initializes filterForm with defaults', async () => {
    const { useTransactionFilters } = await import('./useTransactionFilters')
    const { filterForm } = useTransactionFilters()

    expect(filterForm.date_from).toBe('')
    expect(filterForm.date_to).toBe('')
    expect(filterForm.type).toBe('All')
    expect(filterForm.amount_min).toBe('')
    expect(filterForm.amount_max).toBe('')
    expect(filterForm.reference).toBe('')
    expect(filterForm.has_wallet_id).toBeNull()
    expect(filterForm.from_wallet_id).toBeNull()
    expect(filterForm.to_wallet_id).toBeNull()
  })

  it('syncs filterForm from route query params', async () => {
    mockRoute.query = {
      date_from: '2024-01-01',
      date_to: '2024-12-31',
      type: 'debit',
      amount_min: '100',
      amount_max: '500',
    }
    mockRoute.fullPath = '/?date_from=2024-01-01&date_to=2024-12-31&type=debit'

    const { useTransactionFilters } = await import('./useTransactionFilters')
    const { filterForm } = useTransactionFilters()
    await nextTick()

    expect(filterForm.date_from).toBe('2024-01-01')
    expect(filterForm.date_to).toBe('2024-12-31')
    expect(filterForm.type).toBe('Debit')
    expect(filterForm.amount_min).toBe('100')
    expect(filterForm.amount_max).toBe('500')
  })

  it('handleFilter pushes correct query to router', async () => {
    const { useTransactionFilters } = await import('./useTransactionFilters')
    const { filterForm, handleFilter } = useTransactionFilters()

    filterForm.date_from = '2024-06-01'
    filterForm.type = 'Credit'
    filterForm.amount_min = '50'

    handleFilter()

    expect(mockRouter.push).toHaveBeenCalledWith({
      query: expect.objectContaining({
        page: '1',
        date_from: '2024-06-01',
        type: 'credit',
        amount_min: '50',
      }),
    })
  })

  it('handleFilter omits empty/default values from query', async () => {
    const { useTransactionFilters } = await import('./useTransactionFilters')
    const { filterForm, handleFilter } = useTransactionFilters()

    filterForm.type = 'All' // default — should be excluded
    filterForm.date_from = '' // empty — should be excluded

    handleFilter()

    const pushCall = mockRouter.push.mock.calls[0]![0] as { query: Record<string, string> }
    expect(pushCall.query.type).toBeUndefined()
    expect(pushCall.query.date_from).toBeUndefined()
  })

  it('clearFilters removes all filter keys', async () => {
    mockRoute.query = {
      date_from: '2024-01-01',
      type: 'debit',
      amount_min: '100',
      per_page: '25',
    }

    const { useTransactionFilters } = await import('./useTransactionFilters')
    const { clearFilters } = useTransactionFilters()

    clearFilters()

    const pushCall = mockRouter.push.mock.calls[0]![0] as { query: Record<string, string> }
    expect(pushCall.query.date_from).toBeUndefined()
    expect(pushCall.query.type).toBeUndefined()
    expect(pushCall.query.amount_min).toBeUndefined()
    // per_page should be preserved (not a filter key)
    expect(pushCall.query.per_page).toBe('25')
    expect(pushCall.query.page).toBe('1')
  })

  it('activeFiltersCount reflects active query filters', async () => {
    mockRoute.query = {
      date_from: '2024-01-01',
      type: 'debit',
      amount_min: '100',
    }

    const { useTransactionFilters } = await import('./useTransactionFilters')
    const { activeFiltersCount } = useTransactionFilters()

    // date_from + type = 2 basic filters, amount_min = 1 advanced filter = 3 total
    expect(activeFiltersCount.value).toBe(3)
  })

  it('activeAdvancedFiltersCount only counts advanced filters', async () => {
    mockRoute.query = {
      date_from: '2024-01-01', // basic filter, not counted
      amount_min: '100',
      reference: 'inv-123',
    }

    const { useTransactionFilters } = await import('./useTransactionFilters')
    const { activeAdvancedFiltersCount } = useTransactionFilters()

    // Only amount_min + reference = 2 advanced filters
    expect(activeAdvancedFiltersCount.value).toBe(2)
  })

  it('invalidateQueries calls queryCache for transactions', async () => {
    const { useTransactionFilters } = await import('./useTransactionFilters')
    const { invalidateQueries } = useTransactionFilters()

    await invalidateQueries()

    expect(mockInvalidateQueries).toHaveBeenCalledTimes(1)
    expect(mockInvalidateQueries).toHaveBeenCalledWith({ key: ['transactions'] })
  })

  it('defaults to simple wallet filter mode', async () => {
    const { useTransactionFilters } = await import('./useTransactionFilters')
    const { walletFilterMode } = useTransactionFilters()

    expect(walletFilterMode.value).toBe('simple')
  })

  it('detects specific mode from URL params', async () => {
    mockRoute.query = { from_wallet_id: '5' }
    mockRoute.fullPath = '/?from_wallet_id=5'

    const { useTransactionFilters } = await import('./useTransactionFilters')
    const { walletFilterMode, filterForm } = useTransactionFilters()
    await nextTick()

    expect(walletFilterMode.value).toBe('specific')
    expect(filterForm.from_wallet_id).toBe(5)
  })

  it('toggleWalletFilterMode switches between simple and specific', async () => {
    const { useTransactionFilters } = await import('./useTransactionFilters')
    const { walletFilterMode, toggleWalletFilterMode, filterForm } = useTransactionFilters()

    expect(walletFilterMode.value).toBe('simple')

    toggleWalletFilterMode()
    expect(walletFilterMode.value).toBe('specific')
    expect(filterForm.has_wallet_id).toBeNull()

    filterForm.from_wallet_id = 3
    toggleWalletFilterMode()
    expect(walletFilterMode.value).toBe('simple')
    expect(filterForm.from_wallet_id).toBeNull()
    expect(filterForm.to_wallet_id).toBeNull()
  })

  it('handleFilter sends has_wallet_id in simple mode', async () => {
    const { useTransactionFilters } = await import('./useTransactionFilters')
    const { filterForm, handleFilter } = useTransactionFilters()

    filterForm.has_wallet_id = 42

    handleFilter()

    const pushCall = mockRouter.push.mock.calls[0]![0] as { query: Record<string, string> }
    expect(pushCall.query.has_wallet_id).toBe('42')
    expect(pushCall.query.from_wallet_id).toBeUndefined()
    expect(pushCall.query.to_wallet_id).toBeUndefined()
  })

  it('handleFilter sends from/to in specific mode', async () => {
    const { useTransactionFilters } = await import('./useTransactionFilters')
    const { filterForm, walletFilterMode, handleFilter } = useTransactionFilters()

    walletFilterMode.value = 'specific'
    filterForm.from_wallet_id = 10
    filterForm.to_wallet_id = 'external'

    handleFilter()

    const pushCall = mockRouter.push.mock.calls[0]![0] as { query: Record<string, string> }
    expect(pushCall.query.from_wallet_id).toBe('10')
    expect(pushCall.query.to_wallet_id).toBe('external')
    expect(pushCall.query.has_wallet_id).toBeUndefined()
  })
})
