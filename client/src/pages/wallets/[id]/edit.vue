<script lang="ts" setup>
  import type { ValidationErrors, Wallet as WalletType } from '@/types'
  import { Snowflake, Trash2, Wallet } from 'lucide-vue-next'
  import { reactive, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useMutation, useQuery, useQueryCache } from '@pinia/colada'
  import ConfirmDialog from '@/components/ConfirmDialog.vue'
  import { useConfirmDialog } from '@/composables/useConfirmDialog'
  import { deleteWallet as apiDeleteWallet, toggleWalletFreeze, updateWallet } from '@/api/wallets'
  import { walletByIdQuery, WALLET_QUERY_KEYS } from '@/queries/wallets'
  import { getErrorMessage, getValidationErrors, isApiError } from '@/utils/errors'

  const route = useRoute()
  const router = useRouter()
  const queryCache = useQueryCache()
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

  const { data: queryData, isPending: loading } = useQuery(
    walletByIdQuery,
    () => walletId,
  )

  const wallet = ref<WalletType | null>(null)

  watch(queryData, (newData) => {
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

  const { mutateAsync: updateWalletMutation } = useMutation({
    mutation: (data: { name: string, currency: string }) => updateWallet(walletId, data),
    onSettled: () => {
      queryCache.invalidateQueries({ key: WALLET_QUERY_KEYS.root })
    },
  })

  const { mutateAsync: toggleFreezeMutation } = useMutation({
    mutation: (id: number) => toggleWalletFreeze(id),
    onSettled: () => {
      queryCache.invalidateQueries({ key: WALLET_QUERY_KEYS.root })
    },
  })

  const { mutateAsync: deleteWalletMutation } = useMutation({
    mutation: (id: number) => apiDeleteWallet(id),
    onSettled: () => {
      queryCache.invalidateQueries({ key: WALLET_QUERY_KEYS.root })
    },
  })

  async function submit () {
    processing.value = true
    errors.value = {}

    try {
      await updateWalletMutation(form)
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
    const isFreezing = wallet.value.status === 'active'
    openConfirmDialog({
      title: isFreezing ? 'Freeze Wallet' : 'Unfreeze Wallet',
      message: `Are you sure you want to ${isFreezing ? 'freeze' : 'unfreeze'} the wallet "${wallet.value.name}"?`,
      requiresPin: false,
      onConfirm: async () => {
        try {
          await toggleFreezeMutation(wallet.value?.id!)
        } catch (error) {
          console.error('Error toggling status:', error)
        }
      },
    })
  }

  function handleDelete () {
    if (!wallet.value) return
    openConfirmDialog({
      title: 'Delete Wallet',
      message: `Warning: You are about to permanently delete the wallet "${wallet.value.name}". This action cannot be undone. Only empty wallets can be deleted.`,
      requiresPin: true,
      onConfirm: async () => {
        try {
          await deleteWalletMutation(wallet.value?.id!)
          router.push('/wallets/')
        } catch (error: unknown) {
          if (isApiError(error, 403)) {
            snackbar.value = {
              show: true,
              text: getErrorMessage(error, 'You are not authorized to delete this wallet (it might not be empty).'),
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
      Back to Wallets
    </v-btn>
    <h1 class="text-h5 font-weight-bold text-grey-darken-2">Edit Wallet</h1>
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
              label="Wallet Name"
              placeholder="e.g. Savings, Marketing, Operations"
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
                Wallet Address
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
              label="Base Currency"
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
                Save Changes
              </v-btn>
              <v-btn
                class="text-none font-weight-bold px-8"
                color="grey-darken-1"
                height="48"
                rounded="lg"
                to="/wallets/"
                variant="outlined"
              >
                Cancel
              </v-btn>
            </div>

            <v-divider class="my-4" />

            <div class="pa-6 bg-grey-lighten-4 rounded-lg border">
              <div
                class="text-subtitle-1 font-weight-bold text-grey-darken-3 mb-1"
              >
                Management Actions
              </div>
              <p class="text-caption text-grey-darken-1 mb-6">
                Manage the status or permanently delete this
                wallet.
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
                      ? 'Freeze Wallet'
                      : 'Unfreeze Wallet'
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
                  Delete Wallet
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
                  Only wallets with no transaction history can
                  be deleted.
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
