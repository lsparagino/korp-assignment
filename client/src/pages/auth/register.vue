<script lang="ts" setup>
  import { reactive } from 'vue'
  import { useRouter } from 'vue-router'
  import { register } from '@/api/auth'
  import { useAuthStore } from '@/stores/auth'
  import { useFormSubmit } from '@/composables/useFormSubmit'

  const router = useRouter()
  const authStore = useAuthStore()

  const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  })

  const { processing, errors, submit } = useFormSubmit({
    submitFn: async (data: typeof form) => {
      const response = await register(data)
      authStore.setToken(response.data.access_token)
      authStore.setUser(response.data.user)
    },
    onSuccess: () => router.push('/dashboard'),
  })
</script>

<template>
  <AuthCard>
    <v-form @submit.prevent="submit(form)">
      <div class="d-flex flex-column ga-4">
        <v-text-field
          v-model="form.name"
          autofocus
          color="primary"
          density="comfortable"
          :error-messages="errors.name"
          hide-details="auto"
          label="Name"
          name="name"
          placeholder="Full name"
          required
          type="text"
          variant="outlined"
        />

        <v-text-field
          v-model="form.email"
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

        <v-text-field
          v-model="form.password"
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
          class="text-none font-weight-bold mt-6"
          color="primary"
          height="48"
          :loading="processing"
          rounded="lg"
          type="submit"
        >
          Create account
        </v-btn>
      </div>
    </v-form>

    <template #footer>
      <span class="text-body-2 text-grey-darken-1">Already have an account?</span>
      <router-link
        class="text-body-2 font-weight-bold text-decoration-none text-primary ms-1"
        to="/auth/login"
      >Log in</router-link>
    </template>
  </AuthCard>
</template>

<route lang="yaml">
meta:
    layout: Auth
    public: true
    title: Create an account
    description: Enter your details below to create your account
</route>
