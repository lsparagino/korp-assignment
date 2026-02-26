<script lang="ts" setup>
  import { onMounted, ref } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { useRoute, useRouter } from 'vue-router'
  import { acceptInvitation, verifyInvitation } from '@/api/auth'
  import { useFormSubmit } from '@/composables/useFormSubmit'
  import { useAuthStore } from '@/stores/auth'

  const { t } = useI18n()
  const route = useRoute()
  const router = useRouter()
  const auth = useAuthStore()

  const token = (route.params as Record<string, string>)['token']!
  const email = ref('')
  const verifying = ref(true)
  const invalidToken = ref(false)

  const form = ref({
    password: '',
    password_confirmation: '',
  })

  onMounted(async () => {
    try {
      const response = await verifyInvitation(token)
      email.value = response.data.email
    } catch {
      invalidToken.value = true
    } finally {
      verifying.value = false
    }
  })

  const { processing, serverError, submit } = useFormSubmit({
    submitFn: async (data: typeof form.value) => {
      const response = await acceptInvitation(token, data)
      auth.setToken(response.data.access_token)
      auth.setUser(response.data.user)
      router.push('/dashboard')
    },
  })
</script>

<template>
  <div v-if="verifying" class="py-8 text-center">
    <v-progress-circular color="primary" indeterminate />
    <div class="text-grey mt-4">{{ $t('auth.invitation.verifying') }}</div>
  </div>

  <div v-else-if="invalidToken" class="py-4 text-center">
    <v-icon color="error" icon="lucide:alert-circle" size="48" />
    <h2 class="text-h6 font-weight-bold mt-4">
      {{ $t('auth.invitation.invalidTitle') }}
    </h2>
    <p class="text-body-2 text-grey mt-2">
      {{ $t('auth.invitation.invalidMessage') }}
    </p>
    <v-btn
      block
      class="mt-8"
      color="primary"
      to="/auth/login"
      variant="tonal"
    >
      {{ $t('auth.invitation.backToLogin') }}
    </v-btn>
  </div>

  <template v-else>
    <div class="mb-6 text-center">
      <p class="text-body-2 text-grey-darken-1">
        <i18n-t keypath="auth.invitation.setPasswordMessage" tag="span">
          <template #email>
            <strong>{{ email }}</strong>
          </template>
        </i18n-t>
      </p>
    </div>

    <v-alert
      v-if="serverError"
      class="mb-4"
      density="compact"
      type="error"
      variant="tonal"
    >
      {{ serverError }}
    </v-alert>

    <v-form @submit.prevent="submit(form)">
      <v-text-field
        v-model="form.password"
        autocomplete="new-password"
        :label="$t('auth.invitation.newPassword')"
        required
        type="password"
        variant="outlined"
      />
      <v-text-field
        v-model="form.password_confirmation"
        autocomplete="new-password"
        :label="$t('auth.invitation.confirmPasswordLabel')"
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
        {{ $t('auth.invitation.activateAccount') }}
      </v-btn>
    </v-form>
  </template>
</template>

<route lang="yaml">
meta:
    layout: Auth
    public: true
    title: auth.invitation.title
    description: auth.invitation.description
</route>
