<script lang="ts" setup>
  import { ref } from 'vue'
  import { useRouter } from 'vue-router'
  import Heading from '@/components/Heading.vue'
  import { api } from '@/plugins/api'
  import { useAuthStore } from '@/stores/auth'

  const router = useRouter()
  const authStore = useAuthStore()
  const dialog = ref(false)
  const processing = ref(false)
  const password = ref('')
  const errors = ref<Record<string, string[]>>({})

  async function deleteAccount () {
    processing.value = true
    errors.value = {}

    try {
      await api.delete('/user', { data: { password: password.value } })
      authStore.clearToken()
      router.push('/')
    } catch (error: unknown) {
      const err = error as {
        response?: {
          status?: number
          data?: { errors?: Record<string, string[]> }
        }
      }
      if (err.response?.status === 422) {
        errors.value = err.response.data?.errors ?? {}
      }
    } finally {
      processing.value = false
    }
  }
</script>

<template>
  <div class="mt-12 border-t pt-12">
    <Heading
      description="Permanently delete your account. Any data you have in SecureWallet will be lost forever."
      title="Delete Account"
      variant="small"
    />

    <v-btn
      class="text-none font-weight-bold"
      color="error"
      variant="flat"
      @click="dialog = true"
    >
      Delete Account
    </v-btn>

    <v-dialog v-model="dialog" max-width="500">
      <v-card class="pa-4" rounded="xl">
        <v-card-title class="text-h6 font-weight-bold">Are you sure?</v-card-title>
        <v-card-text>
          Once your account is deleted, all of its resources and data
          will be permanently deleted. Please enter your password to
          confirm you would like to permanently delete your account.

          <v-text-field
            v-model="password"
            class="mt-4"
            color="primary"
            density="comfortable"
            :error-messages="errors.password"
            hide-details="auto"
            label="Password"
            type="password"
            variant="outlined"
          />
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn
            class="text-none"
            variant="text"
            @click="dialog = false"
          >Cancel</v-btn>
          <v-btn
            class="text-none px-6"
            color="error"
            :loading="processing"
            variant="flat"
            @click="deleteAccount"
          >Delete Account</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
