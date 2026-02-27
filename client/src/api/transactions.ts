import type { PaginationParams } from '@/types/pagination'
import { api } from '@/plugins/api'

export interface Transaction {
  id: number
  group_id: string
  type: string
  amount: number
  currency: string
  status: string
  exchange_rate: number
  reference: string | null
  wallet_id: number | null
  counterpart_wallet_id: number | null
  wallet: { id: number, name: string, address: string } | null
  counterpart_wallet: { id: number, name: string, address: string } | null
  external: boolean
  external_address: string | null
  external_name: string | null
  initiator_user_id: number | null
  reviewer_user_id: number | null
  initiator: { id: number, name: string } | null
  reviewer: { id: number, name: string } | null
  reject_reason: string | null
  notes: string | null
  created_at: string
}

type TransactionParams = PaginationParams & {
  date_from?: string
  date_to?: string
  type?: string
  status?: string
  amount_min?: string
  amount_max?: string
  reference?: string
  wallet_id?: number | null
  counterpart_wallet_id?: number | null
}

export interface TransferForm {
  sender_wallet_id: number
  receiver_wallet_id?: number | null
  amount: number
  external: boolean
  external_address?: string
  external_name?: string
  reference: string
  notes?: string
}

export function fetchTransactions(params: TransactionParams) {
  return api.get('/transactions', { params })
}

export function initiateTransfer(form: TransferForm) {
  return api.post('/transfers', form)
}

export function reviewTransfer(groupId: string, payload: { action: 'approve' | 'reject', reason?: string }) {
  return api.post(`/transfers/${groupId}/review`, payload)
}
