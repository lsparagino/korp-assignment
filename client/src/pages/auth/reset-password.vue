<script lang="ts" setup>
  import { onMounted, reactive, ref } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { useRoute, useRouter } from 'vue-router'
  import { resetPassword } from '@/api/auth'
  import { useFormSubmit } from '@/composables/useFormSubmit'

  const { t } = useI18n()
  const route = useRoute()
  const router = useRouter()

  const form = reactive({
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
  })

  const showPassword = ref(false)
  const status = ref('')

  onMounted(() => {
    form.token = (route.query.token as string) || ''
    form.email = (route.query.email as string) || ''
  })

  const { processing, errors, submit } = useFormSubmit({
    submitFn: async (data: typeof form) => {
      const response = await resetPassword(data)
      status.value = response.data.message
    },
    onSuccess: () => {
      setTimeout(() => router.push('/auth/login'), 3000)
    },
    onError: () => {
      status.value = t('common.errorOccurred')
    },
  })
</script>

<template>
  <AuthCard :status="status">
    <v-form @submit.prevent="submit(form)">
      <div class="d-flex flex-column ga-6">
        <v-text-field
          v-model="form.email"
          color="primary"
          density="comfortable"
          hide-details="auto"
          :label="$t('common.email')"
          name="email"
          readonly
          type="email"
          variant="outlined"
        />

        <v-text-field
          v-model="form.password"
          :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
          autofocus
          color="primary"
          density="comfortable"
          :error-messages="errors.password"
          hide-details="auto"
          :label="$t('common.password')"
          name="password"
          :placeholder="$t('common.password')"
          required
          :type="showPassword ? 'text' : 'password'"
          variant="outlined"
          @click:append-inner="showPassword = !showPassword"
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
          :type="showPassword ? 'text' : 'password'"
          variant="outlined"
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
          {{ $t('auth.resetPassword.submit') }}
        </v-btn>
      </div>
    </v-form>
  </AuthCard>
</template>

<route lang="yaml">
meta:
    layout: Auth
    public: true
    title: auth.resetPassword.title
    description: auth.resetPassword.description
</route>
