import { defineStore } from 'pinia'

interface User {
  id: number
  name: string
  email: string
  role: string
  two_factor_confirmed_at: string | null
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null') as User | null,
    token: localStorage.getItem('access_token') || null,
    requiresTwoFactor: false,
    twoFactorUserId: null as number | null,
  }),

  getters: {
    isAuthenticated: state => !!state.token,
  },

  actions: {
    setToken (token: string) {
      this.token = token
      localStorage.setItem('access_token', token)
    },

    setUser (user: User) {
      this.user = user
      localStorage.setItem('user', JSON.stringify(user))
    },

    setTwoFactor (userId: number) {
      this.requiresTwoFactor = true
      this.twoFactorUserId = userId
    },

    clearToken () {
      this.token = null
      this.user = null
      this.requiresTwoFactor = false
      this.twoFactorUserId = null
      localStorage.removeItem('access_token')
      localStorage.removeItem('user')
    },
  },
})
