<script lang="ts" setup>
import type { Transaction } from '@/api/transactions'
import type { Wallet } from '@/api/wallets'
import type { PaginationMeta } from '@/composables/useUrlPagination'
import { ref } from 'vue'
import TransactionDetailModal from '@/components/features/TransactionDetailModal.vue'
import DataTable from '@/components/ui/DataTable.vue'
import { getTransactionStatusColors, getTransactionTypeColors } from '@/utils/colors'
import { formatCurrency, formatDate, getStatusIcon } from '@/utils/formatters'

interface Props {
  items: Transaction[]
  loading?: boolean
  wallets?: Wallet[]
  showPagination?: boolean
  meta?: PaginationMeta
  title?: string
  refreshing?: boolean
  isAdmin?: boolean
  isManagerOrAdmin?: boolean
  compact?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  wallets: () => [],
  showPagination: false,
  title: 'Transactions',
  refreshing: false,
  isAdmin: false,
  isManagerOrAdmin: false,
  compact: false,
})

const emit = defineEmits(['update:page', 'update:per-page', 'refresh', 'reviewed'])

const detailDialog = ref(false)
const selectedTransaction = ref<Transaction | null>(null)

function openDetail(item: Transaction) {
  selectedTransaction.value = item
  detailDialog.value = true
}

function isAssigned(walletId: number | null) {
  if (!walletId) return false
  if (props.isManagerOrAdmin) return true
  return props.wallets.some(w => w.id === walletId)
}

