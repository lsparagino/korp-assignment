<script lang="ts" setup>
  import type { Wallet } from '@/api/wallets'
  import { ref, watch } from 'vue'
  import { useI18n } from 'vue-i18n'
  import PageHeader from '@/components/layout/PageHeader.vue'
  import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
  import Pagination from '@/components/ui/Pagination.vue'
  import { useConfirmDialog } from '@/composables/useConfirmDialog'
  import { useRefreshData } from '@/composables/useRefreshData'
  import { useUrlPagination } from '@/composables/useUrlPagination'
  import { useWalletList } from '@/queries/wallets'
  import { useAuthStore } from '@/stores/auth'
  import { useWalletStore } from '@/stores/wallet'
  import { getCurrencyColors, getStatusColors } from '@/utils/colors'
  import { getErrorMessage, isApiError } from '@/utils/errors'
  import { formatCurrency, getAmountColor } from '@/utils/formatters'

  const { t } = useI18n()
  const authStore = useAuthStore()
  const walletStore = useWalletStore()
  const snackbar = ref({ show: false, text: '', color: 'error' })
  const { confirmDialog, openConfirmDialog } = useConfirmDialog()
  const { page: urlPage, perPage: urlPerPage, handlePageChange, handlePerPageChange } = useUrlPagination()

  const { wallets, meta, isPending: processing, refetch, page, perPage } = useWalletList()

  watch(urlPage, val => { page.value = val }, { immediate: true })
  watch(urlPerPage, val => { perPage.value = val }, { immediate: true })

  const { refreshing, refresh } = useRefreshData(async () => {
    await refetch()
  })

  function toggleFreeze (wallet: Wallet) {
    const isFreezing = wallet.status === 'active'
    openConfirmDialog({
      title: isFreezing ? t('wallets.freezeWallet') : t('wallets.unfreezeWallet'),
      message: isFreezing
        ? t('wallets.confirmFreeze', { name: wallet.name })
        : t('wallets.confirmUnfreeze', { name: wallet.name }),
      requiresPin: false,
      onConfirm: async () => {
        try {
          await walletStore.toggleFreeze(wallet.id)
        } catch (error) {
          console.error('Error toggling wallet status:', error)
        }
      },
    })
  }

  function deleteWallet (wallet: Wallet) {
    openConfirmDialog({
      title: t('wallets.deleteWallet'),
      message: t('wallets.confirmDelete', { name: wallet.name }),
      requiresPin: true,
      onConfirm: async () => {
        try {
          await walletStore.deleteWallet(wallet.id)
        } catch (error: unknown) {
          if (isApiError(error, 403)) {
            snackbar.value = {
              show: true,
              text: getErrorMessage(error, t('wallets.deleteUnauthorized')),
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
  <PageHeader :title="$t('wallets.title')">
    <div class="d-flex ga-2 align-center">
      <v-btn
        :aria-label="$t('common.refreshData')"
        color="grey-darken-1"
        density="comfortable"
        icon="mdi-refresh"
        :loading="refreshing"
        variant="text"
        @click="refresh"
      />
      <v-btn
        v-if="authStore.isAdmin"
        class="text-none font-weight-bold"
        color="primary"
        prepend-icon="mdi-plus"
        rounded="lg"
        to="/wallets/create"
        variant="flat"
      >
        {{ $t('wallets.createWallet') }}
      </v-btn>
    </div>
  </PageHeader>

  <v-card border flat :loading="processing" rounded="lg">
    <div class="overflow-x-auto">
      <v-table density="comfortable">
        <thead class="bg-grey-lighten-4">
          <tr>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              {{ $t('wallets.tableHeaders.name') }}
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              {{ $t('wallets.tableHeaders.address') }}
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              {{ $t('wallets.tableHeaders.balance') }}
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              {{ $t('wallets.tableHeaders.currency') }}
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              {{ $t('wallets.tableHeaders.status') }}
            </th>
            <th
              v-if="authStore.isAdmin"
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-right"
            >
              {{ $t('common.actions') }}
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
