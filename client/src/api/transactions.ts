import type { PaginationParams } from '@/types/pagination'
import { api } from '@/plugins/api'

export interface Transaction {
  id: number
  group_id: string
  type: string
  amount: number
  currency: string
  reference: string | null
  wallet_id: number | null
  counterpart_wallet_id: number | null
  wallet: { id: number; name: string; address: string } | null
  counterpart_wallet: { id: number; name: string; address: string } | null
  external: boolean
  created_at: string
}

type TransactionParams = PaginationParams & {
  date_from?: string
  date_to?: string
  type?: string
  amount_min?: string
  amount_max?: string
  reference?: string
  wallet_id?: number | null
  counterpart_wallet_id?: number | null
}

export function fetchTransactions(params: TransactionParams) {
  return api.get('/transactions', { params })
}
