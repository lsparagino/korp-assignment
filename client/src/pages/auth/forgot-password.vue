<script setup lang="ts">
  import { reactive, ref } from 'vue'
  import api from '@/plugins/api'

  const form = reactive({
    email: '',
  })

  const processing = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const status = ref('')

  async function submit () {
    processing.value = true
    errors.value = {}
    status.value = ''

    try {
      const response = await api.post('/forgot-password', form)
      status.value = response.data.message
    } catch (error: any) {
      if (error.response?.status === 422) {
        errors.value = error.response.data.errors
      } else {
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
    type="success"
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
        label="Email address"
        :error-messages="errors.email"
        name="email"
        hide-details="auto"
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

  <div class="text-center mt-6">
    <span class="text-body-2 text-grey-darken-1">Or, return to</span>
    <router-link class="text-body-2 text-primary font-weight-bold ms-1 text-decoration-none" to="/auth/login">log in</router-link>
  </div>
</template>

<route lang="yaml">
meta:
  layout: Auth
  title: Forgot password
  description: Enter your email to receive a password reset link
</route>
