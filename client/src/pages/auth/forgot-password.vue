<script lang="ts" setup>
  import type { VAlert } from 'vuetify/components'
  import { reactive, ref } from 'vue'
  import { forgotPassword } from '@/api/auth'

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
      const response = await forgotPassword(form)
      status.value = response.data.message
      alertType.value = 'success'
    } catch (error: unknown) {
      const err = error as { response?: { status?: number, data?: { errors?: Record<string, string[]>, message?: string } } }
        if (err.response?.status === 422) {
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
  <AuthCard :alert-type="alertType" :status="status">
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

    <template #footer>
      <span class="text-body-2 text-grey-darken-1">Or, return to</span>
      <router-link
        class="text-body-2 font-weight-bold text-decoration-none text-primary ms-1"
        to="/auth/login"
      >log in</router-link>
    </template>
  </AuthCard>
</template>

<route lang="yaml">
meta:
    layout: Auth
    public: true
    title: Forgot password
    description: Enter your email to receive a password reset link
</route>
