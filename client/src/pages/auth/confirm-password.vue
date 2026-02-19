<script lang="ts" setup>
  import { reactive, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { confirmPassword } from '@/api/auth'

  const router = useRouter()

  const form = reactive({
    password: '',
  })

  const processing = ref(false)
  const errors = ref<Record<string, string[]>>({})

  async function submit () {
    processing.value = true
    errors.value = {}

    try {
      await confirmPassword(form)
      // Usually redirects back or to a specific page
      router.back()
    } catch (error: unknown) {
      const err = error as { response?: { status?: number, data?: { errors?: Record<string, string[]>, message?: string } } }
        if (err.response?.status === 422) {
        errors.value = err.response?.data?.errors ?? {}
      }
    } finally {
      processing.value = false
    }
  }
</script>

<template>
  <AuthCard>
    <v-form @submit.prevent="submit">
      <div class="d-flex flex-column ga-6">
        <v-text-field
          v-model="form.password"
          autocomplete="current-password"
          autofocus
          color="primary"
          density="comfortable"
          :error-messages="errors.password"
          hide-details="auto"
          label="Password"
          name="password"
          placeholder="Password"
          required
          type="password"
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
          Confirm Password
        </v-btn>
      </div>
    </v-form>
  </AuthCard>
</template>

<route lang="yaml">
meta:
    layout: Auth
    public: true
    title: Confirm your password
    description: This is a secure area of the application. Please confirm your password before continuing.
</route>
