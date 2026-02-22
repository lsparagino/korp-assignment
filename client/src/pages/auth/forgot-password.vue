<script lang="ts" setup>
  import type { VAlert } from 'vuetify/components'
  import { computed, reactive, ref } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { forgotPassword } from '@/api/auth'
  import { useFormSubmit } from '@/composables/useFormSubmit'
  import { isApiError } from '@/utils/errors'

  const { t } = useI18n()

  const form = reactive({
    email: '',
  })

  const status = ref('')
  const alertType = ref<VAlert['type']>('success')

  const cooldown = ref(0)
  const canSubmit = computed(() => cooldown.value === 0)

  let timerInterval: ReturnType<typeof setInterval>

  function startCooldown () {
    cooldown.value = 60
    timerInterval = setInterval(() => {
      if (cooldown.value > 0) {
        cooldown.value--
      } else {
        clearInterval(timerInterval)
      }
    }, 1000)
  }

  const { processing, submit } = useFormSubmit({
    submitFn: async (data: typeof form) => {
      try {
        const response = await forgotPassword(data)
        status.value = response.data.message
        alertType.value = 'success'
      } catch (error: unknown) {
        if (isApiError(error, 422)) {
          alertType.value = 'warning'
          status.value = t('auth.forgotPassword.mailboxFull')
        } else {
          alertType.value = 'error'
          status.value = t('common.errorOccurred')
        }
      } finally {
        if (alertType.value === 'success' || alertType.value === 'warning') {
          startCooldown()
        }
      }
    },
  })
</script>

<template>
  <AuthCard :alert-type="alertType" :status="status">
    <v-form @submit.prevent="submit(form)">
      <div class="d-flex flex-column ga-6">
        <v-text-field
          v-model="form.email"
          autofocus
          color="primary"
          density="comfortable"
          hide-details="auto"
          :label="$t('common.emailAddress')"
          name="email"
          placeholder="email@example.com"
          required
          type="email"
          variant="outlined"
        />

        <v-btn
          block
          class="text-none font-weight-bold"
          color="primary"
          :disabled="!canSubmit"
          height="48"
          :loading="processing"
          rounded="lg"
          type="submit"
        >
          <template v-if="!canSubmit">
            {{ $t('auth.forgotPassword.waitToResend', { seconds: cooldown }) }}
          </template>
          <template v-else>
            {{ $t('auth.forgotPassword.submit') }}
          </template>
        </v-btn>
      </div>
    </v-form>

    <template #footer>
      <span class="text-body-2 text-grey-darken-1">{{ $t('auth.forgotPassword.returnTo') }}</span>
      <router-link
        class="text-body-2 font-weight-bold text-decoration-none text-primary ms-1"
        to="/auth/login"
      >{{ $t('auth.forgotPassword.logIn') }}</router-link>
    </template>
  </AuthCard>
</template>

<route lang="yaml">
meta:
    layout: Auth
    public: true
    title: auth.forgotPassword.title
    description: auth.forgotPassword.description
</route>
