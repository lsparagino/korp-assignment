<script lang="ts" setup>
  import type { Wallet } from '@/api/wallets'
  import { ref, watch } from 'vue'
  import { useI18n } from 'vue-i18n'
  import PageHeader from '@/components/layout/PageHeader.vue'
  import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
  import DataTable from '@/components/ui/DataTable.vue'
  import { useConfirmDialog } from '@/composables/useConfirmDialog'
  import { useRefreshData } from '@/composables/useRefreshData'
  import { useUrlPagination } from '@/composables/useUrlPagination'
  import { useWalletList } from '@/queries/wallets'
  import { useAuthStore } from '@/stores/auth'
  import { useWalletStore } from '@/stores/wallet'
  import { getCurrencyColors, getStatusColors } from '@/utils/colors'
  import { getErrorMessage, isApiError } from '@/utils/errors'
  import { formatCurrency } from '@/utils/formatters'

  const { t } = useI18n()
  const authStore = useAuthStore()
  const walletStore = useWalletStore()
  const snackbar = ref({ show: false, text: '', color: 'error' })
  const { confirmDialog, openConfirmDialog, executeConfirm } = useConfirmDialog()
  const { page: urlPage, perPage: urlPerPage, handlePageChange, handlePerPageChange } = useUrlPagination()

  const { wallets, meta, isPending: processing, refetch, page, perPage } = useWalletList()

  watch(urlPage, val => {
    page.value = val
  }, { immediate: true })
  watch(urlPerPage, val => {
    perPage.value = val
  }, { immediate: true })

  const { refreshing, refresh } = useRefreshData(async () => {
    await refetch()
  })

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
        v-if="authStore.isAdmin"
        class="text-none font-weight-bold"
        color="primary"
        data-testid="create-wallet-btn"
        prepend-icon="mdi-plus"
        rounded="lg"
        to="/wallets/create"
        variant="flat"
      >
        {{ $t('wallets.createWallet') }}
      </v-btn>
    </div>
  </PageHeader>

  <DataTable
    :loading="processing"
    :meta="meta"
    :refreshing="refreshing"
    :title="$t('wallets.title')"
    @refresh="refresh"
    @update:page="handlePageChange"
    @update:per-page="handlePerPageChange"
  >
    <template #columns>
      <th>{{ $t('wallets.tableHeaders.name') }}</th>

      <th>{{ $t('wallets.tableHeaders.balance') }}</th>
      <th>{{ $t('wallets.tableHeaders.availableBalance') }}</th>
      <th>{{ $t('wallets.tableHeaders.currency') }}</th>
      <th>{{ $t('wallets.tableHeaders.status') }}</th>
      <th v-if="authStore.isAdmin" class="text-right">
        {{ $t('common.actions') }}
      </th>
    </template>

    <template #body>
      <tr v-for="w in wallets" :key="w.id">
        <td class="font-weight-bold text-grey-darken-3">
          {{ w.name }}
        </td>

        <td
          class="font-weight-bold"
        >
          {{ formatCurrency(w.balance, w.currency) }}
        </td>
        <td class="font-weight-bold">
          {{ formatCurrency(w.available_balance, w.currency) }}
          <v-icon
            v-if="w.available_balance !== w.balance"
            class="ms-1"
            color="amber-darken-2"
            icon="mdi-alert"
            size="14"
          />
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
              data-testid="edit-btn"
              density="comfortable"
              icon="mdi-pencil"
              size="small"
              :to="`/wallets/${w.id}/edit`"
              variant="text"
            />

            <v-btn
              v-if="w.can_delete"
              color="error"
              data-testid="delete-btn"
              density="comfortable"
              icon="mdi-delete"
              size="small"
              variant="text"
              @click="deleteWallet(w)"
            />
          </div>
        </td>
      </tr>
    </template>
  </DataTable>

  <ConfirmDialog
    v-model="confirmDialog.show"
    :message="confirmDialog.message"
    :processing="confirmDialog.processing"
    :requires-pin="confirmDialog.requiresPin"
    :title="confirmDialog.title"
    @confirm="executeConfirm"
  />

  <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="5000">
    {{ snackbar.text }}
  </v-snackbar>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
