<script lang="ts" setup>
  import { onMounted, reactive, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { resetPassword } from '@/api/auth'
  import { getValidationErrors, isApiError } from '@/utils/errors'

  const route = useRoute()
  const router = useRouter()

  const form = reactive({
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
  })

  const processing = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const status = ref('')

  onMounted(() => {
    form.token = (route.query.token as string) || ''
    form.email = (route.query.email as string) || ''
  })

  async function submit () {
    processing.value = true
    errors.value = {}

    try {
      const response = await resetPassword(form)
      status.value = response.data.message
      setTimeout(() => router.push('/auth/login'), 3000)
    } catch (error: unknown) {
      if (isApiError(error, 422)) {
        errors.value = getValidationErrors(error)
      } else {
        status.value = 'An error occurred. Please try again.'
      }
    } finally {
      processing.value = false
    }
  }
</script>

<template>
  <AuthCard :status="status">
    <v-form @submit.prevent="submit">
      <div class="d-flex flex-column ga-6">
        <v-text-field
          v-model="form.email"
          color="primary"
          density="comfortable"
          hide-details="auto"
          label="Email"
          name="email"
          readonly
          type="email"
          variant="outlined"
        />

        <v-text-field
          v-model="form.password"
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

        <v-text-field
          v-model="form.password_confirmation"
          color="primary"
          density="comfortable"
          :error-messages="errors.password_confirmation"
          hide-details="auto"
          label="Confirm password"
          name="password_confirmation"
          placeholder="Confirm password"
          required
          type="password"
          variant="outlined"
        />

        <v-btn
          block
          class="text-none font-weight-bold mt-4"
          color="primary"
          height="48"
          :loading="processing"
          rounded="lg"
          type="submit"
        >
          Reset password
        </v-btn>
      </div>
    </v-form>
  </AuthCard>
</template>

<route lang="yaml">
meta:
    layout: Auth
    public: true
    title: Reset password
    description: Please enter your new password below
</route>
