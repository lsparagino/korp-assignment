<script lang="ts" setup>
  import { onMounted, reactive, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { resetPassword } from '@/api/auth'
  import { useFormSubmit } from '@/composables/useFormSubmit'

  const route = useRoute()
  const router = useRouter()

  const form = reactive({
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
  })

  const showPassword = ref(false)
  const status = ref('')

  onMounted(() => {
    form.token = (route.query.token as string) || ''
    form.email = (route.query.email as string) || ''
  })

  const { processing, errors, submit } = useFormSubmit({
    submitFn: async (data: typeof form) => {
      const response = await resetPassword(data)
      status.value = response.data.message
    },
    onSuccess: () => {
      setTimeout(() => router.push('/auth/login'), 3000)
    },
    onError: () => {
      status.value = 'An error occurred. Please try again.'
    },
  })
</script>

<template>
  <AuthCard :status="status">
    <v-form @submit.prevent="submit(form)">
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
          :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
          color="primary"
          density="comfortable"
          :error-messages="errors.password"
          hide-details="auto"
          label="Password"
          name="password"
          placeholder="Password"
          required
          :type="showPassword ? 'text' : 'password'"
          variant="outlined"
          @click:append-inner="showPassword = !showPassword"
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
          :type="showPassword ? 'text' : 'password'"
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
