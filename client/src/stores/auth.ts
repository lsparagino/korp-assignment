import type { User } from '@/api/auth'
import { useMutation, useQueryCache } from '@pinia/colada'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { logout as apiLogout } from '@/api/auth'
import { AUTH_QUERY_KEYS, userQuery } from '@/queries/auth'
import { usePreferencesStore } from '@/stores/preferences'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(JSON.parse(localStorage.getItem('user') || 'null'))
  const token = ref<string | null>(localStorage.getItem('access_token') || null)
  const twoFactorUserId = ref<number | null>(null)
  const queryCache = useQueryCache()

  const isAdmin = computed(() => user.value?.role === 'admin')
  const isManagerOrAdmin = computed(() => user.value?.role === 'admin' || user.value?.role === 'manager')
  const isAuthenticated = computed(() => !!token.value)
  const isEmailVerified = computed(() => !!user.value?.email_verified_at)

  function setToken(value: string) {
    token.value = value
    localStorage.setItem('access_token', value)
  }

  function clearToken() {
    token.value = null
    user.value = null
    localStorage.removeItem('access_token')
    localStorage.removeItem('user')
  }

  function setUser(value: User) {
    user.value = value
    localStorage.setItem('user', JSON.stringify(value))
  }

  function setTwoFactor(userId: number) {
    twoFactorUserId.value = userId
  }

  async function fetchUser() {
    try {
      const entry = queryCache.ensure(userQuery)
      await queryCache.fetch(entry)
      const data = entry.state.value.data
      if (data) {
        setUser(data)
      }
      const preferencesStore = usePreferencesStore()
      await preferencesStore.load()
    } catch {
      clearToken()
    }
  }

  const { mutateAsync: performLogout } = useMutation({
    mutation: () => apiLogout(),
    onSettled: async () => {
      await queryCache.invalidateQueries({ key: AUTH_QUERY_KEYS.root })
    },
  })

  async function logout() {
    try {
      await performLogout()
    } finally {
      clearToken()
      const preferencesStore = usePreferencesStore()
      preferencesStore.clear()
    }
  }

  return {
    user,
    token,
    twoFactorUserId,
    isAdmin,
    isManagerOrAdmin,
    isAuthenticated,
    isEmailVerified,
    setToken,
    clearToken,
    setUser,
    setTwoFactor,
    fetchUser,
    logout,
  }
})
