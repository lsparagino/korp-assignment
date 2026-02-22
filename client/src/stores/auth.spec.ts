import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from './auth'

// Mock the API module
vi.mock('@/api/auth', () => ({
  fetchUser: vi.fn(),
  logout: vi.fn(),
}))

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {}
  return {
    getItem: vi.fn((key: string) => store[key] ?? null),
    setItem: vi.fn((key: string, value: string) => {
      store[key] = value
    }),
    removeItem: vi.fn((key: string) => {
      delete store[key]
    }),
    clear: vi.fn(() => {
      store = {}
    }),
  }
})()

Object.defineProperty(globalThis, 'localStorage', { value: localStorageMock })

describe('useAuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorageMock.clear()
    vi.clearAllMocks()
  })

  describe('initial state', () => {
    it('starts with null user and no token', () => {
      const store = useAuthStore()
      expect(store.user).toBeNull()
      expect(store.token).toBeNull()
      expect(store.twoFactorUserId).toBeNull()
    })

    it('isAuthenticated is false without token', () => {
      const store = useAuthStore()
      expect(store.isAuthenticated).toBe(false)
    })

    it('isAdmin is false without user', () => {
      const store = useAuthStore()
      expect(store.isAdmin).toBe(false)
    })
  })

  describe('setToken', () => {
    it('sets token and persists to localStorage', () => {
      const store = useAuthStore()
      store.setToken('abc123')

      expect(store.token).toBe('abc123')
      expect(store.isAuthenticated).toBe(true)
      expect(localStorageMock.setItem).toHaveBeenCalledWith('access_token', 'abc123')
    })
  })

  describe('clearToken', () => {
    it('clears token, user, and localStorage', () => {
      const store = useAuthStore()
      store.setToken('abc123')
      store.setUser({ id: 1, name: 'Test', email: 'test@example.com', pending_email: null, email_verified_at: null, role: 'member', two_factor_confirmed_at: null })

      store.clearToken()

      expect(store.token).toBeNull()
      expect(store.user).toBeNull()
      expect(store.isAuthenticated).toBe(false)
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('access_token')
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('user')
    })
  })

  describe('setUser', () => {
    it('sets user and persists to localStorage', () => {
      const store = useAuthStore()
      const user = { id: 1, name: 'Test', email: 'test@example.com', pending_email: null, email_verified_at: null, role: 'admin', two_factor_confirmed_at: null }

      store.setUser(user)

      expect(store.user).toEqual(user)
      expect(localStorageMock.setItem).toHaveBeenCalledWith('user', JSON.stringify(user))
    })

    it('isAdmin reflects admin role', () => {
      const store = useAuthStore()
      store.setUser({ id: 1, name: 'Test', email: 'test@example.com', pending_email: null, email_verified_at: null, role: 'admin', two_factor_confirmed_at: null })
      expect(store.isAdmin).toBe(true)
    })

    it('isAdmin is false for non-admin role', () => {
      const store = useAuthStore()
      store.setUser({ id: 1, name: 'Test', email: 'test@example.com', pending_email: null, email_verified_at: null, role: 'member', two_factor_confirmed_at: null })
      expect(store.isAdmin).toBe(false)
    })
  })

  describe('setTwoFactor', () => {
    it('sets the two-factor user ID', () => {
      const store = useAuthStore()
      store.setTwoFactor(42)
      expect(store.twoFactorUserId).toBe(42)
    })
  })

  describe('fetchUser', () => {
    it('sets user on successful fetch', async () => {
      const { fetchUser: apiFetchUser } = await import('@/api/auth')
      const user = { id: 1, name: 'Fetched', email: 'fetched@example.com', pending_email: null, email_verified_at: null, role: 'admin', two_factor_confirmed_at: null }
      vi.mocked(apiFetchUser).mockResolvedValue({ data: user } as any)

      const store = useAuthStore()
      await store.fetchUser()

      expect(store.user).toEqual(user)
    })

    it('clears token on fetch failure', async () => {
      const { fetchUser: apiFetchUser } = await import('@/api/auth')
      vi.mocked(apiFetchUser).mockRejectedValue(new Error('Unauthorized'))

      const store = useAuthStore()
      store.setToken('stale-token')
      await store.fetchUser()

      expect(store.token).toBeNull()
      expect(store.isAuthenticated).toBe(false)
    })
  })

  describe('logout', () => {
    it('clears token after successful logout API call', async () => {
      const { logout: apiLogout } = await import('@/api/auth')
      vi.mocked(apiLogout).mockResolvedValue({} as any)

      const store = useAuthStore()
      store.setToken('token')
      await store.logout()

      expect(store.token).toBeNull()
      expect(store.isAuthenticated).toBe(false)
    })

    it('clears token even when logout API fails', async () => {
      const { logout: apiLogout } = await import('@/api/auth')
      vi.mocked(apiLogout).mockRejectedValue(new Error('Network error'))

      const store = useAuthStore()
      store.setToken('token')

      await expect(store.logout()).rejects.toThrow('Network error')

      expect(store.token).toBeNull()
      expect(store.isAuthenticated).toBe(false)
    })
  })
})
