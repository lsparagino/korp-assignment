<script lang="ts" setup>
  import type { Transaction } from '@/api/transactions'
  import { getTransactionStatusColors, getTransactionTypeColors } from '@/utils/colors'
  import { formatCurrency, formatDate } from '@/utils/formatters'

  const props = defineProps<{
    modelValue: boolean
    transaction: Transaction | null
    isTransfer?: boolean
  }>()

  const emit = defineEmits(['update:modelValue'])

  function getTransactionLabel () {
    if (!props.transaction) return ''
    if (props.isTransfer) return 'transfer'
    return props.transaction.type
  }

  function getDisplayAmount () {
    if (!props.transaction) return 0
    return props.isTransfer ? Math.abs(Number(props.transaction.amount)) : Number(props.transaction.amount)
  }

  function getFromWallet () {
    if (!props.transaction) return null
    return props.transaction.type.toLowerCase() === 'debit' ? props.transaction.wallet : props.transaction.counterpart_wallet
  }

  function getToWallet () {
    if (!props.transaction) return null
    return props.transaction.type.toLowerCase() === 'debit' ? props.transaction.counterpart_wallet : props.transaction.wallet
  }

  function getExternalLabel () {
    return props.transaction?.external_name || props.transaction?.external_address || 'External'
  }

  function getStatusLabel (status: string) {
    return status === 'pending_approval' ? 'pending' : status
  }
</script>

<template>
  <v-dialog max-width="560" :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <v-card v-if="transaction" rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between pa-5 border-b">
        <span class="text-h6 font-weight-bold">{{ $t('transactions.transactionDetails') }}</span>
        <v-btn
          density="comfortable"
          icon="mdi-close"
          variant="text"
          @click="emit('update:modelValue', false)"
        />
      </v-card-title>

      <v-card-text class="pa-5">
        <!-- Type, Status & Amount Header -->
        <div class="d-flex align-center justify-space-between mb-6">
          <div class="d-flex align-center ga-2">
            <v-chip
              class="text-uppercase font-weight-bold"
              :color="getTransactionTypeColors(getTransactionLabel()).bg"
              variant="flat"
            >
              <span :class="`text-${getTransactionTypeColors(getTransactionLabel()).text}`">
                {{ getTransactionLabel() }}
              </span>
            </v-chip>
            <v-chip
              class="text-uppercase font-weight-bold"
              :color="getTransactionStatusColors(transaction.status).bg"
              variant="flat"
            >
              <span :class="`text-${getTransactionStatusColors(transaction.status).text}`">
                {{ getStatusLabel(transaction.status) }}
              </span>
            </v-chip>
          </div>
          <span
            class="text-h5 font-weight-black"
            :class="`text-${getTransactionTypeColors(getTransactionLabel()).text}`"
          >
            {{ formatCurrency(getDisplayAmount(), transaction.currency) }}
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
              {{ formatDate(transaction.created_at) }}
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
              {{ transaction.currency }}
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
              {{ getFromWallet()?.name || getExternalLabel() }}
            </div>
            <div
              v-if="getFromWallet()?.address"
              class="text-caption text-grey-darken-2 font-weight-regular mt-1"
              style="font-family: monospace"
            >
              {{ getFromWallet()?.address }}
            </div>
            <div
              v-else-if="transaction.external && transaction.external_address"
              class="text-caption text-grey-darken-2 font-weight-regular mt-1"
              style="font-family: monospace"
            >
              {{ transaction.external_address }}
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
              {{ getToWallet()?.name || getExternalLabel() }}
            </div>
            <div
              v-if="getToWallet()?.address"
              class="text-caption text-grey-darken-2 font-weight-regular mt-1"
              style="font-family: monospace"
            >
              {{ getToWallet()?.address }}
            </div>
            <div
              v-else-if="transaction.external && transaction.external_address"
              class="text-caption text-grey-darken-2 font-weight-regular mt-1"
              style="font-family: monospace"
            >
              {{ transaction.external_address }}
            </div>
          </v-list-item>

          <!-- Reference -->
          <template v-if="transaction.reference">
            <v-divider />
            <v-list-item class="px-0">
              <template #prepend>
                <v-icon class="me-3" color="grey-darken-1" icon="mdi-tag" />
              </template>
              <v-list-item-title class="text-caption text-grey-darken-1">
                {{ $t('transactions.reference') }}
              </v-list-item-title>
              <div class="text-body-2 font-weight-medium text-grey-darken-3">
                {{ transaction.reference }}
              </div>
            </v-list-item>
          </template>

          <!-- Notes -->
          <template v-if="transaction.notes">
            <v-divider />
            <v-list-item class="px-0">
              <template #prepend>
                <v-icon class="me-3" color="grey-darken-1" icon="mdi-note-text" />
              </template>
              <v-list-item-title class="text-caption text-grey-darken-1">
                {{ $t('transactions.notes') }}
              </v-list-item-title>
              <div class="text-body-2 font-weight-medium text-grey-darken-3">
                {{ transaction.notes }}
              </div>
            </v-list-item>
          </template>

          <!-- Reject Reason -->
          <template v-if="transaction.reject_reason">
            <v-divider />
            <v-list-item class="px-0">
              <template #prepend>
                <v-icon class="me-3" color="red-darken-1" icon="mdi-close-circle" />
              </template>
              <v-list-item-title class="text-caption text-grey-darken-1">
                {{ $t('transactions.rejectReason') }}
              </v-list-item-title>
              <div class="text-body-2 font-weight-medium text-red-darken-2">
                {{ transaction.reject_reason }}
              </div>
            </v-list-item>
          </template>
        </v-list>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>
