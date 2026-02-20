<script lang="ts" setup>
  import { reactive, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { login } from '@/api/auth'
  import { useAuthStore } from '@/stores/auth'
  import { useFormSubmit } from '@/composables/useFormSubmit'

  const router = useRouter()
  const authStore = useAuthStore()
  const status = ref('')

  const form = reactive({
    email: '',
    password: '',
    remember: false,
  })

  const showPassword = ref(false)

  const { processing, errors, submit } = useFormSubmit({
    submitFn: async (data: typeof form) => {
      const response = await login(data)

      if (response.data.two_factor) {
        authStore.setTwoFactor(response.data.user_id)
        router.push('/auth/two-factor-challenge')
        return
      }

      authStore.setToken(response.data.access_token)
      authStore.setUser(response.data.user)
      router.push('/dashboard')
    },
    onError: () => {
      status.value = 'An error occurred during login.'
    },
  })
</script>

<template>
  <AuthCard :error="status">
    <v-form @submit.prevent="submit(form)">
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
            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
            color="primary"
            density="comfortable"
            :error-messages="errors.password"
            hide-details="auto"
            name="password"
            placeholder="Password"
            required
            :type="showPassword ? 'text' : 'password'"
            variant="outlined"
            @click:append-inner="showPassword = !showPassword"
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
          class="text-none font-weight-bold mt-4"
          color="primary"
          height="48"
          :loading="processing"
          rounded="lg"
          type="submit"
        >
          Log in
        </v-btn>
      </div>
    </v-form>

    <template #footer>
      <span class="text-body-2 text-grey-darken-1">Don't have an account?</span>
      <router-link
        class="text-body-2 font-weight-bold text-decoration-none text-primary ms-1"
        to="/auth/register"
      >Sign up</router-link>
    </template>
  </AuthCard>
</template>

<route lang="yaml">
meta:
    layout: Auth
    public: true
    title: Log in to your account
    description: Enter your email and password below to log in
</route>
