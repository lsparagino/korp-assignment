<script lang="ts" setup>
  import { reactive, ref } from 'vue'
  import Heading from '@/components/ui/Heading.vue'
  import SettingsLayout from '@/components/layout/SettingsLayout.vue'
  import { updatePassword } from '@/api/settings'
  import { getValidationErrors, isApiError } from '@/utils/errors'

  const form = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
  })

  const processing = ref(false)
  const recentlySuccessful = ref(false)
  const errors = ref<Record<string, string[]>>({})

  async function submit () {
    processing.value = true
    errors.value = {}
    recentlySuccessful.value = false

    try {
      await updatePassword(form)
      recentlySuccessful.value = true
      Object.assign(form, {
        current_password: '',
        password: '',
        password_confirmation: '',
      })
      setTimeout(() => (recentlySuccessful.value = false), 3000)
    } catch (error: unknown) {
      if (isApiError(error, 422)) {
        errors.value = getValidationErrors(error)
      }
    } finally {
      processing.value = false
    }
  }
</script>

<template>
  <SettingsLayout>
    <div class="d-flex flex-column ga-6">
      <Heading
        description="Ensure your account is using a long, random password to stay secure"
        title="Update password"
        variant="small"
      />

      <v-form @submit.prevent="submit">
        <div class="d-flex flex-column ga-4">
          <v-text-field
            v-model="form.current_password"
            autocomplete="current-password"
            color="primary"
            density="comfortable"
            :error-messages="errors.current_password"
            hide-details="auto"
            label="Current password"
            name="current_password"
            placeholder="Current password"
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
            label="New password"
            name="password"
            placeholder="New password"
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
            label="Confirm password"
            name="password_confirmation"
            placeholder="Confirm password"
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
              Save password
            </v-btn>

            <v-fade-transition>
              <p
                v-show="recentlySuccessful"
                class="text-body-2 text-grey-darken-1"
              >
                Saved.
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
