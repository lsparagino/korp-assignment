<script lang="ts" setup>
  import type { Transaction } from '@/api/transactions'
  import type { Wallet } from '@/api/wallets'
  import type { PaginationMeta } from '@/composables/useUrlPagination'
  import { ref } from 'vue'
  import Pagination from '@/components/ui/Pagination.vue'
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

  function getTransactionColor (item: Transaction) {
    if (item.to_wallet_id === null) return 'text-red-darken-1'
    if (item.from_wallet_id === null) return 'text-green-darken-1'
    return item.type.toLowerCase() === 'debit'
      ? 'text-red-darken-1'
      : 'text-green-darken-1'
  }
</script>

<template>
  <v-card border flat :loading="loading" rounded="lg">
    <v-card-title class="pa-4 bg-grey-lighten-5 border-b">
      <span class="text-subtitle-1 font-weight-bold text-grey-darken-3">{{
        title
      }}</span>
    </v-card-title>

    <div class="overflow-x-auto">
      <v-table density="comfortable">
        <thead>
          <tr>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              {{ $t('transactions.tableHeaders.date') }}
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              {{ $t('transactions.tableHeaders.type') }}
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              {{ $t('transactions.tableHeaders.amount') }}
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              {{ $t('transactions.tableHeaders.fromWallet') }}
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              {{ $t('transactions.tableHeaders.toWallet') }}
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-left"
            >
              {{ $t('transactions.tableHeaders.reference') }}
            </th>
            <th
              class="text-grey-darken-1 text-uppercase text-caption font-weight-bold text-center"
              style="width: 60px"
            >
              {{ $t('transactions.tableHeaders.actions') }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td
              class="text-grey-darken-2 text-caption text-no-wrap"
            >
              {{ formatDate(item.created_at) }}
            </td>
            <td class="text-grey-darken-3 font-weight-bold">
              <v-chip
                class="text-uppercase font-weight-bold"
                :color="
                  item.type.toLowerCase() === 'debit'
                    ? 'red-lighten-4'
                    : 'green-lighten-4'
                "
                size="x-small"
                variant="flat"
              >
                <span
                  :class="
                    item.type.toLowerCase() === 'debit'
                      ? 'text-red-darken-3'
                      : 'text-green-darken-3'
                  "
                >
                  {{ item.type }}
                </span>
              </v-chip>
            </td>
            <td
              :class="[getTransactionColor(item), 'font-weight-black']"
            >
              {{ formatCurrency(item.amount, item.currency) }}
            </td>
            <td>
              <div class="d-flex align-center">
                <v-avatar
                  class="me-2"
                  :color="
                    isAssigned(item.from_wallet_id)
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
                    isAssigned(item.from_wallet_id)
                      ? 'text-grey-darken-2'
                      : 'text-grey-lighten-1'
                  "
                >{{
                  item.from_wallet?.name || $t('transactions.external')
                }}</span>
              </div>
            </td>
            <td>
              <div class="d-flex align-center">
                <v-avatar
                  class="me-2"
                  :color="
                    isAssigned(item.to_wallet_id)
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
                    isAssigned(item.to_wallet_id)
                      ? 'text-grey-darken-2'
                      : 'text-grey-lighten-1'
                  "
                >{{
                  item.to_wallet?.name || $t('transactions.external')
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
        </tbody>
      </v-table>
    </div>

    <div v-if="showPagination && meta" class="border-t">
      <Pagination
        :meta="meta"
        @update:page="$emit('update:page', $event)"
        @update:per-page="$emit('update:per-page', $event)"
      />
    </div>

    <slot name="footer" />
  </v-card>

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
            :color="
              selectedTransaction.type.toLowerCase() === 'debit'
                ? 'red-lighten-4'
                : 'green-lighten-4'
            "
            variant="flat"
          >
            <span
              :class="
                selectedTransaction.type.toLowerCase() === 'debit'
                  ? 'text-red-darken-3'
                  : 'text-green-darken-3'
              "
            >
              {{ selectedTransaction.type }}
            </span>
          </v-chip>
          <span
            class="text-h5 font-weight-black"
            :class="getTransactionColor(selectedTransaction)"
          >
            {{ formatCurrency(selectedTransaction.amount, selectedTransaction.currency) }}
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
              {{ selectedTransaction.from_wallet?.name || $t('transactions.external') }}
            </div>
            <div
              v-if="selectedTransaction.from_wallet?.address"
              class="text-caption text-grey-darken-2 font-weight-regular mt-1"
              style="font-family: monospace"
            >
              {{ selectedTransaction.from_wallet.address }}
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
              {{ selectedTransaction.to_wallet?.name || $t('transactions.external') }}
            </div>
            <div
              v-if="selectedTransaction.to_wallet?.address"
              class="text-caption text-grey-darken-2 font-weight-regular mt-1"
              style="font-family: monospace"
            >
              {{ selectedTransaction.to_wallet.address }}
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
