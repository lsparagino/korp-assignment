<script lang="ts" setup>
  import { Wallet } from 'lucide-vue-next'
  import { onMounted, reactive, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/plugins/api'

  const route = useRoute()
  const router = useRouter()
  const processing = ref(false)
  const loading = ref(true)
  const errors = ref<Record<string, string[]>>({})

  const form = reactive({
    name: '',
    currency: '',
  })

  const currencies = [
    { title: 'US Dollar (USD)', value: 'USD' },
    { title: 'Euro (EUR)', value: 'EUR' },
  ]

  async function fetchWallet () {
    try {
      const response = await api.get(`/wallets/${route.params.id}`)
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
      await api.put(`/wallets/${route.params.id}`, form)
      router.push('/wallets/')
    } catch (error: any) {
      if (error.response?.status === 422) {
        errors.value = error.response.data.errors
      }
    } finally {
      processing.value = false
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
          </v-sheet>
        </v-col>
      </v-row>
    </v-form>
  </v-card>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
