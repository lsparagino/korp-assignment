import { useMutation, useQuery, useQueryCache } from '@pinia/colada'
import { defineStore } from 'pinia'
import {
  createWallet as apiCreateWallet,
  deleteWallet as apiDeleteWallet,
  toggleWalletFreeze as apiToggleFreeze,
  updateWallet as apiUpdateWallet,
} from '@/api/wallets'
import { DASHBOARD_QUERY_KEYS } from '@/queries/dashboard'
import { WALLET_QUERY_KEYS, walletByIdQuery } from '@/queries/wallets'

export const useWalletStore = defineStore('wallet', () => {
  const queryCache = useQueryCache()

  async function invalidateQueries() {
    await queryCache.invalidateQueries({ key: WALLET_QUERY_KEYS.root })
    await queryCache.invalidateQueries({ key: DASHBOARD_QUERY_KEYS.root })
  }

  function useWalletById(id: string | number) {
    return useQuery(walletByIdQuery, () => id)
  }

  const { mutateAsync: createWallet } = useMutation({
    mutation: (form: { name: string, currency: string }) => apiCreateWallet(form),
    onSettled: async () => await invalidateQueries(),
  })

  const { mutateAsync: updateWallet } = useMutation({
    mutation: ({ id, form }: { id: string | number, form: { name: string, currency: string } }) =>
      apiUpdateWallet(id, form),
    onSettled: async () => await invalidateQueries(),
  })

  const { mutateAsync: toggleFreeze } = useMutation({
    mutation: (id: number) => apiToggleFreeze(id),
    onSettled: async () => await invalidateQueries(),
  })

  const { mutateAsync: deleteWallet } = useMutation({
    mutation: (id: number) => apiDeleteWallet(id),
    onSettled: async () => await invalidateQueries(),
  })

  return {
    useWalletById,
    createWallet,
    updateWallet,
    toggleFreeze,
    deleteWallet,
    invalidateQueries,
  }
})


