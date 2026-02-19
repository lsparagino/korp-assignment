<script lang="ts" setup>
  import { reactive } from 'vue'
  import { useRouter } from 'vue-router'
  import { confirmPassword } from '@/api/auth'
  import { useFormSubmit } from '@/composables/useFormSubmit'

  const router = useRouter()

  const form = reactive({
    password: '',
  })

  const { processing, errors, submit } = useFormSubmit({
    submitFn: (data: typeof form) => confirmPassword(data),
    onSuccess: () => router.back(),
  })
</script>

<template>
  <AuthCard>
    <v-form @submit.prevent="submit(form)">
      <div class="d-flex flex-column ga-6">
        <v-text-field
          v-model="form.password"
          autocomplete="current-password"
          autofocus
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

        <v-btn
          block
          class="text-none font-weight-bold"
          color="primary"
          height="48"
          :loading="processing"
          rounded="lg"
          type="submit"
        >
          Confirm Password
        </v-btn>
      </div>
    </v-form>
  </AuthCard>
</template>

<route lang="yaml">
meta:
    layout: Auth
    public: true
    title: Confirm your password
    description: This is a secure area of the application. Please confirm your password before continuing.
</route>
