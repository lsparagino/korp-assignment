<script lang="ts" setup>
  import type { TransferForm } from '@/api/transactions'
  import type { Wallet } from '@/api/wallets'
  import { useQuery, useQueryCache } from '@pinia/colada'
  import { computed, ref, watch } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { initiateTransfer } from '@/api/transactions'
  import { useFormValidation } from '@/composables/useFormValidation'
  import { WALLET_QUERY_KEYS, walletsListQuery } from '@/queries/wallets'
  import { useAuthStore } from '@/stores/auth'
  import { getValidationErrors, isApiError } from '@/utils/errors'
  import { formatCurrency } from '@/utils/formatters'

  const { t } = useI18n()
  const authStore = useAuthStore()

  const bypassesThreshold = computed(() =>
    authStore.user?.role === 'admin' || authStore.user?.role === 'manager',
  )

  const props = defineProps<{
    modelValue: boolean
  }>()

  const emit = defineEmits(['update:modelValue', 'saved'])

  const dialog = ref(false)
  const processing = ref(false)
  const { formRef, formValid, validate, resetValidation } = useFormValidation()
  const transferType = ref<'external' | 'internal'>('internal')
  const errors = ref<Record<string, string[]>>({})
  const apiError = ref('')
  const step = ref<'form' | 'recap'>('form')

  const form = ref<TransferForm>({
    sender_wallet_id: 0,
    receiver_wallet_id: null,
    amount: 0,
    external: false,
    external_address: '',
    external_name: '',
    reference: '',
    notes: '',
  })

  const { data: walletsData } = useQuery(
    walletsListQuery,
    () => ({ page: 1, perPage: 500 }),
  )
  const wallets = computed<Wallet[]>(() => walletsData.value?.data ?? [])

  const activeWallets = computed(() =>
    wallets.value.filter(w => w.status !== 'frozen'),
  )

  const selectedWallet = computed(() =>
    wallets.value.find(w => w.id === form.value.sender_wallet_id),
  )

  const receiverWallet = computed(() =>
    wallets.value.find(w => w.id === form.value.receiver_wallet_id),
  )

  const currencySymbol = computed(() => {
    const currency = selectedWallet.value?.currency
    if (currency === 'EUR') return '€'
    if (currency === 'USD') return '$'
    return currency ?? '$'
  })

  const senderWalletItems = computed(() =>
    wallets.value.map(w => ({
      ...w,
      disabled: w.status === 'frozen',
    })),
  )

  const receiverWalletOptions = computed(() =>
    wallets.value
      .filter(w =>
        w.id !== form.value.sender_wallet_id
        && (!selectedWallet.value || w.currency === selectedWallet.value.currency),
      )
      .map(w => ({
        ...w,
        disabled: w.status === 'frozen',
      })),
  )

  const APPROVAL_THRESHOLD = 10_000

  const exceedsThreshold = computed(() =>
    form.value.amount > APPROVAL_THRESHOLD,
  )

  // --- Validation rules ---
  const requiredRule = (v: unknown) => !!v || t('validation.required')
  const positiveAmountRule = (v: number) => v > 0 || t('validation.positiveAmount')
  function insufficientFundsRule (v: number) {
    if (!selectedWallet.value) return true
    return v <= Number(selectedWallet.value.available_balance) || t('validation.insufficientFunds')
  }
  const amountRules = [requiredRule, positiveAmountRule, insufficientFundsRule]
  const referenceRules = [requiredRule]

  watch(
    () => props.modelValue,
    val => {
      dialog.value = val
      if (val) {
        resetForm()
      }
    },
    { immediate: true },
  )

  watch(dialog, val => {
    emit('update:modelValue', val)
  })

  watch(transferType, type => {
    form.value.external = type === 'external'
    if (type === 'external') {
      form.value.receiver_wallet_id = null
    } else {
      form.value.external_address = ''
      form.value.external_name = ''
    }
  })

  // When sender wallet changes, clear receiver if same wallet or currency mismatch
  watch(
    () => form.value.sender_wallet_id,
    newSenderId => {
      const receiverId = form.value.receiver_wallet_id
      if (!receiverId) return

      if (receiverId === newSenderId) {
        form.value.receiver_wallet_id = null
        return
      }

      const receiverWallet = wallets.value.find(w => w.id === receiverId)
      if (receiverWallet && selectedWallet.value && receiverWallet.currency !== selectedWallet.value.currency) {
        form.value.receiver_wallet_id = null
      }
    },
  )

  function resetForm () {
    errors.value = {}
    apiError.value = ''
    step.value = 'form'
    transferType.value = 'internal'
    const defaultWallet = activeWallets.value[0]
    form.value = {
      sender_wallet_id: defaultWallet?.id ?? 0,
      receiver_wallet_id: null,
      amount: 0,
      external: false,
      external_address: '',
      external_name: '',
      reference: '',
      notes: '',
    }
    resetValidation()
  }

  const queryCache = useQueryCache()

  async function reviewTransfer () {
    const valid = await validate()
    if (!valid) return
    step.value = 'recap'
  }

  function goBack () {
    step.value = 'form'
    apiError.value = ''
  }

  async function confirmTransfer () {
    processing.value = true
    errors.value = {}
    apiError.value = ''
    try {
      await initiateTransfer(form.value)
      await queryCache.invalidateQueries({ key: WALLET_QUERY_KEYS.root })
      emit('saved')
      dialog.value = false
    } catch (error: unknown) {
      if (isApiError(error, 422)) {
        errors.value = getValidationErrors(error)
        step.value = 'form'
      } else if (isApiError(error, 403)) {
        apiError.value = t('transfers.errors.forbidden')
      } else {
        apiError.value = t('transfers.errors.generic')
      }
    } finally {
      processing.value = false
    }
  }
