import type { User } from '@/api/auth'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { fetchUser as apiFetchUser, logout as apiLogout } from '@/api/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(JSON.parse(localStorage.getItem('user') || 'null'))
  const token = ref<string | null>(localStorage.getItem('access_token') || null)
  const twoFactorUserId = ref<number | null>(null)

  const isAdmin = computed(() => user.value?.role === 'admin')
  const isAuthenticated = computed(() => !!token.value)
  const isEmailVerified = computed(() => !!user.value?.email_verified_at)

  function setToken (value: string) {
    token.value = value
    localStorage.setItem('access_token', value)
  }

  function clearToken () {
    token.value = null
    user.value = null
    localStorage.removeItem('access_token')
    localStorage.removeItem('user')
  }

  function setUser (value: User) {
    user.value = value
    localStorage.setItem('user', JSON.stringify(value))
  }

  function setTwoFactor (userId: number) {
    twoFactorUserId.value = userId
  }

  async function fetchUser () {
    try {
      const response = await apiFetchUser()
      setUser(response.data)
    } catch {
      clearToken()
    }
  }

  async function logout () {
    try {
      await apiLogout()
    } finally {
      clearToken()
    }
  }

  return {
    user,
    token,
    twoFactorUserId,
    isAdmin,
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
