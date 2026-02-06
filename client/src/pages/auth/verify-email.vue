<script lang="ts" setup>
  import { ref } from 'vue'
  import { useRouter } from 'vue-router'
  import api from '@/plugins/api'
  import { useAuthStore } from '@/stores/auth'

  const router = useRouter()
  const authStore = useAuthStore()
  const processing = ref(false)
  const status = ref('')

  async function resend () {
    processing.value = true
    try {
      const response = await api.post('/email/verification-notification')
      status.value = 'verification-link-sent'
    } catch {
      // Handle error
    } finally {
      processing.value = false
    }
  }

  function handleLogout () {
    authStore.clearToken()
    router.push('/auth/login')
  }
</script>

<template>
  <AuthCard
    :status="
      status === 'verification-link-sent'
        ? 'A new verification link has been sent to the email address you provided during registration.'
        : ''
    "
  >
    <div class="d-flex flex-column ga-6 align-center">
      <v-btn
        block
        class="text-none font-weight-bold"
        color="secondary"
        height="48"
        :loading="processing"
        rounded="lg"
        variant="tonal"
        @click="resend"
      >
        Resend verification email
      </v-btn>

      <v-btn
        class="text-body-2 font-weight-bold"
        color="primary"
        variant="text"
        @click="handleLogout"
      >
        Log out
      </v-btn>
    </div>
  </AuthCard>
</template>

<route lang="yaml">
meta:
    layout: Auth
    title: Verify email
    description: Please verify your email address by clicking on the link we just emailed to you.
</route>
