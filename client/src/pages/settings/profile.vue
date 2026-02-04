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
      const response = await api.put('/user/profile-information', form)
      authStore.setUser(response.data)
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
    </div>
  </SettingsLayout>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
