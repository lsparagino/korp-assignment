<script lang="ts" setup>
  import type { Transaction } from '@/api/transactions'
  import type { Wallet } from '@/api/wallets'
  import type { PaginationMeta } from '@/composables/useUrlPagination'
  import { ref } from 'vue'
  import DataTable from '@/components/ui/DataTable.vue'
  import { getTransactionTypeColors } from '@/utils/colors'
  import { formatCurrency, formatDate } from '@/utils/formatters'

  interface Props {
    items: Transaction[]
    loading?: boolean
    wallets?: Wallet[]
    showPagination?: boolean
    meta?: PaginationMeta
    title?: string
    isAdmin?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    loading: false,
    wallets: () => [],
    showPagination: false,
    title: 'Transactions',
    isAdmin: false,
  })

  defineEmits(['update:page', 'update:per-page'])

  const detailDialog = ref(false)
  const selectedTransaction = ref<Transaction | null>(null)

  function openDetail (item: Transaction) {
    selectedTransaction.value = item
    detailDialog.value = true
  }

  function isAssigned (walletId: number | null) {
    if (!walletId) return false
    if (props.isAdmin) return true
    return props.wallets.some(w => w.id === walletId)
  }

  function isTransfer (item: Transaction) {
    return !item.external
      && item.wallet_id !== null
      && item.counterpart_wallet_id !== null
      && isAssigned(item.wallet_id)
      && isAssigned(item.counterpart_wallet_id)
  }

  /**
   * Map the double-entry fields to intuitive from/to display.
   * - Debit: money leaves wallet → wallet is "from", counterpart is "to"
   * - Credit: money enters wallet → counterpart is "from", wallet is "to"
   */
  function getFromWallet (item: Transaction) {
    return item.type.toLowerCase() === 'debit' ? item.wallet : item.counterpart_wallet
  }

  function getToWallet (item: Transaction) {
    return item.type.toLowerCase() === 'debit' ? item.counterpart_wallet : item.wallet
  }

  function getFromWalletId (item: Transaction) {
    return item.type.toLowerCase() === 'debit' ? item.wallet_id : item.counterpart_wallet_id
  }

  function getToWalletId (item: Transaction) {
    return item.type.toLowerCase() === 'debit' ? item.counterpart_wallet_id : item.wallet_id
  }

  function getTransactionLabel (item: Transaction) {
    if (isTransfer(item)) return 'transfer'
    return item.type
  }

  function getTransactionColor (item: Transaction) {
    const label = getTransactionLabel(item)
    return `text-${getTransactionTypeColors(label).text}`
  }

  function getChipColor (item: Transaction) {
    return getTransactionTypeColors(getTransactionLabel(item)).bg
  }

  function getChipTextColor (item: Transaction) {
    return `text-${getTransactionTypeColors(getTransactionLabel(item)).text}`
  }

  function getDisplayAmount (item: Transaction) {
    return isTransfer(item) ? Math.abs(Number(item.amount)) : Number(item.amount)
  }
</script>

