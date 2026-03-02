import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

// Mock the API module
vi.mock('@/api/wallets', () => ({
  createWallet: vi.fn(),
  updateWallet: vi.fn(),
  toggleWalletFreeze: vi.fn(),
  deleteWallet: vi.fn(),
  fetchWallet: vi.fn(),
  fetchWallets: vi.fn(),
}))

// Mock query modules to prevent real @pinia/colada initialization at module load
vi.mock('@/queries/wallets', () => ({
  WALLET_QUERY_KEYS: { root: ['wallets'] },
  walletByIdQuery: vi.fn(),
}))

vi.mock('@/queries/dashboard', () => ({
  DASHBOARD_QUERY_KEYS: { root: ['dashboard'] },
}))

// Mock @pinia/colada
const mockInvalidateQueries = vi.fn()
const mockMutateAsync = vi.fn()
vi.mock('@pinia/colada', () => ({
  useMutation: vi.fn(({ mutation }: { mutation: Function }) => ({
    mutateAsync: (...args: unknown[]) => {
      mockMutateAsync(...args)
      return mutation(...args)
    },
  })),
  useQuery: vi.fn(() => ({
    data: { value: null },
    isPending: { value: false },
  })),
  useQueryCache: vi.fn(() => ({
    invalidateQueries: mockInvalidateQueries,
  })),
  defineQuery: vi.fn((fn: Function) => fn),
  defineQueryOptions: vi.fn((fn: Function) => fn),
}))

describe('useWalletStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('exposes expected actions', async () => {
    const { useWalletStore, useWalletById } = await import('./wallet')
    const store = useWalletStore()

    expect(store.createWallet).toBeDefined()
    expect(store.updateWallet).toBeDefined()
    expect(store.toggleFreeze).toBeDefined()
    expect(store.deleteWallet).toBeDefined()
    expect(store.invalidateQueries).toBeDefined()
    expect(useWalletById).toBeDefined()
  })

  it('calls createWallet API via mutation', async () => {
    const { createWallet: apiCreate } = await import('@/api/wallets')
    vi.mocked(apiCreate).mockResolvedValue({ data: { id: 1 } } as any)

    const { useWalletStore } = await import('./wallet')
    const store = useWalletStore()

    const form = { name: 'Test Wallet', currency: 'USD' }
    await store.createWallet(form)

    expect(apiCreate).toHaveBeenCalledWith(form)
  })

  it('calls updateWallet API via mutation', async () => {
    const { updateWallet: apiUpdate } = await import('@/api/wallets')
    vi.mocked(apiUpdate).mockResolvedValue({ data: {} } as any)

    const { useWalletStore } = await import('./wallet')
    const store = useWalletStore()

    const form = { name: 'Updated', currency: 'EUR' }
    await store.updateWallet({ id: 1, form })

    expect(apiUpdate).toHaveBeenCalledWith(1, form)
  })

  it('calls toggleWalletFreeze API via mutation', async () => {
    const { toggleWalletFreeze: apiToggle } = await import('@/api/wallets')
    vi.mocked(apiToggle).mockResolvedValue({ data: {} } as any)

    const { useWalletStore } = await import('./wallet')
    const store = useWalletStore()

    await store.toggleFreeze(42)

    expect(apiToggle).toHaveBeenCalledWith(42)
  })

  it('calls deleteWallet API via mutation', async () => {
    const { deleteWallet: apiDelete } = await import('@/api/wallets')
    vi.mocked(apiDelete).mockResolvedValue({ data: {} } as any)

    const { useWalletStore } = await import('./wallet')
    const store = useWalletStore()

    await store.deleteWallet(99)

    expect(apiDelete).toHaveBeenCalledWith(99)
  })

  it('invalidateQueries calls queryCache', async () => {
    const { useWalletStore } = await import('./wallet')
    const store = useWalletStore()

    await store.invalidateQueries()

    expect(mockInvalidateQueries).toHaveBeenCalled()
  })
})
