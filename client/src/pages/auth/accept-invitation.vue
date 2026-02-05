<script lang="ts" setup>
  import { ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/plugins/api'

  const route = useRoute()
  const router = useRouter()
  
  const token = (route.params as any).token as string
  const email = (route.query as any).email as string

  const form = ref({
    password: '',
    password_confirmation: '',
  })
  const processing = ref(false)
  const error = ref('')

  async function submit () {
    processing.value = true
    error.value = ''
    try {
      await api.post(`/accept-invitation/${token}`, form.value)
      router.push('/dashboard')
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Something went wrong.'
    } finally {
      processing.value = false
    }
  }
</script>

<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" md="4" sm="8">
        <v-card class="pa-4" rounded="lg">
          <v-card-title class="text-center font-weight-bold text-h5 mb-4">
            Activate Your Account
          </v-card-title>
          <v-card-subtitle class="text-center mb-6">
            Welcome! Please set a password for your account ({{ email }}).
          </v-card-subtitle>
          
          <v-alert
            v-if="error"
            class="mb-4"
            density="compact"
            type="error"
            variant="tonal"
          >
            {{ error }}
          </v-alert>

          <v-form @submit.prevent="submit">
            <v-text-field
              v-model="form.password"
              autocomplete="new-password"
              label="New Password"
              required
              type="password"
              variant="outlined"
            />
            <v-text-field
              v-model="form.password_confirmation"
              autocomplete="new-password"
              label="Confirm Password"
              required
              type="password"
              variant="outlined"
            />
            <v-btn
              block
              class="mt-4"
              color="primary"
              :loading="processing"
              size="large"
              type="submit"
              variant="flat"
            >
              Activate Account
            </v-btn>
          </v-form>
        </v-card>
      </col>
    </v-row>
  </v-container>
</template>

<route lang="yaml">
meta:
    layout: Auth
</route>
