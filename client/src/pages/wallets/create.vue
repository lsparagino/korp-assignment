<script lang="ts" setup>
  import { Wallet } from 'lucide-vue-next'
  import { reactive, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { api } from '@/plugins/api'

  const router = useRouter()
  const processing = ref(false)
  const errors = ref<Record<string, string[]>>({})

  const form = reactive({
    name: '',
    currency: 'USD',
  })

  const currencies = [
    { title: 'US Dollar (USD)', value: 'USD' },
    { title: 'Euro (EUR)', value: 'EUR' },
  ]

  async function submit () {
    processing.value = true
    errors.value = {}

    try {
      await api.post('/wallets', form)
      router.push('/wallets/')
    } catch (error: unknown) {
      const err = error as {
        response?: {
          status?: number
          data?: { errors?: Record<string, string[]> }
        }
      }
      if (err.response?.status === 422) {
        errors.value = err.response.data?.errors ?? {}
      }
    } finally {
      processing.value = false
    }
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
    <h1 class="text-h5 font-weight-bold text-grey-darken-2">
      Create New Wallet
    </h1>
  </div>

  <v-card border class="pa-8" flat rounded="lg">
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
                Create Wallet
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
      </v-row>
    </v-form>
  </v-card>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