</script>

<template>
  <v-dialog v-model="dialog" data-testid="transfer-dialog" max-width="520">
    <v-card rounded="lg">
      <v-card-title class="pa-4 font-weight-bold d-flex align-center justify-space-between">
        {{ step === 'recap' ? $t('transfers.confirmTitle') : $t('transfers.title') }}
        <v-btn
          data-testid="transfer-close-btn"
          density="comfortable"
          icon="mdi-close"
          size="small"
          variant="text"
          @click="dialog = false"
        />
      </v-card-title>
      <v-divider />

      <v-card-text class="pa-4">
        <v-alert
          v-if="apiError"
          class="mb-4"
          color="error"
          data-testid="transfer-api-error"
          density="compact"
          type="error"
          variant="tonal"
        >
          {{ apiError }}
        </v-alert>

        <!-- ── Step 1: Form ── -->
        <v-form v-show="step === 'form'" ref="formRef" v-model="formValid" @submit.prevent="reviewTransfer">
          <!-- Transfer Type -->
          <div class="text-overline font-weight-bold text-grey-darken-1 mb-2">
            {{ $t('transfers.transferType') }}
          </div>
          <v-btn-toggle
            v-model="transferType"
            class="mb-4"
            color="primary"
            data-testid="transfer-type-toggle"
            density="comfortable"
            mandatory
            rounded="lg"
            variant="outlined"
          >
            <v-btn data-testid="transfer-type-internal" value="internal">
              {{ $t('transfers.internal') }}
            </v-btn>
            <v-btn data-testid="transfer-type-external" value="external">
              {{ $t('transfers.external') }}
            </v-btn>
          </v-btn-toggle>

          <!-- From Wallet -->
          <div class="text-overline font-weight-bold text-grey-darken-1 mb-2">
            {{ $t('transfers.fromWallet') }}
          </div>
          <v-select
            v-model="form.sender_wallet_id"
            data-testid="transfer-sender-wallet"
            density="comfortable"
            :error-messages="errors.sender_wallet_id"
            hide-details="auto"
            item-value="id"
            :items="senderWalletItems"
            :rules="[requiredRule]"
            variant="outlined"
          >
            <template #item="{ item, props: itemProps }">
              <v-list-item v-bind="itemProps" :disabled="item.raw.disabled">
                <template #title>
                  <div class="d-flex align-center justify-space-between w-100">
                    <span :class="{ 'text-grey': item.raw.disabled }">
                      {{ item.raw.name }}
                      <v-chip
                        v-if="item.raw.disabled"
                        class="ml-2"
                        color="error"
                        density="compact"
                        size="x-small"
                      >🔒 Frozen</v-chip>
                    </span>
                    <span v-if="!item.raw.disabled" class="text-grey text-body-2">
                      {{ formatCurrency(Number(item.raw.available_balance), item.raw.currency) }}
                    </span>
                  </div>
                </template>
              </v-list-item>
            </template>
            <template #selection="{ item }">
              {{ item.raw.name }} ({{ formatCurrency(Number(item.raw.available_balance), item.raw.currency) }})
            </template>
          </v-select>

          <!-- To Destination (External) -->
          <template v-if="transferType === 'external'">
            <div class="text-overline font-weight-bold text-grey-darken-1 mb-2 mt-4">
              {{ $t('transfers.toExternal') }}
            </div>
            <v-text-field
              v-model="form.external_name"
              class="mb-1"
              data-testid="transfer-external-name"
              density="comfortable"
              :error-messages="errors.external_name"
              hide-details="auto"
              :label="$t('transfers.recipientName')"
              :placeholder="$t('transfers.recipientNamePlaceholder')"
              :rules="[requiredRule]"
              variant="outlined"
            />
            <v-text-field
              v-model="form.external_address"
              class="mt-2"
              data-testid="transfer-external-address"
              density="comfortable"
              :error-messages="errors.external_address"
              hide-details="auto"
              :label="$t('transfers.externalAddress')"
              :placeholder="$t('transfers.externalAddressPlaceholder')"
              :rules="[requiredRule]"
              variant="outlined"
            />
          </template>

          <!-- To Wallet (Internal) -->
          <template v-if="transferType === 'internal'">
            <div class="text-overline font-weight-bold text-grey-darken-1 mb-2 mt-4">
              {{ $t('transfers.toWallet') }}
            </div>
            <v-select
              v-model="form.receiver_wallet_id"
              data-testid="transfer-receiver-wallet"
              density="comfortable"
              :error-messages="errors.receiver_wallet_id"
              hide-details="auto"
              item-value="id"
              :items="receiverWalletOptions"
              :rules="[requiredRule]"
              variant="outlined"
            >
              <template #item="{ item, props: itemProps }">
                <v-list-item v-bind="itemProps" :disabled="item.raw.disabled">
                  <template #title>
                    <div class="d-flex align-center justify-space-between w-100">
                      <span :class="{ 'text-grey': item.raw.disabled }">
                        {{ item.raw.name }}
                        <v-chip
                          v-if="item.raw.disabled"
                          class="ml-2"
                          color="error"
                          density="compact"
                          size="x-small"
                        >🔒 Frozen</v-chip>
                      </span>
                      <span v-if="!item.raw.disabled" class="text-grey text-body-2">
                        {{ formatCurrency(Number(item.raw.available_balance), item.raw.currency) }}
                      </span>
                    </div>
                  </template>
                </v-list-item>
              </template>
              <template #selection="{ item }">
                {{ item.raw.name }} ({{ formatCurrency(Number(item.raw.available_balance), item.raw.currency) }})
              </template>
            </v-select>
          </template>

          <!-- Amount -->
          <div class="text-overline font-weight-bold text-grey-darken-1 mb-2 mt-4">
            {{ $t('transfers.amount') }}
          </div>
          <v-text-field
            v-model.number="form.amount"
            data-testid="transfer-amount"
            density="comfortable"
            :error-messages="errors.amount"
            hide-details="auto"
            min="0.01"
            :prefix="currencySymbol"
            :rules="amountRules"
            step="0.01"
            type="number"
            variant="outlined"
          />

          <v-alert
            v-if="exceedsThreshold"
            class="mt-3"
            :color="bypassesThreshold ? 'info' : 'warning'"
            data-testid="transfer-threshold-warning"
            density="compact"
            :type="bypassesThreshold ? 'info' : 'warning'"
            variant="tonal"
          >
            {{ bypassesThreshold
              ? $t('transfers.thresholdAutoApproved', { amount: formatCurrency(APPROVAL_THRESHOLD, selectedWallet?.currency ?? 'USD') })
              : $t('transfers.thresholdWarning', { amount: formatCurrency(APPROVAL_THRESHOLD, selectedWallet?.currency ?? 'USD') })
            }}
          </v-alert>

          <!-- Reference -->
          <div class="text-overline font-weight-bold text-grey-darken-1 mb-2 mt-4">
            {{ $t('transfers.reference') }}
          </div>
          <v-text-field
            v-model="form.reference"
            data-testid="transfer-reference"
            density="comfortable"
            :error-messages="errors.reference"
            hide-details="auto"
            :placeholder="$t('transfers.referencePlaceholder')"
            :rules="referenceRules"
            variant="outlined"
          />

          <!-- Notes (above threshold only) -->
          <template v-if="exceedsThreshold">
            <div class="text-overline font-weight-bold text-grey-darken-1 mb-2 mt-4">
              {{ $t('transfers.notes') }}
            </div>
            <v-textarea
              v-model="form.notes"
              data-testid="transfer-notes"
              density="comfortable"
              :error-messages="errors.notes"
              hide-details="auto"
              :placeholder="$t('transfers.notesPlaceholder')"
              rows="2"
              variant="outlined"
            />
          </template>
        </v-form>

        <!-- ── Step 2: Recap ── -->
        <div v-if="step === 'recap'" data-testid="transfer-recap">
          <v-list class="pa-0" lines="two">
            <!-- Type -->
            <v-list-item class="px-0">
              <template #prepend>
                <v-icon class="me-3" color="grey-darken-1" icon="mdi-swap-horizontal" />
              </template>
              <v-list-item-title class="text-caption text-grey-darken-1">
                {{ $t('transfers.transferType') }}
              </v-list-item-title>
              <div class="text-body-2 font-weight-medium text-grey-darken-3 text-capitalize">
                {{ transferType }}
              </div>
            </v-list-item>

            <v-divider />

            <!-- From -->
            <v-list-item class="px-0">
              <template #prepend>
                <v-icon class="me-3" color="red-darken-1" icon="mdi-arrow-up-circle" />
              </template>
              <v-list-item-title class="text-caption text-grey-darken-1">
                {{ $t('transfers.fromWallet') }}
              </v-list-item-title>
              <div class="text-body-2 font-weight-medium text-grey-darken-3">
                {{ selectedWallet?.name }}
              </div>
            </v-list-item>

            <v-divider />

            <!-- To -->
            <v-list-item class="px-0">
              <template #prepend>
                <v-icon class="me-3" color="green-darken-1" icon="mdi-arrow-down-circle" />
              </template>
              <v-list-item-title class="text-caption text-grey-darken-1">
                {{ transferType === 'internal' ? $t('transfers.toWallet') : $t('transfers.toExternal') }}
              </v-list-item-title>
              <div class="text-body-2 font-weight-medium text-grey-darken-3">
                {{ transferType === 'internal' ? receiverWallet?.name : form.external_name }}
              </div>
              <div
                v-if="transferType === 'external' && form.external_address"
                class="text-caption text-grey-darken-2 mt-1"
                style="font-family: monospace"
              >
                {{ form.external_address }}
              </div>
            </v-list-item>

            <v-divider />

            <!-- Amount -->
            <v-list-item class="px-0">
              <template #prepend>
                <v-icon class="me-3" color="grey-darken-1" icon="mdi-currency-usd" />
              </template>
              <v-list-item-title class="text-caption text-grey-darken-1">
                {{ $t('transfers.amount') }}
              </v-list-item-title>
              <div class="text-h6 font-weight-black text-primary">
                {{ formatCurrency(form.amount, selectedWallet?.currency ?? 'USD') }}
              </div>
            </v-list-item>

            <v-divider />

            <!-- Reference -->
            <v-list-item class="px-0">
              <template #prepend>
                <v-icon class="me-3" color="grey-darken-1" icon="mdi-tag" />
              </template>
              <v-list-item-title class="text-caption text-grey-darken-1">
                {{ $t('transfers.reference') }}
              </v-list-item-title>
              <div class="text-body-2 font-weight-medium text-grey-darken-3">
                {{ form.reference }}
              </div>
            </v-list-item>

            <!-- Notes -->
            <template v-if="form.notes">
              <v-divider />
              <v-list-item class="px-0">
                <template #prepend>
                  <v-icon class="me-3" color="grey-darken-1" icon="mdi-note-text" />
                </template>
                <v-list-item-title class="text-caption text-grey-darken-1">
                  {{ $t('transfers.notes') }}
                </v-list-item-title>
                <div class="text-body-2 font-weight-medium text-grey-darken-3">
                  {{ form.notes }}
                </div>
              </v-list-item>
            </template>
          </v-list>

          <v-alert
            v-if="exceedsThreshold"
            class="mt-4"
            :color="bypassesThreshold ? 'info' : 'warning'"
            density="compact"
            :type="bypassesThreshold ? 'info' : 'warning'"
            variant="tonal"
          >
            {{ bypassesThreshold
              ? $t('transfers.thresholdAutoApproved', { amount: formatCurrency(APPROVAL_THRESHOLD, selectedWallet?.currency ?? 'USD') })
              : $t('transfers.thresholdWarning', { amount: formatCurrency(APPROVAL_THRESHOLD, selectedWallet?.currency ?? 'USD') })
            }}
          </v-alert>
        </div>
      </v-card-text>

      <v-divider />
      <v-card-actions class="pa-4">
        <v-spacer />
        <!-- Form step actions -->
        <template v-if="step === 'form'">
          <v-btn
            color="grey-darken-1"
            data-testid="transfer-cancel-btn"
            variant="text"
            @click="dialog = false"
          >
            {{ $t('common.cancel') }}
          </v-btn>
          <v-btn
            color="primary"
            data-testid="transfer-submit-btn"
            :disabled="!formValid"
            variant="flat"
            @click="reviewTransfer"
          >
            {{ $t('transfers.submit') }}
          </v-btn>
        </template>
        <!-- Recap step actions -->
        <template v-else>
          <v-btn
            color="grey-darken-1"
            data-testid="transfer-back-btn"
            variant="text"
            @click="goBack"
          >
            {{ $t('common.goBack') }}
          </v-btn>
          <v-btn
            color="primary"
            data-testid="transfer-confirm-btn"
            :loading="processing"
            variant="flat"
            @click="confirmTransfer"
          >
            {{ $t('transfers.initiateTransfer') }}
          </v-btn>
        </template>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
