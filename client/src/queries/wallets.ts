import type { Wallet } from '@/api/wallets'
import type { PaginationMeta } from '@/composables/useUrlPagination'
import { defineQuery, defineQueryOptions, useQuery } from '@pinia/colada'
import { computed, ref } from 'vue'
import { fetchWallet, fetchWallets } from '@/api/wallets'

interface WalletsQueryParams {
  page: number
  perPage: number
}

const DEFAULT_PER_PAGE = 10

export const WALLET_QUERY_KEYS = {
  root: ['wallets'] as const,
  list: (params: WalletsQueryParams) => [...WALLET_QUERY_KEYS.root, params] as const,
  byId: (id: string | number) => [...WALLET_QUERY_KEYS.root, String(id)] as const,
}

export const walletsListQuery = defineQueryOptions(
  (params: WalletsQueryParams) => ({
    key: WALLET_QUERY_KEYS.list(params),
    query: async (): Promise<{ data: Wallet[], meta: PaginationMeta }> => {
      const response = await fetchWallets({ page: params.page, per_page: params.perPage })
      return response.data
    },
  }),
)

export const useWalletList = defineQuery(() => {
  const page = ref(1)
  const perPage = ref(DEFAULT_PER_PAGE)

  const { data: listData, ...rest } = useQuery(
    walletsListQuery,
    () => ({ page: page.value, perPage: perPage.value }),
  )

  const wallets = computed<Wallet[]>(() => listData.value?.data ?? [])
  const meta = computed<PaginationMeta>(() => listData.value?.meta ?? {
    current_page: 1,
    last_page: 1,
    per_page: DEFAULT_PER_PAGE,
    total: 0,
    from: null,
    to: null,
  })

  return { ...rest, page, perPage, wallets, meta }
})

export const walletByIdQuery = defineQueryOptions(
  (id: string | number) => ({
    key: WALLET_QUERY_KEYS.byId(id),
    query: async (): Promise<{ wallet: Wallet }> => {
      const response = await fetchWallet(id)
      return response.data
    },
  }),
)
