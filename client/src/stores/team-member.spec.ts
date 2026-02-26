import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

// Mock the API module
vi.mock('@/api/team-members', () => ({
  createTeamMember: vi.fn(),
  updateTeamMember: vi.fn(),
  deleteTeamMember: vi.fn(),
  promoteTeamMember: vi.fn(),
  fetchTeamMembers: vi.fn(),
}))

// Mock @pinia/colada
const mockInvalidateQueries = vi.fn()
vi.mock('@pinia/colada', () => ({
  useMutation: vi.fn(({ mutation }: { mutation: Function }) => ({
    mutateAsync: (...args: unknown[]) => mutation(...args),
  })),
  useQueryCache: vi.fn(() => ({
    invalidateQueries: mockInvalidateQueries,
  })),
  defineQuery: vi.fn((fn: Function) => fn),
  defineQueryOptions: vi.fn((fn: Function) => fn),
}))

describe('useTeamMemberStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('exposes expected actions', async () => {
    const { useTeamMemberStore } = await import('./team-member')
    const store = useTeamMemberStore()

    expect(store.createMember).toBeDefined()
    expect(store.updateMember).toBeDefined()
    expect(store.deleteMember).toBeDefined()
    expect(store.promoteMember).toBeDefined()
    expect(store.invalidateQueries).toBeDefined()
  })

  it('calls createTeamMember API via mutation', async () => {
    const { createTeamMember: apiCreate } = await import('@/api/team-members')
    vi.mocked(apiCreate).mockResolvedValue({ data: { id: 1 } } as any)

    const { useTeamMemberStore } = await import('./team-member')
    const store = useTeamMemberStore()

    const form = { name: 'John Doe', email: 'john@example.com', wallets: [1, 2] }
    await store.createMember(form)

    expect(apiCreate).toHaveBeenCalledWith(form)
  })

  it('calls updateTeamMember API via mutation', async () => {
    const { updateTeamMember: apiUpdate } = await import('@/api/team-members')
    vi.mocked(apiUpdate).mockResolvedValue({ data: {} } as any)

    const { useTeamMemberStore } = await import('./team-member')
    const store = useTeamMemberStore()

    const form = { name: 'Updated Name', email: 'updated@example.com', wallets: [3] }
    await store.updateMember({ id: 5, form })

    expect(apiUpdate).toHaveBeenCalledWith(5, form)
  })

  it('calls deleteTeamMember API via mutation', async () => {
    const { deleteTeamMember: apiDelete } = await import('@/api/team-members')
    vi.mocked(apiDelete).mockResolvedValue({ data: {} } as any)

    const { useTeamMemberStore } = await import('./team-member')
    const store = useTeamMemberStore()

    await store.deleteMember(7)

    expect(apiDelete).toHaveBeenCalledWith(7)
  })

  it('invalidateQueries calls queryCache', async () => {
    const { useTeamMemberStore } = await import('./team-member')
    const store = useTeamMemberStore()

    await store.invalidateQueries()

    expect(mockInvalidateQueries).toHaveBeenCalled()
  })

  it('calls promoteTeamMember API via mutation', async () => {
    const { promoteTeamMember: apiPromote } = await import('@/api/team-members')
    vi.mocked(apiPromote).mockResolvedValue({ data: {} } as any)

    const { useTeamMemberStore } = await import('./team-member')
    const store = useTeamMemberStore()

    await store.promoteMember({ id: 3, role: 'manager' })

    expect(apiPromote).toHaveBeenCalledWith(3, { role: 'manager' })
  })
})
