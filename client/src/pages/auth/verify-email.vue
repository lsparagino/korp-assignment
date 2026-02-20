<script lang="ts" setup>
  import { computed, onMounted, onUnmounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { sendVerificationEmail, verifyEmail } from '@/api/auth'
  import { useAuthStore } from '@/stores/auth'

  const route = useRoute()
  const router = useRouter()
  const authStore = useAuthStore()

  const verifying = ref(false)
  const resending = ref(false)
  const status = ref('')
  const error = ref('')

  const RESEND_COOLDOWN = 60
  const countdown = ref(RESEND_COOLDOWN)
  let timer: ReturnType<typeof setInterval> | null = null

  const canResend = computed(() => countdown.value === 0 && !resending.value)

  /** Whether the user arrived from a verification link (has query params). */
  const isVerificationLink = ref(false)

  function startCountdown() {
    countdown.value = RESEND_COOLDOWN
    if (timer) clearInterval(timer)
    timer = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0) {
        countdown.value = 0
        if (timer) clearInterval(timer)
      }
    }, 1000)
  }

  onMounted(async () => {
    const { id, hash, expires, signature } = route.query as Record<string, string>

    if (id && hash && expires && signature) {
      isVerificationLink.value = true
      verifying.value = true
      try {
        await verifyEmail(id, hash, expires, signature)
        await authStore.fetchUser()
        status.value = 'Email verified successfully! Redirecting...'
        setTimeout(() => router.push('/dashboard'), 1500)
      } catch (err: unknown) {
        const e = err as { response?: { data?: { message?: string } } }
        error.value = e.response?.data?.message || 'Verification failed. The link may be invalid or expired.'
        isVerificationLink.value = false
      } finally {
        verifying.value = false
      }
    } else {
      startCountdown()
    }
  })

  onUnmounted(() => {
    if (timer) clearInterval(timer)
  })

  async function resend() {
    if (!canResend.value) return
    resending.value = true
    status.value = ''
    error.value = ''
    try {
      await sendVerificationEmail()
      status.value = 'A new verification link has been sent to your email address.'
      startCountdown()
    } catch {
      error.value = 'Failed to send verification email. Please try again.'
    } finally {
      resending.value = false
    }
  }

  async function handleLogout() {
    await authStore.logout()
    router.push('/auth/login')
  }
</script>

<template>
  <AuthCard>
    <v-alert
      v-if="status"
      class="mb-6"
      density="compact"
      type="success"
      variant="tonal"
    >
      {{ status }}
    </v-alert>

    <v-alert
      v-if="error"
      class="mb-6"
      density="compact"
      type="error"
      variant="tonal"
    >
      {{ error }}
    </v-alert>

    <!-- Loader-only view when verifying via email link -->
    <div v-if="isVerificationLink" class="d-flex flex-column align-center ga-4 py-4">
      <v-progress-circular
        v-if="verifying"
        color="primary"
        indeterminate
      />
      <div v-if="verifying" class="text-body-2 text-grey">
        Verifying your email...
      </div>
      <v-btn
        v-if="status && !verifying"
        block
        class="text-none font-weight-bold mt-2"
        color="primary"
        height="48"
        rounded="lg"
        to="/dashboard"
      >
        Continue to dashboard
      </v-btn>
    </div>

    <!-- Normal view with buttons when user visits directly -->
    <template v-else>
      <div class="d-flex flex-column ga-6 align-center">
        <v-btn
          block
          class="text-none font-weight-bold"
          color="secondary"
          :disabled="!canResend"
          height="48"
          :loading="resending"
          rounded="lg"
          variant="tonal"
          @click="resend"
        >
          <template v-if="!canResend && countdown > 0">
            Resend verification email ({{ countdown }}s)
          </template>
          <template v-else>
            Resend verification email
          </template>
        </v-btn>

        <v-btn
          class="text-body-2 font-weight-bold"
          color="primary"
          :disabled="resending"
          variant="text"
          @click="handleLogout"
        >
          Log out
        </v-btn>
      </div>
    </template>
  </AuthCard>
</template>

<route lang="yaml">
meta:
    layout: Auth
    title: Verify email
    description: Please verify your email address by clicking on the link we just emailed to you.
</route>
