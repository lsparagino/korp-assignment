<script lang="ts" setup>
  import { onMounted, reactive, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/plugins/api'

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
      const response = await api.post('/reset-password', form)
      status.value = response.data.message
      setTimeout(() => router.push('/auth/login'), 3000)
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
          class="mt-4 text-none font-weight-bold"
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
    title: Reset password
    description: Please enter your new password below
</route>
