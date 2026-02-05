<script lang="ts" setup>
  import { reactive, ref } from 'vue'
  import Heading from '@/components/Heading.vue'
  import SettingsLayout from '@/components/SettingsLayout.vue'
  import api from '@/plugins/api'
  import { useAuthStore } from '@/stores/auth'

  const authStore = useAuthStore()
  const user = authStore.user!

  const form = reactive({
    name: user.name,
    email: user.email,
  })

  const processing = ref(false)
  const recentlySuccessful = ref(false)
  const errors = ref<Record<string, string[]>>({})

  async function submit () {
    processing.value = true
    errors.value = {}
    recentlySuccessful.value = false

    try {
      const response = await api.patch('/settings/profile', form)
      authStore.setUser(response.data.user)
      recentlySuccessful.value = true
      setTimeout(() => (recentlySuccessful.value = false), 3000)
    } catch (error: any) {
      if (error.response?.status === 422) {
        errors.value = error.response.data.errors
      }
    } finally {
      processing.value = false
    }
  }

  const deleteDialog = ref(false)
  const deleting = ref(false)
  const deleteErrors = ref<Record<string, string[]>>({})
  const deleteForm = reactive({
    password: '',
  })

  async function deleteAccount () {
    deleting.value = true
    deleteErrors.value = {}

    try {
      await api.delete('/settings/profile', { data: deleteForm })
      authStore.clearToken()
      window.location.href = '/'
    } catch (error: any) {
      if (error.response?.status === 422) {
        deleteErrors.value = error.response.data.errors
      }
    } finally {
      deleting.value = false
    }
  }
</script>

<template>
  <SettingsLayout>
    <div class="d-flex flex-column ga-6">
      <Heading
        description="Update your name and email address"
        title="Profile information"
        variant="small"
      />

      <v-form @submit.prevent="submit">
        <div class="d-flex flex-column ga-4">
          <v-text-field
            v-model="form.name"
            autocomplete="name"
            color="primary"
            density="comfortable"
            :error-messages="errors.name"
            hide-details="auto"
            label="Name"
            name="name"
            placeholder="Full name"
            required
            variant="outlined"
          />

          <v-text-field
            v-model="form.email"
            autocomplete="username"
            color="primary"
            density="comfortable"
            :error-messages="errors.email"
            hide-details="auto"
            label="Email address"
            name="email"
            placeholder="Email address"
            required
            type="email"
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
              Save
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

      <v-divider class="my-6" />

      <Heading
        description="Permanently delete your account and all of its data."
        title="Delete account"
        variant="small"
      />

      <div>
        <v-btn
          class="text-none font-weight-bold"
          color="error"
          variant="tonal"
          @click="deleteDialog = true"
        >
          Delete Account
        </v-btn>
      </div>

      <v-dialog v-model="deleteDialog" max-width="500">
        <v-card rounded="lg">
          <v-card-item class="pa-6">
            <v-card-title class="text-h5 font-weight-bold mb-1">
              Delete Account
            </v-card-title>
            <v-card-subtitle class="text-body-1 opacity-100">
              Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
            </v-card-subtitle>

            <v-form class="mt-6" @submit.prevent="deleteAccount">
              <v-text-field
                v-model="deleteForm.password"
                autocomplete="current-password"
                color="primary"
                density="comfortable"
                :error-messages="deleteErrors.password"
                hide-details="auto"
                label="Password"
                name="password"
                placeholder="Password"
                required
                type="password"
                variant="outlined"
              />

              <div class="d-flex justify-end ga-3 mt-8">
                <v-btn
                  class="text-none font-weight-bold"
                  variant="text"
                  @click="deleteDialog = false"
                >
                  Cancel
                </v-btn>
                <v-btn
                  class="text-none font-weight-bold"
                  color="error"
                  :loading="deleting"
                  type="submit"
                  variant="flat"
                >
                  Delete Account
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
