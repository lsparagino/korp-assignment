import { api } from '@/plugins/api'

export interface Transaction {
    id: number
    type: string
    amount: number
    currency: string
    reference: string | null
    from_wallet_id: number | null
    to_wallet_id: number | null
    from_wallet: { name: string, address: string } | null
    to_wallet: { name: string, address: string } | null
    created_at: string
}


interface TransactionParams {
    page?: number
    per_page?: number
    date_from?: string
    date_to?: string
    type?: string
    amount_min?: string
    amount_max?: string
    reference?: string
    from_wallet_id?: number | null
    to_wallet_id?: number | null
}

export function fetchTransactions(params: TransactionParams) {
    return api.get('/transactions', { params })
}
