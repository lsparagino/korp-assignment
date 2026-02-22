<script lang="ts" setup>
  import { reactive, ref } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { useRouter } from 'vue-router'
  import { register } from '@/api/auth'
  import { useFormSubmit } from '@/composables/useFormSubmit'
  import { useAuthStore } from '@/stores/auth'

  const { t } = useI18n()
  const router = useRouter()
  const authStore = useAuthStore()

  const serverError = ref('')

  const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  })

  const { processing, errors, submit } = useFormSubmit({
    submitFn: async (data: typeof form) => {
      serverError.value = ''
      const response = await register(data)
      authStore.setToken(response.data.access_token)
      authStore.setUser(response.data.user)
    },
    onSuccess: () => router.push('/auth/verify-email'),
    onError: (error: unknown) => {
      const e = error as { response?: { data?: { message?: string } } }
      serverError.value = e.response?.data?.message || t('common.genericError')
    },
  })
</script>

<template>
  <AuthCard>
    <v-alert
      v-if="serverError"
      class="mb-6"
      density="compact"
      type="error"
      variant="tonal"
    >
      {{ serverError }}
    </v-alert>

    <v-form @submit.prevent="submit(form)">
      <div class="d-flex flex-column ga-4">
        <v-text-field
          v-model="form.name"
          autofocus
          color="primary"
          density="comfortable"
          :error-messages="errors.name"
          hide-details="auto"
          :label="$t('common.name')"
          name="name"
          :placeholder="$t('common.fullName')"
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
          :label="$t('common.emailAddress')"
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
          :label="$t('common.password')"
          name="password"
          :placeholder="$t('common.password')"
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
          :label="$t('common.confirmPassword')"
          name="password_confirmation"
          :placeholder="$t('common.confirmPassword')"
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
          {{ $t('auth.register.submit') }}
        </v-btn>
      </div>
    </v-form>

    <template #footer>
      <span class="text-body-2 text-grey-darken-1">{{ $t('auth.register.hasAccount') }}</span>
      <router-link
        class="text-body-2 font-weight-bold text-decoration-none text-primary ms-1"
        to="/auth/login"
      >{{ $t('auth.register.logIn') }}</router-link>
    </template>
  </AuthCard>
</template>

<route lang="yaml">
meta:
    layout: Auth
    public: true
    title: auth.register.title
    description: auth.register.description
</route>
