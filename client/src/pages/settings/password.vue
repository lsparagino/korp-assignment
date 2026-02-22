<script lang="ts" setup>
  import { reactive } from 'vue'
  import Heading from '@/components/ui/Heading.vue'
  import SettingsLayout from '@/components/layout/SettingsLayout.vue'
  import { updatePassword } from '@/api/settings'
  import { useFormSubmit } from '@/composables/useFormSubmit'

  const form = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
  })

  const { processing, errors, recentlySuccessful, submit } = useFormSubmit({
    submitFn: (data: { current_password: string, password: string, password_confirmation: string }) =>
      updatePassword(data),
    resetForm: () => {
      Object.assign(form, {
        current_password: '',
        password: '',
        password_confirmation: '',
      })
    },
  })
</script>

<template>
  <SettingsLayout>
    <div class="d-flex flex-column ga-6">
      <Heading
        :description="$t('settings.password.description')"
        :title="$t('settings.password.title')"
        variant="small"
      />

      <v-form @submit.prevent="submit(form)">
        <div class="d-flex flex-column ga-4">
          <v-text-field
            v-model="form.current_password"
            autocomplete="current-password"
            color="primary"
            density="comfortable"
            :error-messages="errors.current_password"
            hide-details="auto"
            :label="$t('settings.password.currentPassword')"
            name="current_password"
            :placeholder="$t('settings.password.currentPassword')"
            type="password"
            variant="outlined"
          />

          <v-text-field
            v-model="form.password"
            autocomplete="new-password"
            color="primary"
            density="comfortable"
            :error-messages="errors.password"
            hide-details="auto"
            :label="$t('settings.password.newPassword')"
            name="password"
            :placeholder="$t('settings.password.newPassword')"
            type="password"
            variant="outlined"
          />

          <v-text-field
            v-model="form.password_confirmation"
            autocomplete="new-password"
            color="primary"
            density="comfortable"
            :error-messages="errors.password_confirmation"
            hide-details="auto"
            :label="$t('common.confirmPassword')"
            name="password_confirmation"
            :placeholder="$t('common.confirmPassword')"
            type="password"
            variant="outlined"
          />

          <div class="d-flex align-center ga-4 mt-2">
            <v-btn
              class="text-none font-weight-bold"
              color="primary"
              :loading="processing"
              type="submit"
              variant="flat"
            >
              {{ $t('settings.password.savePassword') }}
            </v-btn>

            <v-fade-transition>
              <p
                v-show="recentlySuccessful"
                class="text-body-2 text-grey-darken-1"
              >
                {{ $t('common.saved') }}
              </p>
            </v-fade-transition>
          </div>
        </div>
      </v-form>
    </div>
  </SettingsLayout>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
