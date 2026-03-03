<script lang="ts" setup>
import type { Wallet as WalletType } from '@/api/wallets'
import type { ValidationErrors } from '@/utils/errors'
import { Snowflake, Trash2, Wallet } from 'lucide-vue-next'
import { reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import RecentTransactions from '@/components/features/RecentTransactions.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import { useConfirmDialog } from '@/composables/useConfirmDialog'
import { useAuthStore } from '@/stores/auth'
import { useWalletById, useWalletStore } from '@/stores/wallet'
import { getValidationErrors, isApiError } from '@/utils/errors'
import { formatCurrency } from '@/utils/formatters'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const walletStore = useWalletStore()
const authStore = useAuthStore()
const processing = ref(false)
const errors = ref<ValidationErrors>({})
const snackbar = ref({ show: false, text: '', color: 'error' })
const { confirmDialog, openConfirmDialog, executeConfirm } = useConfirmDialog()

const walletId = String((route.params as Record<string, string>).id)

const form = reactive({
  name: '',
  currency: '',
})

const currencies = [
  { title: 'US Dollar (USD)', value: 'USD' },
  { title: 'Euro (EUR)', value: 'EUR' },
]

const { data: queryData, isPending: loading } = useWalletById(walletId)

const wallet = ref<WalletType | null>(null)

watch(queryData, newData => {
  if (newData?.data) {
    wallet.value = newData.data
    form.name = newData.data.name
    form.currency = newData.data.currency
  }
})

function copyAddress() {
  if (wallet.value?.address) {
    navigator.clipboard.writeText(wallet.value.address)
  }
}

async function submit() {
  processing.value = true
  errors.value = {}

  try {
    await walletStore.updateWallet({ id: walletId, form })
    snackbar.value = { show: true, text: t('common.saved'), color: 'success' }
  } catch (error: unknown) {
    if (isApiError(error, 422)) {
      errors.value = getValidationErrors(error)
    }
  } finally {
    processing.value = false
  }
}

function handleToggleStatus() {
  if (!wallet.value) return
  const current = wallet.value
  const isFreezing = current.status === 'active'
  openConfirmDialog({
    title: isFreezing ? t('wallets.freezeWallet') : t('wallets.unfreezeWallet'),
    message: isFreezing
      ? t('wallets.confirmFreeze', { name: current.name })
      : t('wallets.confirmUnfreeze', { name: current.name }),
    requiresPin: false,
    onConfirm: async () => {
      try {
        await walletStore.toggleFreeze(current.id)
      } catch (error) {
        console.error('Error toggling status:', error)
      }
    },
  })
}

function handleDelete() {
  if (!wallet.value) return
  const current = wallet.value
  openConfirmDialog({
    title: t('wallets.deleteWallet'),
    message: t('wallets.confirmDeleteEdit', { name: current.name }),
    requiresPin: true,
    onConfirm: async () => {
      try {
        await walletStore.deleteWallet(current.id)
      } catch (error) {
        console.error('Error deleting wallet:', error)
      }
    },
    onSuccess: () => router.push('/wallets/'),
  })
}
</script>

<template>
  <div class="mb-8">
    <v-btn class="text-none mb-4 px-0" color="primary" prepend-icon="mdi-arrow-left" to="/wallets/" variant="text">
      {{ $t('wallets.backToWallets') }}
    </v-btn>
    <h1 class="text-h5 font-weight-bold text-grey-darken-2" data-testid="page-heading">{{
      $t('wallets.walletDetails') }}</h1>
  </div>

  <v-card v-if="loading" border class="pa-8 text-center" flat rounded="lg">
    <v-progress-circular color="primary" indeterminate />
  </v-card>

  <template v-else>
    <v-row>
      <!-- Left column: Wallet form + management actions -->
      <v-col cols="12" lg="7" order="2" order-lg="1">
        <v-card border class="pa-8" flat rounded="lg">
          <!-- Admin: editable form -->
          <v-form v-if="authStore.isAdmin" @submit.prevent="submit">
            <div class="d-flex flex-column ga-6">
              <v-text-field v-model="form.name" autofocus color="primary" data-testid="wallet-name-input"
                density="comfortable" :error-messages="errors.name" hide-details="auto"
                :label="$t('wallets.walletName')" :placeholder="$t('wallets.walletNamePlaceholder')" required
                variant="outlined">
                <template #prepend-inner>
                  <v-icon color="grey-darken-1" :icon="Wallet" size="20" />
                </template>
              </v-text-field>

              <div v-if="wallet" class="pa-4 bg-grey-lighten-4 rounded-lg border">
                <div class="text-caption text-grey-darken-1 mb-1">
                  {{ $t('wallets.walletAddress') }}
                </div>
                <div class="d-flex align-center ga-2">
                  <v-icon color="grey-darken-1" icon="mdi-link-variant" size="18" />
                  <span class="text-body-2 font-weight-medium text-grey-darken-3"
                    style="font-family: monospace; word-break: break-all">
                    {{ wallet.address }}
                  </span>
                  <v-btn color="grey-darken-1" density="comfortable" icon="mdi-content-copy" size="x-small"
                    variant="text" @click="copyAddress" />
                </div>
              </div>

              <v-select v-model="form.currency" color="primary" data-testid="wallet-currency-select"
                density="comfortable" :error-messages="errors.currency" hide-details="auto" :items="currencies"
                :label="$t('wallets.baseCurrency')" required variant="outlined" />

              <div class="d-flex ga-4 mt-4">
                <v-btn class="text-none font-weight-bold px-8" color="primary" data-testid="wallet-save-btn" height="48"
                  :loading="processing" rounded="lg" type="submit">
                  {{ $t('wallets.saveChanges') }}
                </v-btn>
                <v-btn class="text-none font-weight-bold px-8" color="grey-darken-1" :disabled="processing" height="48"
                  rounded="lg" to="/wallets/" variant="outlined">
                  {{ $t('common.cancel') }}
                </v-btn>
              </div>

              <v-divider class="my-4" />

              <div class="pa-6 bg-grey-lighten-4 rounded-lg border">
                <div class="text-subtitle-1 font-weight-bold text-grey-darken-3 mb-1">
                  {{ $t('wallets.managementActions') }}
                </div>
                <p class="text-caption text-grey-darken-1 mb-6">
                  {{ $t('wallets.managementDescription') }}
                </p>

                <div class="d-flex flex-column flex-sm-row ga-3">
                  <v-btn class="flex-grow-1 text-none" :color="wallet?.status === 'active'
                    ? 'warning'
                    : 'success'
                    " data-testid="freeze-btn" :disabled="processing" :prepend-icon="wallet?.status === 'active'
                      ? Snowflake
                      : 'mdi-fire'
                      " rounded="lg" variant="flat" @click="handleToggleStatus">
                    {{
                      wallet?.status === 'active'
                        ? $t('wallets.freezeWallet')
                        : $t('wallets.unfreezeWallet')
                    }}
                  </v-btn>

                  <v-btn class="flex-grow-1 text-none" color="error" data-testid="delete-wallet-btn"
                    :disabled="!wallet?.can_delete || processing" :prepend-icon="Trash2" rounded="lg" variant="tonal"
                    @click="handleDelete">
                    {{ $t('wallets.deleteWallet') }}
                  </v-btn>
                </div>

                <v-alert v-if="wallet && !wallet.can_delete" class="mt-4" color="info" density="compact"
                  icon="mdi-information" rounded="lg" variant="tonal">
                  <div class="text-caption">
                    {{ $t('wallets.cannotDeleteInfo') }}
                  </div>
                </v-alert>
              </div>
            </div>
          </v-form>

          <!-- Non-admin: read-only view -->
          <div v-else class="d-flex flex-column ga-6">
            <div>
              <div class="text-caption text-grey-darken-1">{{ $t('wallets.walletName') }}</div>
              <div class="text-body-1 font-weight-medium">{{ wallet?.name }}</div>
            </div>

            <div v-if="wallet" class="pa-4 bg-grey-lighten-4 rounded-lg border">
              <div class="text-caption text-grey-darken-1 mb-1">
                {{ $t('wallets.walletAddress') }}
              </div>
              <div class="d-flex align-center ga-2">
                <v-icon color="grey-darken-1" icon="mdi-link-variant" size="18" />
                <span class="text-body-2 font-weight-medium text-grey-darken-3"
                  style="font-family: monospace; word-break: break-all">
                  {{ wallet.address }}
                </span>
                <v-btn color="grey-darken-1" density="comfortable" icon="mdi-content-copy" size="x-small" variant="text"
                  @click="copyAddress" />
              </div>
            </div>

            <div>
              <div class="text-caption text-grey-darken-1">{{ $t('wallets.baseCurrency') }}</div>
              <div class="text-body-1 font-weight-medium">{{ wallet?.currency }}</div>
            </div>
          </div>
        </v-card>
      </v-col>

      <!-- Right column: Recent transactions -->
      <v-col cols="12" lg="5" order="1" order-lg="2">
        <v-card v-if="wallet" border class="pa-4 mb-4" data-testid="wallet-balance-card" flat rounded="lg">
          <v-row dense>
            <v-col cols="6">
              <div class="text-caption text-grey-darken-1">{{ $t('wallets.tableHeaders.balance') }}</div>
              <div class="text-h6 font-weight-black text-grey-darken-3">
                {{ formatCurrency(wallet.balance, wallet.currency) }}
              </div>
            </v-col>
            <v-col cols="6">
              <div class="text-caption text-grey-darken-1">{{ $t('wallets.tableHeaders.availableBalance')
                }}</div>
              <div class="text-h6 font-weight-black"
                :class="wallet.available_balance !== wallet.balance ? 'text-amber-darken-2' : 'text-grey-darken-3'">
                {{ formatCurrency(wallet.available_balance, wallet.currency) }}
              </div>
            </v-col>
          </v-row>
        </v-card>

        <RecentTransactions :filter-params="{ has_wallet_id: Number(walletId) }"
          :title="$t('wallets.recentTransactions')" :view-all-query="{ has_wallet_id: walletId }" />
      </v-col>
    </v-row>
  </template>

  <ConfirmDialog v-model="confirmDialog.show" :message="confirmDialog.message" :processing="confirmDialog.processing"
    :requires-pin="confirmDialog.requiresPin" :title="confirmDialog.title" @confirm="executeConfirm" />

  <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="5000">
    {{ snackbar.text }}
  </v-snackbar>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
