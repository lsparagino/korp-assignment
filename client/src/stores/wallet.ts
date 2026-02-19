import type { Wallet } from '@/api/wallets'
import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { useQuery, useMutation, useQueryCache } from '@pinia/colada'
import {
    createWallet as apiCreateWallet,
    deleteWallet as apiDeleteWallet,
    toggleWalletFreeze as apiToggleFreeze,
    updateWallet as apiUpdateWallet,
} from '@/api/wallets'
import { walletsListQuery, walletByIdQuery, WALLET_QUERY_KEYS } from '@/queries/wallets'

const DEFAULT_PER_PAGE = 10

export const useWalletStore = defineStore('wallet', () => {
    const queryCache = useQueryCache()

    const page = ref(1)
    const perPage = ref(DEFAULT_PER_PAGE)

    const { data: listData, isPending: listLoading } = useQuery(
        walletsListQuery,
        () => ({ page: page.value, perPage: perPage.value }),
    )

    const wallets = computed<Wallet[]>(() => listData.value?.data ?? [])
    const meta = computed(() => listData.value?.meta ?? {
        current_page: 1,
        last_page: 1,
        per_page: DEFAULT_PER_PAGE,
        total: 0,
        from: null,
        to: null,
    })

    function invalidateQueries() {
        queryCache.invalidateQueries({ key: WALLET_QUERY_KEYS.root })
    }

    function useWalletById(id: string | number) {
        return useQuery(walletByIdQuery, () => id)
    }

    const { mutateAsync: createWallet } = useMutation({
        mutation: (form: { name: string, currency: string }) => apiCreateWallet(form),
        onSettled: invalidateQueries,
    })

    const { mutateAsync: updateWallet } = useMutation({
        mutation: ({ id, form }: { id: string | number, form: { name: string, currency: string } }) =>
            apiUpdateWallet(id, form),
        onSettled: invalidateQueries,
    })

    const { mutateAsync: toggleFreeze } = useMutation({
        mutation: (id: number) => apiToggleFreeze(id),
        onSettled: invalidateQueries,
    })

    const { mutateAsync: deleteWallet } = useMutation({
        mutation: (id: number) => apiDeleteWallet(id),
        onSettled: invalidateQueries,
    })

    return {
        page,
        perPage,
        wallets,
        meta,
        listLoading,
        useWalletById,
        createWallet,
        updateWallet,
        toggleFreeze,
        deleteWallet,
    }
})
