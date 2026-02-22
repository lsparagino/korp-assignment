import { defineQueryOptions } from '@pinia/colada'
import { fetchTransactions } from '@/api/transactions'

interface TransactionsQueryParams {
  page: number
  perPage: number
  dateFrom?: string
  dateTo?: string
  type?: string
  amountMin?: string
  amountMax?: string
  reference?: string
  fromWalletId?: number | string | null
  toWalletId?: number | string | null
}

export const TRANSACTION_QUERY_KEYS = {
  root: ['transactions'] as const,
  list: (params: TransactionsQueryParams) => [...TRANSACTION_QUERY_KEYS.root, params] as const,
}

export const transactionsListQuery = defineQueryOptions(
  (params: TransactionsQueryParams) => ({
    key: TRANSACTION_QUERY_KEYS.list(params),
    query: async () => {
      const response = await fetchTransactions({
        page: params.page,
        per_page: params.perPage,
        date_from: params.dateFrom,
        date_to: params.dateTo,
        type: params.type === 'All' ? undefined : params.type,
        amount_min: params.amountMin,
        amount_max: params.amountMax,
        reference: params.reference,
        from_wallet_id: params.fromWalletId as number | null,
        to_wallet_id: params.toWalletId as number | null,
      })
      return response.data
    },
  }),
)
