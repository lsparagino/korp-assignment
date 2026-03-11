import type { PaginationParams } from '@/api/pagination'
import { api } from '@/plugins/api'

export interface Wallet {
  id: number
  name: string
  address: string
  balance: number
  locked_balance: number
  available_balance: number
  currency: string
  status: 'active' | 'frozen'
  can_delete: boolean
}

export interface TransferTarget {
  id: number
  name: string
  currency: string
  status: 'active' | 'frozen'
  is_own: boolean
  balance?: number
  available_balance?: number
}

interface WalletForm {
  name: string
  currency: string
}

export function fetchWallets (params?: PaginationParams) {
  return api.get('/wallets', { params })
}

export function fetchTransferTargets () {
  return api.get('/wallets/transfer-targets')
}

export function fetchWallet (id: string | number) {
  return api.get(`/wallets/${id}`)
}

export function createWallet (form: WalletForm) {
  return api.post('/wallets', form)
}

export function updateWallet (id: string | number, form: WalletForm) {
  return api.put(`/wallets/${id}`, form)
}

export function toggleWalletFreeze (id: number) {
  return api.patch(`/wallets/${id}/toggle-freeze`)
}

export function deleteWallet (id: number) {
  return api.delete(`/wallets/${id}`)
}

export function fetchCurrencies () {
  return api.get<string[]>('/currencies')
}
