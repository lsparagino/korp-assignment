<script lang="ts" setup>
  import type { VAlert } from 'vuetify/components'
  import { reactive, ref } from 'vue'
  import api from '@/plugins/api'

  const form = reactive({
    email: '',
  })

  const processing = ref<boolean>(false)
  const errors = ref<Record<string, string[]>>({})
  const status = ref('')
  const alertType = ref<VAlert['type']>('success')

  async function submit () {
    processing.value = true
    errors.value = {}
    status.value = ''
    try {
      const response = await api.post('/forgot-password', form)
      status.value = response.data.message
      alertType.value = 'success'
    } catch (error: any) {
      if (error.response?.status === 422) {
        alertType.value = 'warning'
        status.value = 'Your mailbox is full'
      } else {
        alertType.value = 'error'
        status.value = 'An error occurred. Please try again.'
      }
    } finally {
      processing.value = false
    }
  }
</script>

<template>
  <v-alert
    v-if="status"
    class="mb-4"
    density="compact"
    :type="alertType"
    variant="tonal"
  >
    {{ status }}
  </v-alert>

  <v-form @submit.prevent="submit">
    <div class="d-flex flex-column ga-6">
      <v-text-field
        v-model="form.email"
        autofocus
        color="primary"
        density="comfortable"
        :error-messages="errors.email"
        hide-details="auto"
        label="Email address"
        name="email"
        placeholder="email@example.com"
        required
        type="email"
        variant="outlined"
      />

      <v-btn
        block
        class="text-none font-weight-bold"
        color="primary"
        height="48"
        :loading="processing"
        rounded="lg"
        type="submit"
      >
        Email password reset link
      </v-btn>
    </div>
  </v-form>

  <div class="mt-6 text-center">
    <span class="text-body-2 text-grey-darken-1">Or, return to</span>
    <router-link
      class="text-body-2 font-weight-bold ms-1 text-decoration-none text-primary"
      to="/auth/login"
    >log in</router-link>
  </div>
</template>

<route lang="yaml">
meta:
    layout: Auth
    title: Forgot password
    description: Enter your email to receive a password reset link
</route>
