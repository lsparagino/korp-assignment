<script lang="ts" setup>
  import type { Transaction } from '@/api/transactions'
  import { ref, watch } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { cancelTransfer, fetchTransactions, reviewTransfer } from '@/api/transactions'
  import TransactionTable from '@/components/features/TransactionTable.vue'
  import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
  import { useConfirmDialog } from '@/composables/useConfirmDialog'
  import { useIdempotencyKey } from '@/composables/useIdempotencyKey'
  import { useAuthStore } from '@/stores/auth'
  import { getTransactionStatusColors, getTransactionTypeColors } from '@/utils/colors'
  import { formatCurrency, formatDate } from '@/utils/formatters'

  const props = defineProps<{
    modelValue: boolean
    transaction: Transaction | null
    isTransfer?: boolean
    isManagerOrAdmin?: boolean
  }>()

  const emit = defineEmits(['update:modelValue', 'reviewed'])

  const { t } = useI18n()
  const authStore = useAuthStore()
  const { confirmDialog, openConfirmDialog, executeConfirm } = useConfirmDialog()
  const { idempotencyKey, regenerateKey } = useIdempotencyKey()
  const rejectMode = ref(false)
  const rejectReason = ref('')
  const submitting = ref(false)
  const error = ref('')

  watch(() => props.transaction, () => regenerateKey())

  // Sliding panel state
  const currentPage = ref<'details' | 'recent'>('details')
  const recentTransactions = ref<Transaction[]>([])
  const loadingRecent = ref(false)

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

  function isPending () {
    return props.transaction?.status === 'pending_approval'
  }

  function canReview () {
    return isPending() && props.isManagerOrAdmin
  }

  function canCancel () {
    return isPending() && props.transaction?.initiator_user_id === authStore.user?.id
  }

  async function handleCancel () {
    if (!props.transaction) return
    submitting.value = true
    error.value = ''
    try {
      await cancelTransfer(props.transaction.group_id, idempotencyKey.value)
      emit('reviewed')
      emit('update:modelValue', false)
    } catch {
      error.value = t('common.genericError')
    } finally {
      submitting.value = false
      regenerateKey()
    }
  }

  async function handleApprove () {
    if (!props.transaction) return
    submitting.value = true
    error.value = ''
    try {
      await reviewTransfer(props.transaction.group_id, { action: 'approve' }, idempotencyKey.value)
      emit('reviewed')
      emit('update:modelValue', false)
    } catch {
      error.value = t('common.genericError')
    } finally {
      submitting.value = false
      regenerateKey()
    }
  }

  async function handleReject () {
    if (!props.transaction || !rejectReason.value.trim()) return
    submitting.value = true
    error.value = ''
    try {
      await reviewTransfer(props.transaction.group_id, { action: 'reject', reason: rejectReason.value }, idempotencyKey.value)
      rejectMode.value = false
      rejectReason.value = ''
      emit('reviewed')
      emit('update:modelValue', false)
    } catch {
      error.value = t('common.genericError')
    } finally {
      submitting.value = false
      regenerateKey()
    }
  }

  async function showRecentTransactions () {
    if (!props.transaction?.initiator) return
    loadingRecent.value = true
    currentPage.value = 'recent'
    try {
      const response = await fetchTransactions({
        initiator_user_id: props.transaction.initiator.id,
        per_page: 5,
      })
      recentTransactions.value = response.data.data
    } catch {
      recentTransactions.value = []
    } finally {
      loadingRecent.value = false
    }
  }

  function backToDetails () {
    currentPage.value = 'details'
  }

  function resetState () {
    rejectMode.value = false
    rejectReason.value = ''
    error.value = ''
    currentPage.value = 'details'
    recentTransactions.value = []
  }
</script>

