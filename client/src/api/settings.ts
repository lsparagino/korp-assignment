import { api } from '@/plugins/api'

export interface RecoveryCode {
  code: string
}

interface ProfileForm {
  name: string
  email: string
}

interface PasswordForm {
  current_password: string
  password: string
  password_confirmation: string
}

export function updateProfile (form: ProfileForm) {
  return api.patch('/settings/profile', form)
}

export function deleteAccount (form: { password: string }) {
  return api.delete('/settings/profile', { data: form })
}

export function updatePassword (form: PasswordForm) {
  return api.put('/settings/password', form)
}

export function enableTwoFactor () {
  return api.post('/user/two-factor/authentication')
}

export function getTwoFactorQrCode () {
  return api.get('/user/two-factor/qr-code')
}

export function confirmTwoFactor (code: string) {
  return api.post('/user/two-factor/confirmed-authentication', { code })
}

export function disableTwoFactor () {
  return api.delete('/user/two-factor/authentication')
}

export function getRecoveryCodes () {
  return api.get('/user/two-factor/recovery-codes')
}

export function cancelPendingEmail () {
  return api.delete('/settings/pending-email')
}

// User Preferences

export interface UserPreferences {
  id: number
  notify_money_received: boolean
  notify_money_sent: boolean
  notify_transaction_approved: boolean
  notify_transaction_rejected: boolean
  notify_approval_needed: boolean
  date_format: string
  number_format: string
  daily_transaction_limit: string | null
  security_threshold: string | null
}

export function fetchUserPreferences () {
  return api.get<{ data: UserPreferences }>('/settings/preferences')
}

export function updateUserPreferences (data: Partial<UserPreferences> & { password?: string, code?: string }) {
  return api.put<{ data: UserPreferences }>('/settings/preferences', data)
}

// Company Thresholds

export interface CompanyThreshold {
  id: number
  currency: string
  approval_threshold: string
}

export function fetchCompanyThresholds () {
  return api.get<{ data: CompanyThreshold[] }>('/settings/thresholds')
}

export function upsertCompanyThreshold (data: { currency: string, approval_threshold: number }) {
  return api.put<{ data: CompanyThreshold }>('/settings/thresholds', data)
}

export function deleteCompanyThreshold (id: number) {
  return api.delete(`/settings/thresholds/${id}`)
}
