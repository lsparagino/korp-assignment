<script lang="ts" setup>
  import type { AddressBookEntry } from '@/api/address-book'
  import type { TransferForm } from '@/api/transactions'
  import type { Wallet } from '@/api/wallets'
  import { useQuery, useQueryCache } from '@pinia/colada'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { useRouter } from 'vue-router'
  import { fetchCompanyThresholds, fetchUserPreferences } from '@/api/settings'
  import { initiateTransfer } from '@/api/transactions'
  import AddressBookDialog from '@/components/features/AddressBookDialog.vue'
  import IdentityConfirmDialog from '@/components/ui/IdentityConfirmDialog.vue'
  import { useFormValidation } from '@/composables/useFormValidation'
  import { useIdentityConfirm } from '@/composables/useIdentityConfirm'
  import { WALLET_QUERY_KEYS, walletsListQuery } from '@/queries/wallets'
  import { useAuthStore } from '@/stores/auth'
  import { getValidationErrors, isApiError } from '@/utils/errors'
  import { formatCurrency, getCurrencySymbol } from '@/utils/formatters'

  const { t } = useI18n()
  const router = useRouter()
  const authStore = useAuthStore()
  const queryCache = useQueryCache()
  const identity = useIdentityConfirm()

  const bypassesThreshold = computed(() =>
    authStore.user?.role === 'admin' || authStore.user?.role === 'manager',
  )

  const processing = ref(false)
  const { formRef, formValid, validate } = useFormValidation()
  const transferType = ref<'external' | 'internal'>('internal')
  const errors = ref<Record<string, string[]>>({})
  const apiError = ref('')
  const step = ref<'form' | 'recap'>('form')
  const addressBookDialog = ref(false)

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

  const selectedWallet = computed(() =>
    wallets.value.find(w => w.id === form.value.sender_wallet_id),
  )

  const receiverWallet = computed(() =>
    wallets.value.find(w => w.id === form.value.receiver_wallet_id),
  )

  const currencySymbol = computed(() =>
    getCurrencySymbol(selectedWallet.value?.currency ?? 'USD'),
  )

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

  const thresholdsMap = ref<Map<string, number>>(new Map())
  const securityThreshold = ref<number | null>(null)

  async function loadThresholds () {
    try {
      const { data } = await fetchCompanyThresholds()
      const map = new Map<string, number>()
      for (const item of data.data) {
        map.set(item.currency, Number(item.approval_threshold))
      }
      thresholdsMap.value = map
    } catch {
      // If thresholds can't be loaded, no warnings will be shown
    }
  }

  async function loadSecurityThreshold () {
    try {
      const { data } = await fetchUserPreferences()
      securityThreshold.value = data.data.security_threshold === null
        ? null
        : Number(data.data.security_threshold)
    } catch {
      // If preferences can't be loaded, no security check will be applied
    }
  }

  function requiresSecurityVerification (): boolean {
    return securityThreshold.value !== null && form.value.amount > securityThreshold.value
  }

  const currentThreshold = computed(() => {
    const currency = selectedWallet.value?.currency
    if (!currency) return null
    return thresholdsMap.value.get(currency) ?? null
  })

  const exceedsThreshold = computed(() =>
    currentThreshold.value !== null && form.value.amount > currentThreshold.value,
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

  // Form initialises sender_wallet_id to 0 (no wallet) — auto-select the first unfrozen wallet once available
  watch(
    wallets,
    list => {
      if (list.length > 0 && form.value.sender_wallet_id === 0) {
        const defaultWallet = list.find(w => w.status !== 'frozen')
        if (defaultWallet) {
          form.value.sender_wallet_id = defaultWallet.id
        }
      }
    },
    { immediate: true },
  )

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

      const receiver = wallets.value.find(w => w.id === receiverId)
      if (receiver && selectedWallet.value && receiver.currency !== selectedWallet.value.currency) {
        form.value.receiver_wallet_id = null
      }
    },
  )

  onMounted(() => {
    loadThresholds()
    loadSecurityThreshold()
  })

  function onAddressBookSelect (entry: AddressBookEntry) {
    form.value.external_name = entry.name
    form.value.external_address = entry.address
  }

  async function reviewTransfer () {
    const valid = await validate()
    if (!valid) return
    step.value = 'recap'
  }

  function goBack () {
    step.value = 'form'
    apiError.value = ''
  }

  async function executeTransfer (extraFields: Record<string, string> = {}) {
    processing.value = true
    errors.value = {}
    apiError.value = ''
    try {
      await initiateTransfer({ ...form.value, ...extraFields })
      await queryCache.invalidateQueries({ key: WALLET_QUERY_KEYS.root })
      router.push('/transactions/')
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

  function confirmTransfer () {
    if (requiresSecurityVerification()) {
      identity.requireConfirmation(async cred => {
        await executeTransfer(cred)
      }).catch(() => {
        // cancelled or handled by the dialog
      })
    } else {
      executeTransfer()
    }
  }
</script>

<template>
  <div class="mb-8">
    <v-btn
      class="text-none mb-4 px-0"
      color="primary"
      data-testid="transfer-back-link"
      prepend-icon="mdi-arrow-left"
      to="/transactions/"
      variant="text"
    >
      {{ $t('transfers.backToTransactions') }}
    </v-btn>
    <h1 class="text-h5 font-weight-bold text-grey-darken-2">
      {{ step === 'recap' ? $t('transfers.confirmTitle') : $t('transfers.title') }}
    </h1>
  </div>

  <v-card border class="pa-6 pa-sm-8" flat rounded="lg">
    <v-row justify="center">
      <v-col cols="12" lg="5" md="8">
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
            class="mb-4 w-100 d-flex"
            color="primary"
            data-testid="transfer-type-toggle"
            density="comfortable"
            mandatory
            rounded="lg"
            variant="outlined"
          >
            <v-btn class="flex-grow-1" data-testid="transfer-type-internal" value="internal">
              {{ $t('transfers.internal') }}
            </v-btn>
            <v-btn class="flex-grow-1" data-testid="transfer-type-external" value="external">
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
            <div class="d-flex align-center justify-space-between mb-2 mt-4">
              <div class="text-overline font-weight-bold text-grey-darken-1">
                {{ $t('transfers.toExternal') }}
              </div>
              <a
                class="text-caption text-primary text-decoration-none cursor-pointer"
                data-testid="transfer-address-book-link"
                @click="addressBookDialog = true"
              >
                {{ $t('addressBook.title') }}
              </a>
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
              ? $t('transfers.thresholdAutoApproved', { amount: formatCurrency(currentThreshold ?? 0, selectedWallet?.currency ?? 'USD') })
              : $t('transfers.thresholdWarning', { amount: formatCurrency(currentThreshold ?? 0, selectedWallet?.currency ?? 'USD') })
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

          <!-- Form Actions -->
          <div class="d-flex ga-4 mt-6">
            <v-btn
              class="text-none font-weight-bold px-8"
              color="primary"
              data-testid="transfer-submit-btn"
              :disabled="!formValid"
              height="48"
              rounded="lg"
              @click="reviewTransfer"
            >
              {{ $t('transfers.submit') }}
            </v-btn>
            <v-btn
              class="text-none font-weight-bold px-8"
              color="grey-darken-1"
              data-testid="transfer-cancel-btn"
              height="48"
              rounded="lg"
              to="/transactions/"
              variant="outlined"
            >
              {{ $t('common.cancel') }}
            </v-btn>
          </div>
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
              ? $t('transfers.thresholdAutoApproved', { amount: formatCurrency(currentThreshold ?? 0, selectedWallet?.currency ?? 'USD') })
              : $t('transfers.thresholdWarning', { amount: formatCurrency(currentThreshold ?? 0, selectedWallet?.currency ?? 'USD') })
            }}
          </v-alert>

          <!-- Recap Actions -->
          <div class="d-flex ga-4 mt-6">
            <v-btn
              class="text-none font-weight-bold px-8"
              color="primary"
              data-testid="transfer-confirm-btn"
              height="48"
              :loading="processing"
              rounded="lg"
              @click="confirmTransfer"
            >
              {{ $t('transfers.initiateTransfer') }}
            </v-btn>
            <v-btn
              class="text-none font-weight-bold px-8"
              color="grey-darken-1"
              data-testid="transfer-back-btn"
              height="48"
              rounded="lg"
              variant="outlined"
              @click="goBack"
            >
              {{ $t('common.goBack') }}
            </v-btn>
          </div>
        </div>
      </v-col>
    </v-row>
  </v-card>

  <!-- Address Book Dialog -->
  <AddressBookDialog
    v-model="addressBookDialog"
    @select="onAddressBookSelect"
  />

  <!-- Identity Verification Dialog -->
  <IdentityConfirmDialog
    v-model="identity.showDialog.value"
    v-model:credential="identity.credential.value"
    :error="identity.error.value"
    :has-two-factor="identity.hasTwoFactor.value"
    :is-submitting="identity.isSubmitting.value"
    @cancel="identity.cancel()"
    @confirm="identity.confirm()"
  />
</template>

<route lang="yaml">
meta:
    layout: App
</route>
