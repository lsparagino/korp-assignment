<script lang="ts" setup>
  import { reactive, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import api from '@/plugins/api'
  import { useAuthStore } from '@/stores/auth'

  const router = useRouter()
  const authStore = useAuthStore()

  const form = reactive({
    email: '',
    password: '',
    remember: false,
  })

  const errors = ref<Record<string, string[]>>({})
  const processing = ref(false)
  const status = ref('')

  async function submit () {
    processing.value = true
    errors.value = {}

    try {
      const response = await api.post('/login', form)

      if (response.data.two_factor) {
        authStore.setTwoFactor(response.data.user_id)
        router.push('/auth/two-factor-challenge')
        return
      }

      authStore.setToken(response.data.access_token)
      authStore.setUser(response.data.user)
      router.push('/dashboard')
    } catch (error: any) {
      if (error.response?.status === 422) {
        errors.value = error.response.data.errors
      } else {
        status.value = 'An error occurred during login.'
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
    type="error"
    variant="tonal"
  >
    {{ status }}
  </v-alert>

  <v-form @submit.prevent="submit">
    <div class="d-flex flex-column ga-4">
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

      <div>
        <div class="d-flex align-center justify-space-between mb-1">
          <span
            class="text-caption font-weight-medium text-grey-darken-3"
          >Password</span>
          <router-link
            class="text-caption font-weight-bold text-decoration-none text-primary"
            to="/auth/forgot-password"
          >
            Forgot password?
          </router-link>
        </div>
        <v-text-field
          v-model="form.password"
          color="primary"
          density="comfortable"
          :error-messages="errors.password"
          hide-details="auto"
          name="password"
          placeholder="Password"
          required
          type="password"
          variant="outlined"
        />
      </div>

      <v-checkbox
        v-model="form.remember"
        class="ms-n3"
        color="primary"
        density="comfortable"
        hide-details
        label="Remember me"
        name="remember"
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
        Log in
      </v-btn>
    </div>

    <div class="mt-6 text-center">
      <span class="text-body-2 text-grey-darken-1">Don't have an account?</span>
      <router-link
        class="text-body-2 font-weight-bold ms-1 text-decoration-none text-primary"
        to="/auth/register"
      >Sign up</router-link>
    </div>
  </v-form>
</template>

<route lang="yaml">
meta:
    layout: Auth
    title: Log in to your account
    description: Enter your email and password below to log in
</route>