function isTransfer(item: Transaction) {
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
function getFromWallet(item: Transaction) {
  return item.type.toLowerCase() === 'debit' ? item.wallet : item.counterpart_wallet
}

function getToWallet(item: Transaction) {
  return item.type.toLowerCase() === 'debit' ? item.counterpart_wallet : item.wallet
}

function getFromWalletId(item: Transaction) {
  return item.type.toLowerCase() === 'debit' ? item.wallet_id : item.counterpart_wallet_id
}

function getToWalletId(item: Transaction) {
  return item.type.toLowerCase() === 'debit' ? item.counterpart_wallet_id : item.wallet_id
}

function getTransactionLabel(item: Transaction) {
  if (isTransfer(item)) return 'transfer'
  return item.type
}

function getTransactionColor(item: Transaction) {
  const label = getTransactionLabel(item)
  return `text-${getTransactionTypeColors(label).text}`
}

function getChipColor(item: Transaction) {
  return getTransactionTypeColors(getTransactionLabel(item)).bg
}

function getChipTextColor(item: Transaction) {
  return `text-${getTransactionTypeColors(getTransactionLabel(item)).text}`
}

function getDisplayAmount(item: Transaction) {
  return isTransfer(item) ? Math.abs(Number(item.amount)) : Number(item.amount)
}

function getStatusLabel(status: string) {
  return status === 'pending_approval' ? 'pending' : status
}

function getExternalLabel(item: Transaction) {
  return item.external_name || item.external_address || 'External'
}

function getFromLabel(item: Transaction) {
  return getFromWallet(item)?.name || getExternalLabel(item)
}

function getToLabel(item: Transaction) {
  return getToWallet(item)?.name || getExternalLabel(item)
}
</script>

<template>
  <DataTable :loading="loading" :meta="showPagination ? meta : undefined" :refreshing="refreshing" :title="title"
    @refresh="emit('refresh')" @update:page="emit('update:page', $event)"
    @update:per-page="emit('update:per-page', $event)">
    <template #columns>
      <!-- Mobile header (xs only, or always when compact) -->
      <th :class="compact ? '' : 'd-sm-none'">{{ $t('transactions.tableHeaders.transaction') }}</th>

      <!-- Desktop headers (sm+, hidden when compact) -->
      <template v-if="!compact">
        <th class="d-none d-sm-table-cell">{{ $t('transactions.tableHeaders.date') }}</th>
        <th class="d-none d-lg-table-cell">{{ $t('transactions.tableHeaders.type') }}</th>
        <th class="d-none d-sm-table-cell" style="width: 40px">{{ $t('transactions.tableHeaders.status') }}</th>
        <th class="d-none d-sm-table-cell">{{ $t('transactions.tableHeaders.amount') }}</th>
        <th class="d-none d-sm-table-cell">{{ $t('transactions.tableHeaders.fromWallet') }}</th>
        <th class="d-none d-sm-table-cell">{{ $t('transactions.tableHeaders.toWallet') }}</th>

        <th class="text-center d-none d-sm-table-cell" style="width: 60px">
          {{ $t('transactions.tableHeaders.actions') }}
        </th>
      </template>
    </template>

    <template #body>
      <tr v-for="item in items" :key="item.id" class="cursor-pointer"
        :style="item.status === 'rejected' ? 'opacity: 0.5' : ''" @click="openDetail(item)">
        <!-- ── Mobile: Single cell with two rows ── -->
        <td :class="compact ? 'py-3' : 'd-sm-none py-3'">
          <!-- Row 1: status icon + date (left) — amount (right) -->
          <div class="d-flex align-center justify-space-between mb-2">
            <div class="d-flex align-center ga-1">
              <v-icon :color="getTransactionStatusColors(item.status).text" :icon="getStatusIcon(item.status)"
                size="14" />
              <span class="text-caption text-grey-darken-1 text-no-wrap">
                {{ formatDate(item.created_at) }}
              </span>
            </div>
            <span class="font-weight-black text-body-2" :class="getTransactionColor(item)">
              {{ formatCurrency(getDisplayAmount(item), item.currency) }}
            </span>
          </div>
          <!-- Row 2: from wallet → to wallet -->
          <div class="d-flex align-center ga-1 text-caption">
            <v-avatar class="flex-shrink-0" :color="isAssigned(getFromWalletId(item)) ? 'primary' : 'grey-lighten-2'"
              rounded="sm" size="14">
              <v-icon color="white" icon="mdi-wallet" size="8" />
            </v-avatar>
            <span class="font-weight-medium text-no-wrap text-truncate"
              :class="isAssigned(getFromWalletId(item)) ? 'text-grey-darken-2' : 'text-grey-lighten-1'">{{
              getFromLabel(item) }}</span>
            <v-icon color="grey" icon="mdi-arrow-right" size="12" />
            <v-avatar class="flex-shrink-0" :color="isAssigned(getToWalletId(item)) ? 'primary' : 'grey-lighten-2'"
              rounded="sm" size="14">
              <v-icon color="white" icon="mdi-wallet" size="8" />
            </v-avatar>
            <span class="font-weight-medium text-no-wrap text-truncate"
              :class="isAssigned(getToWalletId(item)) ? 'text-grey-darken-2' : 'text-grey-lighten-1'">{{
              getToLabel(item) }}</span>
          </div>
        </td>

        <!-- ── Desktop: Date ── -->
        <template v-if="!compact">
          <td class="text-grey-darken-2 text-caption text-no-wrap d-none d-sm-table-cell">
            {{ formatDate(item.created_at) }}
          </td>

          <!-- ── Desktop lg+: Type ── -->
          <td class="text-grey-darken-3 font-weight-bold d-none d-lg-table-cell">
            <v-chip class="text-uppercase font-weight-bold" :color="getChipColor(item)" size="x-small" variant="text">
              <span :class="getChipTextColor(item)">
                {{ getTransactionLabel(item) }}
              </span>
            </v-chip>
          </td>

          <!-- ── Tablet sm/md: Status icon only ── -->
          <td class="d-none d-sm-table-cell d-lg-none text-center">
            <v-icon :color="getTransactionStatusColors(item.status).text" :icon="getStatusIcon(item.status)"
              size="18" />
          </td>

          <!-- ── Desktop lg+: Status chip ── -->
          <td class="d-none d-lg-table-cell">
            <v-chip class="text-uppercase font-weight-bold" :color="getTransactionStatusColors(item.status).bg"
              size="x-small" variant="text">
              <span :class="`text-${getTransactionStatusColors(item.status).text}`">
                {{ getStatusLabel(item.status) }}
              </span>
            </v-chip>
          </td>

          <!-- ── Desktop: Amount ── -->
          <td class="d-none d-sm-table-cell" :class="[getTransactionColor(item), 'font-weight-black']">
            {{ formatCurrency(getDisplayAmount(item), item.currency) }}
          </td>

          <!-- ── Desktop: From Wallet ── -->
          <td class="d-none d-sm-table-cell">
            <div class="d-flex align-center">
              <v-avatar class="me-2" :color="isAssigned(getFromWalletId(item)) ? 'primary' : 'grey-lighten-2'"
                rounded="sm" size="20">
                <v-icon color="white" icon="mdi-wallet" size="12" />
              </v-avatar>
              <span class="text-caption font-weight-medium"
                :class="isAssigned(getFromWalletId(item)) ? 'text-grey-darken-2' : 'text-grey-lighten-1'">{{
                getFromLabel(item) }}</span>
            </div>
          </td>

          <!-- ── Desktop: To Wallet ── -->
          <td class="d-none d-sm-table-cell">
            <div class="d-flex align-center">
              <v-avatar class="me-2" :color="isAssigned(getToWalletId(item)) ? 'primary' : 'grey-lighten-2'"
                rounded="sm" size="20">
                <v-icon color="white" icon="mdi-wallet" size="12" />
              </v-avatar>
              <span class="text-caption font-weight-medium"
                :class="isAssigned(getToWalletId(item)) ? 'text-grey-darken-2' : 'text-grey-lighten-1'">{{
                getToLabel(item) }}</span>
            </div>
          </td>

          <!-- ── Actions (desktop only) ── -->
          <td class="text-center d-none d-sm-table-cell">
            <v-btn color="primary" density="comfortable" icon="mdi-eye" size="small" variant="text"
              @click.stop="openDetail(item)" />
          </td>
        </template>
      </tr>
      <tr v-if="!loading && items.length === 0">
        <td class="text-grey-darken-1 py-8 text-center" colspan="9">
          {{ $t('transactions.noTransactions') }}
        </td>
      </tr>
    </template>

    <template #footer>
      <slot name="footer" />
    </template>
  </DataTable>

  <TransactionDetailModal v-model="detailDialog" :is-manager-or-admin="isManagerOrAdmin"
    :is-transfer="selectedTransaction ? isTransfer(selectedTransaction) : false" :transaction="selectedTransaction"
    @reviewed="emit('reviewed')" />
</template>
