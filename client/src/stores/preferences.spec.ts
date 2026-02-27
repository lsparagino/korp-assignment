import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('@/api/settings', () => ({
  fetchUserPreferences: vi.fn(),
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

describe('usePreferencesStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorageMock.clear()
    vi.clearAllMocks()
  })

  describe('initial state', () => {
    it('defaults to en-GB when localStorage is empty', async () => {
      const { usePreferencesStore } = await import('./preferences')
      const store = usePreferencesStore()

      expect(store.dateLocale).toBe('en-GB')
      expect(store.numberLocale).toBe('en-GB')
    })

    it('hydrates from localStorage when values are cached', async () => {
      localStorageMock.setItem('pref_date_format', 'de-DE')
      localStorageMock.setItem('pref_number_format', 'fr-FR')

      // Force re-import to pick up localStorage values
      vi.resetModules()
      const { usePreferencesStore } = await import('./preferences')
      const store = usePreferencesStore()

      expect(store.dateLocale).toBe('de-DE')
      expect(store.numberLocale).toBe('fr-FR')
    })
  })

  describe('load', () => {
    it('fetches preferences from API and updates state + localStorage', async () => {
      const { fetchUserPreferences } = await import('@/api/settings')
      vi.mocked(fetchUserPreferences).mockResolvedValue({
        data: { data: { date_format: 'en-US', number_format: 'de-DE' } },
      } as any)

      const { usePreferencesStore } = await import('./preferences')
      const store = usePreferencesStore()
      await store.load()

      expect(store.dateLocale).toBe('en-US')
      expect(store.numberLocale).toBe('de-DE')
      expect(localStorageMock.setItem).toHaveBeenCalledWith('pref_date_format', 'en-US')
      expect(localStorageMock.setItem).toHaveBeenCalledWith('pref_number_format', 'de-DE')
    })

    it('keeps defaults when API call fails', async () => {
      const { fetchUserPreferences } = await import('@/api/settings')
      vi.mocked(fetchUserPreferences).mockRejectedValue(new Error('Network error'))

      const { usePreferencesStore } = await import('./preferences')
      const store = usePreferencesStore()
      await store.load()

      expect(store.dateLocale).toBe('en-GB')
      expect(store.numberLocale).toBe('en-GB')
    })
  })

  describe('update', () => {
    it('sets refs and persists to localStorage', async () => {
      const { usePreferencesStore } = await import('./preferences')
      const store = usePreferencesStore()

      store.update('ja-JP', 'zh-CN')

      expect(store.dateLocale).toBe('ja-JP')
      expect(store.numberLocale).toBe('zh-CN')
      expect(localStorageMock.setItem).toHaveBeenCalledWith('pref_date_format', 'ja-JP')
      expect(localStorageMock.setItem).toHaveBeenCalledWith('pref_number_format', 'zh-CN')
    })
  })

  describe('clear', () => {
    it('resets to en-GB and removes localStorage keys', async () => {
      const { usePreferencesStore } = await import('./preferences')
      const store = usePreferencesStore()

      store.update('de-DE', 'fr-FR')
      store.clear()

      expect(store.dateLocale).toBe('en-GB')
      expect(store.numberLocale).toBe('en-GB')
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('pref_date_format')
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('pref_number_format')
    })
  })
})