<template>
  <DataTable
    :loading="loading"
    :meta="showPagination ? meta : undefined"
    @update:page="$emit('update:page', $event)"
    @update:per-page="$emit('update:per-page', $event)"
  >
    <template #columns>
      <th>{{ $t('transactions.tableHeaders.date') }}</th>
      <th>{{ $t('transactions.tableHeaders.type') }}</th>
      <th>{{ $t('transactions.tableHeaders.amount') }}</th>
      <th>{{ $t('transactions.tableHeaders.fromWallet') }}</th>
      <th>{{ $t('transactions.tableHeaders.toWallet') }}</th>
      <th>{{ $t('transactions.tableHeaders.reference') }}</th>
      <th class="text-center" style="width: 60px">
        {{ $t('transactions.tableHeaders.actions') }}
      </th>
    </template>

    <template #body>
      <tr v-for="item in items" :key="item.id">
        <td
          class="text-grey-darken-2 text-caption text-no-wrap"
        >
          {{ formatDate(item.created_at) }}
        </td>
        <td class="text-grey-darken-3 font-weight-bold">
          <v-chip
            class="text-uppercase font-weight-bold"
            :color="getChipColor(item)"
            size="x-small"
            variant="flat"
          >
            <span :class="getChipTextColor(item)">
              {{ getTransactionLabel(item) }}
            </span>
          </v-chip>
        </td>
        <td
          :class="[getTransactionColor(item), 'font-weight-black']"
        >
          {{ formatCurrency(getDisplayAmount(item), item.currency) }}
        </td>
        <td>
          <div class="d-flex align-center">
            <v-avatar
              class="me-2"
              :color="
                isAssigned(getFromWalletId(item))
                  ? 'primary'
                  : 'grey-lighten-2'
              "
              rounded="sm"
              size="20"
            >
              <v-icon
                color="white"
                icon="mdi-wallet"
                size="12"
              />
            </v-avatar>
            <span
              class="text-caption font-weight-medium"
              :class="
                isAssigned(getFromWalletId(item))
                  ? 'text-grey-darken-2'
                  : 'text-grey-lighten-1'
              "
            >{{
              getFromWallet(item)?.name || $t('transactions.external')
            }}</span>
          </div>
        </td>
        <td>
          <div class="d-flex align-center">
            <v-avatar
              class="me-2"
              :color="
                isAssigned(getToWalletId(item))
                  ? 'primary'
                  : 'grey-lighten-2'
              "
              rounded="sm"
              size="20"
            >
              <v-icon
                color="white"
                icon="mdi-wallet"
                size="12"
              />
            </v-avatar>
            <span
              class="text-caption font-weight-medium"
              :class="
                isAssigned(getToWalletId(item))
                  ? 'text-grey-darken-2'
                  : 'text-grey-lighten-1'
              "
            >{{
              getToWallet(item)?.name || $t('transactions.external')
            }}</span>
          </div>
        </td>
        <td class="text-grey-darken-2 text-caption">
          {{ item.reference }}
        </td>
        <td class="text-center">
          <v-btn
            color="primary"
            density="comfortable"
            icon="mdi-eye"
            size="small"
            variant="text"
            @click="openDetail(item)"
          />
        </td>
      </tr>
      <tr v-if="!loading && items.length === 0">
        <td
          class="text-grey-darken-1 py-8 text-center"
          colspan="7"
        >
          {{ $t('transactions.noTransactions') }}
        </td>
      </tr>
    </template>

    <template #footer>
      <slot name="footer" />
    </template>
  </DataTable>

  <!-- Transaction Detail Modal -->
  <v-dialog v-model="detailDialog" max-width="560">
    <v-card v-if="selectedTransaction" rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between pa-5 border-b">
        <span class="text-h6 font-weight-bold">{{ $t('transactions.transactionDetails') }}</span>
        <v-btn
          density="comfortable"
          icon="mdi-close"
          variant="text"
          @click="detailDialog = false"
        />
      </v-card-title>

      <v-card-text class="pa-5">
        <!-- Type & Amount Header -->
        <div class="d-flex align-center justify-space-between mb-6">
          <v-chip
            class="text-uppercase font-weight-bold"
            :color="getChipColor(selectedTransaction)"
            variant="flat"
          >
            <span :class="getChipTextColor(selectedTransaction)">
              {{ getTransactionLabel(selectedTransaction) }}
            </span>
          </v-chip>
          <span
            class="text-h5 font-weight-black"
            :class="getTransactionColor(selectedTransaction)"
          >
            {{ formatCurrency(getDisplayAmount(selectedTransaction), selectedTransaction.currency) }}
          </span>
        </div>

        <!-- Detail Rows -->
        <v-list class="pa-0" lines="two">
          <v-list-item class="px-0">
            <template #prepend>
              <v-icon class="me-3" color="grey-darken-1" icon="mdi-calendar" />
            </template>
            <v-list-item-title class="text-caption text-grey-darken-1">
              {{ $t('transactions.date') }}
            </v-list-item-title>
            <div class="text-body-2 font-weight-medium text-grey-darken-3">
              {{ formatDate(selectedTransaction.created_at) }}
            </div>
          </v-list-item>

          <v-divider />

          <v-list-item class="px-0">
            <template #prepend>
              <v-icon class="me-3" color="grey-darken-1" icon="mdi-currency-usd" />
            </template>
            <v-list-item-title class="text-caption text-grey-darken-1">
              {{ $t('wallets.tableHeaders.currency') }}
            </v-list-item-title>
            <div class="text-body-2 font-weight-medium text-grey-darken-3">
              {{ selectedTransaction.currency }}
            </div>
          </v-list-item>

          <v-divider />

          <!-- From Wallet -->
          <v-list-item class="px-0">
            <template #prepend>
              <v-icon class="me-3" color="red-darken-1" icon="mdi-arrow-up-circle" />
            </template>
            <v-list-item-title class="text-caption text-grey-darken-1">
              {{ $t('transactions.fromWallet') }}
            </v-list-item-title>
            <div class="text-body-2 font-weight-medium text-grey-darken-3">
              {{ getFromWallet(selectedTransaction)?.name || $t('transactions.external') }}
            </div>
            <div
              v-if="getFromWallet(selectedTransaction)?.address"
              class="text-caption text-grey-darken-2 font-weight-regular mt-1"
              style="font-family: monospace"
            >
              {{ getFromWallet(selectedTransaction)?.address }}
            </div>
          </v-list-item>

          <v-divider />

          <!-- To Wallet -->
          <v-list-item class="px-0">
            <template #prepend>
              <v-icon class="me-3" color="green-darken-1" icon="mdi-arrow-down-circle" />
            </template>
            <v-list-item-title class="text-caption text-grey-darken-1">
              {{ $t('transactions.toWallet') }}
            </v-list-item-title>
            <div class="text-body-2 font-weight-medium text-grey-darken-3">
              {{ getToWallet(selectedTransaction)?.name || $t('transactions.external') }}
            </div>
            <div
              v-if="getToWallet(selectedTransaction)?.address"
              class="text-caption text-grey-darken-2 font-weight-regular mt-1"
              style="font-family: monospace"
            >
              {{ getToWallet(selectedTransaction)?.address }}
            </div>
          </v-list-item>

          <template v-if="selectedTransaction.reference">
            <v-divider />
            <v-list-item class="px-0">
              <template #prepend>
                <v-icon class="me-3" color="grey-darken-1" icon="mdi-tag" />
              </template>
              <v-list-item-title class="text-caption text-grey-darken-1">
                {{ $t('transactions.reference') }}
              </v-list-item-title>
              <div class="text-body-2 font-weight-medium text-grey-darken-3">
                {{ selectedTransaction.reference }}
              </div>
            </v-list-item>
          </template>
        </v-list>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>
