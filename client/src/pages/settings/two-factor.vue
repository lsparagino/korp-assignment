<script lang="ts" setup>
  import { onMounted, ref } from 'vue'
  import Heading from '@/components/Heading.vue'
  import SettingsLayout from '@/components/SettingsLayout.vue'
  import api from '@/plugins/api'
  import { useAuthStore } from '@/stores/auth'

  const authStore = useAuthStore()
  const enabled = ref(false)
  const processing = ref(false)
  const qrCode = ref<{ svg: string, url: string } | null>(null)
  const recoveryCodes = ref<any[]>([])

  async function fetchStatus () {
    enabled.value = !!authStore.user?.two_factor_confirmed_at
    if (enabled.value) {
      fetchRecoveryCodes()
    }
  }

  async function enable2FA () {
    processing.value = true
    try {
      await api.post('/user/two-factor-authentication')
      const response = await api.get('/user/two-factor-qr-code')
      qrCode.value = response.data
      // In a real app, you'd confirm it with a code here
      // For now just mark as enabled for demo
      enabled.value = true
    } catch (error) {
      console.error('Error enabling 2FA:', error)
    } finally {
      processing.value = false
    }
  }

  async function disable2FA () {
    processing.value = true
    try {
      await api.delete('/user/two-factor-authentication')
      enabled.value = false
      qrCode.value = null
      recoveryCodes.value = []
    } catch (error) {
      console.error('Error disabling 2FA:', error)
    } finally {
      processing.value = false
    }
  }

  async function fetchRecoveryCodes () {
    try {
      const response = await api.get('/user/two-factor-recovery-codes')
      recoveryCodes.value = response.data
    } catch (error) {
      console.error('Error fetching recovery codes:', error)
    }
  }

  onMounted(fetchStatus)
</script>

<template>
  <SettingsLayout>
    <div class="d-flex flex-column ga-6">
      <Heading
        description="Manage your two-factor authentication settings"
        title="Two-Factor Authentication"
        variant="small"
      />

      <div v-if="!enabled" class="d-flex flex-column ga-4 align-start">
        <v-chip
          class="font-weight-bold"
          color="error"
          size="small"
          variant="flat"
        >Disabled</v-chip>
        <p class="text-body-2 text-grey-darken-1">
          When you enable two-factor authentication, you will be
          prompted for a secure pin during login.
        </p>
        <v-btn
          class="text-none font-weight-bold"
          color="primary"
          :loading="processing"
          variant="flat"
          @click="enable2FA"
        >
          Enable 2FA
        </v-btn>
      </div>

      <div v-else class="d-flex flex-column ga-4 align-start">
        <v-chip
          class="font-weight-bold"
          color="success"
          size="small"
          variant="flat"
        >Enabled</v-chip>
        <p class="text-body-2 text-grey-darken-1">
          Two-factor authentication is enabled. You can manage your
          recovery codes below.
        </p>

        <div v-if="qrCode" class="pa-4 bg-white mb-4 rounded-lg border">
          <div v-html="qrCode.svg" />
        </div>

        <div
          v-if="recoveryCodes.length > 0"
          class="w-100 pa-4 bg-grey-lighten-4 mb-4 rounded-lg"
        >
          <p class="text-caption font-weight-bold mb-2">
            Recovery Codes
          </p>
          <v-row dense>
            <v-col
              v-for="c in recoveryCodes"
              :key="c.code"
              class="text-caption font-monospace"
              cols="6"
            >
              {{ c.code }}
            </v-col>
          </v-row>
        </div>

        <v-btn
          class="text-none font-weight-bold"
          color="error"
          :loading="processing"
          variant="flat"
          @click="disable2FA"
        >
          Disable 2FA
        </v-btn>
      </div>
    </div>
  </SettingsLayout>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
