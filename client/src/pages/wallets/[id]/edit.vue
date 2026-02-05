<script lang="ts" setup>
  import { Snowflake, Trash2, Wallet } from 'lucide-vue-next'
  import { onMounted, reactive, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import ConfirmDialog from '@/components/ConfirmDialog.vue'
  import api from '@/plugins/api'

  interface WalletModel {
    id: number
    name: string
    currency: string
    balance: number
    status: string
  }

  const route = useRoute()
  const router = useRouter()
  const processing = ref(false)
  const loading = ref(true)
  const errors = ref<Record<string, string[]>>({})
  const wallet = ref<WalletModel | null>(null)

  const form = reactive({
    name: '',
    currency: '',
  })

  // Dialog state
  const confirmDialog = ref({
    show: false,
    title: '',
    message: '',
    requiresPin: false,
    onConfirm: () => {},
  })

  const currencies = [
    { title: 'US Dollar (USD)', value: 'USD' },
    { title: 'Euro (EUR)', value: 'EUR' },
  ]

  async function fetchWallet () {
    try {
      const response = await api.get(`/wallets/${(route.params as any).id}`)
      wallet.value = response.data.data
      form.name = response.data.data.name
      form.currency = response.data.data.currency
    } catch (error) {
      console.error('Failed to fetch wallet:', error)
      router.push('/wallets/')
    } finally {
      loading.value = false
    }
  }

  async function submit () {
    processing.value = true
    errors.value = {}

    try {
      await api.put(`/wallets/${(route.params as any).id}`, form)
      router.push('/wallets/')
    } catch (error: any) {
      if (error.response?.status === 422) {
        errors.value = error.response.data.errors
      }
    } finally {
      processing.value = false
    }
  }

  function handleToggleStatus () {
    if (!wallet.value) return
    const isFreezing = wallet.value.status === 'active'
    confirmDialog.value = {
      show: true,
      title: isFreezing ? 'Freeze Wallet' : 'Unfreeze Wallet',
      message: `Are you sure you want to ${isFreezing ? 'freeze' : 'unfreeze'} the wallet "${wallet.value.name}"?`,
      requiresPin: false,
      onConfirm: async () => {
        try {
          await api.patch(`/wallets/${wallet.value?.id}/toggle-freeze`)
          fetchWallet()
        } catch (error) {
          console.error('Error toggling status:', error)
        }
      },
    }
  }

  function handleDelete () {
    if (!wallet.value) return
    confirmDialog.value = {
      show: true,
      title: 'Delete Wallet',
      message: `Warning: You are about to permanently delete the wallet "${wallet.value.name}". This action cannot be undone. Only empty wallets can be deleted.`,
      requiresPin: true,
      onConfirm: async () => {
        try {
          await api.delete(`/wallets/${wallet.value?.id}`)
          router.push('/wallets/')
        } catch (error: any) {
          if (error.response?.status === 403) {
            alert(error.response.data.message || 'You are not authorized to delete this wallet (it might not be empty).')
          } else {
            console.error('Error deleting wallet:', error)
          }
        }
      },
    }
  }

  onMounted(fetchWallet)
</script>

<template>
  <div class="mb-8">
    <v-btn
      class="mb-4 text-none px-0"
      color="primary"
      prepend-icon="mdi-arrow-left"
      to="/wallets/"
      variant="text"
    >
      Back to Wallets
    </v-btn>
    <h1 class="text-h5 font-weight-bold text-grey-darken-2">
      Edit Wallet
    </h1>
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
      <v-row>
        <v-col cols="12" md="6">
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
          </div>
        </v-col>

        <v-col
          class="d-none d-md-flex align-center justify-center"
          cols="12"
          md="6"
        >
          <v-sheet
            class="pa-8 w-100 text-center"
            color="grey-lighten-4"
            max-width="320"
            rounded="xl"
          >
            <v-icon
              class="mb-4"
              color="grey-lighten-1"
              :icon="Wallet"
              size="64"
            />
            <div
              class="text-subtitle-1 font-weight-bold text-grey-darken-3 mb-2"
            >
              Update Wallet
            </div>
            <p class="text-body-2 text-grey-darken-1 mb-6">
              Modify the name or currency of this wallet.
            </p>

            <v-divider class="mb-6" />

            <div class="text-subtitle-2 font-weight-bold text-grey-darken-3 mb-4 text-left">
              Management Actions
            </div>

            <div class="d-flex flex-column ga-2">
              <v-btn
                block
                class="justify-start text-none"
                :color="wallet?.status === 'active' ? 'warning' : 'success'"
                :prepend-icon="wallet?.status === 'active' ? Snowflake : 'mdi-fire'"
                rounded="lg"
                variant="outlined"
                @click="handleToggleStatus"
              >
                {{ wallet?.status === 'active' ? 'Freeze Wallet' : 'Unfreeze Wallet' }}
              </v-btn>

              <v-btn
                block
                class="justify-start text-none"
                color="error"
                :prepend-icon="Trash2"
                rounded="lg"
                variant="outlined"
                @click="handleDelete"
              >
                Delete Wallet
              </v-btn>
            </div>
          </v-sheet>
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
</template>

<route lang="yaml">
meta:
    layout: App
</route>
