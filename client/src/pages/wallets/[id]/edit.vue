<script lang="ts" setup>
  import type { Wallet as WalletType } from '@/api/wallets'
  import type { ValidationErrors } from '@/utils/errors'
  import { Snowflake, Trash2, Wallet } from 'lucide-vue-next'
  import { reactive, ref, watch } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { useRoute, useRouter } from 'vue-router'
  import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
  import { useConfirmDialog } from '@/composables/useConfirmDialog'
  import { useWalletStore } from '@/stores/wallet'
  import { getErrorMessage, getValidationErrors, isApiError } from '@/utils/errors'

  const { t } = useI18n()
  const route = useRoute()
  const router = useRouter()
  const walletStore = useWalletStore()
  const processing = ref(false)
  const errors = ref<ValidationErrors>({})
  const snackbar = ref({ show: false, text: '', color: 'error' })
  const { confirmDialog, openConfirmDialog } = useConfirmDialog()

  const walletId = String((route.params as Record<string, string>).id)

  const form = reactive({
    name: '',
    currency: '',
  })

  const currencies = [
    { title: 'US Dollar (USD)', value: 'USD' },
    { title: 'Euro (EUR)', value: 'EUR' },
  ]

  const { data: queryData, isPending: loading } = walletStore.useWalletById(walletId)

  const wallet = ref<WalletType | null>(null)

  watch(queryData, newData => {
    if (newData?.wallet) {
      wallet.value = newData.wallet
      form.name = newData.wallet.name
      form.currency = newData.wallet.currency
    }
  })

  function copyAddress () {
    if (wallet.value?.address) {
      navigator.clipboard.writeText(wallet.value.address)
    }
  }

  async function submit () {
    processing.value = true
    errors.value = {}

    try {
      await walletStore.updateWallet({ id: walletId, form })
      router.push('/wallets/')
    } catch (error: unknown) {
      if (isApiError(error, 422)) {
        errors.value = getValidationErrors(error)
      }
    } finally {
      processing.value = false
    }
  }

  function handleToggleStatus () {
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

  function handleDelete () {
    if (!wallet.value) return
    const current = wallet.value
    openConfirmDialog({
      title: t('wallets.deleteWallet'),
      message: t('wallets.confirmDeleteEdit', { name: current.name }),
      requiresPin: true,
      onConfirm: async () => {
        try {
          await walletStore.deleteWallet(current.id)
          router.push('/wallets/')
        } catch (error: unknown) {
          if (isApiError(error, 403)) {
            snackbar.value = {
              show: true,
              text: getErrorMessage(error, t('wallets.deleteUnauthorizedEdit')),
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
  <div class="mb-8">
    <v-btn
      class="text-none mb-4 px-0"
      color="primary"
      prepend-icon="mdi-arrow-left"
      to="/wallets/"
      variant="text"
    >
      {{ $t('wallets.backToWallets') }}
    </v-btn>
    <h1 class="text-h5 font-weight-bold text-grey-darken-2">{{ $t('wallets.editWallet') }}</h1>
  </div>

  <v-card
    v-if="loading"
    border
    class="pa-8 text-center"
    flat
    rounded="lg"
  >
    <v-progress-circular color="primary" indeterminate />
  </v-card>

  <v-card
    v-else
    border
    class="pa-8"
    flat
    rounded="lg"
  >
    <v-form @submit.prevent="submit">
      <v-row justify="center">
        <v-col cols="12" lg="5" md="8">
          <div class="d-flex flex-column ga-6">
            <v-text-field
              v-model="form.name"
              autofocus
              color="primary"
              density="comfortable"
              :error-messages="errors.name"
              hide-details="auto"
              :label="$t('wallets.walletName')"
              :placeholder="$t('wallets.walletNamePlaceholder')"
              required
              variant="outlined"
            >
              <template #prepend-inner>
                <v-icon
                  color="grey-darken-1"
                  :icon="Wallet"
                  size="20"
                />
              </template>
            </v-text-field>

            <div v-if="wallet" class="pa-4 bg-grey-lighten-4 rounded-lg border">
              <div class="text-caption text-grey-darken-1 mb-1">
                {{ $t('wallets.walletAddress') }}
              </div>
              <div class="d-flex align-center ga-2">
                <v-icon
                  color="grey-darken-1"
                  icon="mdi-link-variant"
                  size="18"
                />
                <span
                  class="text-body-2 font-weight-medium text-grey-darken-3"
                  style="font-family: monospace; word-break: break-all"
                >
                  {{ wallet.address }}
                </span>
                <v-btn
                  color="grey-darken-1"
                  density="comfortable"
                  icon="mdi-content-copy"
                  size="x-small"
                  variant="text"
                  @click="copyAddress"
                />
              </div>
            </div>

            <v-select
              v-model="form.currency"
              color="primary"
              density="comfortable"
              :error-messages="errors.currency"
              hide-details="auto"
              :items="currencies"
              :label="$t('wallets.baseCurrency')"
              required
              variant="outlined"
            />

            <div class="d-flex ga-4 mt-4">
              <v-btn
                class="text-none font-weight-bold px-8"
                color="primary"
                height="48"
                :loading="processing"
                rounded="lg"
                type="submit"
              >
                {{ $t('wallets.saveChanges') }}
              </v-btn>
              <v-btn
                class="text-none font-weight-bold px-8"
                color="grey-darken-1"
                height="48"
                rounded="lg"
                to="/wallets/"
                variant="outlined"
              >
                {{ $t('common.cancel') }}
              </v-btn>
            </div>

            <v-divider class="my-4" />

            <div class="pa-6 bg-grey-lighten-4 rounded-lg border">
              <div
                class="text-subtitle-1 font-weight-bold text-grey-darken-3 mb-1"
              >
                {{ $t('wallets.managementActions') }}
              </div>
              <p class="text-caption text-grey-darken-1 mb-6">
                {{ $t('wallets.managementDescription') }}
              </p>

              <div class="d-flex flex-column flex-sm-row ga-3">
                <v-btn
                  class="flex-grow-1 text-none"
                  :color="
                    wallet?.status === 'active'
                      ? 'warning'
                      : 'success'
                  "
                  :prepend-icon="
                    wallet?.status === 'active'
                      ? Snowflake
                      : 'mdi-fire'
                  "
                  rounded="lg"
                  variant="flat"
                  @click="handleToggleStatus"
                >
                  {{
                    wallet?.status === 'active'
                      ? $t('wallets.freezeWallet')
                      : $t('wallets.unfreezeWallet')
                  }}
                </v-btn>

                <v-btn
                  class="flex-grow-1 text-none"
                  color="error"
                  :disabled="!wallet?.can_delete"
                  :prepend-icon="Trash2"
                  rounded="lg"
                  variant="tonal"
                  @click="handleDelete"
                >
                  {{ $t('wallets.deleteWallet') }}
                </v-btn>
              </div>

              <v-alert
                v-if="wallet && !wallet.can_delete"
                class="mt-4"
                color="info"
                density="compact"
                icon="mdi-information"
                rounded="lg"
                variant="tonal"
              >
                <div class="text-caption">
                  {{ $t('wallets.cannotDeleteInfo') }}
                </div>
              </v-alert>
            </div>
          </div>
        </v-col>
      </v-row>
    </v-form>
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