<template>
  <v-dialog
    max-width="560"
    :model-value="modelValue"
    @after-leave="resetState"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <v-card v-if="transaction" rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between pa-5 border-b">
        <span class="text-h6 font-weight-bold">{{ $t('transactions.transactionDetails') }}</span>
        <v-btn density="comfortable" icon="mdi-close" variant="text" @click="emit('update:modelValue', false)" />
      </v-card-title>

      <div class="sliding-container">
        <div
          class="sliding-track"
          :style="{ transform: currentPage === 'recent' ? 'translateX(-50%)' : 'translateX(0)' }"
        >
          <!-- ═══ Page 1: Transaction Details ═══ -->
          <div class="sliding-page">
            <v-card-text class="pa-5">
              <!-- Type, Status & Amount Header -->
              <div class="d-flex flex-column flex-sm-row align-center align-sm-center justify-space-between ga-2 mb-6">
                <div class="d-flex align-center ga-1">
                  <v-chip
                    class="text-uppercase font-weight-bold"
                    :color="getTransactionTypeColors(getTransactionLabel()).text"
                    size="small"
                    variant="text"
                  >
                    {{ getTransactionLabel() }}
                  </v-chip>
                  <v-chip
                    class="text-uppercase font-weight-bold"
                    :color="getTransactionStatusColors(transaction.status).text"
                    size="small"
                    variant="text"
                  >
                    {{ getStatusLabel(transaction.status) }}
                  </v-chip>
                </div>
                <span
                  class="text-h5 font-weight-black"
                  :class="`text-${getTransactionTypeColors(getTransactionLabel()).text}`"
                >
                  {{ formatCurrency(getDisplayAmount(), transaction.source_currency) }}
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
                    {{ transaction.source_currency }}
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

                <!-- Initiator -->
                <template v-if="transaction.initiator">
                  <v-divider />
                  <v-list-item class="px-0" data-testid="initiator-row">
                    <template #prepend>
                      <v-icon class="me-3" color="grey-darken-1" icon="mdi-account" />
                    </template>
                    <v-list-item-title class="text-caption text-grey-darken-1">
                      {{ $t('transactions.initiator') }}
                    </v-list-item-title>
                    <div class="text-body-2 font-weight-medium text-grey-darken-3">
                      {{ transaction.initiator.name }}
                    </div>
                    <template v-if="isManagerOrAdmin" #append>
                      <v-btn
                        class="text-none text-caption"
                        color="primary"
                        data-testid="view-recent-btn"
                        size="small"
                        variant="text"
                        @click.stop="showRecentTransactions"
                      >
                        {{ $t('transactions.viewRecentTransactions') }}
                      </v-btn>
                    </template>
                  </v-list-item>
                </template>

                <!-- Reviewer -->
                <template v-if="transaction.reviewer">
                  <v-divider />
                  <v-list-item class="px-0" data-testid="reviewer-row">
                    <template #prepend>
                      <v-icon class="me-3" color="grey-darken-1" icon="mdi-account-check" />
                    </template>
                    <v-list-item-title class="text-caption text-grey-darken-1">
                      {{ $t('transactions.reviewer') }}
                    </v-list-item-title>
                    <div class="text-body-2 font-weight-medium text-grey-darken-3">
                      {{ transaction.reviewer.name }}
                    </div>
                  </v-list-item>
                </template>

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

              <!-- Approval Actions -->
              <template v-if="canReview()">
                <v-divider class="my-4" />

                <v-alert
                  v-if="error"
                  class="mb-4"
                  color="error"
                  data-testid="review-error"
                  density="compact"
                  type="error"
                  variant="tonal"
                >
                  {{ error }}
                </v-alert>

                <template v-if="!rejectMode">
                  <div class="d-flex ga-3">
                    <v-btn
                      class="text-none font-weight-bold flex-grow-1"
                      color="success"
                      data-testid="approve-btn"
                      :loading="submitting"
                      prepend-icon="mdi-check-circle"
                      rounded="lg"
                      variant="flat"
                      @click="handleApprove"
                    >
                      {{ $t('transactions.approve') }}
                    </v-btn>
                    <v-btn
                      class="text-none font-weight-bold flex-grow-1"
                      color="error"
                      data-testid="reject-btn"
                      :disabled="submitting"
                      prepend-icon="mdi-close-circle"
                      rounded="lg"
                      variant="outlined"
                      @click="rejectMode = true"
                    >
                      {{ $t('transactions.reject') }}
                    </v-btn>
                  </div>
                </template>

                <template v-else>
                  <v-textarea
                    v-model="rejectReason"
                    auto-grow
                    class="mb-3"
                    color="error"
                    data-testid="reject-reason-input"
                    :label="$t('transactions.rejectReasonLabel')"
                    :placeholder="$t('transactions.rejectReasonPlaceholder')"
                    rows="3"
                    variant="outlined"
                  />
                  <div class="d-flex ga-3">
                    <v-btn
                      class="text-none font-weight-bold flex-grow-1"
                      color="error"
                      data-testid="confirm-reject-btn"
                      :disabled="!rejectReason.trim()"
                      :loading="submitting"
                      prepend-icon="mdi-close-circle"
                      rounded="lg"
                      variant="flat"
                      @click="handleReject"
                    >
                      {{ $t('transactions.reject') }}
                    </v-btn>
                    <v-btn
                      class="text-none font-weight-bold"
                      color="grey-darken-1"
                      data-testid="cancel-reject-btn"
                      :disabled="submitting"
                      rounded="lg"
                      variant="outlined"
                      @click="rejectMode = false"
                    >
                      {{ $t('common.cancel') }}
                    </v-btn>
                  </div>
                </template>
              </template>

              <!-- Cancel Action -->
              <template v-if="canCancel()">
                <v-divider v-if="!canReview()" class="my-4" />

                <v-alert
                  v-if="error && !canReview()"
                  class="mb-4"
                  color="error"
                  data-testid="cancel-error"
                  density="compact"
                  type="error"
                  variant="tonal"
                >
                  {{ error }}
                </v-alert>

                <v-btn
                  block
                  class="text-none font-weight-bold mt-3"
                  color="grey-darken-1"
                  data-testid="cancel-transaction-btn"
                  :loading="submitting"
                  prepend-icon="mdi-cancel"
                  rounded="lg"
                  variant="outlined"
                  @click="openConfirmDialog({
                    title: t('transactions.cancelTransaction'),
                    message: t('transactions.confirmCancel'),
                    requiresPin: false,
                    onConfirm: handleCancel,
                  })"
                >
                  {{ $t('transactions.cancelTransaction') }}
                </v-btn>
              </template>
            </v-card-text>
          </div>

          <!-- ═══ Page 2: Recent Transactions ═══ -->
          <div class="sliding-page">
            <v-card-text class="pa-5">
              <v-btn
                class="text-none mb-4"
                color="primary"
                data-testid="back-to-details-btn"
                prepend-icon="mdi-arrow-left"
                size="small"
                variant="text"
                @click="backToDetails"
              >
                {{ $t('transactions.backToDetails') }}
              </v-btn>

              <div class="text-subtitle-1 font-weight-bold mb-4">
                {{ $t('transactions.recentTransactionsByUser', { name: transaction.initiator?.name }) }}
              </div>

              <TransactionTable
                compact
                is-admin
                :items="recentTransactions"
                :loading="loadingRecent"
                :title="$t('transactions.recentTransactionsByUser', { name: transaction.initiator?.name })"
              />
            </v-card-text>
          </div>
        </div>
      </div>

      <ConfirmDialog
        v-model="confirmDialog.show"
        :message="confirmDialog.message"
        :processing="confirmDialog.processing"
        :title="confirmDialog.title"
        @confirm="executeConfirm"
      />
    </v-card>
  </v-dialog>
</template>

<style scoped>
.sliding-container {
  overflow: hidden;
}

.sliding-track {
  display: flex;
  width: 200%;
  transition: transform 0.3s ease-in-out;
}

.sliding-page {
  width: 50%;
  flex-shrink: 0;
}
</style>
