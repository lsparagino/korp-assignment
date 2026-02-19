import type { User } from '@/types'
import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { api } from '@/plugins/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(JSON.parse(localStorage.getItem('user') || 'null'))
  const token = ref<string | null>(localStorage.getItem('access_token') || null)
  const twoFactorUserId = ref<number | null>(null)

  const isAdmin = computed(() => user.value?.role === 'admin')
  const isAuthenticated = computed(() => !!token.value)

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
      const response = await api.get('/user')
      setUser(response.data)
    } catch {
      clearToken()
    }
  }

  async function logout() {
    try {
      await api.post('/logout')
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
    setToken,
    clearToken,
    setUser,
    setTwoFactor,
    fetchUser,
    logout,
  }
})
