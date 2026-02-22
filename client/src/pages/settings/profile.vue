<script lang="ts" setup>
  import { computed, reactive, ref } from 'vue'
  import { useI18n } from 'vue-i18n'
  import Heading from '@/components/ui/Heading.vue'
  import SettingsLayout from '@/components/layout/SettingsLayout.vue'
  import { deleteAccount as apiDeleteAccount, updateProfile, cancelPendingEmail as apiCancelPendingEmail } from '@/api/settings'
  import { useFormSubmit } from '@/composables/useFormSubmit'
  import { useAuthStore } from '@/stores/auth'

  const { t } = useI18n()
  const authStore = useAuthStore()
  const user = authStore.user!

  const form = reactive({
    name: user.name,
    email: user.email,
  })

  const cancellingPendingEmail = ref(false)
  const serverError = ref('')

  const isDirty = computed(() => form.name !== user.name || form.email !== user.email)

  const { processing, errors, recentlySuccessful, submit } = useFormSubmit({
    submitFn: async (data: { name: string, email: string }) => {
      serverError.value = ''
      const response = await updateProfile(data)
      authStore.setUser(response.data.user)
    },
    onError: (error: unknown) => {
      const e = error as { response?: { data?: { message?: string } } }
      serverError.value = e.response?.data?.message || t('common.genericError')
    },
  })

  async function cancelPendingEmail() {
    cancellingPendingEmail.value = true
    try {
      const response = await apiCancelPendingEmail()
      authStore.setUser(response.data.user)
    } finally {
      cancellingPendingEmail.value = false
    }
  }

  const deleteDialog = ref(false)
  const deleteForm = reactive({
    password: '',
  })

  const {
    processing: deleting,
    errors: deleteErrors,
    submit: deleteAccount,
  } = useFormSubmit({
    submitFn: (data: typeof deleteForm) => apiDeleteAccount(data),
    onSuccess: () => {
      authStore.clearToken()
      window.location.href = '/'
    },
  })
</script>

<template>
  <SettingsLayout>
    <div class="d-flex flex-column ga-6">
      <Heading
        :description="$t('settings.profile.description')"
        :title="$t('settings.profile.title')"
        variant="small"
      />

      <v-alert
        v-if="serverError"
        class="mb-4"
        density="compact"
        type="error"
        variant="tonal"
      >
        {{ serverError }}
      </v-alert>

      <v-alert
        v-if="authStore.user?.pending_email"
        class="mb-2"
        density="compact"
        type="warning"
        variant="tonal"
      >
        <div class="d-flex align-center justify-space-between">
          <i18n-t keypath="settings.profile.pendingEmail" tag="span">
            <template #email>
              <strong>{{ authStore.user.pending_email }}</strong>
            </template>
          </i18n-t>
          <v-btn
            class="text-none font-weight-bold ms-4"
            :disabled="processing"
            :loading="cancellingPendingEmail"
            size="small"
            variant="text"
            @click="cancelPendingEmail"
          >
            {{ $t('common.cancel') }}
          </v-btn>
        </div>
      </v-alert>

      <v-form @submit.prevent="submit(form)">
        <div class="d-flex flex-column ga-4">
          <v-text-field
            v-model="form.name"
            autocomplete="name"
            color="primary"
            density="comfortable"
            :disabled="cancellingPendingEmail"
            :error-messages="errors.name"
            hide-details="auto"
            :label="$t('common.name')"
            name="name"
            :placeholder="$t('common.fullName')"
            required
            variant="outlined"
          />

          <v-text-field
            v-model="form.email"
            autocomplete="username"
            color="primary"
            density="comfortable"
            :disabled="cancellingPendingEmail"
            :error-messages="errors.email"
            hide-details="auto"
            :label="$t('common.emailAddress')"
            name="email"
            :placeholder="$t('common.emailAddress')"
            required
            type="email"
            variant="outlined"
          />

          <div class="d-flex align-center ga-4 mt-2">
            <v-btn
              class="text-none font-weight-bold"
              color="primary"
              :disabled="!isDirty || cancellingPendingEmail"
              :loading="processing"
              type="submit"
              variant="flat"
            >
              {{ $t('common.save') }}
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

      <v-divider class="my-6" />

      <Heading
        :description="$t('settings.profile.deleteAccountDescription')"
        :title="$t('settings.profile.deleteAccount')"
        variant="small"
      />

      <div>
        <v-btn
          class="text-none font-weight-bold"
          color="error"
          variant="tonal"
          @click="deleteDialog = true"
        >
          {{ $t('settings.profile.deleteAccountButton') }}
        </v-btn>
      </div>

      <v-dialog v-model="deleteDialog" max-width="500">
        <v-card rounded="lg">
          <v-card-item class="pa-6">
            <v-card-title class="text-h5 font-weight-bold mb-1">
              {{ $t('settings.profile.deleteAccountDialogTitle') }}
            </v-card-title>
            <v-card-subtitle
              class="text-body-1 text-wrap opacity-100"
            >
              {{ $t('settings.profile.deleteAccountDialogMessage') }}
            </v-card-subtitle>

            <v-form class="mt-6" @submit.prevent="deleteAccount(deleteForm)">
              <v-text-field
                v-model="deleteForm.password"
                autocomplete="current-password"
                color="primary"
                density="comfortable"
                :error-messages="deleteErrors.password"
                hide-details="auto"
                :label="$t('common.password')"
                name="password"
                :placeholder="$t('common.password')"
                required
                type="password"
                variant="outlined"
              />

              <div class="d-flex ga-3 mt-8 justify-end">
                <v-btn
                  class="text-none font-weight-bold"
                  variant="text"
                  @click="deleteDialog = false"
                >
                  {{ $t('common.cancel') }}
                </v-btn>
                <v-btn
                  class="text-none font-weight-bold"
                  color="error"
                  :loading="deleting"
                  type="submit"
                  variant="flat"
                >
                  {{ $t('settings.profile.deleteAccountButton') }}
                </v-btn>
              </div>
            </v-form>
          </v-card-item>
        </v-card>
      </v-dialog>
    </div>
  </SettingsLayout>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
