<script lang="ts" setup>
  import type { Wallet } from '@/types'
  import { computed, ref } from 'vue'
  import { useMutation, useQuery, useQueryCache } from '@pinia/colada'
  import ConfirmDialog from '@/components/ConfirmDialog.vue'
  import PageHeader from '@/components/PageHeader.vue'
  import Pagination from '@/components/Pagination.vue'
  import { useConfirmDialog } from '@/composables/useConfirmDialog'
  import { deleteWallet as apiDeleteWallet, toggleWalletFreeze } from '@/api/wallets'
  import { walletsListQuery, WALLET_QUERY_KEYS } from '@/queries/wallets'
  import { useAuthStore } from '@/stores/auth'
  import { getErrorMessage, isApiError } from '@/utils/errors'
  import { getCurrencyColors, getStatusColors } from '@/utils/colors'
  import { formatCurrency, getAmountColor } from '@/utils/formatters'
  import { useRoute, useRouter } from 'vue-router'

  const route = useRoute()
  const router = useRouter()
  const authStore = useAuthStore()
  const snackbar = ref({ show: false, text: '', color: 'error' })
  const { confirmDialog, openConfirmDialog } = useConfirmDialog()
  const queryCache = useQueryCache()

  const defaultPerPage = 10
  const page = computed(() => Number(route.query.page) || 1)
  const perPage = computed(() => Number(route.query.per_page) || defaultPerPage)

  const { data, isPending: processing } = useQuery(
    walletsListQuery,
    () => ({ page: page.value, perPage: perPage.value }),
  )

  const wallets = computed<Wallet[]>(() => data.value?.data ?? [])
  const meta = computed(() => data.value?.meta ?? {
    current_page: 1,
    last_page: 1,
    per_page: defaultPerPage,
    total: 0,
    from: null,
    to: null,
  })

  function updateUrl(newPage: number, newPerPage: number) {
    const query = { ...route.query }
    if (newPage === 1) {
      delete query.page
    } else {
      query.page = String(newPage)
    }
    if (newPerPage === defaultPerPage) {
      delete query.per_page
    } else {
      query.per_page = String(newPerPage)
    }
    router.push({ query })
  }

  function handlePageChange(newPage: number) {
    updateUrl(newPage, perPage.value)
  }

  function handlePerPageChange(newPerPage: number) {
    updateUrl(1, newPerPage)
  }

  const { mutateAsync: toggleFreezeApi } = useMutation({
    mutation: (walletId: number) => toggleWalletFreeze(walletId),
    onSettled: () => {
      queryCache.invalidateQueries({ key: WALLET_QUERY_KEYS.root })
    },
  })

  const { mutateAsync: deleteWalletApi } = useMutation({
    mutation: (walletId: number) => apiDeleteWallet(walletId),
    onSettled: () => {
      queryCache.invalidateQueries({ key: WALLET_QUERY_KEYS.root })
    },
  })

  function toggleFreeze (wallet: Wallet) {
    const isFreezing = wallet.status === 'active'
    openConfirmDialog({
      title: isFreezing ? 'Freeze Wallet' : 'Unfreeze Wallet',
      message: `Are you sure you want to ${isFreezing ? 'freeze' : 'unfreeze'} the wallet "${wallet.name}"?`,
      requiresPin: false,
      onConfirm: async () => {
        try {
          await toggleFreezeApi(wallet.id)
        } catch (error) {
          console.error('Error toggling wallet status:', error)
        }
      },
    })
  }

  function deleteWallet (wallet: Wallet) {
    openConfirmDialog({
      title: 'Delete Wallet',
      message: `Warning: You are about to permanently delete the wallet "${wallet.name}". This action cannot be undone.`,
      requiresPin: true,
      onConfirm: async () => {
        try {
          await deleteWalletApi(wallet.id)
        } catch (error: unknown) {
          if (isApiError(error, 403)) {
            snackbar.value = {
              show: true,
              text: getErrorMessage(error, 'You are not authorized to delete this wallet.'),
              color: 'error',
            }
          } else {
            console.error('Error deleting wallet:', error)
          }
        }
      },
    })
  }
</script>

<template>
  <PageHeader title="Wallets">
    <v-btn
      v-if="authStore.isAdmin"
      class="text-none font-weight-bold"
      color="primary"
      prepend-icon="mdi-plus"
      rounded="lg"
      to="/wallets/create"
      variant="flat"
    >
      Create Wallet
    </v-btn>
  </PageHeader>

  <v-card border flat :loading="processing" rounded="lg">
    <div class="overflow-x-auto">
      <v-table density="comfortable">
        <thead class="bg-grey-lighten-4">
          <tr>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Name
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Address
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Balance
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Currency
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              Status
            </th>
            <th
              v-if="authStore.isAdmin"
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-right"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="w in wallets" :key="w.id">
            <td class="font-weight-bold text-grey-darken-3">
              {{ w.name }}
            </td>
            <td
              class="text-caption text-grey-darken-1"
              style="font-family: monospace"
            >
              {{ w.address }}
            </td>
            <td
              class="font-weight-bold"
              :class="getAmountColor(w.balance)"
            >
              {{ formatCurrency(w.balance, w.currency) }}
            </td>
            <td>
              <v-chip
                class="font-weight-bold"
                :color="getCurrencyColors(w.currency).bg"
                size="small"
                variant="flat"
              >
                <span
                  class="font-weight-bold"
                  :class="`text-${getCurrencyColors(w.currency).text}`"
                >{{ w.currency }}</span>
              </v-chip>
            </td>
            <td>
              <v-chip
                class="font-weight-bold text-uppercase"
                :color="getStatusColors(w.status).bg"
                size="small"
                variant="flat"
              >
                <span
                  class="font-weight-bold"
                  :class="`text-${getStatusColors(w.status).text}`"
                >{{ w.status }}</span>
              </v-chip>
            </td>
            <td v-if="authStore.isAdmin" class="text-right">
              <div class="d-flex ga-2 justify-end">
                <v-btn
                  color="primary"
                  density="comfortable"
                  icon="mdi-pencil"
                  size="small"
                  :to="`/wallets/${w.id}/edit`"
                  variant="text"
                />
                <v-btn
                  :color="
                    w.status === 'active'
                      ? 'warning'
                      : 'success'
                  "
                  density="comfortable"
                  :icon="
                    w.status === 'active'
                      ? 'mdi-snowflake'
                      : 'mdi-fire'
                  "
                  size="small"
                  variant="text"
                  @click="toggleFreeze(w)"
                />
                <v-btn
                  v-if="w.can_delete"
                  color="error"
                  density="comfortable"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  @click="deleteWallet(w)"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </v-table>
    </div>

    <div class="border-t">
      <Pagination
        :meta="meta"
        @update:page="handlePageChange"
        @update:per-page="handlePerPageChange"
      />
    </div>
  </v-card>

  <ConfirmDialog
    v-model="confirmDialog.show"
    :message="confirmDialog.message"
    :requires-pin="confirmDialog.requiresPin"
    :title="confirmDialog.title"
    @confirm="confirmDialog.onConfirm"
  />

  <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="5000">
    {{ snackbar.text }}
  </v-snackbar>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
