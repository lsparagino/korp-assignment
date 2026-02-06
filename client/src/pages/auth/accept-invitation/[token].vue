<script lang="ts" setup>
  import { onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/plugins/api'
  import { useAuthStore } from '@/stores/auth'

  const route = useRoute()
  const router = useRouter()
  const auth = useAuthStore()

  const token = (route.params as any).token as string
  const email = ref('')
  const verifying = ref(true)
  const invalidToken = ref(false)

  const form = ref({
    password: '',
    password_confirmation: '',
  })
  const processing = ref(false)
  const error = ref('')

  onMounted(async () => {
    try {
      const response = await api.get(`/invitation/${token}`)
      email.value = response.data.email
    } catch {
      invalidToken.value = true
    } finally {
      verifying.value = false
    }
  })

  async function submit () {
    processing.value = true
    error.value = ''
    try {
      const response = await api.post(
        `/accept-invitation/${token}`,
        form.value,
      )
      auth.setToken(response.data.access_token)
      auth.setUser(response.data.user)
      router.push('/dashboard')
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Something went wrong.'
    } finally {
      processing.value = false
    }
  }
</script>

<template>
  <div v-if="verifying" class="py-8 text-center">
    <v-progress-circular color="primary" indeterminate />
    <div class="text-grey mt-4">Verifying invitation...</div>
  </div>

  <div v-else-if="invalidToken" class="py-4 text-center">
    <v-icon color="error" icon="lucide:alert-circle" size="48" />
    <h2 class="text-h6 font-weight-bold mt-4">
      Invalid or Expired Invitation
    </h2>
    <p class="text-body-2 text-grey mt-2">
      This invitation link is invalid or has already been used. Please
      contact your administrator.
    </p>
    <v-btn
      block
      class="mt-8"
      color="primary"
      to="/auth/login"
      variant="tonal"
    >
      Back to Login
    </v-btn>
  </div>

  <template v-else>
    <div class="mb-6 text-center">
      <p class="text-body-2 text-grey-darken-1">
        Hello! Please set a password for your account
        <strong>{{ email }}</strong>.
      </p>
    </div>

    <v-alert
      v-if="error"
      class="mb-4"
      density="compact"
      type="error"
      variant="tonal"
    >
      {{ error }}
    </v-alert>

    <v-form @submit.prevent="submit">
      <v-text-field
        v-model="form.password"
        autocomplete="new-password"
        label="New Password"
        required
        type="password"
        variant="outlined"
      />
      <v-text-field
        v-model="form.password_confirmation"
        autocomplete="new-password"
        label="Confirm Password"
        required
        type="password"
        variant="outlined"
      />
      <v-btn
        block
        class="mt-4"
        color="primary"
        :loading="processing"
        size="large"
        type="submit"
        variant="flat"
      >
        Activate Account
      </v-btn>
    </v-form>
  </template>
</template>

<route lang="yaml">
meta:
    layout: Auth
    public: true
    title: Activate Your Account
    description: Join the SecureWallet team.
</route>
