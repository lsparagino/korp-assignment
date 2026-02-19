import type { Wallet } from '@/types'
import { defineQueryOptions } from '@pinia/colada'
import { fetchWallet, fetchWallets } from '@/api/wallets'

interface WalletsQueryParams {
    page: number
    perPage: number
}

export const WALLET_QUERY_KEYS = {
    root: ['wallets'] as const,
    list: (params: WalletsQueryParams) => [...WALLET_QUERY_KEYS.root, params] as const,
    byId: (id: string | number) => [...WALLET_QUERY_KEYS.root, String(id)] as const,
}

export const walletsListQuery = defineQueryOptions(
    (params: WalletsQueryParams) => ({
        key: WALLET_QUERY_KEYS.list(params),
        query: async (): Promise<{ data: Wallet[], meta: { current_page: number, last_page: number, per_page: number, total: number, from: number | null, to: number | null } }> => {
            const response = await fetchWallets({ page: params.page, per_page: params.perPage })
            return response.data
        },
    }),
)

export const walletByIdQuery = defineQueryOptions(
    (id: string | number) => ({
        key: WALLET_QUERY_KEYS.byId(id),
        query: async (): Promise<{ wallet: Wallet }> => {
            const response = await fetchWallet(id)
            return response.data
        },
    }),
)
