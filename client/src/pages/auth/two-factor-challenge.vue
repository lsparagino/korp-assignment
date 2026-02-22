<script lang="ts" setup>
  import { computed, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useI18n } from 'vue-i18n'
  import { twoFactorChallenge } from '@/api/auth'
  import { useAuthStore } from '@/stores/auth'
  import { useFormSubmit } from '@/composables/useFormSubmit'

  const { t } = useI18n()
  const route = useRoute()
  const router = useRouter()
  const authStore = useAuthStore()
  const showRecoveryInput = ref(false)
  const code = ref('')
  const recoveryCode = ref('')

  const authConfigContent = computed(() => {
    if (showRecoveryInput.value) {
      return {
        title: t('auth.twoFactor.recoveryCode'),
        description: t('auth.twoFactor.recoveryCodeDescription'),
        buttonText: t('auth.twoFactor.useAuthCode'),
      }
    }
    return {
      title: t('auth.twoFactor.authCode'),
      description: t('auth.twoFactor.authCodeDescription'),
      buttonText: t('auth.twoFactor.useRecoveryCode'),
    }
  })

  function toggleRecoveryMode () {
    showRecoveryInput.value = !showRecoveryInput.value
    errors.value = {}
    code.value = ''
    recoveryCode.value = ''
  }

  const { processing, errors, submit } = useFormSubmit({
    submitFn: async () => {
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
    },
  })

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
    <v-form @submit.prevent="submit({})">
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
              {{ $t('common.continue') }}
            </v-btn>

            <div class="text-center">
              <span class="text-body-2 text-grey-darken-1">{{ $t('auth.twoFactor.orYouCan') }}
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
              :label="$t('auth.twoFactor.recoveryCodeLabel')"
              :placeholder="$t('auth.twoFactor.recoveryCodePlaceholder')"
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
              {{ $t('common.continue') }}
            </v-btn>

            <div class="text-center">
              <span class="text-body-2 text-grey-darken-1">{{ $t('auth.twoFactor.orYouCan') }}
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
