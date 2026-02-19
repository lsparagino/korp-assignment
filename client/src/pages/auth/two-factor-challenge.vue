<script lang="ts" setup>
  import { computed, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { twoFactorChallenge } from '@/api/auth'
  import { useAuthStore } from '@/stores/auth'

  const route = useRoute()
  const router = useRouter()
  const authStore = useAuthStore()
  const showRecoveryInput = ref(false)
  const processing = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const code = ref('')
  const recoveryCode = ref('')

  const authConfigContent = computed(() => {
    if (showRecoveryInput.value) {
      return {
        title: 'Recovery Code',
        description:
          'Please confirm access to your account by entering one of your emergency recovery codes.',
        buttonText: 'login using an authentication code',
      }
    }
    return {
      title: 'Authentication Code',
      description:
        'Enter the authentication code provided by your authenticator application.',
      buttonText: 'login using a recovery code',
    }
  })

  function toggleRecoveryMode () {
    showRecoveryInput.value = !showRecoveryInput.value
    errors.value = {}
    code.value = ''
    recoveryCode.value = ''
  }

  async function submit () {
    processing.value = true
    errors.value = {}

    try {
      const payload = showRecoveryInput.value
        ? {
          recovery_code: recoveryCode.value,
          user_id: authStore.twoFactorUserId,
        }
        : { code: code.value, user_id: authStore.twoFactorUserId }

      const response = await twoFactorChallenge(payload)

      authStore.setToken(response.data.access_token)
      authStore.setUser(response.data.user)
      router.push('/dashboard')
    } catch (error: unknown) {
      const err = error as { response?: { status?: number, data?: { errors?: Record<string, string[]>, message?: string } } }
        if (err.response?.status === 422) {
        errors.value = err.response?.data?.errors ?? {}
      }
    } finally {
      processing.value = false
    }
  }

  watch(
    authConfigContent,
    val => {
      route.meta.title = val.title
      route.meta.description = val.description
    },
    { immediate: true },
  )
</script>

<template>
  <AuthCard>
    <v-form @submit.prevent="submit">
      <div class="d-flex flex-column ga-6">
        <template v-if="!showRecoveryInput">
          <div class="d-flex flex-column ga-4">
            <div class="d-flex justify-center">
              <v-otp-input
                v-model="code"
                autofocus
                color="primary"
                :disabled="processing"
                length="6"
              />
            </div>
            <v-alert
              v-if="errors.code"
              density="compact"
              type="error"
              variant="tonal"
            >
              {{ errors.code[0] }}
            </v-alert>

            <v-btn
              block
              class="text-none font-weight-bold"
              color="primary"
              :disabled="processing"
              height="48"
              rounded="lg"
              type="submit"
            >
              Continue
            </v-btn>

            <div class="text-center">
              <span class="text-body-2 text-grey-darken-1">or you can
              </span>
              <button
                class="text-body-2 font-weight-bold text-decoration-underline text-primary"
                type="button"
                @click="toggleRecoveryMode"
              >
                {{ authConfigContent.buttonText }}
              </button>
            </div>
          </div>
        </template>

        <template v-else>
          <div class="d-flex flex-column ga-4">
            <v-text-field
              v-model="recoveryCode"
              color="primary"
              density="comfortable"
              :error-messages="errors.recovery_code"
              hide-details="auto"
              label="Recovery Code"
              placeholder="Enter recovery code"
              required
              variant="outlined"
            />

            <v-btn
              block
              class="text-none font-weight-bold"
              color="primary"
              :disabled="processing"
              height="48"
              rounded="lg"
              type="submit"
            >
              Continue
            </v-btn>

            <div class="text-center">
              <span class="text-body-2 text-grey-darken-1">or you can
              </span>
              <button
                class="text-body-2 font-weight-bold text-decoration-underline text-primary"
                type="button"
                @click="toggleRecoveryMode"
              >
                {{ authConfigContent.buttonText }}
              </button>
            </div>
          </div>
        </template>
      </div>
    </v-form>
  </AuthCard>
</template>

<route lang="yaml">
meta:
    layout: Auth
    public: true
</route>
