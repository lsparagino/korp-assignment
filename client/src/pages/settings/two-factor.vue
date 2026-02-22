<script lang="ts" setup>
  import { onMounted, ref } from 'vue'
  import { confirmTwoFactor, disableTwoFactor, enableTwoFactor, getRecoveryCodes, getTwoFactorQrCode } from '@/api/settings'
  import SettingsLayout from '@/components/layout/SettingsLayout.vue'
  import Heading from '@/components/ui/Heading.vue'
  import { useAuthStore } from '@/stores/auth'

  const authStore = useAuthStore()
  const enabled = ref(false)
  const processing = ref(false)
  const confirming = ref(false)
  const confirmationCode = ref('')
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
      await enableTwoFactor()
      const response = await getTwoFactorQrCode()
      qrCode.value = response.data
      confirming.value = true
    } catch (error) {
      console.error('Error enabling 2FA:', error)
    } finally {
      processing.value = false
    }
  }

  async function confirm2FA () {
    processing.value = true
    try {
      await confirmTwoFactor(confirmationCode.value)
      await authStore.fetchUser()
      enabled.value = true
      confirming.value = false
      qrCode.value = null
      fetchRecoveryCodes()
    } catch (error) {
      console.error('Error confirming 2FA:', error)
    } finally {
      processing.value = false
    }
  }

  async function disable2FA () {
    processing.value = true
    try {
      await disableTwoFactor()
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
      const response = await getRecoveryCodes()
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
        :description="$t('settings.twoFactor.description')"
        :title="$t('settings.twoFactor.title')"
        variant="small"
      />

      <div
        v-if="!enabled && !confirming"
        class="d-flex flex-column ga-4 align-start"
      >
        <v-chip
          class="font-weight-bold"
          color="error"
          size="small"
          variant="flat"
        >{{ $t('settings.twoFactor.disabled') }}</v-chip>
        <p class="text-body-2 text-grey-darken-1">
          {{ $t('settings.twoFactor.enableDescription') }}
        </p>
        <v-btn
          class="text-none font-weight-bold"
          color="primary"
          :loading="processing"
          variant="flat"
          @click="enable2FA"
        >
          {{ $t('settings.twoFactor.enable') }}
        </v-btn>
      </div>

      <div
        v-else-if="confirming"
        class="d-flex flex-column ga-4 align-start"
      >
        <v-chip
          class="font-weight-bold"
          color="warning"
          size="small"
          variant="flat"
        >{{ $t('settings.twoFactor.setupInProgress') }}</v-chip>
        <p class="text-body-2 text-grey-darken-1">
          {{ $t('settings.twoFactor.setupDescription') }}
        </p>

        <div v-if="qrCode" class="w-100">
          <div
            class="pa-4 d-inline-block mb-4 rounded-lg border bg-white"
          >
            <div v-html="qrCode.svg" />
          </div>

          <div class="d-flex flex-column ga-4 align-start mb-6">
            <p class="text-body-2 font-weight-bold">
              {{ $t('settings.twoFactor.enterCode') }}
            </p>
            <v-text-field
              v-model="confirmationCode"
              class="w-100"
              color="primary"
              density="compact"
              :label="$t('settings.twoFactor.confirmationCode')"
              maxlength="6"
              placeholder="000000"
              style="max-width: 200px"
              variant="outlined"
            />
            <div class="d-flex ga-3">
              <v-btn
                class="text-none font-weight-bold"
                color="primary"
                :disabled="confirmationCode.length !== 6"
                :loading="processing"
                variant="flat"
                @click="confirm2FA"
              >
                {{ $t('settings.twoFactor.confirmAndEnable') }}
              </v-btn>
              <v-btn
                class="text-none font-weight-bold"
                variant="text"
                @click="
                  confirming = false;
                  qrCode = null;
                "
              >
                {{ $t('common.cancel') }}
              </v-btn>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="d-flex flex-column ga-4 align-start">
        <v-chip
          class="font-weight-bold"
          color="success"
          size="small"
          variant="flat"
        >{{ $t('settings.twoFactor.enabled') }}</v-chip>
        <p class="text-body-2 text-grey-darken-1">
          {{ $t('settings.twoFactor.enabledDescription') }}
        </p>

        <div
          v-if="recoveryCodes.length > 0"
          class="w-100 pa-4 bg-grey-lighten-4 mb-4 rounded-lg"
        >
          <p class="text-caption font-weight-bold mb-2">
            {{ $t('settings.twoFactor.recoveryCodes') }}
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
          {{ $t('settings.twoFactor.disable') }}
        </v-btn>
      </div>
    </div>
  </SettingsLayout>
</template>

<route lang="yaml">
meta:
    layout: App
</route>
