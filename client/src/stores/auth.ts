import type { User } from '@/types'
import { defineStore } from 'pinia'
import api from '@/plugins/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null') as User | null,
    token: localStorage.getItem('access_token') || null,
    twoFactorUserId: null as number | null,
  }),
  getters: {
    isAdmin: state => state.user?.role === 'admin',
    isAuthenticated: state => !!state.token,
  },
  actions: {
    setToken (token: string) {
      this.token = token
      localStorage.setItem('access_token', token)
    },
    clearToken () {
      this.token = null
      this.user = null
      localStorage.removeItem('access_token')
      localStorage.removeItem('user')
    },
    setUser (user: User) {
      this.user = user
      localStorage.setItem('user', JSON.stringify(user))
    },
    setTwoFactor (userId: number) {
      this.twoFactorUserId = userId
    },
    async fetchUser () {
      try {
        const response = await api.get('/user')
        this.setUser(response.data)
      } catch {
        this.clearToken()
      }
    },
    async logout () {
      try {
        await api.post('/logout')
      } finally {
        this.clearToken()
      }
    },
  },
})
