import axios from 'axios'
import { api } from '@/plugins/api'

export interface User {
  id: number
  name: string
  email: string
  pending_email: string | null
  email_verified_at: string | null
  role: string
  two_factor_confirmed_at: string | null
}

interface LoginForm {
  email: string
  password: string
  remember: boolean
}

interface RegisterForm {
  name: string
  email: string
  password: string
  password_confirmation: string
}

interface ResetPasswordForm {
  token: string
  email: string
  password: string
  password_confirmation: string
}

export function login (form: LoginForm) {
  return api.post('/login', form)
}

export function register (form: RegisterForm) {
  return api.post('/register', form)
}

export function logout () {
  return api.post('/logout')
}

export function forgotPassword (form: { email: string }) {
  return api.post('/forgot-password', form)
}

export function resetPassword (form: ResetPasswordForm) {
  return api.post('/reset-password', form)
}

export function confirmPassword (form: { password: string }) {
  return api.post('/user/confirm-password', form)
}

export function twoFactorChallenge (payload: Record<string, unknown>) {
  return api.post('/two-factor-challenge', payload)
}

export function sendVerificationEmail () {
  return api.post('/email/verification-notification')
}

export function verifyEmail (id: string, hash: string, expires: string, signature: string) {
  return axios.get(`${import.meta.env.VITE_API_BASE_URL}/email/verify/${id}/${hash}`, {
    params: { expires, signature },
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  })
}

export function fetchUser () {
  return api.get('/user')
}

export function verifyInvitation (token: string) {
  return api.get(`/invitation/${token}`)
}

export function acceptInvitation (token: string, form: { password: string, password_confirmation: string }) {
  return api.post(`/accept-invitation/${token}`, form)
}

export function deleteUser (password: string) {
  return api.delete('/user', { data: { password } })
}
